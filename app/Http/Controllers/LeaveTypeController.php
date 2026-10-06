<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeaveBalance;
use App\Models\LeaveTransaction;
use App\Models\LeaveType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaveTypeController extends Controller
{
    public function index()
    {
        $leaveTypes = LeaveType::withCount('applications')->get();
        return view('leave_types.index', compact('leaveTypes'));
    }

    public function edit($id)
    {
        $leaveType = LeaveType::findOrFail($id);

        return view('leave_types.edit', compact('leaveType'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:30|unique:leave_types,code',
            'description' => 'nullable|string',
            'days_allowed' => 'required|numeric|min:0|max:365',
            'is_paid' => 'required|boolean',
            'requires_attachment' => 'required|boolean',
            'attachment_required_after_days' => 'nullable|integer|min:0',
            'is_emergency_type' => 'required|boolean',
            'color' => 'required|string|max:20',
        ]);

        $lt = LeaveType::create($validated);
        AuditLog::log('leave_type_created', 'LeaveType', $lt->id, "Created leave type {$lt->name}");

        return back()->with('success', "Leave type {$lt->name} created successfully.");
    }

    public function update(Request $request, $id)
    {
        $leaveType = LeaveType::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'code' => ['required', 'string', 'max:30', Rule::unique('leave_types')->ignore($leaveType->id)],
            'description' => 'nullable|string',
            'days_allowed' => 'required|numeric|min:0|max:365',
            'is_paid' => 'required|boolean',
            'requires_attachment' => 'required|boolean',
            'attachment_required_after_days' => 'nullable|integer|min:0',
            'is_emergency_type' => 'required|boolean',
            'color' => 'required|string|max:20',
        ]);

        $leaveType->update($validated);
        AuditLog::log('leave_type_updated', 'LeaveType', $leaveType->id, "Updated leave type {$leaveType->name}");

        return back()->with('success', "Leave type {$leaveType->name} updated successfully.");
    }

    public function toggle($id)
    {
        $leaveType = LeaveType::findOrFail($id);
        $leaveType->is_active = !$leaveType->is_active;
        $leaveType->save();

        AuditLog::log('leave_type_toggled', 'LeaveType', $leaveType->id, "Toggled status for {$leaveType->name}");

        return back()->with('success', "Status for {$leaveType->name} updated.");
    }

    public function destroy($id)
    {
        $leaveType = LeaveType::findOrFail($id);

        $hasHistory = $leaveType->applications()->exists()
            || LeaveBalance::where('leave_type_id', $leaveType->id)->exists()
            || LeaveTransaction::where('leave_type_id', $leaveType->id)->exists();

        if ($hasHistory) {
            return back()->with('error', 'This leave type has leave history or balances and cannot be deleted. Disable it instead to keep those records.');
        }

        $name = $leaveType->name;
        $leaveType->delete();
        AuditLog::log('leave_type_deleted', 'LeaveType', $id, "Deleted leave type {$name}");

        return back()->with('success', "Leave type {$name} deleted.");
    }
}
