<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\LeavePolicy;
use Illuminate\Http\Request;

class LeavePolicyController extends Controller
{
    public function index()
    {
        $policies = LeavePolicy::withCount('employees')->get();
        return view('policies.index', compact('policies'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'annual_days' => 'required|numeric|min:0|max:365',
            'accrual_type' => 'required|in:upfront,monthly',
            'monthly_accrual_rate' => 'required|numeric|min:0',
            'max_carry_forward' => 'required|numeric|min:0',
            'leave_year_type' => 'required|in:calendar,anniversary',
            'min_days_advance_notice' => 'required|integer|min:0',
            'max_team_on_leave' => 'required|integer|min:1',
            'approval_workflow' => 'required|in:team_lead_then_hr,direct_hr,dept_head_then_hr',
        ]);

        $policy = LeavePolicy::create($validated);
        AuditLog::log('policy_created', 'LeavePolicy', $policy->id, "Created policy {$policy->name}");

        return back()->with('success', "Policy {$policy->name} created successfully.");
    }

    public function update(Request $request, $id)
    {
        $policy = LeavePolicy::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'annual_days' => 'required|numeric|min:0|max:365',
            'accrual_type' => 'required|in:upfront,monthly',
            'monthly_accrual_rate' => 'required|numeric|min:0',
            'max_carry_forward' => 'required|numeric|min:0',
            'leave_year_type' => 'required|in:calendar,anniversary',
            'min_days_advance_notice' => 'required|integer|min:0',
            'max_team_on_leave' => 'required|integer|min:1',
            'approval_workflow' => 'required|in:team_lead_then_hr,direct_hr,dept_head_then_hr',
        ]);

        $policy->update($validated);
        AuditLog::log('policy_updated', 'LeavePolicy', $policy->id, "Updated policy {$policy->name}");

        return back()->with('success', "Policy {$policy->name} updated successfully.");
    }
}
