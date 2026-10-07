<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\LeaveApproval;
use App\Models\LeaveAttachment;
use App\Models\LeaveComment;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveStatusNotification;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LeaveWorkflowService
{
    public function __construct(
        protected LeaveCalculationService $calculationService,
        protected LeaveLedgerService $ledgerService
    ) {}

    /**
     * Submit a new leave application.
     */
    public function submitApplication(
        Employee $employee,
        LeaveType $leaveType,
        array $validatedData,
        ?UploadedFile $attachmentFile = null,
        ?int $authUserId = null
    ): LeaveApplication {
        return DB::transaction(function () use ($employee, $leaveType, $validatedData, $attachmentFile, $authUserId) {
            $startDate = $validatedData['start_date'];
            $endDate = $validatedData['end_date'] ?? $startDate;
            $isHalfDay = !empty($validatedData['is_half_day']);
            $halfDayType = $isHalfDay ? ($validatedData['half_day_type'] ?? 'morning') : null;
            $isEmergency = !empty($validatedData['is_emergency']) || $leaveType->is_emergency_type || $leaveType->code === 'emergency';

            // Serialize applications for this employee so concurrent submissions cannot overlap.
            Employee::whereKey($employee->id)->lockForUpdate()->firstOrFail();

            $overlappingApplication = LeaveApplication::where('employee_id', $employee->id)
                ->whereIn('status', ['pending_team_lead', 'pending_manager', 'pending_hr', 'approved', 'cancellation_requested'])
                ->where('start_date', '<=', $endDate)
                ->where('end_date', '>=', $startDate)
                ->first();

            if ($overlappingApplication) {
                throw new Exception(sprintf(
                    'You already have a leave request from %s to %s. Choose dates that do not overlap.',
                    $overlappingApplication->start_date->format('d M Y'),
                    $overlappingApplication->end_date->format('d M Y')
                ));
            }

            // Run calculation engine
            $calc = $this->calculationService->calculate(
                employee: $employee,
                leaveType: $leaveType,
                startDate: $startDate,
                endDate: $endDate,
                isHalfDay: $isHalfDay,
                halfDayType: $halfDayType,
                isEmergency: $isEmergency
            );

            // Validate the annual leave date window.
            if (!$calc['advance_notice_valid']) {
                throw new Exception($calc['advance_notice_message']);
            }

            if ($calc['total_days'] <= 0) {
                throw new Exception("The selected dates contain 0 working days (all dates fall on weekends or public holidays).");
            }

            // Validate balance
            $balance = $this->ledgerService->getOrCreateBalance($employee, $leaveType, Carbon::parse($startDate)->year);
            if ($leaveType->code !== 'unpaid' && $calc['total_days'] > $balance->available_days) {
                throw new Exception(sprintf(
                    "Insufficient leave balance! You requested %.1f days, but only have %.1f available days of %s.",
                    $calc['total_days'],
                    $balance->available_days,
                    $leaveType->name
                ));
            }

            // Team leads and HR staff have their own leave requests reviewed by the manager.
            $policy = $employee->leavePolicy;
            $workflow = $policy ? $policy->approval_workflow : 'team_lead_then_hr';
            $applicantRole = $employee->user?->role;

            $initialStatus = 'pending_team_lead';
            $approvalLevel = 'team_lead';

            if ($applicantRole === 'manager') {
                $initialStatus = 'pending_hr';
                $approvalLevel = 'hr';
            } elseif (in_array($applicantRole, ['team_lead', 'hr', 'admin'], true)
                || $workflow === 'direct_hr'
                || !$employee->team_lead_id
                || $employee->team_lead_id === $authUserId) {
                $initialStatus = 'pending_manager';
                $approvalLevel = 'manager';
            }

            // Generate application number
            $year = Carbon::now()->year;
            $count = LeaveApplication::whereYear('created_at', $year)->count() + 1;
            $appNumber = sprintf('LA-%s-%04d', $year, $count);

            // Create application
            $application = LeaveApplication::create([
                'application_number' => $appNumber,
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'total_days' => $calc['total_days'],
                'is_half_day' => $isHalfDay,
                'half_day_type' => $halfDayType,
                'is_emergency' => $isEmergency,
                'reason' => $validatedData['reason'],
                'manual_attachment_expected' => (bool) ($validatedData['manual_attachment_expected'] ?? false),
                'status' => $initialStatus,
                'current_approval_level' => $approvalLevel,
                'team_conflict_count' => count($calc['team_conflicts']),
                'submitted_at' => Carbon::now(),
            ]);

            // Save individual days
            foreach ($calc['days_breakdown'] as $day) {
                LeaveApplicationDay::create([
                    'leave_application_id' => $application->id,
                    'date' => $day['date'],
                    'day_type' => $day['day_type'],
                    'is_working_day' => $day['is_working_day'],
                    'weight' => $day['weight'],
                ]);
            }

            // Reserve days in balance ledger
            $this->ledgerService->reservePendingDays($application);

            // Handle attachment
            if ($attachmentFile) {
                $filename = Str::random(24) . '.' . $attachmentFile->getClientOriginalExtension();
                $path = $attachmentFile->storeAs('attachments', $filename, 'local');

                LeaveAttachment::create([
                    'leave_application_id' => $application->id,
                    'file_name' => $filename,
                    'original_name' => $attachmentFile->getClientOriginalName(),
                    'mime_type' => $attachmentFile->getClientMimeType(),
                    'file_size' => $attachmentFile->getSize(),
                    'file_path' => $path,
                    'uploaded_by' => $authUserId ?? $employee->user_id,
                ]);
            }

            // Audit log
            AuditLog::log(
                action: 'leave_application_submitted',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: sprintf('%s submitted %s application (%s to %s, %.1f days)', $employee->full_name, $leaveType->name, $startDate, $endDate, $calc['total_days']),
                newValues: $application->toArray()
            );

            // Notify approvers
            $this->notifyNextApprover($application);

            // Notify employee of successful submission
            if ($employee->user) {
                $employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Application Submitted',
                    message: "Your {$leaveType->name} application ({$startDate} to {$endDate}) has been submitted successfully.",
                    type: 'info'
                ));
            }

            return $application;
        });
    }

    /**
     * Team Lead approves application.
     */
    public function approveByTeamLead(LeaveApplication $application, int $approverUserId, ?string $comment = null): void
    {
        $this->finalizeApproval($application, $approverUserId, 'team_lead', $comment);
    }

    /** Manager approves leave requested by a team lead or HR user. */
    public function approveByManager(LeaveApplication $application, int $approverUserId, ?string $comment = null): void
    {
        $this->finalizeApproval($application, $approverUserId, 'manager', $comment);
    }

    private function finalizeApproval(LeaveApplication $application, int $approverUserId, string $level, ?string $comment): void
    {
        DB::transaction(function () use ($application, $approverUserId, $level, $comment) {
            $application->status = 'approved';
            $application->current_approval_level = 'completed';
            $application->save();

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'approver_id' => $approverUserId,
                'level' => $level,
                'action' => 'approved',
                'comment' => $comment,
            ]);

            if ($comment) {
                LeaveComment::create([
                    'leave_application_id' => $application->id,
                    'user_id' => $approverUserId,
                    'comment' => $comment,
                ]);
            }

            $this->ledgerService->deductApprovedDays($application, $approverUserId);

            $approverLabel = $level === 'manager' ? 'Manager' : 'Team Lead';
            AuditLog::log(
                action: $level . '_approved_leave',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "{$approverLabel} approved leave application {$application->application_number} ({$application->total_days} days). Comment: {$comment}"
            );

            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Request Approved',
                    message: sprintf(
                        'Your %s request from %s to %s (%.1f working days) has been approved by your %s.%s',
                        $application->leaveType->name,
                        $application->start_date->format('d M'),
                        $application->end_date->format('d M Y'),
                        $application->total_days,
                        strtolower($approverLabel),
                        $comment ? " Note: {$comment}" : ''
                    ),
                    type: 'success'
                ));
            }

            $this->notifyHrOfApprovedLeave($application, $approverLabel);
        });
    }

    private function notifyHrOfApprovedLeave(LeaveApplication $application, string $approvedBy): void
    {
        $hrUsers = User::whereIn('role', ['hr', 'admin'])->get();
        Notification::send($hrUsers, new LeaveStatusNotification(
            application: $application,
            title: 'Leave approved by ' . $approvedBy,
            message: sprintf(
                '%s is approved for leave from %s to %s (%s working days).',
                $application->employee->full_name,
                $application->start_date->format('d M Y'),
                $application->end_date->format('d M Y'),
                rtrim(rtrim(number_format((float) $application->total_days, 1), '0'), '.')
            ),
            type: 'info'
        ));
    }

    /**
     * Team Lead rejects application.
     */
    public function rejectByTeamLead(LeaveApplication $application, int $approverUserId, string $reason): void
    {
        DB::transaction(function () use ($application, $approverUserId, $reason) {
            $application->status = 'rejected';
            $application->rejection_reason = $reason;
            $application->rejected_by = $approverUserId;
            $application->save();

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'approver_id' => $approverUserId,
                'level' => 'team_lead',
                'action' => 'rejected',
                'comment' => $reason,
            ]);

            LeaveComment::create([
                'leave_application_id' => $application->id,
                'user_id' => $approverUserId,
                'comment' => "Rejected: " . $reason,
            ]);

            // Release pending days
            $this->ledgerService->releasePendingDays($application);

            AuditLog::log(
                action: 'team_lead_rejected_leave',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "Team Lead rejected leave application {$application->application_number}. Reason: {$reason}"
            );

            // Notify employee
            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Request Rejected',
                    message: "Your leave request was rejected by your Team Lead. Reason: {$reason}",
                    type: 'danger'
                ));
            }
        });
    }

    public function rejectByManager(LeaveApplication $application, int $approverUserId, string $reason): void
    {
        DB::transaction(function () use ($application, $approverUserId, $reason) {
            $application->status = 'rejected';
            $application->rejection_reason = $reason;
            $application->rejected_by = $approverUserId;
            $application->current_approval_level = 'completed';
            $application->save();

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'approver_id' => $approverUserId,
                'level' => 'manager',
                'action' => 'rejected',
                'comment' => $reason,
            ]);

            LeaveComment::create([
                'leave_application_id' => $application->id,
                'user_id' => $approverUserId,
                'comment' => 'Rejected: ' . $reason,
            ]);

            $this->ledgerService->releasePendingDays($application);
            AuditLog::log(
                action: 'manager_rejected_leave',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "Manager rejected leave application {$application->application_number}. Reason: {$reason}"
            );

            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Request Rejected',
                    message: "Your leave request was rejected by the Manager. Reason: {$reason}",
                    type: 'danger'
                ));
            }
        });
    }

    /**
     * HR approves application (Final approval).
     */
    public function approveByHr(LeaveApplication $application, int $approverUserId, ?string $comment = null): void
    {
        DB::transaction(function () use ($application, $approverUserId, $comment) {
            $application->status = 'approved';
            $application->save();

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'approver_id' => $approverUserId,
                'level' => 'hr',
                'action' => 'approved',
                'comment' => $comment,
            ]);

            if (!empty($comment)) {
                LeaveComment::create([
                    'leave_application_id' => $application->id,
                    'user_id' => $approverUserId,
                    'comment' => $comment,
                ]);
            }

            // Deduct days from ledger
            $this->ledgerService->deductApprovedDays($application, $approverUserId);

            AuditLog::log(
                action: 'hr_approved_leave',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "HR approved leave application {$application->application_number} ({$application->total_days} days). Comment: {$comment}"
            );

            // Notify employee
            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Request Approved!',
                    message: sprintf(
                        'Your %s request from %s to %s (%.1f days) has been officially approved.%s',
                        $application->leaveType->name,
                        $application->start_date->format('d M'),
                        $application->end_date->format('d M Y'),
                        $application->total_days,
                        $comment ? " Note: {$comment}" : ""
                    ),
                    type: 'success'
                ));
            }
        });
    }

    /**
     * HR rejects application.
     */
    public function rejectByHr(LeaveApplication $application, int $approverUserId, string $reason): void
    {
        DB::transaction(function () use ($application, $approverUserId, $reason) {
            $application->status = 'rejected';
            $application->rejection_reason = $reason;
            $application->rejected_by = $approverUserId;
            $application->save();

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'approver_id' => $approverUserId,
                'level' => 'hr',
                'action' => 'rejected',
                'comment' => $reason,
            ]);

            LeaveComment::create([
                'leave_application_id' => $application->id,
                'user_id' => $approverUserId,
                'comment' => "HR Rejected: " . $reason,
            ]);

            // Release pending days
            $this->ledgerService->releasePendingDays($application);

            AuditLog::log(
                action: 'hr_rejected_leave',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "HR rejected leave application {$application->application_number}. Reason: {$reason}"
            );

            // Notify employee
            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Request Rejected by HR',
                    message: "Your leave request was rejected by HR. Reason: {$reason}",
                    type: 'danger'
                ));
            }
        });
    }

    /**
     * Employee requests cancellation of an approved leave.
     */
    public function requestCancellation(LeaveApplication $application, string $reason, int $employeeUserId): void
    {
        if ($application->status !== 'approved') {
            throw new Exception("Only approved leave requests can be cancelled.");
        }

        DB::transaction(function () use ($application, $reason, $employeeUserId) {
            $application->status = 'cancellation_requested';
            $application->cancellation_reason = $reason;
            $application->save();

            LeaveComment::create([
                'leave_application_id' => $application->id,
                'user_id' => $employeeUserId,
                'comment' => "Requested Cancellation: " . $reason,
            ]);

            AuditLog::log(
                action: 'cancellation_requested',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "Employee requested cancellation for {$application->application_number}. Reason: {$reason}"
            );

            $this->notifyNextApprover($application, 'Cancellation Request');
        });
    }

    /**
     * Approve cancellation and refund days to ledger.
     */
    public function approveCancellation(LeaveApplication $application, int $approverUserId, ?string $comment = null): void
    {
        DB::transaction(function () use ($application, $approverUserId, $comment) {
            $reason = $application->cancellation_reason ?? 'Approved employee cancellation request';

            $application->status = 'cancelled';
            $application->cancelled_at = Carbon::now();
            $application->cancelled_by = $approverUserId;
            $application->save();

            LeaveApproval::create([
                'leave_application_id' => $application->id,
                'approver_id' => $approverUserId,
                'level' => 'hr',
                'action' => 'cancellation_approved',
                'comment' => $comment,
            ]);

            // Refund days back into ledger
            $this->ledgerService->refundCancelledDays($application, $approverUserId, $reason);

            AuditLog::log(
                action: 'cancellation_approved',
                entityType: 'LeaveApplication',
                entityId: $application->id,
                description: "Cancellation approved for {$application->application_number}. Days refunded: {$application->total_days}"
            );

            // Notify employee
            if ($application->employee->user) {
                $application->employee->user->notify(new LeaveStatusNotification(
                    application: $application,
                    title: 'Leave Cancellation Approved',
                    message: "Your leave cancellation request for {$application->application_number} was approved. {$application->total_days} days have been returned to your balance.",
                    type: 'success'
                ));
            }
        });
    }

    /**
     * Helper to notify approvers based on workflow level.
     */
    protected function notifyNextApprover(LeaveApplication $application, string $prefix = 'New Leave Application'): void
    {
        if ($application->status === 'pending_team_lead') {
            $teamLead = $application->employee->teamLead;
            if ($teamLead) {
                $teamLead->notify(new LeaveStatusNotification(
                    application: $application,
                    title: "{$prefix} - Review Required",
                    message: "{$application->employee->full_name} submitted a {$application->leaveType->name} request ({$application->total_days} days) awaiting your review.",
                    type: 'warning'
                ));
            } else {
                $this->notifyHrApprovers($application);
            }
        } elseif ($application->status === 'pending_manager') {
            $manager = User::whereKey($application->employee->manager_id)
                ->where('role', 'manager')
                ->first() ?? User::where('role', 'manager')->first();
            if ($manager) {
                $manager->notify(new LeaveStatusNotification(
                    application: $application,
                    title: "{$prefix} - Manager Review Required",
                    message: "{$application->employee->full_name} submitted a {$application->leaveType->name} request ({$application->total_days} working days) from {$application->start_date->format('d M')} to {$application->end_date->format('d M Y')}.",
                    type: 'warning'
                ));
            }
        } elseif (in_array($application->status, ['pending_hr', 'cancellation_requested'])) {
            $this->notifyHrApprovers($application);
        }
    }

    /**
     * Notify all HR and Admin users.
     */
    protected function notifyHrApprovers(LeaveApplication $application): void
    {
        $hrUsers = User::whereIn('role', ['hr', 'admin'])->get();
        Notification::send($hrUsers, new LeaveStatusNotification(
            application: $application,
            title: $application->is_emergency ? '🚨 URGENT: Emergency Leave Request' : 'Leave Application Awaiting HR Review',
            message: sprintf(
                '%s submitted %s (%s to %s, %.1f days)%s',
                $application->employee->full_name,
                $application->leaveType->name,
                $application->start_date->format('d M'),
                $application->end_date->format('d M Y'),
                $application->total_days,
                $application->is_emergency ? ' - [EMERGENCY EXCEPTION]' : ''
            ),
            type: $application->is_emergency ? 'danger' : 'info'
        ));
    }
}
