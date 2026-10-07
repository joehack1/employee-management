<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\LeaveStatusNotification;
use App\Services\LeaveWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TeamLeadLeaveEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_assigned_team_lead_receives_email_notification_when_employee_applies(): void
    {
        Notification::fake();

        $teamLead = User::factory()->create([
            'name' => 'Team Lead Example',
            'email' => 'lead@example.test',
            'role' => 'team_lead',
        ]);
        $employeeUser = User::factory()->create([
            'name' => 'Employee Example',
            'email' => 'employee@example.test',
            'role' => 'employee',
        ]);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_number' => 'EMP-TEST-001',
            'first_name' => 'Employee',
            'last_name' => 'Example',
            'email' => 'employee@example.test',
            'team_lead_id' => $teamLead->id,
            'date_employed' => Carbon::today()->subYear()->toDateString(),
            'employment_status' => 'active',
        ]);
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

        $startDate = Carbon::today()->next(Carbon::MONDAY)->toDateString();
        $endDate = Carbon::parse($startDate)->addDay()->toDateString();

        $application = app(LeaveWorkflowService::class)->submitApplication(
            employee: $employee,
            leaveType: $leaveType,
            validatedData: [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'reason' => 'Testing team lead leave email.',
            ],
            authUserId: $employeeUser->id,
        );

        $this->assertSame('pending_team_lead', $application->status);
        Notification::assertSentTo(
            $teamLead,
            LeaveStatusNotification::class,
            fn (LeaveStatusNotification $notification, array $channels): bool =>
                in_array('mail', $channels, true)
                && $notification->application->is($application)
                && str_contains($notification->message, 'Employee Example')
        );
    }
}
