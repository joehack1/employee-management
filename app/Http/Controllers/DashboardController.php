<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $employee = $user->employee;

        if ($user->isManager()) {
            return $this->managerDashboard();
        }

        if ($user->isHr()) {
            return $this->hrDashboard();
        }

        if ($user->role === 'team_lead') {
            return $this->teamLeadDashboard($user, $employee);
        }

        return $this->employeeDashboard($user, $employee);
    }

    protected function managerDashboard()
    {
        $today = Carbon::today()->toDateString();
        $year = Carbon::now()->year;
        $totalEmployees = Employee::where('employment_status', 'active')->count();

        $onLeaveToday = LeaveApplication::with(['employee.department', 'employee.team', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->orderBy('start_date')
            ->get();

        $pendingRequests = LeaveApplication::with(['employee.department', 'employee.user', 'leaveType'])
            ->where('status', 'pending_manager')
            ->orderBy('is_emergency', 'desc')
            ->orderBy('created_at')
            ->get();

        $upcomingLeave = LeaveApplication::with(['employee.department', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', '>', $today)
            ->orderBy('start_date')
            ->take(12)
            ->get();

        $annualLeaveType = LeaveType::where('code', 'annual')->first();
        $annualDaysUsed = LeaveApplication::where('status', 'approved')
            ->where('leave_type_id', $annualLeaveType?->id)
            ->whereYear('start_date', $year)
            ->sum('total_days');

        $departments = Department::withCount('employees')->get();

        return view('dashboard.manager', compact(
            'totalEmployees', 'onLeaveToday', 'pendingRequests', 'upcomingLeave',
            'annualDaysUsed', 'departments', 'year'
        ));
    }

    protected function employeeDashboard($user, $employee)
    {
        $year = Carbon::now()->year;
        $today = Carbon::today()->toDateString();

        // Ensure balances exist
        if ($employee) {
            $leaveTypes = LeaveType::where('is_active', true)->get();
            $balances = LeaveBalance::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('year', $year)
                ->get();

            // Recent Applications
            $applications = LeaveApplication::with(['leaveType', 'approvals.approver'])
                ->where('employee_id', $employee->id)
                ->orderBy('created_at', 'desc')
                ->take(10)
                ->get();

            $pendingApplications = $applications->whereIn('status', ['pending_team_lead', 'pending_manager', 'pending_hr']);
            $approvedApplications = $applications->where('status', 'approved');
            $rejectedApplications = $applications->where('status', 'rejected');

            $upcomingLeave = LeaveApplication::with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('status', 'approved')
                ->where('start_date', '>=', $today)
                ->orderBy('start_date', 'asc')
                ->take(5)
                ->get();
        } else {
            $balances = collect();
            $applications = collect();
            $pendingApplications = collect();
            $approvedApplications = collect();
            $rejectedApplications = collect();
            $upcomingLeave = collect();
        }

        $notifications = $user->notifications()->take(5)->get();

        return view('dashboard.employee', compact(
            'user',
            'employee',
            'balances',
            'applications',
            'pendingApplications',
            'approvedApplications',
            'rejectedApplications',
            'upcomingLeave',
            'notifications'
        ));
    }

    protected function teamLeadDashboard($user, $employee)
    {
        $today = Carbon::today()->toDateString();

        // Get team members assigned to this lead
        $teamMembers = Employee::with(['user', 'department', 'team'])
            ->where(function ($q) use ($user, $employee) {
                $q->where('team_lead_id', $user->id);
                if ($employee && $employee->team_id) {
                    $q->orWhere('team_id', $employee->team_id);
                }
            })
            ->where('employment_status', 'active')
            ->get();

        $teamMemberIds = $teamMembers->pluck('id')->toArray();

        // Pending requests for team
        $pendingRequests = LeaveApplication::with(['employee.user', 'leaveType'])
            ->whereIn('employee_id', $teamMemberIds)
            ->where('status', 'pending_team_lead')
            ->orderBy('created_at', 'asc')
            ->get();

        // Team members on leave today
        $onLeaveToday = LeaveApplication::with(['employee.user', 'leaveType'])
            ->whereIn('employee_id', $teamMemberIds)
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();

        // Upcoming team leave
        $upcomingTeamLeave = LeaveApplication::with(['employee.user', 'leaveType'])
            ->whereIn('employee_id', $teamMemberIds)
            ->where('status', 'approved')
            ->where('start_date', '>', $today)
            ->orderBy('start_date', 'asc')
            ->take(8)
            ->get();

        return view('dashboard.team_lead', compact(
            'user',
            'employee',
            'teamMembers',
            'pendingRequests',
            'onLeaveToday',
            'upcomingTeamLeave'
        ));
    }

    protected function hrDashboard()
    {
        $today = Carbon::today()->toDateString();
        $tomorrow = Carbon::tomorrow()->toDateString();
        $year = Carbon::now()->year;

        // Statistics
        $totalEmployees = Employee::where('employment_status', 'active')->count();

        $onLeaveToday = LeaveApplication::with(['employee.department', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->get();

        $returningToday = LeaveApplication::with(['employee.department', 'leaveType'])
            ->where('status', 'approved')
            ->where('end_date', $today)
            ->get();

        $pendingApprovals = LeaveApplication::with(['employee.department', 'leaveType', 'approvals.approver'])
            ->where('status', 'pending_hr')
            ->orderBy('is_emergency', 'desc')
            ->orderBy('created_at', 'asc')
            ->get();

        $emergencyRequests = LeaveApplication::with(['employee.department', 'leaveType'])
            ->where('is_emergency', true)
            ->where('status', 'pending_hr')
            ->get();

        // Leave days used
        $annualType = LeaveType::where('code', 'annual')->first();
        $sickType = LeaveType::where('code', 'sick')->first();

        $annualDaysUsed = LeaveApplication::where('status', 'approved')
            ->where('leave_type_id', $annualType?->id)
            ->whereYear('start_date', $year)
            ->sum('total_days');

        $sickDaysUsed = LeaveApplication::where('status', 'approved')
            ->where('leave_type_id', $sickType?->id)
            ->whereYear('start_date', $year)
            ->sum('total_days');

        // Low leave balances (< 3 days remaining)
        $lowBalances = LeaveBalance::with(['employee.department', 'leaveType'])
            ->where('leave_type_id', $annualType?->id)
            ->where('year', $year)
            ->get()
            ->filter(fn($b) => $b->available_days <= 3.0)
            ->take(6);

        // Upcoming leave
        $upcomingLeave = LeaveApplication::with(['employee.department', 'leaveType'])
            ->where('status', 'approved')
            ->where('start_date', '>', $today)
            ->orderBy('start_date', 'asc')
            ->take(6)
            ->get();

        // Monthly trends data for chart
        $monthlyTrends = [];
        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        for ($m = 1; $m <= 12; $m++) {
            $annualCount = LeaveApplication::where('status', 'approved')
                ->where('leave_type_id', $annualType?->id)
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $m)
                ->sum('total_days');

            $sickCount = LeaveApplication::where('status', 'approved')
                ->where('leave_type_id', $sickType?->id)
                ->whereYear('start_date', $year)
                ->whereMonth('start_date', $m)
                ->sum('total_days');

            $monthlyTrends[] = [
                'month' => $months[$m - 1],
                'annual' => (float) $annualCount,
                'sick' => (float) $sickCount,
                'total' => (float) ($annualCount + $sickCount),
            ];
        }

        // Departments breakdown
        $departments = Department::withCount('employees')->get();

        return view('dashboard.hr', compact(
            'totalEmployees',
            'onLeaveToday',
            'returningToday',
            'pendingApprovals',
            'emergencyRequests',
            'annualDaysUsed',
            'sickDaysUsed',
            'lowBalances',
            'upcomingLeave',
            'monthlyTrends',
            'departments'
        ));
    }
}
