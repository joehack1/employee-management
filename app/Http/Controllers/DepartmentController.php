<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\Request;

class DepartmentController extends Controller
{
    public function showForManager($id)
    {
        $department = Department::with([
            'teams.leader',
            'teams.employees',
            'employees.team',
            'employees.manager',
            'employees.teamLead',
        ])->withCount('employees')->findOrFail($id);

        $activeEmployeesCount = $department->employees
            ->where('employment_status', 'active')
            ->count();

        return view('departments.manager-show', compact('department', 'activeEmployeesCount'));
    }

    public function index()
    {
        $departments = Department::with(['teams.leader', 'parent', 'employees'])->get();
        $teamLeads = User::whereIn('role', ['team_lead', 'hr', 'administrator'])->get();

        return view('departments.index', compact('departments', 'teamLeads'));
    }

    public function storeDepartment(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:departments,code',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
        ]);

        $dept = Department::create($validated);
        AuditLog::log('department_created', 'Department', $dept->id, "Created department {$dept->name} ({$dept->code})");

        return back()->with('success', 'Department created successfully.');
    }

    public function storeTeam(Request $request)
    {
        $validated = $request->validate([
            'department_id' => 'required|exists:departments,id',
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
            'leader_id' => 'nullable|exists:users,id',
        ]);

        $team = Team::create($validated);
        AuditLog::log('team_created', 'Team', $team->id, "Created team {$team->name}");

        return back()->with('success', 'Team created successfully.');
    }

    public function toggleDepartment($id)
    {
        $dept = Department::findOrFail($id);
        $dept->is_active = !$dept->is_active;
        $dept->save();

        AuditLog::log('department_toggled', 'Department', $dept->id, "Toggled status of department {$dept->name}");

        return back()->with('success', "Department status updated.");
    }
}
