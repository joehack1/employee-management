@extends('layouts.app')

@section('title', 'Leave Account & Ledger: ' . $employee->full_name . ' - LeaveFlow')

@section('content')
<div class="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ openAdjustModal: false, selectedType: '', adjustmentAmount: 1, reason: '' }">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <a href="{{ route('employees.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                &larr; Back to Employee Directory
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-2">
                Leave Account & Ledger: {{ $employee->full_name }}
            </h1>
            <p class="text-xs text-slate-500">
                {{ $employee->employee_number }} • {{ $employee->job_title }} • {{ $employee->department->name ?? 'General' }}
            </p>
        </div>

        <button @click="openAdjustModal = true" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            HR Balance Adjustment (+ / -)
        </button>
    </div>

    <!-- Requirement 5 & 6: Leave Balances Table for this Employee -->
    <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-base font-bold text-slate-900">Current Year Leave Balances ({{ $year }})</h2>
                <p class="text-xs text-slate-500">Separately showing Entitlement, Approved Used, Pending Reservations, and Available Days</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Leave Type</th>
                        <th class="px-4 py-3 text-center">Entitled</th>
                        <th class="px-4 py-3 text-center">Carry Forward</th>
                        <th class="px-4 py-3 text-center">HR Adjustments</th>
                        <th class="px-4 py-3 text-center text-rose-600">Used</th>
                        <th class="px-4 py-3 text-center text-amber-600">Pending</th>
                        <th class="px-4 py-3 text-center font-extrabold text-blue-700">Available Balance</th>
                        <th class="px-4 py-3 text-right">Quick Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($balances as $bal)
                        <tr class="hover:bg-slate-50 transition text-xs">
                            <td class="px-4 py-3.5 flex items-center gap-2">
                                <span class="w-2.5 h-2.5 rounded-full ring-1 ring-black/10" style="background-color: {{ $bal->leaveType->display_color }}"></span>
                                <span class="font-bold text-slate-900">{{ $bal->leaveType->name }}</span>
                            </td>
                            <td class="px-4 py-3.5 text-center font-semibold">{{ $bal->entitled_days }}</td>
                            <td class="px-4 py-3.5 text-center text-slate-500">{{ $bal->carried_forward_days }}</td>
                            <td class="px-4 py-3.5 text-center font-bold">
                                @if($bal->manual_adjustment_days != 0)
                                    <span class="px-2 py-0.5 rounded {{ $bal->manual_adjustment_days > 0 ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                                        {{ $bal->manual_adjustment_days > 0 ? '+' : '' }}{{ $bal->manual_adjustment_days }}
                                    </span>
                                @else
                                    0.0
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-rose-600">{{ $bal->used_days }}</td>
                            <td class="px-4 py-3.5 text-center font-bold text-amber-600">{{ $bal->pending_days }}</td>
                            <td class="px-4 py-3.5 text-center">
                                <span class="inline-flex px-3 py-1 rounded-xl font-extrabold bg-blue-50 text-blue-700 border border-blue-200">
                                    {{ $bal->available_days }} days
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right">
                                <button @click="openAdjustModal = true; selectedType = '{{ $bal->leave_type_id }}'" class="font-bold text-blue-600 hover:text-blue-800">
                                    Adjust &rarr;
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-6 text-center text-slate-400">No leave balances initialized.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Requirement 6 & 21: Full Ledger Transaction History -->
    <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <div>
                <h2 class="text-base font-bold text-slate-900">Immutable Ledger Audit Trail</h2>
                <p class="text-xs text-slate-500">Every change to {{ $employee->full_name }}'s balance is permanently recorded here with reason and actor.</p>
            </div>
            <span class="text-xs font-semibold text-slate-400">{{ $transactions->total() }} Records</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                <thead class="bg-slate-50 font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Timestamp</th>
                        <th class="px-4 py-3">Leave Type</th>
                        <th class="px-4 py-3">Action Type</th>
                        <th class="px-4 py-3 text-center">Delta (Days)</th>
                        <th class="px-4 py-3 text-center">Running Balance</th>
                        <th class="px-4 py-3">Audit Reason</th>
                        <th class="px-4 py-3">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($transactions as $tx)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3 text-slate-500 whitespace-nowrap">{{ $tx->created_at->format('d M Y H:i:s') }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-900">{{ $tx->leaveType->name }}</td>
                            <td class="px-4 py-3 capitalize text-slate-700">{{ str_replace('_', ' ', $tx->type) }}</td>
                            <td class="px-4 py-3 text-center font-bold">
                                @if($tx->amount > 0)
                                    <span class="text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-md">+{{ $tx->amount }}</span>
                                @else
                                    <span class="text-rose-600 bg-rose-50 px-2 py-0.5 rounded-md">{{ $tx->amount }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center font-extrabold text-blue-700">{{ $tx->running_balance }} d</td>
                            <td class="px-4 py-3 text-slate-600">{{ $tx->reason }}</td>
                            <td class="px-4 py-3 text-slate-400">{{ $tx->creator ? $tx->creator->name : 'System' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400">No transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transactions->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $transactions->links() }}
            </div>
        @endif
    </div>

    <!-- Requirement 6: HR Adjustment Modal (+2 or -1 days with mandatory reason) -->
    <div x-show="openAdjustModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openAdjustModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 sm:p-8 text-left shadow-2xl">
            <h3 class="text-lg font-bold text-slate-900">HR Leave Balance Adjustment</h3>
            <p class="text-xs text-slate-500 mt-1">
                Add or reduce leave days for {{ $employee->full_name }}. An audit log and ledger entry will be created.
            </p>

            <form action="{{ route('employees.adjustBalance', $employee->id) }}" method="POST" class="mt-6 space-y-4">
                @csrf

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Leave Type <span class="text-rose-500">*</span></label>
                    <select name="leave_type_id" required x-model="selectedType" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        <option value="">-- Select Leave Type --</option>
                        @foreach($leaveTypes as $lt)
                            <option value="{{ $lt->id }}">{{ $lt->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Adjustment Amount (Days) <span class="text-rose-500">*</span></label>
                    <p class="text-[11px] text-slate-400">Use positive numbers to add days (e.g. +2) or negative to deduct (e.g. -1).</p>
                    <input type="number" step="0.5" name="adjustment_amount" required x-model="adjustmentAmount" placeholder="+2 or -1" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-sm font-bold text-blue-700">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Mandatory Reason / Justification <span class="text-rose-500">*</span></label>
                    <textarea name="reason" required rows="3" x-model="reason" placeholder="e.g. Extra leave awarded for delivering Q2 project, or leave correction." class="mt-1 block w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>

                <div class="pt-2 flex items-center justify-end gap-2 border-t border-slate-100">
                    <button type="button" @click="openAdjustModal = false" class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-100">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white shadow-xs">
                        Post Adjustment to Ledger
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
