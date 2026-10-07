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
        $maxTeamOnLeave = $policy ? $policy->max_team_on_leave : 2;

        // Fetch working days (0 = Sunday, 1 = Monday, ..., 6 = Saturday)
        // Saturday (6) and Sunday (0) are never counted as leave days.
        // The calendar settings may disable weekdays, but cannot make weekends workdays.
        $workingDayNumbers = WorkingDay::whereBetween('day_of_week', [1, 5])
            ->where('is_working_day', true)
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
                if ($totalDays > 0 && !empty($daysBreakdown)) {
                    $daysBreakdown[0]['weight'] = 0.5;
                    $daysBreakdown[0]['day_type'] = $halfDayType ?? 'half_day';
                }
            } else {
                // Apply the half day to the last working day, even when the selected
                // end date is a weekend or holiday.
                $totalDays = $workingDaysCount > 0 ? (float) ($workingDaysCount - 0.5) : 0.0;
                for ($i = count($daysBreakdown) - 1; $i >= 0; $i--) {
                    if ($daysBreakdown[$i]['is_working_day']) {
                        $daysBreakdown[$i]['weight'] = 0.5;
                        $daysBreakdown[$i]['day_type'] = $halfDayType ?? 'half_day';
                        break;
                    }
                }
            }
        } else {
            $totalDays = (float) $workingDaysCount;
        }

        // Annual leave may normally start today or within the next two days.
        // Emergency requests may use past dates or a later future start date.
        $today = Carbon::today();
        $isAdvanceNoticeValid = true;
        $advanceNoticeMessage = null;

        $effectiveEmergency = $isEmergency || $leaveType->is_emergency_type || $leaveType->code === 'emergency';

        if (!$effectiveEmergency && $leaveType->code === 'annual') {
            $latestAllowedDate = $today->copy()->addDays(2);
            if ($start->lt($today) || $start->gt($latestAllowedDate)) {
                $isAdvanceNoticeValid = false;
                $advanceNoticeMessage = 'Annual leave may normally start today or within the next 2 days. Check Emergency Leave Exception to request a past date or a start date more than 2 days away.';
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
            ->whereIn('status', ['approved', 'pending_team_lead', 'pending_manager', 'pending_hr'])
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
