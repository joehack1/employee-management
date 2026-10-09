<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\PublicHoliday;
use App\Models\Team;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LeaveCalendarController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $month = $request->input('month', Carbon::now()->month);
        $year = $request->input('year', Carbon::now()->year);

        $canViewOrganizationCalendar = $user->isHr() || $user->isManager();
        $departments = $canViewOrganizationCalendar ? Department::where('is_active', true)->get() : collect();
        $employees = $user->isHr()
            ? Employee::orderBy('first_name')->orderBy('last_name')->get(['id', 'first_name', 'last_name', 'employee_number'])
            : collect();
        $selectedEmployee = $user->isHr() ? $request->input('employee_id', '') : '';

        if ($canViewOrganizationCalendar) {
            $teams = Team::all();
        } elseif ($user->isTeamLead()) {
            $teamIds = Employee::query()
                ->where(function ($query) use ($user, $employee) {
                    $query->where('team_lead_id', $user->id);
                    if ($employee?->team_id) {
                        $query->orWhere('team_id', $employee->team_id);
                    }
                })
                ->whereNotNull('team_id')
                ->pluck('team_id')
                ->unique();
            $teams = Team::whereIn('id', $teamIds)->get();
        } else {
            $teams = $employee?->team_id ? Team::whereKey($employee->team_id)->get() : collect();
        }

        return view('calendar.index', compact('month', 'year', 'departments', 'teams', 'employees', 'selectedEmployee', 'user', 'employee'));
    }

    public function eventsJson(Request $request)
    {
        $user = Auth::user();
        $employee = $user->employee;

        $month = (int) $request->input('month', Carbon::now()->month);
        $year = (int) $request->input('year', Carbon::now()->year);

        $startOfMonth = Carbon::create($year, $month, 1)->startOfMonth()->toDateString();
        $endOfMonth = Carbon::create($year, $month, 1)->endOfMonth()->toDateString();

        $query = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType'])
            ->where('status', 'approved')
            ->where(function ($q) use ($startOfMonth, $endOfMonth) {
                $q->whereBetween('start_date', [$startOfMonth, $endOfMonth])
                  ->orWhereBetween('end_date', [$startOfMonth, $endOfMonth])
                  ->orWhere(function ($inner) use ($startOfMonth, $endOfMonth) {
                      $inner->where('start_date', '<=', $startOfMonth)
                            ->where('end_date', '>=', $endOfMonth);
                  });
            });

        // Employees and team leads can only see approved leave for their team.
        if ($user->isTeamLead() && !$user->isHr()) {
            $teamMemberIds = Employee::query()
                ->where(function ($q) use ($user, $employee) {
                    $q->where('team_lead_id', $user->id);
                    if ($employee?->team_id) {
                        $q->orWhere('team_id', $employee->team_id);
                    }
                })
                ->pluck('id');
            $query->whereIn('employee_id', $teamMemberIds);
        } elseif (!$user->isHr() && !$user->isAdmin() && !$user->isManager()) {
            // Standard employees see only their own team's calendar.
            if ($employee && $employee->team_id) {
                $teamMemberIds = Employee::where('team_id', $employee->team_id)->pluck('id');
                $query->whereIn('employee_id', $teamMemberIds);
            } else {
                $query->where('employee_id', $employee?->id ?? 0);
            }
        }

        if (($user->isHr() || $user->isManager()) && $request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }

        if (($user->isHr() || $user->isManager()) && $request->filled('team_id')) {
            $query->whereHas('employee', fn($q) => $q->where('team_id', $request->team_id));
        }

        if ($user->isHr() && $request->filled('employee_id') && ctype_digit((string) $request->input('employee_id'))) {
            $query->where('employee_id', (int) $request->input('employee_id'));
        }

        $applications = $query->get();

        $events = [];

        // Holidays
        $holidays = PublicHoliday::whereBetween('date', [$startOfMonth, $endOfMonth])->get();
        foreach ($holidays as $h) {
            $events[] = [
                'id' => 'holiday-' . $h->id,
                'title' => '🎉 ' . $h->name,
                'start' => $h->date->toDateString(),
                'end' => $h->date->toDateString(),
                'type' => 'holiday',
                'color' => '#1d9692',
                'allDay' => true,
            ];
        }

        foreach ($applications as $app) {
            $events[] = [
                'id' => 'leave-' . $app->id,
                'title' => $app->employee->full_name . ' (' . $app->leaveType->name . ')',
                'start' => $app->start_date->toDateString(),
                'end' => $app->end_date->toDateString(),
                'employee_name' => $app->employee->full_name,
                'leave_type' => $app->leaveType->name,
                'department' => $app->employee->department->name ?? 'N/A',
                'total_days' => $app->total_days,
                'is_half_day' => $app->is_half_day,
                'half_day_type' => $app->half_day_type,
                'url' => route('leave.show', $app->id),
                'color' => $app->leaveType->display_color,
            ];
        }

        return response()->json($events);
    }
}
