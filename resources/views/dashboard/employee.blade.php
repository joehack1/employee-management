@extends('layouts.app')

@section('title', 'Employee Dashboard - LeaveFlow')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="dashboardGreeting(@json(auth()->id()))">
    @include('dashboard.greeting')
    <!-- Employee Header & Status Card -->
    <div class="dashboard-hero bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200/80 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="flex items-start sm:items-center gap-4">
            @if($user->avatar)
                <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }} profile picture" class="w-16 h-16 rounded-2xl object-cover shadow-lg flex-shrink-0">
            @else
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-2xl flex items-center justify-center shadow-lg shadow-blue-500/20 flex-shrink-0">
                    {{ substr($user->name, 0, 1) }}
                </div>
            @endif
            <div>
                <div class="flex flex-wrap items-center gap-2">
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $employee ? $employee->full_name : $user->name }}</h1>
                    @if($employee)
                        <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                            {{ $employee->employee_number }}
                        </span>
                        <!-- Status Badge (Req 33) -->
                        @if($employee->current_status === 'working')
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-emerald-600 text-white">
                                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span> Working
                            </span>
                        @elseif($employee->current_status === 'on_leave')
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-rose-600 text-white">
                                <span class="w-2 h-2 rounded-full bg-rose-500 animate-pulse"></span> On Leave
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-0.5 rounded-full text-xs font-semibold bg-amber-600 text-white">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span> Pending Leave
                            </span>
                        @endif
                    @endif
                </div>
                <p class="text-xs text-slate-500 mt-1">
                    {{ $employee->job_title ?? 'Employee' }} • {{ $employee->department->name ?? 'General' }} 
                    @if($employee?->team) • Team: {{ $employee->team->name }} @endif
                    @if($employee?->teamLead) • Team Lead: <span class="font-medium text-slate-700">{{ $employee->teamLead->name }}</span> @endif
                </p>
            </div>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('leave.history') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold transition">
                Leave History & Ledger
            </a>
            @can('apply-leave')<a href="{{ route('leave.create') }}" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Apply for Leave
            </a>
            @endcan
        </div>
    </div>

    <!-- Requirement 1 & 5: Leave Entitlement Summary Table & Cards -->
    <div class="space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Leave Entitlement & Balances ({{ date('Y') }})</h2>
                <p class="text-xs text-slate-500">Live ledger balance accounting for approved usage and pending reservations</p>
            </div>
            <span class="text-xs text-slate-400 font-medium">Policy: {{ $employee->leavePolicy->name ?? 'Standard' }}</span>
        </div>

        <!-- Desktop Entitlement Summary Table -->
        <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                    <thead class="bg-slate-50/80 text-xs font-bold text-slate-600 uppercase tracking-wider">
                        <tr>
                            <th class="px-6 py-4">Leave Type</th>
                            <th class="px-6 py-4 text-center">Entitled</th>
                            <th class="px-6 py-4 text-center text-slate-500">Adjustments</th>
                            <th class="px-6 py-4 text-center text-rose-600">Used (Approved)</th>
                            <th class="px-6 py-4 text-center text-amber-600">Pending</th>
                            <th class="px-6 py-4 text-center font-extrabold text-blue-700">Available</th>
                            <th class="px-6 py-4 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($balances as $bal)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 flex items-center gap-3">
                                    <span class="w-3 h-3 rounded-full ring-1 ring-black/10" style="background-color: {{ $bal->leaveType->display_color }}"></span>
                                    <div>
                                        <p class="font-bold text-slate-900">{{ $bal->leaveType->name }}</p>
                                        <p class="text-[11px] text-slate-400">{{ $bal->leaveType->description }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center font-semibold text-slate-800">
                                    {{ number_format($bal->entitled_days, 1) }}
                                </td>
                                <td class="px-6 py-4 text-center text-slate-500 text-xs">
                                    @if($bal->manual_adjustment_days != 0)
                                        <span class="px-2 py-0.5 rounded-md {{ $bal->manual_adjustment_days > 0 ? 'bg-emerald-600 text-white' : 'bg-rose-600 text-white' }}">
                                            {{ $bal->manual_adjustment_days > 0 ? '+' : '' }}{{ number_format($bal->manual_adjustment_days, 1) }}
                                        </span>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-rose-600">
                                    {{ number_format($bal->used_days, 1) }}
                                </td>
                                <td class="px-6 py-4 text-center font-bold text-amber-600">
                                    {{ number_format($bal->pending_days, 1) }}
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-xl text-sm font-extrabold bg-blue-600 text-white">
                                        {{ number_format($bal->available_days, 1) }} days
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    @can('apply-leave')<a href="{{ route('leave.create') }}?leave_type_id={{ $bal->leave_type_id }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                        Apply &rarr;
                                    </a>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-8 text-center text-slate-400 text-xs">
                                    No leave balances initialized for this leave year.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Two-Column Grid: Applications & Activity -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Left: Applications List (Pending, Approved, Rejected) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-6">
                <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                    <div>
                        <h3 class="text-base font-bold text-slate-900">Recent Leave Applications</h3>
                        <p class="text-xs text-slate-500">Track approvals and review history</p>
                    </div>
                    <a href="{{ route('leave.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">View All</a>
                </div>

                <div class="divide-y divide-slate-100">
                    @forelse($applications as $app)
                        <div class="py-4 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-slate-50/50 p-2 rounded-2xl transition">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-bold text-slate-600 text-xs flex-shrink-0">
                                    {{ substr($app->leaveType->name, 0, 2) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2">
                                        <a href="{{ route('leave.show', $app->id) }}" class="font-bold text-slate-900 hover:text-blue-600 text-sm">
                                            {{ $app->leaveType->name }}
                                        </a>
                                        @if($app->is_emergency)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-600 text-white">
                                                🚨 Emergency
                                            </span>
                                        @endif
                                        @if($app->is_half_day)
                                            <span class="px-1.5 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-600">
                                                Half-day ({{ ucfirst($app->half_day_type) }})
                                            </span>
                                        @endif
                                    </div>
                                    <p class="text-xs text-slate-500 mt-0.5">
                                        {{ $app->start_date->format('d M Y') }} &rarr; {{ $app->end_date->format('d M Y') }}
                                        • <span class="font-semibold text-slate-700">{{ $app->total_days }} {{ Str::plural('day', $app->total_days) }}</span>
                                    </p>
                                    @if($app->rejection_reason)
                                        <p class="text-xs text-rose-600 mt-1 font-medium bg-rose-50 p-1.5 rounded-lg border border-rose-200">
                                            Rejection reason: {{ $app->rejection_reason }}
                                        </p>
                                    @endif
                                </div>
                            </div>

                            <div class="flex items-center gap-3 justify-between sm:justify-end">
                                <span class="px-3 py-1 rounded-xl text-xs font-semibold border {{ $app->status_badge['class'] }}">
                                    {{ $app->status_badge['label'] }}
                                </span>
                                <a href="{{ route('leave.show', $app->id) }}" class="p-2 rounded-lg hover:bg-slate-100 text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400 text-xs">
                            No leave applications submitted yet. Click "Apply for Leave" above.
                        </div>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Right: Upcoming Leave & Calendar Preview -->
        <div class="space-y-6">
            <!-- Upcoming Leave Card -->
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-sm font-bold text-slate-900">Upcoming Approved Leave</h3>
                    <span class="text-xs text-emerald-600 font-semibold">Confirmed</span>
                </div>
                <div class="space-y-3">
                    @forelse($upcomingLeave as $u)
                        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100 space-y-1">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-xs text-slate-800">{{ $u->leaveType->name }}</span>
                                <span class="text-xs font-semibold text-blue-600">{{ $u->total_days }} days</span>
                            </div>
                            <p class="text-xs text-slate-500">
                                {{ $u->start_date->format('d M') }} - {{ $u->end_date->format('d M Y') }}
                            </p>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400 py-4 text-center">No upcoming leaves scheduled.</p>
                    @endforelse
                </div>
            </div>

            <!-- Quick Information & Rules Callout -->
            <div class="bg-gradient-to-br from-blue-50 to-indigo-50/50 rounded-3xl p-6 border border-blue-100 space-y-3 text-xs text-slate-600">
                <h4 class="font-bold text-slate-900 flex items-center gap-1.5 text-sm">
                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/></svg>
                    Leave Guidelines
                </h4>
                <ul class="space-y-2 list-disc list-inside text-slate-600">
                    <li><strong>3-Day Notice Rule:</strong> Annual leave must be requested at least 3 days in advance.</li>
                    <li><strong>Emergency Exception:</strong> For urgent medical or personal emergencies, check <em>Emergency Leave?</em> to bypass the 3-day notice rule and allow backdated submission.</li>
                    <li><strong>Sick Leave:</strong> Up to 30 days at full pay, followed by up to 15 days at half pay. A medical attachment is required for every sick leave request.</li>
                    <li><strong>Compassionate Leave:</strong> Everyone is entitled to 7 paid days when a loved one passes away.</li>
                    <li><strong>Half-Day Leave:</strong> Morning or afternoon half-days calculate as 0.5 working days.</li>
                    <li><strong>Non-Working Days:</strong> Weekends and gazetted public holidays are automatically excluded from your leave count.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
