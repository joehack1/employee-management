<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\LeaveType;
use App\Models\User;
use App\Services\LeaveWorkflowService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class ManagerAssignedTeamLeadApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_approve_request_for_employee_assigned_only_to_them_as_manager(): void
    {
        Notification::fake();

        $manager = User::factory()->create(['role' => 'manager']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_number' => 'EMP-MANAGER-ONLY-001',
            'first_name' => 'Morgan',
            'last_name' => 'Employee',
            'email' => $employeeUser->email,
            'manager_id' => $manager->id,
            'employment_status' => 'active',
            'date_employed' => Carbon::today()->subYear()->toDateString(),
        ]);
        $application = $this->submitLeave($employee, $employeeUser, 'Manager-only assignment.');

        $this->assertSame('pending_manager', $application->status);
        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Morgan Employee')
            ->assertSee('Manager-only assignment.');
        $this->get(route('leave.show', $application->id))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->assertSee(route('approvals.managerApprove', $application->id), false);

        $this->post(route('approvals.managerApprove', $application->id), ['comment' => 'Approved'])
            ->assertRedirect(route('approvals.pending'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_manager_can_approve_team_lead_pending_request_when_assigned_only_as_manager(): void
    {
        Notification::fake();

        $manager = User::factory()->create(['role' => 'manager']);
        $otherLead = User::factory()->create(['role' => 'team_lead']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_number' => 'EMP-MANAGER-ASSIGNED-001',
            'first_name' => 'Jamie',
            'last_name' => 'Employee',
            'email' => $employeeUser->email,
            'manager_id' => $manager->id,
            'team_lead_id' => $otherLead->id,
            'employment_status' => 'active',
            'date_employed' => Carbon::today()->subYear()->toDateString(),
        ]);
        $application = $this->submitLeave($employee, $employeeUser, 'Assigned manager review.');

        $this->assertSame('pending_team_lead', $application->status);
        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Jamie Employee')
            ->assertSee('Assigned manager review.');
        $this->get(route('leave.show', $application->id))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->assertSee(route('approvals.managerApprove', $application->id), false);

        $this->post(route('approvals.managerApprove', $application->id), ['comment' => 'Approved'])
            ->assertRedirect(route('approvals.pending'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_manager_can_approve_employee_leave_when_assigned_as_their_team_lead(): void
    {
        Notification::fake();

        $manager = User::factory()->create(['role' => 'manager']);
        $employeeUser = User::factory()->create(['role' => 'employee']);
        $employee = Employee::create([
            'user_id' => $employeeUser->id,
            'employee_number' => 'EMP-ASSIGNED-001',
            'first_name' => 'Alex',
            'last_name' => 'Employee',
            'email' => $employeeUser->email,
            'team_lead_id' => $manager->id,
            'employment_status' => 'active',
            'date_employed' => Carbon::today()->subYear()->toDateString(),
        ]);
        $leaveType = $this->sickLeaveType();
        $startDate = Carbon::today()->next(Carbon::MONDAY)->toDateString();
        $application = app(LeaveWorkflowService::class)->submitApplication(
            employee: $employee,
            leaveType: $leaveType,
            validatedData: [
                'start_date' => $startDate,
                'end_date' => Carbon::parse($startDate)->addDay()->toDateString(),
                'reason' => 'Assigned team lead approval.',
            ],
            authUserId: $employeeUser->id,
        );

        $this->assertSame('pending_team_lead', $application->status);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Alex Employee')
            ->assertSee('Assigned team lead approval.');

        $this->get(route('leave.show', $application->id))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->assertSee(route('approvals.leadApprove', $application->id), false);

        $this->post(route('approvals.leadApprove', $application->id))
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
    }

    public function test_manager_can_approve_a_team_leads_request_assigned_to_them(): void
    {
        Notification::fake();

        $manager = User::factory()->create(['role' => 'manager']);
        $teamLead = User::factory()->create(['role' => 'team_lead']);
        $employee = Employee::create([
            'user_id' => $teamLead->id,
            'employee_number' => 'EMP-TEAM-LEAD-001',
            'first_name' => 'Taylor',
            'last_name' => 'Lead',
            'email' => $teamLead->email,
            'manager_id' => $manager->id,
            'employment_status' => 'active',
            'date_employed' => Carbon::today()->subYear()->toDateString(),
        ]);
        $leaveType = $this->sickLeaveType();
        $startDate = Carbon::today()->next(Carbon::MONDAY)->toDateString();
        $application = app(LeaveWorkflowService::class)->submitApplication(
            employee: $employee,
            leaveType: $leaveType,
            validatedData: [
                'start_date' => $startDate,
                'end_date' => Carbon::parse($startDate)->addDay()->toDateString(),
                'reason' => 'Team lead leave request.',
            ],
            authUserId: $teamLead->id,
        );

        $this->assertSame('pending_manager', $application->status);

        $this->actingAs($manager)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Taylor Lead')
            ->assertSee('Team lead leave request.');

        $this->get(route('leave.show', $application->id))
            ->assertOk()
            ->assertSee('Approve')
            ->assertSee('Reject')
            ->assertSee(route('approvals.managerApprove', $application->id), false);

        $this->post(route('approvals.managerApprove', $application->id), ['comment' => 'Approved'])
            ->assertRedirect(route('approvals.pending'))
            ->assertSessionHas('success');

        $this->assertSame('approved', $application->fresh()->status);
    }

    private function sickLeaveType(): LeaveType
    {
        return LeaveType::create([
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
    }

    private function submitLeave(Employee $employee, User $applicant, string $reason): \App\Models\LeaveApplication
    {
        $startDate = Carbon::today()->next(Carbon::MONDAY)->toDateString();

        return app(LeaveWorkflowService::class)->submitApplication(
            employee: $employee,
            leaveType: $this->sickLeaveType(),
            validatedData: [
                'start_date' => $startDate,
                'end_date' => Carbon::parse($startDate)->addDay()->toDateString(),
                'reason' => $reason,
            ],
            authUserId: $applicant->id,
        );
    }
}
