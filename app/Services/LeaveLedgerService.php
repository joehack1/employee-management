<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaveLedgerService
{
    /**
     * Get or initialize leave balance record for employee, leave type, and year.
     */
    public function getOrCreateBalance(Employee $employee, LeaveType $leaveType, ?int $year = null): LeaveBalance
    {
        $year = $year ?? Carbon::now()->year;

        return LeaveBalance::firstOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'year' => $year,
            ],
            [
                'entitled_days' => $leaveType->code === 'annual' ? 0 : ($leaveType->days_allowed ?? 0),
                'carried_forward_days' => 0,
                'manual_adjustment_days' => 0,
                'used_days' => 0,
                'pending_days' => 0,
            ]
        );
    }

    /**
     * Perform an HR adjustment (+/- days) with mandatory reason and ledger entry.
     */
    public function adjustBalance(
        Employee $employee,
        LeaveType $leaveType,
        float $adjustmentAmount,
        string $reason,
        int $hrUserId,
        ?int $year = null
    ): LeaveBalance {
        return DB::transaction(function () use ($employee, $leaveType, $adjustmentAmount, $reason, $hrUserId, $year) {
            $year = $year ?? Carbon::now()->year;
            $balance = $this->getOrCreateBalance($employee, $leaveType, $year);

            $oldManual = (float) $balance->manual_adjustment_days;
            $newManual = $oldManual + $adjustmentAmount;
            $balance->manual_adjustment_days = $newManual;
            $balance->save();

            // Calculate new current available running balance
            $runningBalance = $balance->available_days;

            LeaveTransaction::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'leave_application_id' => null,
                'type' => 'hr_adjustment',
                'amount' => $adjustmentAmount,
                'running_balance' => $runningBalance,
                'reason' => $reason,
                'created_by' => $hrUserId,
            ]);

            AuditLog::log(
                action: 'hr_leave_adjustment',
                entityType: 'LeaveBalance',
                entityId: $balance->id,
                description: sprintf(
                    'HR adjusted leave balance for %s (%s): %s%.2f days. Reason: %s',
                    $employee->full_name,
                    $leaveType->name,
                    $adjustmentAmount > 0 ? '+' : '',
                    $adjustmentAmount,
                    $reason
                ),
                oldValues: ['manual_adjustment_days' => $oldManual],
                newValues: ['manual_adjustment_days' => $newManual]
            );

            return $balance;
        });
    }

    /**
     * Record a pending leave application (reserves days from available balance).
     */
    public function reservePendingDays(LeaveApplication $application): void
    {
        $year = Carbon::parse($application->start_date)->year;
        $balance = $this->getOrCreateBalance($application->employee, $application->leaveType, $year);

        $balance->pending_days = (float) $balance->pending_days + (float) $application->total_days;
        $balance->save();
    }

    /**
     * Release pending days if application rejected, withdrawn, or cancelled before approval.
     */
    public function releasePendingDays(LeaveApplication $application): void
    {
        $year = Carbon::parse($application->start_date)->year;
        $balance = $this->getOrCreateBalance($application->employee, $application->leaveType, $year);

        $balance->pending_days = max(0, (float) $balance->pending_days - (float) $application->total_days);
        $balance->save();
    }

    /**
     * Deduct days upon final approval: decreases pending, increases used, creates deduction transaction.
     */
    public function deductApprovedDays(LeaveApplication $application, int $approverUserId): void
    {
        DB::transaction(function () use ($application, $approverUserId) {
            $year = Carbon::parse($application->start_date)->year;
            $balance = $this->getOrCreateBalance($application->employee, $application->leaveType, $year);

            $days = (float) $application->total_days;

            // Reduce pending and increase used
            $balance->pending_days = max(0, (float) $balance->pending_days - $days);
            $balance->used_days = (float) $balance->used_days + $days;
            $balance->save();

            $runningBalance = $balance->available_days;

            LeaveTransaction::create([
                'employee_id' => $application->employee_id,
                'leave_type_id' => $application->leave_type_id,
                'leave_application_id' => $application->id,
                'type' => 'approved_deduction',
                'amount' => -$days,
                'running_balance' => $runningBalance,
                'reason' => sprintf('Approved %s for %s (%s to %s)', $application->leaveType->name, $application->application_number, $application->start_date->format('d M'), $application->end_date->format('d M Y')),
                'created_by' => $approverUserId,
            ]);
        });
    }

    /**
     * Refund days if an approved leave is cancelled.
     */
    public function refundCancelledDays(LeaveApplication $application, int $approverUserId, string $reason): void
    {
        DB::transaction(function () use ($application, $approverUserId, $reason) {
            $year = Carbon::parse($application->start_date)->year;
            $balance = $this->getOrCreateBalance($application->employee, $application->leaveType, $year);

            $days = (float) $application->total_days;

            // Reduce used days
            $balance->used_days = max(0, (float) $balance->used_days - $days);
            $balance->save();

            $runningBalance = $balance->available_days;

            LeaveTransaction::create([
                'employee_id' => $application->employee_id,
                'leave_type_id' => $application->leave_type_id,
                'leave_application_id' => $application->id,
                'type' => 'cancellation_refund',
                'amount' => +$days,
                'running_balance' => $runningBalance,
                'reason' => sprintf('Cancellation refund for %s (%s). Reason: %s', $application->application_number, $application->leaveType->name, $reason),
                'created_by' => $approverUserId,
            ]);
        });
    }

    /**
     * Execute carry-forward for an employee at year-end.
     */
    public function applyCarryForward(Employee $employee, LeaveType $leaveType, int $fromYear, int $toYear): void
    {
        $oldBalance = $this->getOrCreateBalance($employee, $leaveType, $fromYear);
        $newBalance = $this->getOrCreateBalance($employee, $leaveType, $toYear);

        $policy = $employee->leavePolicy;
        $maxCarryForward = $policy ? (float) $policy->max_carry_forward : 5.0;

        $remainingOld = $oldBalance->remaining_days;
        $carried = min($remainingOld, $maxCarryForward);
        $expired = max(0, $remainingOld - $carried);

        if ($carried > 0) {
            $newBalance->carried_forward_days = $carried;
            $newBalance->save();

            LeaveTransaction::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'leave_application_id' => null,
                'type' => 'carry_forward',
                'amount' => +$carried,
                'running_balance' => $newBalance->available_days,
                'reason' => "Carry forward from {$fromYear} to {$toYear} (Max allowed: {$maxCarryForward} days)",
                'created_by' => auth()->id(),
            ]);
        }

        if ($expired > 0) {
            LeaveTransaction::create([
                'employee_id' => $employee->id,
                'leave_type_id' => $leaveType->id,
                'leave_application_id' => null,
                'type' => 'expired',
                'amount' => -$expired,
                'running_balance' => $oldBalance->available_days,
                'reason' => "Expired unused leave from {$fromYear} ({$expired} days forfeited)",
                'created_by' => auth()->id(),
            ]);
        }
    }
}
