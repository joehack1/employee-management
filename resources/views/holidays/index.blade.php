@extends('layouts.app')

@section('title', 'Holidays & Working Calendar - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-8" x-data="{ openHolidayModal: false }">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Holidays & Working Calendar</h1>
            <p class="text-xs text-slate-500">Configure weekly working schedule and statutory public holidays excluded from leave counts</p>
        </div>
        <button @click="openHolidayModal = true" class="px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold shadow-xs">
            + Add Public Holiday
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Working Days Configuration (Req 15) -->
        <div class="bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
            <div>
                <h2 class="text-base font-bold text-slate-900">Organizational Working Days</h2>
                <p class="text-xs text-slate-500">Saturdays and Sundays are always excluded from leave counts.</p>
            </div>

            <form action="{{ route('working_days.update') }}" method="POST" class="space-y-3">
                @csrf
                <div class="divide-y divide-slate-100">
                    @foreach($workingDays as $wd)
                        <label class="py-2.5 flex items-center justify-between text-xs px-2 rounded-lg transition {{ $wd->day_of_week === 0 || $wd->day_of_week === 6 ? 'opacity-60' : 'cursor-pointer hover:bg-slate-50' }}">
                            <span class="font-semibold text-slate-800">{{ $wd->name }}{{ in_array($wd->day_of_week, [0, 6], true) ? ' (non-working)' : '' }}</span>
                            <input type="checkbox" name="working_days[]" value="{{ $wd->day_of_week }}" {{ $wd->is_working_day ? 'checked' : '' }} {{ in_array($wd->day_of_week, [0, 6], true) ? 'disabled' : '' }} class="h-4 w-4 text-blue-600 focus:ring-blue-500 rounded border-slate-300">
                        </label>
                    @endforeach
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full py-2 bg-slate-800 hover:bg-slate-900 text-white text-xs font-semibold rounded-xl transition">
                        Update Working Schedule
                    </button>
                </div>
            </form>
        </div>

        <!-- Public Holidays List (Req 16) -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-6 shadow-xs border border-slate-200 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Public Holidays & Company Shutdowns</h2>
                    <p class="text-xs text-slate-500">Automatically omitted when employees apply for annual leave</p>
                </div>
                <span class="text-xs font-bold text-purple-700 bg-purple-50 px-2.5 py-1 rounded-full border border-purple-200">
                    {{ $holidays->count() }} Gazetted
                </span>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100 text-left text-xs">
                    <thead class="bg-slate-50 font-bold text-slate-600 uppercase">
                        <tr>
                            <th class="px-4 py-3">Holiday Name</th>
                            <th class="px-4 py-3">Observed Date</th>
                            <th class="px-4 py-3">Type</th>
                            <th class="px-4 py-3 text-center">Recurring?</th>
                            <th class="px-4 py-3 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse($holidays as $h)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="px-4 py-3 font-bold text-slate-900 flex items-center gap-2">
                                    <span>🎉</span> {{ $h->name }}
                                </td>
                                <td class="px-4 py-3 text-slate-700">
                                    {{ $h->date->format('l, d M Y') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $h->type === 'public_holiday' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                                        {{ ucfirst(str_replace('_', ' ', $h->type)) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center text-slate-500">
                                    {{ $h->is_recurring ? 'Annual' : 'One-time' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <form action="{{ route('holidays.destroy', $h->id) }}" method="POST" class="inline" onsubmit="return confirm('Remove this holiday?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-rose-500 hover:text-rose-700 text-xs font-semibold">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-8 text-center text-slate-400">No public holidays added.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Create Holiday Modal -->
    <div x-show="openHolidayModal" x-cloak class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 p-4">
        <div @click.outside="openHolidayModal = false" class="bg-white rounded-3xl max-w-md w-full p-6 text-left shadow-2xl">
            <h3 class="text-base font-bold text-slate-900">Add Public Holiday</h3>
            <form action="{{ route('holidays.store') }}" method="POST" class="mt-4 space-y-3">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Holiday Name</label>
                    <input type="text" name="name" required placeholder="e.g. Mashujaa Day" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Date</label>
                    <input type="date" name="date" required value="{{ date('Y-m-d') }}" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-700">Type</label>
                    <select name="type" class="mt-1 block w-full px-3 py-2 border border-slate-300 rounded-xl text-xs">
                        <option value="public_holiday">Public Holiday</option>
                        <option value="company_shutdown">Company Shutdown</option>
                    </select>
                </div>
                <div class="flex items-center gap-2 pt-1">
                    <input type="checkbox" id="is_recurring" name="is_recurring" value="1" class="h-4 w-4 text-blue-600 rounded">
                    <label for="is_recurring" class="text-xs text-slate-700">Recurs Every Year on Same Date</label>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="openHolidayModal = false" class="px-4 py-2 rounded-xl text-xs text-slate-600">Cancel</button>
                    <button type="submit" class="px-4 py-2 rounded-xl text-xs font-semibold bg-blue-600 hover:bg-blue-700 text-white">Save Holiday</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
