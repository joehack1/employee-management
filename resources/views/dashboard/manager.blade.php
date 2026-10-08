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
        <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
            <div><h2 class="font-bold text-slate-900">HR and team lead leave requests</h2><p class="text-xs text-slate-500 mt-1">These requests need your decision</p></div>
            <a href="{{ route('approvals.pending') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">Open approval queue</a>
        </div>
        <div class="divide-y divide-slate-100">
            @forelse($pendingRequests as $leave)
                <div class="p-5 flex flex-col lg:flex-row lg:items-center justify-between gap-4" x-data="{ approve: false, reject: false }">
                    <div><p class="font-semibold text-slate-900">{{ $leave->employee->full_name }} <span class="font-normal text-slate-500">({{ str_replace('_', ' ', $leave->employee->user->role ?? 'staff') }})</span></p><p class="mt-1 text-xs text-slate-500">{{ $leave->leaveType->name }} · {{ $leave->start_date->format('d M Y') }} – {{ $leave->end_date->format('d M Y') }} · {{ $leave->total_days }} days</p><p class="mt-1 text-xs text-slate-500">{{ $leave->reason }}</p></div>
                    <div class="flex items-center gap-2 shrink-0">
                        <button @click="approve = true" class="px-3 py-2 rounded-lg bg-teal-600 text-white text-xs font-semibold hover:bg-teal-700">Approve</button>
                        <button @click="reject = true" class="px-3 py-2 rounded-lg bg-rose-700 text-white text-xs font-semibold hover:bg-rose-800">Reject</button>
                        <a href="{{ route('leave.show', $leave->id) }}" class="px-3 py-2 rounded-lg border border-slate-200 text-xs font-semibold text-slate-600">Details</a>
                    </div>
                    <div x-show="approve" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"><div @click.outside="approve = false" class="bg-white rounded-2xl max-w-md w-full p-6"><h3 class="font-bold text-slate-900">Approve leave request</h3><form class="mt-4 space-y-3" method="POST" action="{{ route('approvals.managerApprove', $leave->id) }}">@csrf<textarea name="comment" rows="3" class="w-full rounded-xl border border-slate-300 p-3 text-sm" placeholder="Optional comment"></textarea><div class="flex justify-end gap-2"><button type="button" @click="approve = false" class="px-4 py-2 text-sm text-slate-600">Cancel</button><button class="px-4 py-2 rounded-lg bg-teal-600 text-white text-sm font-semibold">Confirm approval</button></div></form></div></div>
                    <div x-show="reject" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4"><div @click.outside="reject = false" class="bg-white rounded-2xl max-w-md w-full p-6"><h3 class="font-bold text-slate-900">Reject leave request</h3><form class="mt-4 space-y-3" method="POST" action="{{ route('approvals.managerReject', $leave->id) }}">@csrf<textarea name="rejection_reason" required minlength="5" rows="3" class="w-full rounded-xl border border-slate-300 p-3 text-sm" placeholder="Reason for rejection"></textarea><div class="flex justify-end gap-2"><button type="button" @click="reject = false" class="px-4 py-2 text-sm text-slate-600">Cancel</button><button class="px-4 py-2 rounded-lg bg-rose-700 text-white text-sm font-semibold">Confirm rejection</button></div></form></div></div>
                </div>
            @empty
                <p class="p-8 text-center text-sm text-slate-400">No requests are waiting for manager approval.</p>
            @endforelse
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
                    <div class="px-6 py-4 flex items-center justify-between"><span class="text-sm font-medium text-slate-700">{{ $department->name }}</span><span class="px-2.5 py-1 rounded-full bg-teal-600 text-white text-xs font-semibold">{{ $department->employees_count }} staff</span></div>
                @endforeach
            </div>
        </section>
    </div>
</div>
@endsection
