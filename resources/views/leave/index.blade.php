@extends('layouts.app')

@section('title', 'My Leave Requests - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Leave Requests</h1>
            <p class="text-xs text-slate-500">Track all your submitted applications and approval statuses</p>
        </div>
        <a href="{{ route('leave.create') }}" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Apply for Leave
        </a>
    </div>

    <!-- Filter Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('leave.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <select name="status" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" onchange="this.form.submit()">
                    <option value="">All Statuses</option>
                    <option value="pending_team_lead" {{ request('status') === 'pending_team_lead' ? 'selected' : '' }}>Pending Team Lead</option>
                    <option value="pending_hr" {{ request('status') === 'pending_hr' ? 'selected' : '' }}>Pending HR</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                </select>
            </div>
            <div>
                <select name="leave_type_id" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" onchange="this.form.submit()">
                    <option value="">All Leave Types</option>
                    @foreach($leaveTypes as $lt)
                        <option value="{{ $lt->id }}" {{ request('leave_type_id') == $lt->id ? 'selected' : '' }}>{{ $lt->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <a href="{{ route('leave.index') }}" class="inline-flex items-center justify-center w-full px-3 py-2 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">
                    Clear Filters
                </a>
            </div>
        </form>
    </div>

    <!-- Applications Table -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-6 py-4">Application #</th>
                        <th class="px-6 py-4">Leave Type</th>
                        <th class="px-6 py-4">Dates</th>
                        <th class="px-6 py-4 text-center">Days</th>
                        <th class="px-6 py-4">Submitted At</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($applications as $app)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 font-bold text-slate-900">
                                <a href="{{ route('leave.show', $app->id) }}" class="hover:text-blue-600">
                                    {{ $app->application_number }}
                                </a>
                            </td>
                            <td class="px-6 py-4">
                                <span class="font-semibold text-slate-800">{{ $app->leaveType->name }}</span>
                                @if($app->is_emergency)
                                    <span class="block text-[10px] text-amber-600 font-bold">🚨 Emergency</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-600">
                                {{ $app->start_date->format('d M Y') }} &rarr; {{ $app->end_date->format('d M Y') }}
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-900">
                                {{ $app->total_days }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-400">
                                {{ $app->created_at->format('d M Y H:i') }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-xl text-xs font-semibold border {{ $app->status_badge['class'] }}">
                                    {{ $app->status_badge['label'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('leave.show', $app->id) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                    View Details &rarr;
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400 text-xs">
                                No leave applications found matching your criteria.
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
