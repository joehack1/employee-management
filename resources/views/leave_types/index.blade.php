@extends('layouts.app')

@section('title', 'Leave Types Management - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="{ openCreateModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Leave Types Configuration</h1>
            <p class="text-xs text-slate-500">Configure entitlements, document upload rules, emergency classifications, and activation</p>
        </div>
        <button @click="openCreateModal = true" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs">
            + New Leave Type
        </button>
    </div>

    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-600 uppercase">
                    <tr>
                        <th class="px-6 py-4">Leave Type</th>
                        <th class="px-6 py-4 text-center">Standard Days</th>
                        <th class="px-6 py-4 text-center">Paid Status</th>
                        <th class="px-6 py-4 text-center">Document Rule</th>
                        <th class="px-6 py-4 text-center">Emergency Type</th>
                        <th class="px-6 py-4 text-center">Status</th>
                        <th class="px-6 py-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium text-xs">
                    @foreach($leaveTypes as $lt)
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 flex items-center gap-3">
                                <span class="w-3 h-3 rounded-full ring-1 ring-black/10" style="background-color: {{ $lt->display_color }}"></span>
                                <div>
                                    <p class="font-bold text-slate-900 text-sm">{{ $lt->name }}</p>
                                    <p class="text-[11px] text-slate-400">{{ $lt->code }} • {{ $lt->description }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center font-bold text-slate-800">
                                {{ $lt->days_allowed }} days
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $lt->is_paid ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $lt->is_paid ? 'Paid' : 'Unpaid' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($lt->requires_attachment)
                                    <span class="text-slate-700 font-medium">
                                        Mandatory @if($lt->attachment_required_after_days > 0) (>{{ $lt->attachment_required_after_days }}d) @endif
                                    </span>
                                @else
                                    <span class="text-slate-400">Optional</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($lt->is_emergency_type)
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                        🚨 Emergency
                                    </span>
                                @else
                                    <span class="text-slate-400">Standard</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $lt->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-200 text-slate-600' }}">
                                    {{ $lt->is_active ? 'Active' : 'Disabled' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a href="{{ route('leave_types.edit', $lt->id) }}" class="font-bold text-xs text-teal-700 hover:text-teal-900 mr-3">Edit</a>
                                <form action="{{ route('leave_types.toggle', $lt->id) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit" class="font-bold text-xs {{ $lt->is_active ? 'text-rose-600 hover:text-rose-800' : 'text-emerald-600 hover:text-emerald-800' }}">
                                        {{ $lt->is_active ? 'Disable' : 'Enable' }}
                                    </button>
                                </form>
                                <form action="{{ route('leave_types.destroy', $lt->id) }}" method="POST" class="inline ml-3" onsubmit="return confirm('Delete {{ addslashes($lt->name) }}? Leave types with history cannot be deleted.');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="font-bold text-xs text-rose-700 hover:text-rose-900">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create Leave Type Modal -->
    <div x-show="openCreateModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openCreateModal = false" class="bg-white rounded-3xl max-w-lg w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Create New Leave Type</h3>
            <form action="{{ route('leave_types.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Leave Type Name</label>
                        <input type="text" name="name" required placeholder="e.g. Sabbatical Leave" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Unique Code</label>
                        <input type="text" name="code" required placeholder="sabbatical" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Days Allowed</label>
                        <input type="number" step="0.5" name="days_allowed" required value="10" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Color Tag</label>
                        <input type="color" name="color" value="{{ old('color', '#1d9692') }}" required class="mt-1 block h-10 w-full cursor-pointer rounded-xl border border-slate-300 bg-white p-1">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Is Paid?</label>
                        <select name="is_paid" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                            <option value="1">Paid Leave</option>
                            <option value="0">Unpaid Leave</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Emergency Type?</label>
                        <select name="is_emergency_type" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                            <option value="0">No (Standard)</option>
                            <option value="1">Yes (Bypasses 3-day notice)</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Requires Attachment?</label>
                        <select name="requires_attachment" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                            <option value="0">No / Optional</option>
                            <option value="1">Yes (Mandatory)</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700">Required After (Days)</label>
                        <input type="number" name="attachment_required_after_days" value="0" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-700">Description</label>
                    <textarea name="description" rows="2" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs"></textarea>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openCreateModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">Save Leave Type</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
