@extends('layouts.app')

@section('title', 'HR Executive Dashboard - LeaveFlow')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Top Header -->
    <div class="dashboard-hero rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">HR Executive Dashboard</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-600 text-white">
                    Administrator
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Real-time organizational leave metrics, approval queues, and balance control</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <!-- Manual Trigger for Reminders -->
            <form action="{{ route('admin.reminders.run') }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold flex items-center gap-1.5 shadow-xs transition">
                    <svg class="w-3.5 h-3.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Run Reminders Scan
                </button>
            </form>
            <a href="{{ route('employees.create') }}" class="px-4 py-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Add Employee
            </a>
        </div>
    </div>

    <!-- Requirement 9: Executive Statistics Metrics Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-4">
        <!-- Metric 1: Total Employees -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Active Staff</p>
            <p class="text-2xl font-extrabold text-slate-900 mt-1">{{ $totalEmployees }}</p>
            <span class="text-[10px] text-emerald-600 font-semibold mt-1 inline-block">100% headcount</span>
        </div>

        <!-- Metric 2: On Leave Today -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">On Leave Today</p>
            <p class="text-2xl font-extrabold text-rose-600 mt-1">{{ $onLeaveToday->count() }}</p>
            <span class="text-[10px] text-slate-400 mt-1 inline-block">currently away</span>
        </div>

        <!-- Metric 3: Returning Today -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Returning Today</p>
            <p class="text-2xl font-extrabold text-emerald-600 mt-1">{{ $returningToday->count() }}</p>
            <span class="text-[10px] text-emerald-600 mt-1 inline-block">resuming work</span>
        </div>

        <!-- Metric 4: HR actions -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Manager Leave / Cancellations</p>
            <p class="text-2xl font-extrabold text-amber-500 mt-1">{{ $pendingApprovals->count() }}</p>
            <a href="{{ route('approvals.pending') }}" class="text-[10px] text-amber-600 font-bold hover:underline mt-1 inline-block">Review actions &rarr;</a>
        </div>

        <!-- Metric 5: Annual Leave Used -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Annual Leave Used</p>
            <p class="text-2xl font-extrabold text-blue-700 mt-1">{{ number_format($annualDaysUsed, 1) }}</p>
            <span class="text-[10px] text-slate-400 mt-1 inline-block">days YTD</span>
        </div>

        <!-- Metric 6: Sick Leave Used -->
        <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs">
            <p class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sick Leave Used</p>
            <p class="text-2xl font-extrabold text-purple-600 mt-1">{{ number_format($sickDaysUsed, 1) }}</p>
            <span class="text-[10px] text-slate-400 mt-1 inline-block">days YTD</span>
        </div>
    </div>

    <!-- Emergency Requests Banner if any -->
    @if($emergencyRequests->count() > 0)
        <div class="p-4 rounded-2xl bg-amber-500/10 border-2 border-amber-500/40 flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <span class="text-2xl">🚨</span>
                <div>
                    <h3 class="text-sm font-bold text-amber-900">Urgent: {{ $emergencyRequests->count() }} Manager Leave Request(s)</h3>
                    <p class="text-xs text-amber-700">A manager has submitted an emergency leave request for HR review.</p>
                </div>
            </div>
                    <a href="{{ route('approvals.pending') }}" class="px-3.5 py-1.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-semibold text-xs transition">
                Review Request &rarr;
            </a>
        </div>
    @endif

    <!-- Main Grid: HR actions & monthly analytics -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left 2 Cols: Pending Queue -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">HR Actions</h2>
                        <p class="text-xs text-slate-500">Manager leave requests and cancellation confirmations need HR action</p>
                    </div>
                    <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-blue-600 text-white">
                        {{ $pendingApprovals->count() }} Requests
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                        <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                            <tr>
                                <th class="px-4 py-3">Employee</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Dates</th>
                                <th class="px-4 py-3 text-center">Days</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3 text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium">
                            @forelse($pendingApprovals as $app)
                                <tr class="hover:bg-slate-50 transition" x-data="{ openHrApprove: false, openHrReject: false }">
                                    <td class="px-4 py-3.5 flex items-center gap-2.5">
                                        <div class="w-8 h-8 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center">
                                            {{ substr($app->employee->first_name, 0, 1) }}
                                        </div>
                                        <div>
                                            <a href="{{ route('leave.show', $app->id) }}" class="font-bold text-slate-900 hover:text-blue-600">
                                                {{ $app->employee->full_name }}
                                            </a>
                                            <p class="text-[11px] text-slate-400">{{ $app->employee->department->name ?? 'General' }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="font-semibold text-slate-800">{{ $app->leaveType->name }}</span>
                                        @if($app->is_emergency)
                                            <span class="block text-[10px] text-amber-600 font-bold">🚨 Emergency</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3.5 text-xs text-slate-600">
                                        {{ $app->start_date->format('d M') }} - {{ $app->end_date->format('d M Y') }}
                                    </td>
                                    <td class="px-4 py-3.5 text-center font-bold text-slate-900">
                                        {{ $app->total_days }}
                                    </td>
                                    <td class="px-4 py-3.5">
                                        <span class="px-2 py-0.5 rounded-lg text-xs font-semibold border {{ $app->status_badge['class'] }}">
                                            {{ $app->status_badge['label'] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3.5 text-right space-x-1.5">
                                        @if($app->status === 'cancellation_requested')
                                            <form action="{{ route('approvals.approveCancellation', $app->id) }}" method="POST" class="inline">
                                                @csrf
                                                <button type="submit" class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold">
                                                    Approve Cancel
                                                </button>
                                            </form>
                                        @else
                                            <button @click="openHrApprove = true" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs">
                                                Approve
                                            </button>
                                            <button @click="openHrReject = true" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs">
                                                Reject
                                            </button>
                                        @endif
                                        <a href="{{ route('leave.show', $app->id) }}" class="p-1.5 text-slate-400 hover:text-slate-600">
                                            &rarr;
                                        </a>

                                        <!-- HR Approve Modal with permanent comment (Req 10) -->
                                        <div x-show="openHrApprove" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                            <div @click.outside="openHrApprove = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                                <h3 class="text-base font-bold text-slate-900">HR Final Approval</h3>
                                                <p class="text-xs text-slate-500 mt-1">
                                                    Approving {{ $app->employee->full_name }}'s {{ $app->leaveType->name }} for {{ $app->total_days }} days. This will deduct days from the employee ledger.
                                                </p>
                                                <form action="{{ route('approvals.hrApprove', $app->id) }}" method="POST" class="mt-4 space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-700">Approval Comment / Handover Note (Optional)</label>
                                                        <textarea name="comment" rows="3" placeholder="e.g. Approved. Please ensure handover is completed before Friday." class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-2 pt-2">
                                                        <button type="button" @click="openHrApprove = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">Approve & Deduct</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        <!-- HR Reject Modal with mandatory comment -->
                                        <div x-show="openHrReject" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                            <div @click.outside="openHrReject = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                                <h3 class="text-base font-bold text-rose-600">Reject Application</h3>
                                                <p class="text-xs text-slate-500 mt-1">
                                                    Rejection releases pending days back to the employee's available balance. A reason is mandatory.
                                                </p>
                                                <form action="{{ route('approvals.hrReject', $app->id) }}" method="POST" class="mt-4 space-y-3">
                                                    @csrf
                                                    <div>
                                                        <label class="block text-xs font-semibold text-slate-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                                                        <textarea name="rejection_reason" required rows="3" placeholder="e.g. Insufficient staffing during the requested period." class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-xl text-xs"></textarea>
                                                    </div>
                                                    <div class="flex justify-end gap-2 pt-2">
                                                        <button type="button" @click="openHrReject = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white">Reject Leave</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-slate-400 text-xs">
                                        All leave applications are up to date! No pending approvals.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Requirement 30: Monthly Leave Usage Bar Chart (Pure CSS/HTML) -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Leave Usage Trend ({{ date('Y') }})</h2>
                        <p class="text-xs text-slate-500">Days taken by month across the organization</p>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-blue-500"></span> Annual</span>
                        <span class="flex items-center gap-1.5"><span class="w-3 h-3 rounded bg-purple-500"></span> Sick</span>
                    </div>
                </div>

                <div class="h-44 flex items-end justify-between gap-2 pt-8 border-b border-slate-100 px-2">
                    @php $maxTotal = max(10, collect($monthlyTrends)->max('total')); @endphp
                    @foreach($monthlyTrends as $item)
                        @php
                            $annualH = ($item['annual'] / $maxTotal) * 100;
                            $sickH = ($item['sick'] / $maxTotal) * 100;
                        @endphp
                        <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end group relative">
                            <!-- Tooltip -->
                            <div class="opacity-0 group-hover:opacity-100 transition absolute -top-8 px-2 py-1 bg-slate-900 text-white text-[10px] rounded shadow-md pointer-events-none whitespace-nowrap z-10">
                                {{ $item['month'] }}: {{ $item['annual'] }} Annual, {{ $item['sick'] }} Sick (Total: {{ $item['total'] }}d)
                            </div>
                            <div class="w-full max-w-[24px] flex flex-col gap-0.5 rounded-t overflow-hidden">
                                @if($item['sick'] > 0)
                                    <div style="height: {{ max(4, $sickH) }}px" class="bg-purple-500 w-full"></div>
                                @endif
                                @if($item['annual'] > 0)
                                    <div style="height: {{ max(4, $annualH) }}px" class="bg-blue-500 w-full"></div>
                                @endif
                                @if($item['total'] == 0)
                                    <div class="h-1 bg-slate-200 w-full rounded"></div>
                                @endif
                            </div>
                            <span class="text-[10px] text-slate-400 font-semibold">{{ $item['month'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Right Col: Low Balances & Returning Staff -->
        <div class="space-y-6">
            <!-- Low Leave Balance Alerts -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900 flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        Low Balance Alerts (&le; 3 days)
                    </h3>
                </div>
                <div class="divide-y divide-slate-100">
                    @forelse($lowBalances as $lb)
                        <div class="py-2.5 flex items-center justify-between">
                            <div>
                                <p class="text-xs font-bold text-slate-800">{{ $lb->employee->full_name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $lb->employee->department->name ?? 'General' }}</p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-600 text-white">
                                    {{ $lb->available_days }} days left
                                </span>
                                <a href="{{ route('employees.leaveAccount', $lb->employee_id) }}" class="block text-[10px] text-blue-600 hover:underline mt-0.5">Adjust &rarr;</a>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">No employees with critical low balances.</p>
                    @endforelse
                </div>
            </div>

            <!-- Staff Currently Away Today -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Away Today ({{ $onLeaveToday->count() }})</h3>
                    <a href="{{ route('calendar.index') }}" class="text-xs text-blue-600 hover:underline">Calendar</a>
                </div>
                <div class="space-y-3">
                    @forelse($onLeaveToday as $away)
                        <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                            <div>
                                <p class="font-bold text-slate-800">{{ $away->employee->full_name }}</p>
                                <p class="text-[10px] text-slate-400">{{ $away->employee->department->name ?? '' }} • {{ $away->leaveType->name }}</p>
                            </div>
                            <span class="text-[10px] font-semibold text-slate-500">
                                Returns {{ Carbon::parse($away->end_date)->addDay()->format('d M') }}
                            </span>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-3 text-center">All employees are currently in office.</p>
                    @endforelse
                </div>
            </div>

            <!-- Departments Quick Overview -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <h3 class="text-sm font-bold text-slate-900">Departments Overview</h3>
                <div class="space-y-2">
                    @foreach($departments as $dept)
                        <div class="flex items-center justify-between text-xs py-1">
                            <span class="font-medium text-slate-700">{{ $dept->name }}</span>
                            <span class="text-slate-400 font-semibold">{{ $dept->employees_count }} staff</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
