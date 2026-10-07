@extends('layouts.app')

@section('title', 'Edit Leave Type - LeaveFlow')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="bg-white rounded-3xl border border-slate-200 shadow-xs p-6 sm:p-8">
        <a href="{{ route('leave_types.index') }}" class="text-xs font-semibold text-teal-700 hover:text-teal-900">&larr; Back to leave types</a>
        <h1 class="mt-3 text-2xl font-bold text-slate-900">Edit {{ $leaveType->name }}</h1>
        <p class="mt-1 text-sm text-slate-500">Update entitlement, eligibility, and document requirements.</p>

        <form action="{{ route('leave_types.update', $leaveType->id) }}" method="POST" class="mt-6 space-y-5">
            @csrf
            @method('PUT')
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <label class="text-xs font-semibold text-slate-700">Name
                    <input name="name" required maxlength="100" value="{{ old('name', $leaveType->name) }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </label>
                <label class="text-xs font-semibold text-slate-700">Code
                    <input name="code" required maxlength="30" value="{{ old('code', $leaveType->code) }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </label>
                <label class="text-xs font-semibold text-slate-700">Days allowed
                    <input type="number" name="days_allowed" required min="0" max="365" step="0.5" value="{{ old('days_allowed', $leaveType->days_allowed) }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </label>
                <label class="text-xs font-semibold text-slate-700">Display color
                    <input type="color" name="color" required value="{{ old('color', $leaveType->display_color) }}" class="mt-1 block h-11 w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-1">
                </label>
                <label class="text-xs font-semibold text-slate-700">Paid leave?
                    <select name="is_paid" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="1" {{ old('is_paid', (int) $leaveType->is_paid) == 1 ? 'selected' : '' }}>Paid</option>
                        <option value="0" {{ old('is_paid', (int) $leaveType->is_paid) == 0 ? 'selected' : '' }}>Unpaid</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Requires attachment?
                    <select name="requires_attachment" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="1" {{ old('requires_attachment', (int) $leaveType->requires_attachment) == 1 ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('requires_attachment', (int) $leaveType->requires_attachment) == 0 ? 'selected' : '' }}>No</option>
                    </select>
                </label>
                <label class="text-xs font-semibold text-slate-700">Require attachment after (days)
                    <input type="number" name="attachment_required_after_days" min="0" value="{{ old('attachment_required_after_days', $leaveType->attachment_required_after_days) }}" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                </label>
                <label class="text-xs font-semibold text-slate-700">Emergency leave type?
                    <select name="is_emergency_type" required class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">
                        <option value="1" {{ old('is_emergency_type', (int) $leaveType->is_emergency_type) == 1 ? 'selected' : '' }}>Yes</option>
                        <option value="0" {{ old('is_emergency_type', (int) $leaveType->is_emergency_type) == 0 ? 'selected' : '' }}>No</option>
                    </select>
                </label>
            </div>
            <div>
                <input type="hidden" name="requires_reason" value="0">
                <label class="flex items-start gap-2 text-xs font-semibold text-slate-700">
                    <input type="checkbox" name="requires_reason" value="1" {{ old('requires_reason', $leaveType->requires_reason) ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 text-teal-600">
                    <span>Require employees to provide a reason for this leave type</span>
                </label>
                <p class="mt-1 ml-6 text-[11px] text-slate-500">A reason is always required when an employee checks Emergency Leave Exception.</p>
            </div>
            <label class="block text-xs font-semibold text-slate-700">Description
                <textarea name="description" rows="4" class="mt-1 block w-full rounded-xl border border-slate-300 px-3 py-2.5 text-sm">{{ old('description', $leaveType->description) }}</textarea>
            </label>
            <div class="flex justify-end gap-3 border-t border-slate-100 pt-5">
                <a href="{{ route('leave_types.index') }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600">Cancel</a>
                <button class="px-5 py-2.5 rounded-xl bg-teal-600 hover:bg-teal-700 text-white text-sm font-semibold">Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
