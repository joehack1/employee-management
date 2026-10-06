@extends('layouts.app')

@section('title', 'Departments & Teams - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ openDeptModal: false, openTeamModal: false, targetDeptId: '' }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Departments & Teams Hierarchy</h1>
            <p class="text-xs text-slate-500">Configure organizational units, sub-teams, and team leadership structure</p>
        </div>
        <div class="flex items-center gap-2">
            <button @click="openDeptModal = true" class="px-4 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs">
                + New Department
            </button>
        </div>
    </div>

    <!-- Departments & Teams Tree -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach($departments as $dept)
            <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
                <div class="flex items-start justify-between border-b border-slate-100 pb-3">
                    <div>
                        <div class="flex items-center gap-2">
                            <span class="px-2 py-0.5 rounded font-bold text-[10px] bg-blue-50 text-blue-700 border border-blue-200">{{ $dept->code }}</span>
                            <h2 class="text-base font-bold text-slate-900">{{ $dept->name }}</h2>
                        </div>
                        <p class="text-xs text-slate-500 mt-1">{{ $dept->description ?? 'No description' }}</p>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-bold text-slate-700">{{ $dept->employees->count() }} staff</span>
                    </div>
                </div>

                <!-- Sub-teams (Requirement 24) -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Sub-Teams</span>
                        <button @click="openTeamModal = true; targetDeptId = '{{ $dept->id }}'" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                            + Add Team
                        </button>
                    </div>

                    <div class="space-y-2">
                        @forelse($dept->teams as $team)
                            <div class="p-3 rounded-2xl bg-slate-50 border border-slate-100 flex items-center justify-between text-xs">
                                <div>
                                    <p class="font-bold text-slate-800">{{ $team->name }}</p>
                                    @if($team->leader)
                                        <p class="text-[10px] text-slate-400">Lead: <span class="font-semibold text-slate-600">{{ $team->leader->name }}</span></p>
                                    @endif
                                </div>
                                <span class="text-[10px] px-2 py-0.5 rounded bg-white text-slate-600 border border-slate-200">
                                    {{ $team->employees->count() }} members
                                </span>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic py-2">No sub-teams created under this department yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Create Department Modal -->
    <div x-show="openDeptModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openDeptModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Add New Department</h3>
            <form action="{{ route('departments.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Department Name</label>
                    <input type="text" name="name" required placeholder="e.g. Operations" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Code (e.g. OPS, ICT, FIN)</label>
                    <input type="text" name="code" required placeholder="OPS" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs uppercase">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openDeptModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">Save Department</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Create Team Modal -->
    <div x-show="openTeamModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openTeamModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Add Sub-Team</h3>
            <form action="{{ route('teams.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <input type="hidden" name="department_id" :value="targetDeptId">
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Team Name</label>
                    <input type="text" name="name" required placeholder="e.g. Software Development" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Team Leader</label>
                    <select name="leader_id" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                        <option value="">-- Optional Team Leader --</option>
                        @foreach($teamLeads as $lead)
                            <option value="{{ $lead->id }}">{{ $lead->name }} ({{ ucfirst($lead->role) }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openTeamModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">Save Team</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
