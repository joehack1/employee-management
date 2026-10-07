<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $year = now()->year;
        $entitlements = [
            'sick' => 45,
            'compassionate' => 7,
        ];

        foreach ($entitlements as $code => $days) {
            $leaveType = DB::table('leave_types')->where('code', $code)->first();
            if (!$leaveType) {
                continue;
            }

            DB::table('leave_types')->where('id', $leaveType->id)->update([
                'days_allowed' => $days,
                'description' => $code === 'sick'
                    ? 'Up to 30 days at full pay followed by up to 15 days at half pay. A supporting medical document is required for every request.'
                    : '7 paid days upon the death of a loved one.',
                'updated_at' => now(),
            ]);

            $employees = DB::table('employees')->select('id')->get();
            foreach ($employees as $employee) {
                $balance = DB::table('leave_balances')
                    ->where('employee_id', $employee->id)
                    ->where('leave_type_id', $leaveType->id)
                    ->where('year', $year)
                    ->first();

                if ($balance) {
                    DB::table('leave_balances')->where('id', $balance->id)->update([
                        'entitled_days' => $days,
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('leave_balances')->insert([
                        'employee_id' => $employee->id,
                        'leave_type_id' => $leaveType->id,
                        'year' => $year,
                        'entitled_days' => $days,
                        'carried_forward_days' => 0,
                        'manual_adjustment_days' => 0,
                        'used_days' => 0,
                        'pending_days' => 0,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down(): void
    {
        // Entitlements are policy data and may already have been used or adjusted.
    }
};
