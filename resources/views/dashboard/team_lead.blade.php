@extends('layouts.app')

@section('title', 'Team Lead Dashboard - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <!-- Header -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Team Lead Portal</h1>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                    Lead: {{ $user->name }}
                </span>
            </div>
            <p class="text-xs text-slate-500 mt-1">Review team leave applications, manage schedules, and monitor availability</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('calendar.index') }}" class="px-4 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 text-slate-700 text-xs font-semibold">
                Team Calendar
            </a>
            <a href="{{ route('approvals.pending') }}" class="px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-600 text-white text-xs font-semibold shadow-xs">
                Pending Reviews ({{ $pendingRequests->count() }})
            </a>
        </div>
    </div>

    <!-- Requirement 8: Pending Requests Queue for Team Lead -->
    <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Pending Team Leave Requests</h2>
                <p class="text-xs text-slate-500">Requires your review and approval before proceeding to HR</p>
            </div>
            <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-amber-50 text-amber-700 border border-amber-200">
                {{ $pendingRequests->count() }} Pending
            </span>
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
                    @forelse($pendingRequests as $req)
                        <tr class="hover:bg-slate-50 transition" x-data="{ openRejectModal: false, openApproveModal: false }">
                            <td class="px-4 py-3.5 flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-full bg-blue-100 text-blue-700 font-bold text-xs flex items-center justify-center">
                                    {{ substr($req->employee->first_name, 0, 1) }}
                                </div>
                                <div>
                                    <p class="font-bold text-slate-900">{{ $req->employee->full_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $req->employee->job_title }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="font-semibold text-slate-800">{{ $req->leaveType->name }}</span>
                                @if($req->is_emergency)
                                    <span class="block text-[10px] text-amber-600 font-bold">🚨 Emergency Request</span>
                                @endif
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600">
                                {{ $req->start_date->format('d M') }} - {{ $req->end_date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3.5 text-center font-bold text-slate-900">
                                {{ $req->total_days }}
                            </td>
                            <td class="px-4 py-3.5 text-xs text-slate-600 max-w-xs truncate" title="{{ $req->reason }}">
                                {{ $req->reason }}
                            </td>
                            <td class="px-4 py-3.5">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold border {{ $req->status_badge['class'] }}">
                                    {{ $req->status_badge['label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3.5 text-right space-x-2">
                                <button @click="openApproveModal = true" class="px-3 py-1.5 rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold transition shadow-xs">
                                    Approve
                                </button>
                                <button @click="openRejectModal = true" class="px-3 py-1.5 rounded-lg bg-rose-600 hover:bg-rose-700 text-white text-xs font-semibold transition shadow-xs">
                                    Reject
                                </button>
                                <a href="{{ route('leave.show', $req->id) }}" class="px-2 py-1.5 rounded-lg text-slate-500 hover:bg-slate-100 text-xs">
                                    Details
                                </a>

                                <!-- Approve Modal with optional comment -->
                                <div x-show="openApproveModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="openApproveModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-bold text-slate-900">Approve Leave Request</h3>
                                        <p class="text-xs text-slate-500 mt-1">
                                            Approving {{ $req->employee->full_name }}'s {{ $req->leaveType->name }} for {{ $req->total_days }} days ({{ $req->start_date->format('d M') }} - {{ $req->end_date->format('d M Y') }}).
                                        </p>
                                        <form action="{{ route('approvals.leadApprove', $req->id) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700">Approval Comment (Optional)</label>
                                                <textarea name="comment" rows="3" placeholder="e.g. Approved. Please ensure handover is completed before Friday." class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                                            </div>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button type="button" @click="openApproveModal = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-emerald-600 hover:bg-emerald-700 text-white">Confirm Approval</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>

                                <!-- Reject Modal with mandatory comment (Req 8 & 10) -->
                                <div x-show="openRejectModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
                                    <div @click.outside="openRejectModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
                                        <h3 class="text-base font-bold text-rose-600">Reject Leave Request</h3>
                                        <p class="text-xs text-slate-500 mt-1">
                                            You are rejecting {{ $req->employee->full_name }}'s leave application. A clear explanation is required.
                                        </p>
                                        <form action="{{ route('approvals.leadReject', $req->id) }}" method="POST" class="mt-4 space-y-3">
                                            @csrf
                                            <div>
                                                <label class="block text-xs font-semibold text-slate-700">Reason for Rejection <span class="text-rose-500">*</span></label>
                                                <textarea name="rejection_reason" required rows="3" placeholder="e.g. Too many team members are already scheduled to be away during this period." class="mt-1 block w-full px-3 py-2 border border-rose-300 rounded-xl text-xs focus:ring-rose-500 focus:border-rose-500"></textarea>
                                            </div>
                                            <div class="flex justify-end gap-2 pt-2">
                                                <button type="button" @click="openRejectModal = false" class="px-4 py-2 rounded-xl text-xs font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                                                <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-rose-600 hover:bg-rose-700 text-white">Reject Leave</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-8 text-center text-slate-400 text-xs">
                                No pending leave requests requiring team lead review right now.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Team Members Roster & Current Availability Status -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Team Members List -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Your Team Members ({{ $teamMembers->count() }})</h2>
            <div class="divide-y divide-slate-100">
                @forelse($teamMembers as $tm)
                    <div class="py-3 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-700 font-bold text-xs flex items-center justify-center">
                                {{ substr($tm->first_name, 0, 1) }}
                            </div>
                            <div>
                                <p class="text-xs font-bold text-slate-900">{{ $tm->full_name }}</p>
                                <p class="text-[11px] text-slate-400">{{ $tm->job_title }} • {{ $tm->employee_number }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            @if($tm->current_status === 'working')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Working
                                </span>
                            @elseif($tm->current_status === 'on_leave')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-50 text-rose-700 border border-rose-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> On Leave
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-50 text-amber-700 border border-amber-200">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Leave
                                </span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">No team members assigned.</p>
                @endforelse
            </div>
        </div>

        <!-- Team Upcoming Leave -->
        <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
            <h2 class="text-base font-bold text-slate-900">Upcoming Team Leave</h2>
            <div class="space-y-3">
                @forelse($upcomingTeamLeave as $u)
                    <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 text-xs space-y-1">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-slate-800">{{ $u->employee->full_name }}</span>
                            <span class="text-blue-600 font-semibold">{{ $u->total_days }} days</span>
                        </div>
                        <p class="text-slate-500 text-[11px]">
                            {{ $u->leaveType->name }} • {{ $u->start_date->format('d M') }} - {{ $u->end_date->format('d M Y') }}
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400 py-4 text-center">No upcoming team leaves.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
