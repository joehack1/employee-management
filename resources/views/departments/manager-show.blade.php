@extends('layouts.app')

@section('title', $department->name . ' Department - LeaveFlow')

@section('content')
<div class="max-w-[1920px] mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6">
    <a href="{{ route('dashboard') }}" class="inline-flex items-center gap-2 text-xs font-semibold text-slate-500 hover:text-slate-800">
        <span aria-hidden="true">&larr;</span> Back to Manager Dashboard
    </a>

    <section class="bg-white rounded-3xl border border-slate-200 p-6 sm:p-8 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-3">
                    <span class="px-2.5 py-1 rounded-lg bg-blue-600 text-white text-xs font-bold">{{ $department->code }}</span>
                    <h1 class="text-2xl font-bold text-slate-900">{{ $department->name }}</h1>
                </div>
                <p class="mt-2 text-sm text-slate-500">{{ $department->description ?: 'Department overview and assigned staff.' }}</p>
            </div>
            <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-semibold {{ $department->is_active ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                {{ $department->is_active ? 'Active department' : 'Inactive department' }}
            </span>
        </div>
    </section>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">Active employees</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $activeEmployeesCount }}</p></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">Total employees</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $department->employees_count }}</p></div>
        <div class="bg-white p-5 rounded-2xl border border-slate-200"><p class="text-xs text-slate-500">Teams</p><p class="mt-2 text-3xl font-bold text-slate-900">{{ $department->teams->count() }}</p></div>
    </div>

    <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100">
            <h2 class="font-bold text-slate-900">Department employees</h2>
            <p class="mt-1 text-xs text-slate-500">Active and inactive employees assigned to {{ $department->name }}.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Employee</th>
                        <th class="px-6 py-3">Position</th>
                        <th class="px-6 py-3">Team</th>
                        <th class="px-6 py-3">Manager</th>
                        <th class="px-6 py-3">Team Lead</th>
                        <th class="px-6 py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($department->employees->sortBy('first_name') as $employee)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-900">{{ $employee->full_name }}</p>
                                <p class="mt-0.5 text-xs text-slate-500">{{ $employee->employee_number }}</p>
                            </td>
                            <td class="px-6 py-4 text-slate-600">{{ $employee->job_title ?: '—' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $employee->team?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $employee->manager?->name ?? '—' }}</td>
                            <td class="px-6 py-4 text-slate-600">{{ $employee->teamLead?->name ?? '—' }}</td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 rounded-full text-xs font-semibold {{ $employee->employment_status === 'active' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                                    {{ ucfirst($employee->employment_status) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-6 py-10 text-center text-sm text-slate-400">No employees are assigned to this department.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    @if($department->teams->isNotEmpty())
        <section class="bg-white rounded-3xl border border-slate-200 shadow-xs overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100">
                <h2 class="font-bold text-slate-900">Department teams</h2>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4 p-6">
                @foreach($department->teams as $team)
                    <div class="rounded-2xl border border-slate-200 p-4">
                        <h3 class="font-semibold text-slate-900">{{ $team->name }}</h3>
                        <p class="mt-1 text-xs text-slate-500">Team Lead: {{ $team->leader?->name ?? 'Not assigned' }}</p>
                        <p class="mt-2 text-xs font-semibold text-teal-700">{{ $team->employees->where('employment_status', 'active')->count() }} active members</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</div>
@endsection
