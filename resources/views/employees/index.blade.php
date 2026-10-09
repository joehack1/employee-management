@extends('layouts.app')

@section('title', 'Employee Directory - LeaveFlow')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Employee Directory</h1>
            <p class="text-xs text-slate-500">Manage organizational staff, assignments, leave policies, and account access</p>
        </div>
        <a href="{{ route('employees.create') }}" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center gap-1.5 self-start sm:self-auto">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Add New Employee
        </a>
    </div>

    <!-- Filter & Search Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs">
        <form method="GET" action="{{ route('employees.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-3">
            <div class="sm:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Search by name, employee #, or email..." class="w-full text-xs px-3.5 py-2 border border-slate-300 rounded-xl">
            </div>
            <div>
                <select name="department_id" class="w-full text-xs px-3 py-2 border border-slate-300 rounded-xl" onchange="this.form.submit()">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}" {{ request('department_id') == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <button type="submit" class="w-full px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition">
                    Search
                </button>
            </div>
        </form>
    </div>

    <!-- Employee Table -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-6 py-4">Employee</th>
                        <th class="px-6 py-4">Department & Team</th>
                        <th class="px-6 py-4">Role</th>
                        <th class="px-6 py-4">Team Lead</th>
                        <th class="px-6 py-4">Availability</th>
                        <th class="px-6 py-4">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-xs">
                    @forelse($employees as $emp)
                        <tr class="employee-directory-row">
                            <td class="px-6 py-4 flex items-center gap-3">
                                @if($emp->user?->avatar)
                                    <img src="{{ asset('storage/' . $emp->user->avatar) }}" alt="{{ $emp->full_name }} profile picture" class="w-9 h-9 rounded-full object-cover">
                                @else
                                    <div class="w-9 h-9 rounded-full bg-slate-100 text-slate-800 font-bold flex items-center justify-center">
                                        {{ substr($emp->first_name, 0, 1) }}
                                    </div>
                                @endif
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ $emp->full_name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $emp->employee_number }} • {{ $emp->email }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800">{{ $emp->department->name ?? 'None' }}</p>
                                <p class="text-[11px] text-slate-400">{{ $emp->team->name ?? 'General Staff' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                <span class="capitalize px-2 py-0.5 rounded-md font-semibold {{ $emp->user->role === 'hr' ? 'bg-purple-600 text-white' : ($emp->user->role === 'team_lead' ? 'bg-amber-600 text-white' : 'bg-slate-100 text-slate-700') }}">
                                    {{ str_replace('_', ' ', $emp->user->role) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $emp->teamLead ? $emp->teamLead->name : 'N/A' }}
                            </td>
                            <td class="px-6 py-4">
                                @if($emp->current_status === 'working')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-600 text-white">
                                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Working
                                    </span>
                                @elseif($emp->current_status === 'on_leave')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-rose-600 text-white">
                                        <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span> On Leave
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-amber-600 text-white">
                                        <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span> Pending Leave
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $emp->employment_status === 'active' ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-700' }}">
                                    {{ ucfirst($emp->employment_status) }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                <a href="{{ route('employees.leaveAccount', $emp->id) }}" class="font-bold text-blue-600 hover:text-blue-800" title="Manage Leave Balances & Adjustments">
                                    Leave Ledger &rarr;
                                </a>
                                <a href="{{ route('employees.edit', $emp->id) }}" class="text-slate-500 hover:text-slate-800">
                                    Edit
                                </a>
                                <form action="{{ route('employees.toggle', $emp->id) }}" method="POST" class="inline" onsubmit="return confirm('Toggle employment status for this employee?')">
                                    @csrf
                                    <button type="submit" class="text-xs {{ $emp->employment_status === 'active' ? 'text-rose-500 hover:text-rose-700' : 'text-emerald-600 hover:text-emerald-800' }}">
                                        {{ $emp->employment_status === 'active' ? 'Deactivate' : 'Reactivate' }}
                                    </button>
                                </form>
                                <form action="{{ route('employees.destroy', $emp->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove this employee from the directory and disable their login? Their leave history will be retained.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-rose-700 hover:text-rose-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                No employees found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($employees->hasPages())
            <div class="p-4 border-t border-slate-100">
                {{ $employees->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
