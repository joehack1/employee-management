@extends('layouts.app')

@section('title', $viewTitle . ' - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">{{ $viewTitle }}</h1>
            <p class="text-xs text-slate-500">Review leave applications and record your decision</p>
        </div>
    </div>

    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-6 py-4">Employee</th>
                        <th class="px-6 py-4">Leave Type</th>
                        <th class="px-6 py-4">Requested Dates</th>
                        <th class="px-6 py-4 text-center">Days</th>
                        <th class="px-6 py-4">Reason</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50 transition" x-data="{ openApprove: false, openReject: false }">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-800 font-bold text-xs flex items-center justify-center">
                                    {{ substr($app->employee->first_name, 0, 1) }}
                                </div>
                                <div>
                                    <a href="{{ route('leave.show', $app->id) }}" class="font-bold text-slate-900 hover:text-blue-600">
                                        {{ $app->employee->full_name }}
                                    </a>
                                    <p class="text-[11px] text-slate-400">{{ $app->employee->department->name ?? 'General' }} • {{ $app->employee->employee_number }}</p>
                                    @if($approvalLevel === 'manager')
                                        <p class="text-[10px] font-semibold uppercase tracking-wide text-teal-700">{{ str_replace('_', ' ', $app->employee->user->role ?? 'staff') }}</p>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-900">{{ $app->leaveType->name }}</span>
                                @if($app->is_emergency)
                                    <span class="block text-[10px] text-amber-600 font-bold">🚨 Emergency</span>
                                @endif
                                @if($app->is_half_day)
                                    <span class="block text-[10px] text-slate-400">Half-day ({{ $app->half_day_type }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $app->start_date->format('d M') }} &rarr; {{ $app->end_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-900">
                                {{ $app->total_days }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600 max-w-xs truncate" title="{{ $app->reason }}">
                                {{ $app->reason }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-semibold border {{ $app->status_badge['class'] }}">
                                    {{ $app->status_badge['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if($app->status === 'cancellation_requested')
                                    <form action="{{ route('approvals.approveCancellation', $app->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 rounded-lg bg-orange-600 hover:bg-orange-700 text-white text-xs font-semibold">
                                            Approve Cancellation
                                        </button>
                                    </form>
                                @else
                                    <button @click="openApprove = true" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold shadow-xs">
                                        Approve
                                    </button>
                                    <button @click="openReject = true" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold shadow-xs">
                                        Reject
                                    </button>
                                @endif
                                <a href="{{ route('leave.show', $app->id) }}" class="text-xs text-slate-400 hover:text-slate-600">Details</a>

                                <!-- Approve Modal -->
                                <div x-show="openApprove" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="openApprove = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-bold text-slate-900">Approve Request</h3>
                                        <form action="{{ $approvalLevel === 'manager' ? route('approvals.managerApprove', $app->id) : ($isHrView ? route('approvals.hrApprove', $app->id) : route('approvals.leadApprove', $app->id)) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700">Comment (Optional)</label>
                                                <textarea name="comment" rows="3" placeholder="Approval comment or handover note..." class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                                            </div>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button type="button" @click="openApprove = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">Confirm Approval</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Reject Modal with Mandatory Reason (Req 8 & 10) -->
                                <div x-show="openReject" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="openReject = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-bold text-rose-600">Reject Application</h3>
                                        <form action="{{ $approvalLevel === 'manager' ? route('approvals.managerReject', $app->id) : ($isHrView ? route('approvals.hrReject', $app->id) : route('approvals.leadReject', $app->id)) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                                                <textarea name="rejection_reason" required rows="3" placeholder="Provide mandatory explanation for rejection..." class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-xl text-xs"></textarea>
                                            </div>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button type="button" @click="openReject = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white">Reject Leave</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No applications currently waiting in this review queue.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($applications->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $applications->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
