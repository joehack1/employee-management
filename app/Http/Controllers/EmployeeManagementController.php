<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeavePolicy;
use App\Models\LeaveType;
use App\Models\User;
use App\Notifications\EmployeeAssignmentNotification;
use App\Notifications\EmployeeWelcomeNotification;
use App\Services\LeaveLedgerService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class EmployeeManagementController extends Controller
{
    public function __construct(
        protected LeaveLedgerService $ledgerService
    ) {}

    public function index(Request $request)
    {
        $query = Employee::with(['user', 'department', 'team', 'teamLead', 'leavePolicy']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('first_name', 'like', "%{$s}%")
                  ->orWhere('last_name', 'like', "%{$s}%")
                  ->orWhere('employee_number', 'like', "%{$s}%")
                  ->orWhere('email', 'like', "%{$s}%");
            });
        }

        if ($request->filled('department_id')) {
            $query->where('department_id', $request->department_id);
        }

        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }

        $employees = $query->orderBy('first_name')->paginate(15);
        $departments = Department::where('is_active', true)->get();

        return view('employees.index', compact('employees', 'departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $teamLeads = User::whereIn('role', ['team_lead', 'hr', 'administrator', 'manager'])->get();
        $policies = LeavePolicy::all();

        return view('employees.create', compact('departments', 'teamLeads', 'policies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'employee_number' => ['required', 'string', 'unique:employees,employee_number', 'unique:users,employee_number'],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'unique:users,email', 'unique:employees,email'],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'job_title' => ['required', 'string', 'max:100'],
            'team_lead_id' => ['nullable', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'date_employed' => ['required', 'date', 'before_or_equal:today'],
            'leave_policy_id' => ['nullable', 'exists:leave_policies,id'],
            'annual_entitlement' => ['required', 'numeric', 'min:0', 'max:365'],
            'role' => ['required', 'in:employee,team_lead,hr,manager,administrator'],
        ]);

        $generatedPassword = Str::password(14);
        $validated['annual_entitlement'] = (new Employee(['date_employed' => $validated['date_employed']]))->annualLeaveEntitlement();

        $employee = DB::transaction(function () use ($validated, $generatedPassword) {
            $user = User::create([
                'name' => "{$validated['first_name']} {$validated['last_name']}",
                'email' => $validated['email'],
                'role' => $validated['role'],
                'employee_number' => $validated['employee_number'],
                'phone' => $validated['phone'],
                'password' => Hash::make($generatedPassword),
                'is_active' => true,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_number' => $validated['employee_number'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'gender' => $validated['gender'],
                'department_id' => $validated['department_id'],
                'team_id' => $validated['team_id'] ?? null,
                'job_title' => $validated['job_title'],
                'team_lead_id' => $validated['team_lead_id'],
                'manager_id' => $validated['manager_id'],
                'date_employed' => $validated['date_employed'],
                'employment_status' => 'active',
                'leave_policy_id' => $validated['leave_policy_id'],
                'annual_entitlement' => $validated['annual_entitlement'],
            ]);

            // Initialize balances
            $year = Carbon::now()->year;
            $leaveTypes = LeaveType::where('is_active', true)->get();
            foreach ($leaveTypes as $lt) {
                $days = $lt->code === 'annual' ? (float) $employee->annual_entitlement : $lt->days_allowed;
                $balance = $this->ledgerService->getOrCreateBalance($employee, $lt, $year);
                $balance->entitled_days = $days;
                $balance->save();

                $employee->transactions()->create([
                    'leave_type_id' => $lt->id,
                    'type' => 'initial_entitlement',
                    'amount' => $days,
                    'running_balance' => $days,
                    'reason' => "Initial entitlement for new employee",
                    'created_by' => Auth::id(),
                ]);
            }

            AuditLog::log('employee_created', 'Employee', $employee->id, "Employee {$employee->full_name} created by HR");

            return $employee;
        });

        $this->notifyAssignedManagersAndTeamLeads(
            [$validated['manager_id'] ?? null, $validated['team_lead_id'] ?? null],
            $employee
        );

        $employee->user->notify(new EmployeeWelcomeNotification($generatedPassword));

        return redirect()->route('employees.index')->with('success', 'Employee created successfully.');
    }

    public function edit($id)
    {
        $employee = Employee::with('user')->findOrFail($id);
        $departments = Department::where('is_active', true)->get();
        $teamLeads = User::whereIn('role', ['team_lead', 'hr', 'administrator', 'manager'])->get();
        $policies = LeavePolicy::all();

        return view('employees.edit', compact('employee', 'departments', 'teamLeads', 'policies'));
    }

    public function update(Request $request, $id)
    {
        $employee = Employee::findOrFail($id);
        $user = $employee->user;
        $previousManagerId = $employee->manager_id;
        $previousTeamLeadId = $employee->team_lead_id;

        $validated = $request->validate([
            'employee_number' => ['required', 'string', Rule::unique('employees')->ignore($employee->id)],
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'gender' => ['required', 'in:male,female'],
            'department_id' => ['nullable', 'exists:departments,id'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'job_title' => ['required', 'string', 'max:100'],
            'team_lead_id' => ['nullable', 'exists:users,id'],
            'manager_id' => ['nullable', 'exists:users,id'],
            'date_employed' => ['required', 'date', 'before_or_equal:today'],
            'leave_policy_id' => ['nullable', 'exists:leave_policies,id'],
            'annual_entitlement' => ['required', 'numeric', 'min:0', 'max:365'],
            'role' => ['required', 'in:employee,team_lead,hr,manager,administrator'],
            'employment_status' => ['required', 'in:active,probation,deactivated'],
        ]);

        DB::transaction(function () use ($employee, $user, $validated) {
            $user->update([
                'name' => "{$validated['first_name']} {$validated['last_name']}",
                'email' => $validated['email'],
                'role' => $validated['role'],
                'employee_number' => $validated['employee_number'],
                'phone' => $validated['phone'],
                'is_active' => $validated['employment_status'] !== 'deactivated',
            ]);

            $employee->update($validated);
            $employee->annual_entitlement = $employee->annualLeaveEntitlement();
            $employee->save();

            AuditLog::log('employee_updated', 'Employee', $employee->id, "Employee {$employee->full_name} updated by HR");
        });

        $newAssigneeIds = [];
        if ((int) $previousManagerId !== (int) ($validated['manager_id'] ?? 0)) {
            $newAssigneeIds[] = $validated['manager_id'] ?? null;
        }
        if ((int) $previousTeamLeadId !== (int) ($validated['team_lead_id'] ?? 0)) {
            $newAssigneeIds[] = $validated['team_lead_id'] ?? null;
        }

        if ($newAssigneeIds) {
            $this->notifyAssignedManagersAndTeamLeads($newAssigneeIds, $employee);
        }

        return redirect()->route('employees.index')->with('success', 'Employee updated successfully.');
    }

    public function toggleStatus($id)
    {
        $employee = Employee::findOrFail($id);
        $user = $employee->user;

        if ($employee->employment_status === 'active') {
            $employee->employment_status = 'deactivated';
            if ($user) $user->is_active = false;
            $msg = "Employee {$employee->full_name} deactivated.";
        } else {
            $employee->employment_status = 'active';
            if ($user) $user->is_active = true;
            $msg = "Employee {$employee->full_name} reactivated.";
        }

        $employee->save();
        if ($user) $user->save();

        AuditLog::log('employee_status_toggled', 'Employee', $employee->id, $msg);

        return back()->with('success', $msg);
    }

    public function resetPassword(Request $request, $id)
    {
        $request->validate(['password' => 'required|string|min:6|confirmed']);
        $employee = Employee::findOrFail($id);
        $user = $employee->user;

        if ($user) {
            $user->password = Hash::make($request->password);
            $user->save();
            AuditLog::log('password_reset_by_admin', 'User', $user->id, "Password reset for {$employee->full_name}");
        }

        return back()->with('success', "Password successfully reset for {$employee->full_name}.");
    }

    /**
     * Requirement 6: HR Leave Account, Balances & Transactions Ledger
     */
    public function leaveAccount($id)
    {
        $employee = Employee::with(['department', 'team', 'leavePolicy', 'user'])->findOrFail($id);
        $year = Carbon::now()->year;

        $allowedLeaveTypes = $employee->leaveTypesForGender(LeaveType::where('is_active', true)->get());
        $allowedLeaveTypeIds = $allowedLeaveTypes->pluck('id');
        $balances = LeaveBalance::with('leaveType')
            ->where('employee_id', $employee->id)
            ->whereIn('leave_type_id', $allowedLeaveTypeIds)
            ->where('year', $year)
            ->get();

        $transactions = $employee->transactions()
            ->with(['leaveType', 'creator'])
            ->whereIn('leave_type_id', $allowedLeaveTypeIds)
            ->whereYear('created_at', $year)
            ->orderBy('id', 'desc')
            ->paginate(20);

        $leaveTypes = $allowedLeaveTypes;

        return view('employees.leave_account', compact('employee', 'balances', 'transactions', 'leaveTypes', 'year'));
    }

    /**
     * Requirement 6: HR Adjustment with Ledger Entry
     */
    public function adjustBalance(Request $request, $id)
    {
        $request->validate([
            'leave_type_id' => ['required', 'exists:leave_types,id'],
            'adjustment_amount' => ['required', 'numeric', 'not_in:0'], // +2 or -1
            'reason' => ['required', 'string', 'min:5', 'max:500'],
        ]);

        $employee = Employee::findOrFail($id);
        $leaveType = LeaveType::findOrFail($request->leave_type_id);
        abort_unless($employee->leaveTypesForGender(collect([$leaveType]))->isNotEmpty(), 403, 'This leave type is not available for this employee.');

        try {
            $this->ledgerService->adjustBalance(
                employee: $employee,
                leaveType: $leaveType,
                adjustmentAmount: (float) $request->adjustment_amount,
                reason: $request->reason,
                hrUserId: Auth::id()
            );

            return back()->with('success', sprintf(
                'Leave balance adjusted successfully: %s%.2f days applied to %s for %s.',
                $request->adjustment_amount > 0 ? '+' : '',
                $request->adjustment_amount,
                $leaveType->name,
                $employee->full_name
            ));
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function notifyAssignedManagersAndTeamLeads(array $userIds, Employee $employee): void
    {
        $recipients = User::whereIn('id', array_filter($userIds))
            ->where('is_active', true)
            ->get()
            ->unique('id');

        if ($recipients->isNotEmpty()) {
            Notification::send($recipients, new EmployeeAssignmentNotification($employee));
        }
    }
}
