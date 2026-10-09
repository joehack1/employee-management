@extends('layouts.app')

@section('title', 'Manager Dashboard - LeaveFlow')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="dashboardGreeting(@json(auth()->id()))">
    @include('dashboard.greeting')
    <div class="dashboard-hero bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <p class="text-xs font-bold uppercase tracking-wider text-blue-700">Management overview</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">Company leave dashboard</h1>
            <p class="mt-1 text-sm text-slate-500">Review HR and team lead leave, and see staff availability across the company.</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('calendar.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-700 text-xs font-semibold hover:bg-slate-50">Company calendar</a>
            <a href="{{ route('reports.index') }}" class="px-4 py-2 rounded-xl bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700">Reports & analytics</a>
        </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">Active employees</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $totalEmployees }}</p></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">On leave today</p><p class="mt-2 text-3xl font-bold text-teal-700">{{ $onLeaveToday->count() }}</p></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">Awaiting manager review</p><p class="mt-2 text-3xl font-bold text-rose-700">{{ $pendingRequests->count() }}</p></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">Annual leave used in {{ $year }}</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($annualDaysUsed, 1) }} <span class="text-base font-medium text-slate-500">days</span></p></div>
    </div>

    <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100"><h2 class="font-bold text-slate-900">Your team’s annual leave balances</h2><p class="text-xs text-slate-500 mt-1">Available annual leave for {{ $year }}</p></div>
        <div class="overflow-x-auto"><table class="min-w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Employee</th><th class="px-6 py-3">Employee number</th><th class="px-6 py-3 text-right">Remaining annual leave</th></tr></thead><tbody class="divide-y divide-slate-100">
            @forelse($managerTeamMembers as $member)
                <tr><td class="px-6 py-4 font-semibold text-slate-900">{{ $member->full_name }}</td><td class="px-6 py-4 text-slate-600">{{ $member->employee_number }}</td><td class="px-6 py-4 text-right font-bold text-blue-700">{{ number_format((float) ($member->balances->first()?->available_days ?? 0), 1) }} days</td></tr>
            @empty
                <tr><td colspan="3" class="px-6 py-8 text-center text-slate-400">No active employees are assigned to you.</td></tr>
            @endforelse
        </tbody></table></div>
    </section>

    <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div><h2 class="font-bold text-slate-900">Staff currently on leave</h2><p class="text-xs text-slate-500 mt-1">Approved leave covering today</p></div>
            <span class="px-3 py-1 rounded-full bg-teal-600 text-white text-xs font-semibold">{{ $onLeaveToday->count() }} away</span>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500"><tr><th class="px-6 py-3">Employee</th><th class="px-6 py-3">Department</th><th class="px-6 py-3">Leave type</th><th class="px-6 py-3">Dates</th><th class="px-6 py-3 text-right">Duration</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($onLeaveToday as $leave)
                        <tr><td class="px-6 py-4 font-semibold text-slate-900">{{ $leave->employee->full_name }}</td><td class="px-6 py-4 text-slate-600">{{ $leave->employee->department->name ?? '—' }}</td><td class="px-6 py-4 text-slate-600">{{ $leave->leaveType->name }}</td><td class="px-6 py-4 text-slate-600">{{ $leave->start_date->format('d M Y') }} – {{ $leave->end_date->format('d M Y') }}</td><td class="px-6 py-4 text-right font-bold text-slate-900">{{ $leave->total_days }} days</td></tr>
                    @empty
                        <tr><td colspan="5" class="px-6 py-10 text-center text-sm text-slate-400">No one is on leave today.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between gap-4">
            <div>
                <h2 class="font-bold text-slate-900">Pending Manager Leave Requests</h2>
                <p class="text-xs text-slate-500 mt-1">Review requests assigned to you and record your decision.</p>
            </div>
            <a href="{{ route('approvals.pending') }}" class="shrink-0 text-xs font-semibold text-teal-700 hover:text-teal-900">Open approval queue</a>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Leave Type</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3 text-center">Days</th>
                        <th class="px-4 py-3">Reason</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($pendingRequests as $leave)
                        <tr class="hover:bg-slate-50 transition" x-data="{ openApproveModal: false, openRejectModal: false }">
                            <td class="px-4 py-3.5">
                                <p class="font-bold text-slate-900">{{ $leave->employee->full_name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $leave->employee->job_title }} · {{ $leave->employee->employee_number }}</p>
                                <p class="text-[11px] text-slate-500">{{ str_replace('_', ' ', $leave->employee->user->role ?? 'employee') }}</p>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-slate-800">{{ $leave->leaveType->name }}</span>
                                @if($leave->is_emergency)
                                    <span class="block text-[10px] text-amber-600 font-bold">🚨 Emergency Request</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }}</td>
                            <td class="px-4 py-3.5 text-center font-bold text-slate-900">{{ $leave->total_days }}</td>
                            <td class="px-4 py-3.5 text-xs text-slate-600 max-w-xs truncate" title="{{ $leave->reason }}">{{ $leave->reason ?: '—' }}</td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $leave->status_badge['class'] }}">{{ $leave->status_badge['label'] }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-right whitespace-nowrap space-x-1">
                                <button type="button" @click="openApproveModal = true" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold">Approve</button>
                                <button type="button" @click="openRejectModal = true" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold">Reject</button>
                                <a href="{{ route('leave.show', $leave->id) }}" class="px-2 py-1.5 rounded-lg text-slate-500 hover:bg-slate-100 text-xs">Details</a>

                                <template x-teleport="body">
                                <div x-show="openApproveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="openApproveModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-bold text-slate-900">Approve Leave Request</h3>
                                        <p class="text-xs text-slate-500 mt-1">Approving {{ $leave->employee->full_name }}’s {{ $leave->leaveType->name }} for {{ $leave->total_days }} days.</p>
                                        <form action="{{ route('approvals.managerApprove', $leave->id) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            <input type="hidden" name="return_to" value="dashboard">
                                            <label class="block text-xs font-semibold text-slate-700">Approval Comment (Optional)</label>
                                            <textarea name="comment" rows="3" placeholder="Add an optional comment" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button type="button" @click="openApproveModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 text-white">Confirm Approval</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                </template>

                                <template x-teleport="body">
                                <div x-show="openRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="openRejectModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-bold text-rose-600">Reject Leave Request</h3>
                                        <p class="text-xs text-slate-500 mt-1">A clear reason will be sent to the employee and HR.</p>
                                        <form action="{{ route('approvals.managerReject', $leave->id) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            <input type="hidden" name="return_to" value="dashboard">
                                            <label class="block text-xs font-semibold text-slate-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                                            <textarea name="rejection_reason" required minlength="5" maxlength="500" rows="3" placeholder="Provide a reason for rejection" class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-xl text-xs"></textarea>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button type="button" @click="openRejectModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 text-white">Reject Leave</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                </template>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="px-4 py-8 text-center text-slate-400 text-xs">No pending leave requests are assigned to you.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100"><h2 class="font-bold text-slate-900">Upcoming approved leave</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse($upcomingLeave as $leave)
                    <div class="px-6 py-4 flex items-center justify-between gap-4"><div><p class="text-sm font-semibold text-slate-900">{{ $leave->employee->full_name }}</p><p class="mt-1 text-xs text-slate-500">{{ $leave->leaveType->name }} · {{ $leave->total_days }} days</p></div><p class="text-right text-xs text-slate-600">{{ $leave->start_date->format('d M') }} – {{ $leave->end_date->format('d M Y') }}</p></div>
                @empty
                    <p class="p-8 text-center text-sm text-slate-400">No upcoming leave is scheduled.</p>
                @endforelse
            </div>
        </section>
        <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100"><h2 class="font-bold text-slate-900">Departments</h2><p class="text-xs text-slate-500 mt-1">Active and total staff by department</p></div>
            <div class="divide-y divide-slate-100">
                @foreach($departments as $department)
                    <a href="{{ route('manager.departments.show', $department->id) }}" class="px-6 py-4 flex items-center justify-between gap-4 hover:bg-slate-50 transition group">
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-teal-700">{{ $department->name }}</span>
                        <span class="flex items-center gap-3">
                            <span class="text-xs text-slate-500">{{ $department->active_employees_count }} active / {{ $department->employees_count }} total</span>
                            <svg class="h-4 w-4 text-slate-400 group-hover:text-teal-700" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m9 5 7 7-7 7"/></svg>
                        </span>
                    </a>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
