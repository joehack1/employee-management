@extends('layouts.app')

@section('title', 'System Audit Trail - LeaveFlow')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">System Audit Trail</h1>
            <p class="text-xs text-slate-500">Immutable chronological security and compliance log of all system operations</p>
        </div>
        <span class="text-xs font-semibold px-3 py-1 bg-slate-100 text-slate-700 rounded-xl border border-slate-200">
            Total Logs: {{ $logs->total() }}
        </span>
    </div>

    <!-- Search / Filter -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('audit.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by description or user..." class="w-full text-xs px-3.5 py-2 border border-slate-300 rounded-xl">
            </div>
            <div>
                <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition">
                    Filter Logs
                </button>
            </div>
        </form>
    </div>

    <!-- Audit Logs Table (Req 21) -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-xs">
                <thead class="bg-slate-50 font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-6 py-4">Timestamp</th>
                        <th class="px-6 py-4">Actor</th>
                        <th class="px-6 py-4">Action</th>
                        <th class="px-6 py-4">Entity</th>
                        <th class="px-6 py-4">Description</th>
                        <th class="px-6 py-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($logs as $log)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-slate-500 whitespace-nowrap">
                                {{ $log->created_at->format('d M Y H:i:s') }}
                            </td>
                            <td class="px-6 py-4 font-bold text-slate-900">
                                {{ $log->user ? $log->user->name : 'System' }}
                                @if($log->user)
                                    <span class="block text-[10px] text-slate-400 font-normal">({{ ucfirst($log->user->role) }})</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-100 text-slate-700">
                                    {{ $log->action }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $log->entity_type }} #{{ $log->entity_id }}
                            </td>
                            <td class="px-6 py-4 text-slate-700 max-w-md">
                                {{ $log->description }}
                            </td>
                            <td class="px-6 py-4 text-slate-400 font-mono text-[11px]">
                                {{ $log->ip_address ?? '127.0.0.1' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-slate-400">
                                No audit records logged.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($logs->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
