<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveStatusNotification;
use App\Services\LeaveWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ApprovalEmailRecipientsTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_gets_email_when_team_lead_applies_and_hr_is_emailed_after_manager_approval(): void
    {
        Notification::fake();

        $manager = $this->user('manager', 'manager@example.test');
        $hr = $this->user('hr', 'hr@example.test');
        [$applicantUser, $employee, $leaveType] = $this->applicant('team_lead', 'lead@example.test', [
            'manager_id' => $manager->id,
        ]);
        $service = app(LeaveWorkflowService::class);
        $application = $this->submit($service, $employee, $leaveType, $applicantUser);

        $this->assertSame('pending_manager', $application->status);
        Notification::assertSentTo($manager, LeaveStatusNotification::class, function ($notification, array $channels) use ($application): bool {
            return in_array('mail', $channels, true)
                && $notification->application->is($application)
                && str_contains($notification->title, 'Manager Review Required');
        });

        $service->approveByManager($application, $manager->id);

        Notification::assertSentTo($hr, LeaveStatusNotification::class, function ($notification, array $channels) use ($application): bool {
            return in_array('mail', $channels, true)
                && $notification->application->is($application)
                && $notification->title === 'Leave approved by Manager';
        });
    }

    public function test_hr_gets_email_after_team_lead_approval(): void
    {
        Notification::fake();

        $hr = $this->user('hr', 'hr@example.test');
        $teamLead = $this->user('team_lead', 'lead@example.test');
        [$applicantUser, $employee, $leaveType] = $this->applicant('employee', 'employee@example.test', [
            'team_lead_id' => $teamLead->id,
        ]);
        $service = app(LeaveWorkflowService::class);
        $application = $this->submit($service, $employee, $leaveType, $applicantUser);

        $service->approveByTeamLead($application, $teamLead->id);

        Notification::assertSentTo($hr, LeaveStatusNotification::class, function ($notification, array $channels) use ($application): bool {
            return in_array('mail', $channels, true)
                && $notification->application->is($application)
                && $notification->title === 'Leave approved by Team Lead';
        });
    }

    public function test_hr_gets_email_after_hr_approval(): void
    {
        Notification::fake();

        $hr = $this->user('hr', 'hr@example.test');
        [$applicantUser, $employee, $leaveType] = $this->applicant('manager', 'applicant-manager@example.test');
        $service = app(LeaveWorkflowService::class);
        $application = $this->submit($service, $employee, $leaveType, $applicantUser);

        $service->approveByHr($application, $hr->id);

        Notification::assertSentTo($hr, LeaveStatusNotification::class, function ($notification, array $channels) use ($application): bool {
            return in_array('mail', $channels, true)
                && $notification->application->is($application)
                && $notification->title === 'Leave approved by HR';
        });
    }

    private function user(string $role, string $email): User
    {
        return User::factory()->create([
            'name' => ucfirst(str_replace('_', ' ', $role)),
            'email' => $email,
            'role' => $role,
        ]);
    }

    /** @return array{User, Employee, LeaveType} */
    private function applicant(string $role, string $email, array $employeeAttributes = []): array
    {
        $user = $this->user($role, $email);
        $employee = Employee::create(array_merge([
            'user_id' => $user->id,
            'employee_number' => 'EMP-' . strtoupper(substr(md5($email), 0, 8)),
            'first_name' => 'Test',
            'last_name' => 'Applicant',
            'email' => $email,
            'date_employed' => Carbon::today()->subYear()->toDateString(),
            'employment_status' => 'active',
        ], $employeeAttributes));
        $leaveType = LeaveType::create([
            'name' => 'Sick Leave',
            'code' => 'sick',
            'days_allowed' => 30,
            'is_paid' => true,
            'requires_attachment' => false,
            'attachment_required_after_days' => 0,
            'requires_reason' => false,
            'is_emergency_type' => false,
            'color' => '#1d9692',
            'is_active' => true,
        ]);

        return [$user, $employee, $leaveType];
    }

    private function submit(LeaveWorkflowService $service, Employee $employee, LeaveType $leaveType, User $applicant): LeaveApplication
    {
        $startDate = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        return $service->submitApplication(
            employee: $employee,
            leaveType: $leaveType,
            validatedData: [
                'start_date' => $startDate,
                'end_date' => Carbon::parse($startDate)->addDay()->toDateString(),
                'reason' => 'Testing approval email notifications.',
            ],
            authUserId: $applicant->id,
        );
    }
}
