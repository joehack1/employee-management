<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveApplication;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $year = $request->input('year', Carbon::now()->year);
        $departmentId = $request->input('department_id');
        $leaveTypeId = $request->input('leave_type_id');
        $reportType = $request->input('report_type', 'utilization');

        $departments = Department::where('is_active', true)->get();
        $leaveTypes = LeaveType::all();

        // 1. Leave Applications query
        $appQuery = LeaveApplication::with(['employee.department', 'leaveType'])
            ->whereYear('start_date', $year);

        if ($departmentId) {
            $appQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($leaveTypeId) {
            $appQuery->where('leave_type_id', $leaveTypeId);
        }

        $applications = $appQuery->orderBy('start_date', 'desc')->get();

        // 2. Department aggregation
        $deptStats = Department::with(['employees.leaveApplications' => function($q) use ($year) {
            $q->where('status', 'approved')->whereYear('start_date', $year);
        }])->get()->map(function($dept) {
            $totalDays = 0;
            $approvedCount = 0;
            foreach ($dept->employees as $emp) {
                $approvedCount += $emp->leaveApplications->count();
                $totalDays += $emp->leaveApplications->sum('total_days');
            }
            return [
                'name' => $dept->name,
                'code' => $dept->code,
                'employee_count' => $dept->employees->count(),
                'application_count' => $approvedCount,
                'days_taken' => $totalDays,
            ];
        });

        // 3. Balances query
        $balQuery = LeaveBalance::with(['employee.department', 'leaveType'])
            ->where('year', $year);

        if ($departmentId) {
            $balQuery->whereHas('employee', fn($q) => $q->where('department_id', $departmentId));
        }

        if ($leaveTypeId) {
            $balQuery->where('leave_type_id', $leaveTypeId);
        }

        $balances = $balQuery->get();

        return view('reports.index', compact(
            'year',
            'reportType',
            'departments',
            'leaveTypes',
            'applications',
            'deptStats',
            'balances',
            'departmentId',
            'leaveTypeId'
        ));
    }

    /**
     * CSV Export for reports.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $year = $request->input('year', Carbon::now()->year);
        $type = $request->input('report_type', 'annual');

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"" . ($type === 'balances' ? 'annual_leave_balances' : 'annual_leave') . "_{$year}.csv\"",
        ];

        return response()->stream(function () use ($year, $type, $request) {
            $handle = fopen('php://output', 'w');

            if ($type === 'balances') {
                fputcsv($handle, ['Employee Number', 'Employee Name', 'Department', 'Leave Type', 'Year', 'Entitled', 'Carried Forward', 'Adjustments', 'Used', 'Pending', 'Available Balance']);

                $query = LeaveBalance::with(['employee.department', 'leaveType'])
                    ->where('year', $year)
                    ->whereHas('leaveType', fn($q) => $q->where('code', 'annual'));
                if ($request->filled('department_id')) {
                    $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
                }

                foreach ($query->cursor() as $b) {
                    fputcsv($handle, [
                        $b->employee->employee_number ?? '',
                        $b->employee->full_name ?? '',
                        $b->employee->department->name ?? 'N/A',
                        $b->leaveType->name ?? '',
                        $b->year,
                        $b->entitled_days,
                        $b->carried_forward_days,
                        $b->manual_adjustment_days,
                        $b->used_days,
                        $b->pending_days,
                        $b->available_days,
                    ]);
                }
            } else {
                // Applications export
                fputcsv($handle, ['Application #', 'Employee', 'Department', 'Leave Type', 'Start Date', 'End Date', 'Days', 'Half Day', 'Emergency', 'Status', 'Submitted At', 'Reason']);

                $query = LeaveApplication::with(['employee.department', 'leaveType'])
                    ->whereYear('start_date', $year)
                    ->whereHas('leaveType', fn($q) => $q->where('code', 'annual'));
                if ($request->filled('department_id')) {
                    $query->whereHas('employee', fn($q) => $q->where('department_id', $request->department_id));
                }

                foreach ($query->cursor() as $app) {
                    fputcsv($handle, [
                        $app->application_number,
                        $app->employee->full_name ?? '',
                        $app->employee->department->name ?? '',
                        $app->leaveType->name ?? '',
                        $app->start_date->format('Y-m-d'),
                        $app->end_date->format('Y-m-d'),
                        $app->total_days,
                        $app->is_half_day ? 'Yes (' . $app->half_day_type . ')' : 'No',
                        $app->is_emergency ? 'Yes' : 'No',
                        $app->status,
                        $app->submitted_at ? $app->submitted_at->format('Y-m-d H:i') : '',
                        $app->reason,
                    ]);
                }
            }

            fclose($handle);
        }, 200, $headers);
    }
}
