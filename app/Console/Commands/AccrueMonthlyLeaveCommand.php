<?php

namespace App\Console\Commands;

use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AccrueMonthlyLeaveCommand extends Command
{
    protected $signature = 'leave:accrue-monthly {--date= : Accrual date for an administrative run (YYYY-MM-DD)}';

    protected $description = 'Credit monthly annual leave entitlement based on employee tenure';

    public function handle(): int
    {
        $date = $this->option('date')
            ? Carbon::parse($this->option('date'))->startOfDay()
            : Carbon::today();
        $period = $date->format('Y-m');
        $leaveType = LeaveType::where('code', 'annual')->first();

        if (!$leaveType) {
            $this->warn('Annual leave type was not found. No balances were changed.');
            return self::SUCCESS;
        }

        $credited = 0;
        Employee::query()
            ->where('employment_status', 'active')
            ->whereDate('date_employed', '<=', $date->toDateString())
            ->orderBy('id')
            ->chunkById(100, function ($employees) use ($date, $period, $leaveType, &$credited) {
                foreach ($employees as $employee) {
                    $reason = "Monthly annual leave accrual {$period}";
                    $rate = $employee->date_employed->lt($date->copy()->subYears(3)) ? 2.25 : 1.75;

                    DB::transaction(function () use ($employee, $date, $period, $leaveType, $reason, $rate, &$credited) {
                        $alreadyCredited = LeaveTransaction::where('employee_id', $employee->id)
                            ->where('leave_type_id', $leaveType->id)
                            ->where('type', 'accrual')
                            ->where('reason', $reason)
                            ->exists();

                        if ($alreadyCredited) {
                            return;
                        }

                        $balance = LeaveBalance::firstOrCreate(
                            ['employee_id' => $employee->id, 'leave_type_id' => $leaveType->id, 'year' => $date->year],
                            ['entitled_days' => 0, 'carried_forward_days' => 0, 'manual_adjustment_days' => 0, 'used_days' => 0, 'pending_days' => 0]
                        );

                        $balance = LeaveBalance::whereKey($balance->id)->lockForUpdate()->first();
                        $balance->entitled_days = (float) $balance->entitled_days + $rate;
                        $balance->save();

                        LeaveTransaction::create([
                            'employee_id' => $employee->id,
                            'leave_type_id' => $leaveType->id,
                            'type' => 'accrual',
                            'amount' => $rate,
                            'running_balance' => $balance->available_days,
                            'reason' => $reason,
                            'created_by' => null,
                        ]);

                        $credited++;
                    });
                }
            });

        $this->info("Credited {$period} annual leave for {$credited} employee(s).");
        return self::SUCCESS;
    }
}
