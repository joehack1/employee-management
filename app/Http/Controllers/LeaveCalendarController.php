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

        $departments = Department::where('is_active', true)->get();
        $teams = Team::all();

        return view('calendar.index', compact('month', 'year', 'departments', 'teams', 'user', 'employee'));
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

        // Team Leads only see team unless HR/Admin
        if ($user->role === 'team_lead') {
            $teamMemberIds = Employee::where('team_lead_id', $user->id)
                ->orWhere(function ($q) use ($employee) {
                    if ($employee && $employee->team_id) {
                        $q->where('team_id', $employee->team_id);
                    }
                })
                ->pluck('id');
            $query->whereIn('employee_id', $teamMemberIds);
        } elseif (!$user->isHr() && !$user->isAdmin() && !$user->isManager()) {
            // Standard employee sees own team or own leaves
            if ($employee && $employee->team_id) {
                $teamMemberIds = Employee::where('team_id', $employee->team_id)->pluck('id');
                $query->whereIn('employee_id', $teamMemberIds);
            } else {
                $query->where('employee_id', $employee?->id ?? 0);
            }
        }

        if ($request->filled('department_id')) {
            $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
        }

        if ($request->filled('team_id')) {
            $query->whereHas('employee', fn($q) => $q->where('team_id', $request->team_id));
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
                'color' => match($app->leaveType->color) {
                    'blue', 'emerald', 'purple', 'cyan', 'indigo' => '#1d9692',
                    'amber', 'rose' => '#a01e22',
                    default => '#1d9692',
                },
            ];
        }

        return response()->json($events);
    }
}
