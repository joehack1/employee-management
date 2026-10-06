@extends('layouts.app')

@section('title', 'My Profile & Security - LeaveFlow')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8">
    <div>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">My Profile & Security</h1>
        <p class="text-xs text-slate-500">View employment details, update contact information, and manage your account password</p>
    </div>

    <!-- Requirement 1: Employee Profile Information Card -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200 space-y-6">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-4">
                @if($user->avatar)
                    <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }} profile picture" class="w-14 h-14 rounded-2xl object-cover shadow-md">
                @else
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-tr from-blue-600 to-indigo-600 text-white font-bold text-xl flex items-center justify-center shadow-md">
                        {{ substr($user->name, 0, 1) }}
                    </div>
                @endif
                <div>
                    <h2 class="text-lg font-bold text-slate-900">{{ $user->name }}</h2>
                    <p class="text-xs text-slate-500">{{ $user->email }}</p>
                </div>
            </div>
            <span class="px-3 py-1 rounded-xl text-xs font-semibold uppercase tracking-wider bg-slate-100 text-slate-700">
                {{ str_replace('_', ' ', $user->role) }}
            </span>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 text-xs">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Employee Number</span>
                <span class="text-sm font-bold text-slate-900">{{ $employee->employee_number ?? $user->employee_number ?? 'N/A' }}</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Department</span>
                <span class="text-sm font-bold text-slate-900">{{ $employee->department->name ?? 'General' }}</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Job Title</span>
                <span class="text-sm font-bold text-slate-900">{{ $employee->job_title ?? 'Employee' }}</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Date Employed</span>
                <span class="text-sm font-bold text-slate-900">{{ $employee ? $employee->date_employed->format('d M Y') : 'N/A' }}</span>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-xs pt-2">
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">Team Lead</span>
                <span class="text-sm font-bold text-slate-900">{{ $employee && $employee->teamLead ? $employee->teamLead->name : 'N/A' }}</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-100">
                <span class="text-slate-400 font-semibold block uppercase text-[10px]">HR Manager</span>
                <span class="text-sm font-bold text-slate-900">{{ $employee && $employee->manager ? $employee->manager->name : 'N/A' }}</span>
            </div>
            <div class="p-3.5 rounded-2xl bg-blue-50/70 border border-blue-200">
                <span class="text-blue-600 font-semibold block uppercase text-[10px]">Annual Leave Entitlement</span>
                <span class="text-base font-extrabold text-blue-700">{{ $employee ? $employee->annual_entitlement : 21 }} days/year</span>
            </div>
        </div>

        <!-- Update Phone -->
        <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data" class="pt-4 border-t border-slate-100 grid grid-cols-1 sm:grid-cols-2 gap-4">
            @csrf
            <div class="flex-1">
                <label class="block text-xs font-semibold text-slate-700">Phone Contact</label>
                <input type="text" name="phone" value="{{ old('phone', $user->phone) }}" placeholder="+254 700 000 000" class="mt-1 block w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700">Profile picture <span class="font-normal text-slate-400">(optional, up to 2 MB)</span></label>
                <input type="file" name="avatar" accept="image/*" class="mt-1 block w-full text-xs text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-3 file:py-2 file:font-semibold file:text-blue-700">
                @error('avatar')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div class="sm:col-span-2">
                <button type="submit" class="px-5 py-2 bg-slate-800 hover:bg-slate-900 text-white font-semibold text-xs rounded-xl shadow-xs transition">
                    Update Profile
                </button>
            </div>
        </form>
    </div>

    <!-- Requirement 1: Change Password Form -->
    <div class="bg-white rounded-3xl p-6 sm:p-8 shadow-xs border border-slate-200 space-y-4">
        <div>
            <h2 class="text-base font-bold text-slate-900">Change Account Password</h2>
            <p class="text-xs text-slate-500">Ensure your account is protected with a strong credentials</p>
        </div>

        <form action="{{ route('profile.password') }}" method="POST" class="space-y-4 max-w-lg">
            @csrf
            <div>
                <label class="block text-xs font-semibold text-slate-700">Current Password</label>
                <input type="password" name="current_password" required placeholder="••••••••" class="mt-1 block w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700">New Password</label>
                <input type="password" name="password" required minlength="6" placeholder="••••••••" class="mt-1 block w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-700">Confirm New Password</label>
                <input type="password" name="password_confirmation" required minlength="6" placeholder="••••••••" class="mt-1 block w-full px-3.5 py-2 border border-slate-300 rounded-xl text-xs">
            </div>
            <div>
                <button type="submit" class="px-5 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold text-xs rounded-xl shadow-xs transition">
                    Update Password
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
