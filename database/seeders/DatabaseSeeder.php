<?php

namespace Database\Seeders;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\EmailTemplate;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveApplicationDay;
use App\Models\LeaveApproval;
use App\Models\LeaveBalance;
use App\Models\LeaveComment;
use App\Models\LeavePolicy;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use App\Models\SystemSetting;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;

        // 1. Working Days
        $days = [
            ['day_of_week' => 0, 'name' => 'Sunday', 'is_working_day' => false],
            ['day_of_week' => 1, 'name' => 'Monday', 'is_working_day' => true],
            ['day_of_week' => 2, 'name' => 'Tuesday', 'is_working_day' => true],
            ['day_of_week' => 3, 'name' => 'Wednesday', 'is_working_day' => true],
            ['day_of_week' => 4, 'name' => 'Thursday', 'is_working_day' => true],
            ['day_of_week' => 5, 'name' => 'Friday', 'is_working_day' => true],
            ['day_of_week' => 6, 'name' => 'Saturday', 'is_working_day' => false],
        ];
        foreach ($days as $day) {
            WorkingDay::updateOrCreate(['day_of_week' => $day['day_of_week']], $day);
        }

        // 2. Public Holidays
        $holidays = [
            ['name' => "New Year's Day", 'date' => '2026-01-01', 'type' => 'public_holiday', 'is_recurring' => true],
            ['name' => 'Good Friday', 'date' => '2026-04-03', 'type' => 'public_holiday', 'is_recurring' => false],
            ['name' => 'Easter Monday', 'date' => '2026-04-06', 'type' => 'public_holiday', 'is_recurring' => false],
            ['name' => 'Labour Day', 'date' => '2026-05-01', 'type' => 'public_holiday', 'is_recurring' => true],
            ['name' => 'Madaraka Day', 'date' => '2026-06-01', 'type' => 'public_holiday', 'is_recurring' => true],
            ['name' => 'Mashujaa Day', 'date' => '2026-10-20', 'type' => 'public_holiday', 'is_recurring' => true],
            ['name' => 'Jamhuri Day', 'date' => '2026-12-12', 'type' => 'public_holiday', 'is_recurring' => true],
            ['name' => 'Christmas Day', 'date' => '2026-12-25', 'type' => 'public_holiday', 'is_recurring' => true],
            ['name' => 'Boxing Day', 'date' => '2026-12-26', 'type' => 'public_holiday', 'is_recurring' => true],
        ];
        foreach ($holidays as $h) {
            PublicHoliday::updateOrCreate(['name' => $h['name'], 'date' => $h['date']], $h);
        }

        // 3. Leave Types
        $leaveTypes = [
            [
                'name' => 'Annual Leave',
                'code' => 'annual',
                'description' => 'Standard paid annual leave allowance for employees. Requires 3-day advance notice.',
                'days_allowed' => 21.0,
                'is_paid' => true,
                'requires_attachment' => false,
                'is_emergency_type' => false,
                'color' => 'blue',
            ],
            [
                'name' => 'Sick Leave',
                'code' => 'sick',
                'description' => 'Medical leave for health recovery. Requires medical certificate for leave beyond 2 days.',
                'days_allowed' => 14.0,
                'is_paid' => true,
                'requires_attachment' => true,
                'attachment_required_after_days' => 2,
                'is_emergency_type' => false,
                'color' => 'emerald',
            ],
            [
                'name' => 'Emergency Leave',
                'code' => 'emergency',
                'description' => 'Urgent unforeseen personal or family emergencies. Bypasses 3-day notice rule and can be backdated.',
                'days_allowed' => 5.0,
                'is_paid' => true,
                'requires_attachment' => false,
                'is_emergency_type' => true,
                'color' => 'amber',
            ],
            [
                'name' => 'Maternity Leave',
                'code' => 'maternity',
                'description' => 'Paid maternity leave for female employees.',
                'days_allowed' => 90.0,
                'is_paid' => true,
                'requires_attachment' => true,
                'attachment_required_after_days' => 0,
                'is_emergency_type' => false,
                'color' => 'purple',
            ],
            [
                'name' => 'Paternity Leave',
                'code' => 'paternity',
                'description' => 'Paid paternity leave for male employees.',
                'days_allowed' => 14.0,
                'is_paid' => true,
                'requires_attachment' => true,
                'attachment_required_after_days' => 0,
                'is_emergency_type' => false,
                'color' => 'indigo',
            ],
            [
                'name' => 'Compassionate Leave',
                'code' => 'compassionate',
                'description' => 'Leave granted upon bereavement of an immediate family member.',
                'days_allowed' => 5.0,
                'is_paid' => true,
                'requires_attachment' => false,
                'is_emergency_type' => false,
                'color' => 'rose',
            ],
            [
                'name' => 'Unpaid Leave',
                'code' => 'unpaid',
                'description' => 'Approved unpaid leave when paid leave entitlement is exhausted.',
                'days_allowed' => 30.0,
                'is_paid' => false,
                'requires_attachment' => false,
                'is_emergency_type' => false,
                'color' => 'slate',
            ],
            [
                'name' => 'Study Leave',
                'code' => 'study',
                'description' => 'Leave for exams and certified professional education.',
                'days_allowed' => 10.0,
                'is_paid' => true,
                'requires_attachment' => true,
                'attachment_required_after_days' => 1,
                'is_emergency_type' => false,
                'color' => 'cyan',
            ],
        ];

        $typeModels = [];
        foreach ($leaveTypes as $lt) {
            $typeModels[$lt['code']] = LeaveType::updateOrCreate(['code' => $lt['code']], $lt);
        }

        // 4. Leave Policies
        $standardPolicy = LeavePolicy::updateOrCreate(
            ['name' => 'Standard Corporate Policy'],
            [
                'annual_days' => 21.0,
                'accrual_type' => 'upfront',
                'monthly_accrual_rate' => 1.75,
                'max_carry_forward' => 5.0,
                'leave_year_type' => 'calendar',
                'min_days_advance_notice' => 3,
                'max_team_on_leave' => 2,
                'approval_workflow' => 'team_lead_then_hr',
                'is_default' => true,
            ]
        );

        $directHrPolicy = LeavePolicy::updateOrCreate(
            ['name' => 'Executive Direct HR Policy'],
            [
                'annual_days' => 25.0,
                'accrual_type' => 'upfront',
                'monthly_accrual_rate' => 2.08,
                'max_carry_forward' => 8.0,
                'leave_year_type' => 'calendar',
                'min_days_advance_notice' => 1,
                'max_team_on_leave' => 3,
                'approval_workflow' => 'direct_hr',
                'is_default' => false,
            ]
        );

        // 5. Departments and Teams
        $ictDept = Department::updateOrCreate(['code' => 'ICT'], [
            'name' => 'Information & Communication Technology',
            'code' => 'ICT',
            'description' => 'Engineering, Infrastructure, and Technical Support',
            'is_active' => true,
        ]);

        $devTeam = Team::updateOrCreate(['department_id' => $ictDept->id, 'name' => 'Software Development'], [
            'department_id' => $ictDept->id,
            'name' => 'Software Development',
            'description' => 'Web applications, APIs, and mobile systems engineering',
        ]);

        $supportTeam = Team::updateOrCreate(['department_id' => $ictDept->id, 'name' => 'IT Support'], [
            'department_id' => $ictDept->id,
            'name' => 'IT Support',
            'description' => 'Helpdesk, workstation configuration, and internal support',
        ]);

        $infraTeam = Team::updateOrCreate(['department_id' => $ictDept->id, 'name' => 'Infrastructure'], [
            'department_id' => $ictDept->id,
            'name' => 'Infrastructure',
            'description' => 'Cloud infrastructure, servers, and network security',
        ]);

        $financeDept = Department::updateOrCreate(['code' => 'FIN'], [
            'name' => 'Finance & Accounting',
            'code' => 'FIN',
            'description' => 'Financial planning, accounting, and procurement',
            'is_active' => true,
        ]);

        $accountsTeam = Team::updateOrCreate(['department_id' => $financeDept->id, 'name' => 'Accounts'], [
            'department_id' => $financeDept->id,
            'name' => 'Accounts',
            'description' => 'Bookkeeping, payroll, and billing',
        ]);

        $procurementTeam = Team::updateOrCreate(['department_id' => $financeDept->id, 'name' => 'Procurement'], [
            'department_id' => $financeDept->id,
            'name' => 'Procurement',
            'description' => 'Vendor management and supply acquisition',
        ]);

        $hrDept = Department::updateOrCreate(['code' => 'HR'], [
            'name' => 'Human Resources',
            'code' => 'HR',
            'description' => 'People operations, recruitment, and benefits',
            'is_active' => true,
        ]);

        $marketingDept = Department::updateOrCreate(['code' => 'MKT'], [
            'name' => 'Marketing',
            'code' => 'MKT',
            'description' => 'Growth, communications, and public relations',
            'is_active' => true,
        ]);

        $opsDept = Department::updateOrCreate(['code' => 'OPS'], [
            'name' => 'Operations',
            'code' => 'OPS',
            'description' => 'Business operations and logistics',
            'is_active' => true,
        ]);

        // 6. Users & Employees
        $password = Hash::make('password');

        // User 1: Sarah Jenkins (HR Manager / Admin)
        $userHr = User::updateOrCreate(['email' => 'hr@company.com'], [
            'name' => 'Sarah Jenkins',
            'email' => 'hr@company.com',
            'role' => 'hr',
            'employee_number' => 'EMP-001',
            'phone' => '+254 700 111 222',
            'password' => $password,
            'is_active' => true,
        ]);

        $empHr = Employee::updateOrCreate(['employee_number' => 'EMP-001'], [
            'user_id' => $userHr->id,
            'employee_number' => 'EMP-001',
            'first_name' => 'Sarah',
            'last_name' => 'Jenkins',
            'email' => 'hr@company.com',
            'phone' => '+254 700 111 222',
            'department_id' => $hrDept->id,
            'team_id' => null,
            'job_title' => 'HR Manager',
            'date_employed' => '2022-01-15',
            'employment_status' => 'active',
            'leave_policy_id' => $standardPolicy->id,
            'annual_entitlement' => 21.0,
        ]);

        // User 2: James Vance (Team Lead - Software Development)
        $userLead = User::updateOrCreate(['email' => 'lead@company.com'], [
            'name' => 'James Vance',
            'email' => 'lead@company.com',
            'role' => 'team_lead',
            'employee_number' => 'EMP-002',
            'phone' => '+254 700 333 444',
            'password' => $password,
            'is_active' => true,
        ]);

        $empLead = Employee::updateOrCreate(['employee_number' => 'EMP-002'], [
            'user_id' => $userLead->id,
            'employee_number' => 'EMP-002',
            'first_name' => 'James',
            'last_name' => 'Vance',
            'email' => 'lead@company.com',
            'phone' => '+254 700 333 444',
            'department_id' => $ictDept->id,
            'team_id' => $devTeam->id,
            'job_title' => 'Lead Software Engineer',
            'manager_id' => $userHr->id,
            'date_employed' => '2023-03-01',
            'employment_status' => 'active',
            'leave_policy_id' => $standardPolicy->id,
            'annual_entitlement' => 21.0,
        ]);

        $devTeam->leader_id = $userLead->id;
        $devTeam->save();

        // User 3: Joel Loter (Senior Developer - Super prominent in prompt)
        $userJoel = User::updateOrCreate(['email' => 'joel@company.com'], [
            'name' => 'Joel Loter',
            'email' => 'joel@company.com',
            'role' => 'employee',
            'employee_number' => 'EMP-003',
            'phone' => '+254 700 555 666',
            'password' => $password,
            'is_active' => true,
        ]);

        $empJoel = Employee::updateOrCreate(['employee_number' => 'EMP-003'], [
            'user_id' => $userJoel->id,
            'employee_number' => 'EMP-003',
            'first_name' => 'Joel',
            'last_name' => 'Loter',
            'email' => 'joel@company.com',
            'phone' => '+254 700 555 666',
            'department_id' => $ictDept->id,
            'team_id' => $devTeam->id,
            'job_title' => 'Senior Developer',
            'team_lead_id' => $userLead->id,
            'manager_id' => $userHr->id,
            'date_employed' => '2024-02-10',
            'employment_status' => 'active',
            'leave_policy_id' => $standardPolicy->id,
            'annual_entitlement' => 21.0,
        ]);

        // User 4: Mary Wanjiku (Software Engineer)
        $userMary = User::updateOrCreate(['email' => 'mary@company.com'], [
            'name' => 'Mary Wanjiku',
            'email' => 'mary@company.com',
            'role' => 'employee',
            'employee_number' => 'EMP-004',
            'phone' => '+254 700 777 888',
            'password' => $password,
            'is_active' => true,
        ]);

        $empMary = Employee::updateOrCreate(['employee_number' => 'EMP-004'], [
            'user_id' => $userMary->id,
            'employee_number' => 'EMP-004',
            'first_name' => 'Mary',
            'last_name' => 'Wanjiku',
            'email' => 'mary@company.com',
            'phone' => '+254 700 777 888',
            'department_id' => $ictDept->id,
            'team_id' => $devTeam->id,
            'job_title' => 'Software Engineer',
            'team_lead_id' => $userLead->id,
            'manager_id' => $userHr->id,
            'date_employed' => '2024-06-15',
            'employment_status' => 'active',
            'leave_policy_id' => $standardPolicy->id,
            'annual_entitlement' => 21.0,
        ]);

        // User 5: Peter Otieno (QA Engineer)
        $userPeter = User::updateOrCreate(['email' => 'peter@company.com'], [
            'name' => 'Peter Otieno',
            'email' => 'peter@company.com',
            'role' => 'employee',
            'employee_number' => 'EMP-005',
            'phone' => '+254 700 999 000',
            'password' => $password,
            'is_active' => true,
        ]);

        $empPeter = Employee::updateOrCreate(['employee_number' => 'EMP-005'], [
            'user_id' => $userPeter->id,
            'employee_number' => 'EMP-005',
            'first_name' => 'Peter',
            'last_name' => 'Otieno',
            'email' => 'peter@company.com',
            'phone' => '+254 700 999 000',
            'department_id' => $ictDept->id,
            'team_id' => $devTeam->id,
            'job_title' => 'QA Engineer',
            'team_lead_id' => $userLead->id,
            'manager_id' => $userHr->id,
            'date_employed' => '2024-08-01',
            'employment_status' => 'active',
            'leave_policy_id' => $standardPolicy->id,
            'annual_entitlement' => 21.0,
        ]);

        // User 6: David Ndungu (Finance Accounts Lead)
        $userDavid = User::updateOrCreate(['email' => 'david@company.com'], [
            'name' => 'David Ndungu',
            'email' => 'david@company.com',
            'role' => 'employee',
            'employee_number' => 'EMP-006',
            'phone' => '+254 711 222 333',
            'password' => $password,
            'is_active' => true,
        ]);

        $empDavid = Employee::updateOrCreate(['employee_number' => 'EMP-006'], [
            'user_id' => $userDavid->id,
            'employee_number' => 'EMP-006',
            'first_name' => 'David',
            'last_name' => 'Ndungu',
            'email' => 'david@company.com',
            'phone' => '+254 711 222 333',
            'department_id' => $financeDept->id,
            'team_id' => $accountsTeam->id,
            'job_title' => 'Senior Accountant',
            'manager_id' => $userHr->id,
            'date_employed' => '2023-11-20',
            'employment_status' => 'active',
            'leave_policy_id' => $standardPolicy->id,
            'annual_entitlement' => 21.0,
        ]);

        $allEmps = [$empHr, $empLead, $empJoel, $empMary, $empPeter, $empDavid];

        // 7. Seed Balances & Ledger Transactions for all employees
        foreach ($allEmps as $emp) {
            foreach ($typeModels as $code => $lt) {
                $days = $lt->days_allowed;

                $bal = LeaveBalance::updateOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'leave_type_id' => $lt->id,
                        'year' => $year,
                    ],
                    [
                        'entitled_days' => $days,
                        'carried_forward_days' => 0,
                        'manual_adjustment_days' => 0,
                        'used_days' => 0,
                        'pending_days' => 0,
                    ]
                );

                // Initial ledger transaction
                LeaveTransaction::firstOrCreate(
                    [
                        'employee_id' => $emp->id,
                        'leave_type_id' => $lt->id,
                        'type' => 'initial_entitlement',
                    ],
                    [
                        'amount' => $days,
                        'running_balance' => $days,
                        'reason' => "Initial entitlement allocation for year {$year}",
                        'created_by' => $userHr->id,
                    ]
                );
            }
        }

        // 8. Specific prompt scenario for Joel Loter (requirement 5, 6, 8, 10):
        // Joel Loter has:
        // Entitled: 21, Used: 7 (3-day annual leave in Aug + 4-day annual leave in Sep), HR adjustment: +2 days, Pending: 3 days, Available: 13 days
        $annualLt = $typeModels['annual'];
        $sickLt = $typeModels['sick'];
        $emergencyLt = $typeModels['emergency'];

        $joelBal = LeaveBalance::where('employee_id', $empJoel->id)->where('leave_type_id', $annualLt->id)->first();
        if ($joelBal) {
            $joelBal->used_days = 7.0;
            $joelBal->manual_adjustment_days = 2.0;
            $joelBal->pending_days = 3.0;
            $joelBal->save();

            // Record transaction for HR adjustment
            LeaveTransaction::create([
                'employee_id' => $empJoel->id,
                'leave_type_id' => $annualLt->id,
                'type' => 'hr_adjustment',
                'amount' => 2.0,
                'running_balance' => 23.0,
                'reason' => 'Management award: Extra leave awarded for delivering Q2 project ahead of schedule',
                'created_by' => $userHr->id,
                'created_at' => Carbon::now()->subMonths(1),
            ]);

            // Deduction transactions
            LeaveTransaction::create([
                'employee_id' => $empJoel->id,
                'leave_type_id' => $annualLt->id,
                'type' => 'approved_deduction',
                'amount' => -3.0,
                'running_balance' => 20.0,
                'reason' => 'Approved Annual Leave for LA-2026-0001 (10-14 Aug 2026)',
                'created_by' => $userHr->id,
                'created_at' => Carbon::now()->subMonths(2),
            ]);

            LeaveTransaction::create([
                'employee_id' => $empJoel->id,
                'leave_type_id' => $annualLt->id,
                'type' => 'approved_deduction',
                'amount' => -4.0,
                'running_balance' => 16.0,
                'reason' => 'Approved Annual Leave for LA-2026-0002 (02-05 Sep 2026)',
                'created_by' => $userHr->id,
                'created_at' => Carbon::now()->subMonth(),
            ]);
        }

        // Sick leave for Joel: Entitled 14, Used 2, Remaining 12
        $joelSickBal = LeaveBalance::where('employee_id', $empJoel->id)->where('leave_type_id', $sickLt->id)->first();
        if ($joelSickBal) {
            $joelSickBal->used_days = 2.0;
            $joelSickBal->save();

            LeaveTransaction::create([
                'employee_id' => $empJoel->id,
                'leave_type_id' => $sickLt->id,
                'type' => 'approved_deduction',
                'amount' => -2.0,
                'running_balance' => 12.0,
                'reason' => 'Approved Sick Leave for LA-2026-0003 (18-19 Sep 2026)',
                'created_by' => $userHr->id,
                'created_at' => Carbon::now()->subWeeks(3),
            ]);
        }

        // Emergency leave for Joel: Entitled 5, Used 1, Remaining 4
        $joelEmergBal = LeaveBalance::where('employee_id', $empJoel->id)->where('leave_type_id', $emergencyLt->id)->first();
        if ($joelEmergBal) {
            $joelEmergBal->used_days = 1.0;
            $joelEmergBal->save();

            LeaveTransaction::create([
                'employee_id' => $empJoel->id,
                'leave_type_id' => $emergencyLt->id,
                'type' => 'approved_deduction',
                'amount' => -1.0,
                'running_balance' => 4.0,
                'reason' => 'Approved Emergency Leave for LA-2026-0004 (04 Aug 2026)',
                'created_by' => $userHr->id,
                'created_at' => Carbon::now()->subMonths(2),
            ]);
        }

        // 9. Sample Leave Applications:
        // App 1: Past approved Annual Leave for Joel
        $app1 = LeaveApplication::create([
            'application_number' => 'LA-2026-0001',
            'employee_id' => $empJoel->id,
            'leave_type_id' => $annualLt->id,
            'start_date' => '2026-08-10',
            'end_date' => '2026-08-14',
            'total_days' => 5.0,
            'is_half_day' => false,
            'is_emergency' => false,
            'reason' => 'Annual family vacation',
            'status' => 'approved',
            'current_approval_level' => 'hr',
            'submitted_at' => '2026-08-01 10:00:00',
        ]);

        LeaveApproval::create([
            'leave_application_id' => $app1->id,
            'approver_id' => $userLead->id,
            'level' => 'team_lead',
            'action' => 'approved',
            'comment' => 'Approved. Work handed over to team.',
        ]);

        LeaveApproval::create([
            'leave_application_id' => $app1->id,
            'approver_id' => $userHr->id,
            'level' => 'hr',
            'action' => 'approved',
            'comment' => 'Approved. Have a great vacation!',
        ]);

        // App 2: Past approved Sick Leave for Mary
        $app2 = LeaveApplication::create([
            'application_number' => 'LA-2026-0002',
            'employee_id' => $empMary->id,
            'leave_type_id' => $sickLt->id,
            'start_date' => '2026-09-07',
            'end_date' => '2026-09-07',
            'total_days' => 1.0,
            'is_half_day' => false,
            'is_emergency' => false,
            'reason' => 'Doctor appointment and medical observation',
            'doctor_hospital_info' => 'Nairobi Hospital, Dr. Kimani',
            'status' => 'approved',
            'current_approval_level' => 'hr',
            'submitted_at' => '2026-09-07 08:30:00',
        ]);

        // App 3: Approved leave for Peter Otieno on Oct 14-16 (to trigger team conflict warning for others!)
        $app3 = LeaveApplication::create([
            'application_number' => 'LA-2026-0003',
            'employee_id' => $empPeter->id,
            'leave_type_id' => $annualLt->id,
            'start_date' => '2026-10-14',
            'end_date' => '2026-10-16',
            'total_days' => 3.0,
            'is_half_day' => false,
            'is_emergency' => false,
            'reason' => 'Personal matters and rest',
            'status' => 'approved',
            'current_approval_level' => 'hr',
            'submitted_at' => '2026-10-01 09:00:00',
        ]);

        // App 4: Pending Team Lead review for Mary Wanjiku (Annual Leave Oct 14-16, conflict with Peter!)
        $app4 = LeaveApplication::create([
            'application_number' => 'LA-2026-0004',
            'employee_id' => $empMary->id,
            'leave_type_id' => $annualLt->id,
            'start_date' => '2026-10-14',
            'end_date' => '2026-10-16',
            'total_days' => 3.0,
            'is_half_day' => false,
            'is_emergency' => false,
            'reason' => 'Visiting family upcountry',
            'status' => 'pending_team_lead',
            'current_approval_level' => 'team_lead',
            'team_conflict_count' => 1,
            'submitted_at' => '2026-10-05 14:00:00',
        ]);

        // App 5: Pending HR review for Joel Loter (Annual Leave Oct 21-23, approved by James Vance!)
        $app5 = LeaveApplication::create([
            'application_number' => 'LA-2026-0005',
            'employee_id' => $empJoel->id,
            'leave_type_id' => $annualLt->id,
            'start_date' => '2026-10-21',
            'end_date' => '2026-10-23',
            'total_days' => 3.0,
            'is_half_day' => false,
            'is_emergency' => false,
            'reason' => 'Family travel and wedding attendance',
            'status' => 'pending_hr',
            'current_approval_level' => 'hr',
            'team_conflict_count' => 0,
            'submitted_at' => '2026-10-05 11:30:00',
        ]);

        LeaveApproval::create([
            'leave_application_id' => $app5->id,
            'approver_id' => $userLead->id,
            'level' => 'team_lead',
            'action' => 'approved',
            'comment' => 'Approved by James Vance. Work and test coverage handed over to team.',
            'created_at' => '2026-10-05 15:10:00',
        ]);

        LeaveComment::create([
            'leave_application_id' => $app5->id,
            'user_id' => $userLead->id,
            'comment' => 'Approved by James Vance. Work and test coverage handed over to team.',
            'created_at' => '2026-10-05 15:10:00',
        ]);

        // App 6: Past Rejected Leave for Joel to demonstrate rejection comment & history
        $app6 = LeaveApplication::create([
            'application_number' => 'LA-2026-0006',
            'employee_id' => $empJoel->id,
            'leave_type_id' => $annualLt->id,
            'start_date' => '2026-08-24',
            'end_date' => '2026-08-28',
            'total_days' => 5.0,
            'is_half_day' => false,
            'is_emergency' => false,
            'reason' => 'Extended summer holiday',
            'status' => 'rejected',
            'rejection_reason' => 'Too many team members are already scheduled to be away during this release period.',
            'rejected_by' => $userLead->id,
            'current_approval_level' => 'team_lead',
            'submitted_at' => '2026-08-15 09:00:00',
        ]);

        LeaveApproval::create([
            'leave_application_id' => $app6->id,
            'approver_id' => $userLead->id,
            'level' => 'team_lead',
            'action' => 'rejected',
            'comment' => 'Too many team members are already scheduled to be away during this release period.',
        ]);

        // 10. Email Templates
        $emailTemplates = [
            [
                'code' => 'leave_submitted',
                'name' => 'Leave Application Submitted',
                'subject' => 'Your {leave_type} request has been submitted',
                'body_template' => "Hello {employee_name},\n\nYour {leave_type} application for {start_date} to {end_date} ({total_days} days) has been successfully submitted and forwarded for review.\n\nApplication ID: {application_number}",
            ],
            [
                'code' => 'lead_approved',
                'name' => 'Team Lead Approved',
                'subject' => 'Team Lead Approved Your Leave Application',
                'body_template' => "Hello {employee_name},\n\nYour {leave_type} application has been approved by your Team Lead and has now progressed to HR for final sign-off.\n\nApprover Comment: {comment}",
            ],
            [
                'code' => 'hr_approved',
                'name' => 'Leave Application Approved',
                'subject' => 'Leave Application Approved: {start_date} - {end_date}',
                'body_template' => "Hello {employee_name},\n\nCongratulations! Your {leave_type} request ({start_date} to {end_date}, {total_days} days) has been approved.\n\nHR Comment: {comment}\n\nPlease ensure your handover is complete before leaving.",
            ],
            [
                'code' => 'leave_rejected',
                'name' => 'Leave Application Rejected',
                'subject' => 'Update regarding your leave application',
                'body_template' => "Hello {employee_name},\n\nWe regret to inform you that your {leave_type} request has been rejected.\n\nReason: {rejection_reason}",
            ],
            [
                'code' => 'emergency_alert',
                'name' => 'Urgent: Emergency Leave Alert',
                'subject' => '🚨 URGENT: Emergency Leave Request Submitted',
                'body_template' => "Attention HR / Team Lead,\n\n{employee_name} has submitted an emergency leave application ({start_date} to {end_date}). This request bypassed normal notice requirements.\n\nReason: {reason}",
            ],
        ];

        foreach ($emailTemplates as $tmpl) {
            EmailTemplate::updateOrCreate(['code' => $tmpl['code']], $tmpl);
        }

        // 11. Initial Audit Logs
        AuditLog::create([
            'user_id' => $userHr->id,
            'action' => 'system_initialized',
            'entity_type' => 'System',
            'entity_id' => 1,
            'description' => 'Employee Leave Management System initialized with enterprise policies, departments, and seed data.',
            'ip_address' => '127.0.0.1',
        ]);

        AuditLog::create([
            'user_id' => $userHr->id,
            'action' => 'leave_adjustment',
            'entity_type' => 'LeaveBalance',
            'entity_id' => $joelBal->id,
            'description' => 'HR: Sarah Jenkins. Action: +2 Annual Leave. Employee: Joel Loter. Reason: Extra leave awarded for delivering Q2 project ahead of schedule.',
            'ip_address' => '127.0.0.1',
            'created_at' => Carbon::now()->subMonths(1),
        ]);
    }
}
