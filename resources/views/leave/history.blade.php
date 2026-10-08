@extends('layouts.app')

@section('title', 'Leave History & Ledger - LeaveFlow')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leave History & Transaction Ledger</h1>
            <p class="text-xs text-slate-500">Immutable ledger accounting and chronological history of all leave activities</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-slate-500 font-semibold">Year:</span>
            <select class="text-xs px-3 py-1.5 border border-slate-300 rounded-xl" onchange="window.location.href = '{{ route('leave.history') }}?year=' + this.value">
                @for($y = date('Y') + 1; $y >= date('Y') - 2; $y--)
                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                @endfor
            </select>
        </div>
    </div>

    <!-- Requirement 38: The Leave Ledger Table (The Core Audit Trail) -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 p-6 space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Leave Balance Ledger ({{ $year }})</h2>
            <p class="text-xs text-slate-500">Every entitlement, deduction, refund, and HR adjustment recorded with timestamped audit entries.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Date & Time</th>
                        <th class="px-4 py-3">Leave Type</th>
                        <th class="px-4 py-3">Transaction Type</th>
                        <th class="px-4 py-3 text-center">Amount (Days)</th>
                        <th class="px-4 py-3 text-center">Running Balance</th>
                        <th class="px-4 py-3">Reason / Details</th>
                        <th class="px-4 py-3">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $t)
                        <tr class="hover:bg-slate-50 transition text-xs">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">
                                {{ $t->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-4 py-3 font-semibold text-slate-900">
                                {{ $t->leaveType->name }}
                            </td>
                            <td class="px-4 py-3">
                                <span class="capitalize text-slate-700">
                                    {{ str_replace('_', ' ', $t->type) }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold">
                                @if($t->amount > 0)
                                    <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">+{{ number_format($t->amount, 1) }}</span>
                                @else
                                    <span class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md">{{ number_format($t->amount, 1) }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-extrabold text-blue-700">
                                {{ number_format($t->running_balance, 1) }} d
                            </td>
                            <td class="px-4 py-3 text-slate-600">
                                {{ $t->reason }}
                            </td>
                            <td class="px-4 py-3 text-slate-400">
                                {{ $t->creator ? $t->creator->name : 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">
                                No ledger transactions found for {{ $year }}.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Requirement 20: Applications History Timeline -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 p-6 space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Annual Leave Applications History ({{ $year }})</h2>
            <p class="text-xs text-slate-500">Complete chronological record of all your leave requests.</p>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($applications as $app)
                <div class="py-4 flex items-center justify-between hover:bg-slate-50/50 p-2 rounded-xl transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center font-bold text-slate-600 text-xs">
                            {{ substr($app->leaveType->name, 0, 2) }}
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <a href="{{ route('leave.show', $app->id) }}" class="font-bold text-slate-900 hover:text-blue-600 text-sm">
                                    {{ $app->leaveType->name }}
                                </a>
                                <span class="text-xs text-slate-400">({{ $app->application_number }})</span>
                            </div>
                            <p class="text-xs text-slate-500 mt-0.5">
                                {{ $app->start_date->format('d M') }} &rarr; {{ $app->end_date->format('d M Y') }}
                                • <span class="font-semibold text-slate-700">{{ $app->total_days }} days</span>
                                • Reason: {{ Str::limit($app->reason, 50) }}
                            </p>
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <span class="px-2.5 py-1 rounded-xl text-xs font-semibold border {{ $app->status_badge['class'] }}">
                            {{ $app->status_badge['label'] }}
                        </span>
                        <a href="{{ route('leave.show', $app->id) }}" class="p-1.5 text-slate-400 hover:text-slate-600 text-xs font-semibold">
                            Details &rarr;
                        </a>
                    </div>
                </div>
            @empty
                <div class="py-8 text-center text-slate-400 text-xs">
                    No applications filed in {{ $year }}.
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
