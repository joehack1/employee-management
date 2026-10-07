@extends('layouts.app')

@section('title', 'Leave Policies & Workflows - LeaveFlow')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{ openPolicyModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leave Policies & Workflows</h1>
            <p class="text-xs text-slate-500">Configure advance notice rules, team conflict limits, carry-forward policies, and approval chains</p>
        </div>
        <button @click="openPolicyModal = true" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs">
            + New Leave Policy
        </button>
    </div>

    <!-- Policies Cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($policies as $p)
            <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">{{ $p->name }}</h2>
                        <p class="text-xs text-slate-400">{{ $p->employees_count }} employees assigned</p>
                    </div>
                    @if($p->is_default)
                        <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-600 text-white">
                            Organization Default
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-2 gap-4 text-xs">
                    <!-- Rule 1: Advance Notice -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">3-Day Notice Rule</span>
                        <span class="text-base font-extrabold text-slate-900">{{ $p->min_days_advance_notice }} Days</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">Advance notice required for normal leave</p>
                    </div>

                    <!-- Rule 2: Max Teammates on Leave -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Team Conflict Limit</span>
                        <span class="text-base font-extrabold text-amber-600">Max {{ $p->max_team_on_leave }} staff</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">Simultaneous team members on leave</p>
                    </div>

                    <!-- Rule 3: Carry Forward -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Max Carry Forward</span>
                        <span class="text-base font-extrabold text-slate-900">{{ $p->max_carry_forward }} Days</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">Unused days carried into next year</p>
                    </div>

                    <!-- Rule 4: Accrual Type -->
                    <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                        <span class="text-slate-400 font-semibold block text-[10px] uppercase">Accrual Method</span>
                        <span class="text-base font-extrabold text-slate-900 capitalize">{{ $p->accrual_type }}</span>
                        <p class="text-[10px] text-slate-500 mt-0.5">
                            {{ $p->accrual_type === 'monthly' ? $p->monthly_accrual_rate . ' days/month' : 'All upfront on Jan 1' }}
                        </p>
                    </div>
                </div>

                <!-- Approval Workflow Routing (Req 7) -->
                <div class="p-4 rounded-2xl bg-blue-50/50 border border-blue-100 space-y-2 text-xs">
                    <span class="font-bold text-blue-900 block text-[11px] uppercase tracking-wider">Approval Routing Chain</span>
                    <div class="flex items-center gap-2 font-semibold text-slate-800">
                        <span class="px-2.5 py-1 rounded-lg bg-white border border-slate-200">Employee</span>
                        <span>&rarr;</span>
                        @if($p->approval_workflow === 'team_lead_then_hr')
                            <span class="px-2.5 py-1 rounded-lg bg-amber-600 text-white">Team Lead</span>
                            <span>&rarr;</span>
                        @elseif($p->approval_workflow === 'dept_head_then_hr')
                            <span class="px-2.5 py-1 rounded-lg bg-purple-600 text-white">Dept Head</span>
                            <span>&rarr;</span>
                        @endif
                        <span class="px-2.5 py-1 rounded-lg bg-blue-600 text-white shadow-xs">HR Final</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create Policy Modal -->
    <div x-show="openPolicyModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openPolicyModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Add New Leave Policy</h3>
            <form action="{{ route('policies.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Policy Name</label>
                    <input type="text" name="name" required placeholder="e.g. Sales Department Policy" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Annual Days Allowed</label>
                        <input type="number" step="0.5" name="annual_days" required value="21" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Advance Notice (Days)</label>
                        <input type="number" name="min_days_advance_notice" required value="3" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Max Carry Forward</label>
                        <input type="number" step="0.5" name="max_carry_forward" required value="5" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Max Team On Leave</label>
                        <input type="number" name="max_team_on_leave" required value="2" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Accrual Type</label>
                        <select name="accrual_type" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                            <option value="upfront">Upfront (Full at start)</option>
                            <option value="monthly">Monthly Accrual</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Monthly Accrual Rate</label>
                        <input type="number" step="0.01" name="monthly_accrual_rate" required value="1.75" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Leave Year Type</label>
                        <select name="leave_year_type" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                            <option value="calendar">Calendar Year (Jan - Dec)</option>
                            <option value="anniversary">Joining Anniversary</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Approval Workflow Routing</label>
                        <select name="approval_workflow" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                            <option value="team_lead_then_hr">Employee &rarr; Team Lead &rarr; HR</option>
                            <option value="direct_hr">Employee &rarr; Direct HR</option>
                            <option value="dept_head_then_hr">Employee &rarr; Dept Head &rarr; HR</option>
                        </select>
                    </div>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openPolicyModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">Save Policy</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
