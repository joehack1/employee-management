@extends('layouts.app')

@section('title', 'Edit Employee - ' . $employee->full_name)

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{ openResetModal: false }">
    <div class="flex items-center justify-between">
        <div>
            <a href="{{ route('employees.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                &larr; Back to Directory
            </a>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-2">Edit Employee: {{ $employee->full_name }}</h1>
        </div>
        <button @click="openResetModal = true" class="px-4 py-2 rounded-xl border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-semibold">
            Reset Password
        </button>
    </div>

    <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-xs border border-slate-200">
        <form action="{{ route('employees.update', $employee->id) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Identity Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Employee # <span class="text-rose-500">*</span></label>
                    <input type="text" name="employee_number" required value="{{ old('employee_number', $employee->employee_number) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">First Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="first_name" required value="{{ old('first_name', $employee->first_name) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Last Name <span class="text-rose-500">*</span></label>
                    <input type="text" name="last_name" required value="{{ old('last_name', $employee->last_name) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
            </div>

            <!-- Contact Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Work Email <span class="text-rose-500">*</span></label>
                    <input type="email" name="email" required value="{{ old('email', $employee->email) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Phone Number</label>
                    <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
            </div>

            <!-- Position & Department Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Job Title / Position <span class="text-rose-500">*</span></label>
                    <input type="text" name="job_title" required value="{{ old('job_title', $employee->job_title) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Department</label>
                    <select name="department_id" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        <option value="">-- Select Department --</option>
                        @foreach($departments as $dept)
                            <option value="{{ $dept->id }}" {{ old('department_id', $employee->department_id) == $dept->id ? 'selected' : '' }}>{{ $dept->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Team</label>
                    <select name="team_id" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        <option value="">-- Select Team --</option>
                        @foreach($teams as $team)
                            <option value="{{ $team->id }}" {{ old('team_id', $employee->team_id) == $team->id ? 'selected' : '' }}>{{ $team->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Reporting Structure -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Direct Team Lead</label>
                    <select name="team_lead_id" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        <option value="">-- Select Team Lead --</option>
                        @foreach($teamLeads as $lead)
                            <option value="{{ $lead->id }}" {{ old('team_lead_id', $employee->team_lead_id) == $lead->id ? 'selected' : '' }}>{{ $lead->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Reporting Manager</label>
                    <select name="manager_id" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        <option value="">-- Select Manager --</option>
                        @foreach($teamLeads->where('role', 'manager') as $manager)
                            <option value="{{ $manager->id }}" {{ old('manager_id', $employee->manager_id) == $manager->id ? 'selected' : '' }}>{{ $manager->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <!-- Employment Details -->
            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 p-4 rounded-2xl bg-slate-50 border border-slate-200">
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Date Joined</label>
                    <input type="date" name="date_employed" required value="{{ old('date_employed', $employee->date_employed->format('Y-m-d')) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Leave Policy</label>
                    <select name="leave_policy_id" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        @foreach($policies as $p)
                            <option value="{{ $p->id }}" {{ old('leave_policy_id', $employee->leave_policy_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Annual Days</label>
                    <input type="number" step="0.5" name="annual_entitlement" required value="{{ old('annual_entitlement', $employee->annual_entitlement) }}" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm font-bold text-blue-700">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Employment Status</label>
                    <select name="employment_status" class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                        <option value="active" {{ $employee->employment_status === 'active' ? 'selected' : '' }}>Active</option>
                        <option value="probation" {{ $employee->employment_status === 'probation' ? 'selected' : '' }}>Probation</option>
                        <option value="deactivated" {{ $employee->employment_status === 'deactivated' ? 'selected' : '' }}>Deactivated</option>
                    </select>
                </div>
            </div>

            <!-- Role Selection -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider">System Role <span class="text-rose-500">*</span></label>
                <select name="role" required class="mt-1 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl text-xs sm:text-sm">
                    <option value="employee" {{ $employee->user->role === 'employee' ? 'selected' : '' }}>Employee</option>
                    <option value="team_lead" {{ $employee->user->role === 'team_lead' ? 'selected' : '' }}>Team Lead</option>
                    <option value="hr" {{ $employee->user->role === 'hr' ? 'selected' : '' }}>HR Manager</option>
                    <option value="admin" {{ $employee->user->role === 'admin' ? 'selected' : '' }}>Administrator</option>
                    <option value="manager" {{ $employee->user->role === 'manager' ? 'selected' : '' }}>Manager</option>
                </select>
            </div>

            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('employees.index') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold">
                    Cancel
                </a>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition">
                    Save Changes
                </button>
            </div>
        </form>
    </div>

    <!-- Password Reset Modal -->
    <div x-show="openResetModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openResetModal = false" class="bg-white rounded-2xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Reset Employee Password</h3>
            <form action="{{ route('employees.resetPassword', $employee->id) }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">New Password</label>
                    <input type="password" name="password" required minlength="6" placeholder="••••••••" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Confirm Password</label>
                    <input type="password" name="password_confirmation" required minlength="6" placeholder="••••••••" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openResetModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
