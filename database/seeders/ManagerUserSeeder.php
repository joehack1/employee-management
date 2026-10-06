<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ManagerUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $manager = User::firstOrCreate(
                ['email' => 'manager@company.com'],
                [
                    'name' => 'Alex Morgan',
                    'role' => 'manager',
                    'employee_number' => 'EMP-007',
                    'password' => Hash::make(env('DEMO_MANAGER_PASSWORD', 'password')),
                    'is_active' => true,
                ]
            );

            $hrDepartment = Department::firstOrCreate(
                ['code' => 'HR'],
                ['name' => 'Human Resources', 'description' => 'People operations', 'is_active' => true]
            );
            $standardPolicyId = DB::table('leave_policies')->where('is_default', true)->value('id');

            $employee = Employee::updateOrCreate(
                ['employee_number' => 'EMP-007'],
                [
                    'user_id' => $manager->id,
                    'employee_number' => 'EMP-007',
                    'first_name' => 'Alex',
                    'last_name' => 'Morgan',
                    'email' => 'manager@company.com',
                    'department_id' => $hrDepartment->id,
                    'team_id' => null,
                    'job_title' => 'General Manager',
                    'team_lead_id' => null,
                    'manager_id' => null,
                    'date_employed' => '2023-01-01',
                    'employment_status' => 'active',
                    'leave_policy_id' => $standardPolicyId,
                    'annual_entitlement' => 21,
                ]
            );

            Employee::where('user_id', '!=', $manager->id)
                ->update(['manager_id' => $manager->id]);

            foreach (LeaveType::where('is_active', true)->get() as $leaveType) {
                LeaveBalance::firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => Carbon::now()->year,
                    ],
                    [
                        'entitled_days' => $leaveType->code === 'annual' ? 0 : $leaveType->days_allowed,
                        'carried_forward_days' => 0,
                        'manual_adjustment_days' => 0,
                        'used_days' => 0,
                        'pending_days' => 0,
                    ]
                );
            }
        });
    }
}
