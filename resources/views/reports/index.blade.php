@extends('layouts.app')

@section('title', 'Leave Reports & Analytics - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leave Utilization Reports</h1>
            <p class="text-xs text-slate-500">Comprehensive departmental summaries, individual employee balances, and audit exports</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('reports.export') }}?year={{ $year }}&report_type=applications&department_id={{ $departmentId }}" class="px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs flex items-center gap-1.5 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                Export CSV (Applications)
            </a>
            <a href="{{ route('reports.export') }}?year={{ $year }}&report_type=balances&department_id={{ $departmentId }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold shadow-xs transition">
                Export CSV (Balances)
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('reports.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Leave Year</label>
                <select name="year" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" onchange="this.form.submit()">
                    @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                    @endfor
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Department</label>
                <select name="department_id" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ $departmentId == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Leave Type</label>
                <select name="leave_type_id" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" onchange="this.form.submit()">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ $leaveTypeId == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex items-end">
                <a href="{{ route('reports.index') }}" class="w-full py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-xs font-semibold rounded-xl text-center transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Requirement 29: Departmental Breakdown Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        @foreach($deptStats as $ds)
            <div class="bg-white p-5 rounded-3xl border border-slate-200 shadow-xs space-y-2">
                <div class="flex items-center justify-between">
                    <span class="text-[10px] font-bold px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-200">{{ $ds['code'] }}</span>
                    <span class="text-xs text-slate-400">{{ $ds['employee_count'] }} staff</span>
                </div>
                <h3 class="text-sm font-bold text-slate-900">{{ $ds['name'] }}</h3>
                <div class="pt-2 border-t border-slate-100 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Days Taken ({{ $year }}):</span>
                    <span class="font-extrabold text-blue-700">{{ number_format($ds['days_taken'], 1) }} d</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Report Table (Requirement 29: October / Yearly Leave Report) -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden space-y-4 p-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Leave Activity Report ({{ $year }})</h2>
                <p class="text-xs text-slate-500">Filtered view showing {{ $applications->count() }} records</p>
            </div>
            <button onclick="window.print()" class="text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-200 px-3 py-1.5 rounded-xl">
                Print Report
            </button>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50 font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Employee</th>
                        <th class="px-4 py-3">Department</th>
                        <th class="px-4 py-3">Leave Type</th>
                        <th class="px-4 py-3">Dates</th>
                        <th class="px-4 py-3 text-center">Days</th>
                        <th class="px-4 py-3">Status</th>
                        <th class="px-4 py-3">Submitted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 font-bold text-slate-900">
                                <a href="{{ route('leave.show', $app->id) }}" class="hover:text-blue-600">
                                    {{ $app->employee->full_name }}
                                </a>
                                <p class="text-[10px] text-slate-400 font-normal">{{ $app->employee->employee_number }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $app->employee->department->name ?? 'General' }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-800">
                                {{ $app->leaveType->name }}
                                @if($app->is_emergency)
                                    <span class="text-[10px] text-amber-600 font-bold block">🚨 Emergency</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $app->start_date->format('d M') }} - {{ $app->end_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-slate-900">
                                {{ $app->total_days }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border {{ $app->status_badge['class'] }}">
                                    {{ $app->status_badge['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-slate-400">
                                {{ $app->created_at->format('d M Y') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No leave applications matching the filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
