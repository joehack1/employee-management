<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveType;
use App\Models\PublicHoliday;
use App\Models\WorkingDay;
use Carbon\Carbon;
use Carbon\CarbonPeriod;

class LeaveCalculationService
{
    /**
     * Calculate leave days breakdown between start date and end date.
     *
     * @return array [
     *   'total_days' => float,
     *   'working_days_count' => int,
     *   'weekend_days_count' => int,
     *   'holiday_days_count' => int,
     *   'days_breakdown' => array,
     *   'advance_notice_valid' => bool,
     *   'advance_notice_message' => ?string,
     *   'team_conflicts' => array,
     *   'exceeds_team_limit' => bool,
     * ]
     */
    public function calculate(
        Employee $employee,
        LeaveType $leaveType,
        string $startDate,
        string $endDate,
        bool $isHalfDay = false,
        ?string $halfDayType = null,
        bool $isEmergency = false
    ): array {
        $start = Carbon::parse($startDate)->startOfDay();
        $end = Carbon::parse($endDate)->startOfDay();

        // Ensure chronological order
        if ($end->lt($start)) {
            $end = $start->copy();
        }

        $policy = $employee->leavePolicy;
        $minAdvanceDays = $policy ? $policy->min_days_advance_notice : 3;
        $maxTeamOnLeave = $policy ? $policy->max_team_on_leave : 2;

        // Fetch working days (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        $workingDayNumbers = WorkingDay::where('is_working_day', true)
            ->pluck('day_of_week')
            ->toArray();

        // If no working days defined, fallback to Mon-Fri (1-5)
        if (empty($workingDayNumbers)) {
            $workingDayNumbers = [1, 2, 3, 4, 5];
        }

        // Fetch public holidays in range
        $holidays = PublicHoliday::whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn($h) => Carbon::parse($h->date)->toDateString());

        $period = CarbonPeriod::create($start, $end);
        $daysBreakdown = [];
        $workingDaysCount = 0;
        $weekendDaysCount = 0;
        $holidayDaysCount = 0;

        foreach ($period as $date) {
            $dateStr = $date->toDateString();
            $dayOfWeek = $date->dayOfWeek; // 0 (Sun) - 6 (Sat)

            $isWorkingDay = in_array($dayOfWeek, $workingDayNumbers);
            $isHoliday = $holidays->has($dateStr);
            $holidayName = $isHoliday ? $holidays->get($dateStr)->name : null;

            if ($isHoliday) {
                $holidayDaysCount++;
                $dayType = 'holiday';
                $weight = 0.0;
            } elseif (!$isWorkingDay) {
                $weekendDaysCount++;
                $dayType = 'weekend';
                $weight = 0.0;
            } else {
                $workingDaysCount++;
                $dayType = 'working_day';
                $weight = 1.0;
            }

            $daysBreakdown[] = [
                'date' => $dateStr,
                'day_name' => $date->format('l'),
                'is_working_day' => $isWorkingDay && !$isHoliday,
                'is_holiday' => $isHoliday,
                'holiday_name' => $holidayName,
                'day_type' => $dayType,
                'weight' => $weight,
            ];
        }

        // Calculate total days
        if ($isHalfDay) {
            if ($start->equalTo($end)) {
                $totalDays = $workingDaysCount > 0 ? 0.5 : 0.0;
                if (!empty($daysBreakdown)) {
                    $daysBreakdown[0]['weight'] = $totalDays;
                    $daysBreakdown[0]['day_type'] = $halfDayType ?? 'half_day';
                }
            } else {
                // Multi-day with half-day at end
                $totalDays = max(0.5, (float) ($workingDaysCount - 0.5));
                if (!empty($daysBreakdown)) {
                    $lastIdx = count($daysBreakdown) - 1;
                    $daysBreakdown[$lastIdx]['weight'] = 0.5;
                    $daysBreakdown[$lastIdx]['day_type'] = $halfDayType ?? 'half_day';
                }
            }
        } else {
            $totalDays = (float) $workingDaysCount;
        }

        // 3-Day Advance Rule Validation
        $today = Carbon::today();
        $isAdvanceNoticeValid = true;
        $advanceNoticeMessage = null;

        $effectiveEmergency = $isEmergency || $leaveType->is_emergency_type || $leaveType->code === 'emergency';

        if (!$effectiveEmergency && $leaveType->code === 'annual') {
            $earliestAllowedDate = $today->copy()->addDays($minAdvanceDays);
            if ($start->lt($earliestAllowedDate)) {
                $isAdvanceNoticeValid = false;
                $advanceNoticeMessage = "Annual leave must be applied for at least {$minAdvanceDays} days in advance. Earliest allowed start date is " . $earliestAllowedDate->format('d M Y') . ".";
            }
        }

        // Check Team Conflicts
        $teamConflicts = $this->checkTeamConflicts($employee, $start->toDateString(), $end->toDateString());
        $exceedsTeamLimit = count($teamConflicts) >= $maxTeamOnLeave;

        return [
            'total_days' => $totalDays,
            'working_days_count' => $workingDaysCount,
            'weekend_days_count' => $weekendDaysCount,
            'holiday_days_count' => $holidayDaysCount,
            'days_breakdown' => $daysBreakdown,
            'advance_notice_valid' => $isAdvanceNoticeValid,
            'advance_notice_message' => $advanceNoticeMessage,
            'team_conflicts' => $teamConflicts,
            'exceeds_team_limit' => $exceedsTeamLimit,
            'max_team_on_leave' => $maxTeamOnLeave,
        ];
    }

    /**
     * Check how many teammates are on leave during requested dates.
     */
    public function checkTeamConflicts(Employee $employee, string $startDate, string $endDate, ?int $ignoreApplicationId = null): array
    {
        if (!$employee->team_id && !$employee->department_id) {
            return [];
        }

        $query = LeaveApplication::query()
            ->with(['employee'])
            ->where('employee_id', '!=', $employee->id)
            ->whereIn('status', ['approved', 'pending_team_lead', 'pending_hr'])
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($inner) use ($startDate, $endDate) {
                      $inner->where('start_date', '<=', $startDate)
                            ->where('end_date', '>=', $endDate);
                  });
            });

        if ($ignoreApplicationId) {
            $query->where('id', '!=', $ignoreApplicationId);
        }

        // Match by team if set, else department
        $query->whereHas('employee', function ($q) use ($employee) {
            if ($employee->team_id) {
                $q->where('team_id', $employee->team_id);
            } else {
                $q->where('department_id', $employee->department_id);
            }
        });

        $conflictingApplications = $query->get();

        $results = [];
        foreach ($conflictingApplications as $app) {
            $results[] = [
                'employee_name' => $app->employee->full_name,
                'leave_type' => $app->leaveType->name ?? 'Leave',
                'start_date' => Carbon::parse($app->start_date)->format('d M'),
                'end_date' => Carbon::parse($app->end_date)->format('d M Y'),
                'status' => $app->status,
                'total_days' => $app->total_days,
            ];
        }

        return $results;
    }
}
