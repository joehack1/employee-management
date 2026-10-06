@extends('layouts.app')

@section('title', 'Leave Calendar - LeaveFlow')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 space-y-6" x-data="leaveCalendar()">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Organization Leave Calendar</h1>
            <p class="text-xs text-slate-500">Visual schedule of approved employee absences, holidays, and team coverage</p>
        </div>
        
        <!-- Month Navigation -->
        <div class="flex items-center gap-2">
            <button @click="prevMonth()" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </button>
            <span class="text-sm font-bold text-slate-800 px-3 py-1 bg-white border border-slate-200 rounded-xl min-w-[140px] text-center" x-text="monthName + ' ' + currentYear"></span>
            <button @click="nextMonth()" class="p-2 rounded-xl border border-slate-200 hover:bg-slate-100 text-slate-600 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </button>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-2xl border border-slate-200 shadow-xs flex flex-wrap items-center justify-between gap-4">
        <div class="flex flex-wrap items-center gap-3">
            @if(auth()->user()->isHr() || auth()->user()->isManager())
                <select x-model="selectedDepartment" @change="fetchEvents()" class="text-xs px-3 py-2 border border-slate-300 rounded-xl">
                    <option value="">All Departments</option>
                    @foreach($departments as $dept)
                        <option value="{{ $dept->id }}">{{ $dept->name }}</option>
                    @endforeach
                </select>
            @endif

            <select x-model="selectedTeam" @change="fetchEvents()" class="text-xs px-3 py-2 border border-slate-300 rounded-xl">
                <option value="">All Teams</option>
                @foreach($teams as $team)
                    <option value="{{ $team->id }}">{{ $team->name }}</option>
                @endforeach
            </select>
        </div>

        <!-- Legend -->
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-600">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span> Annual</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-emerald-500"></span> Sick</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span> Emergency</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span> Public Holiday</span>
        </div>
    </div>

    <!-- Calendar Grid (Req 13) -->
    <div class="bg-white rounded-3xl shadow-xs border border-slate-200 overflow-hidden">
        <!-- Days of Week Header -->
        <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50 text-center text-xs font-bold text-slate-600 py-3 uppercase tracking-wider">
            <div>Sun</div>
            <div>Mon</div>
            <div>Tue</div>
            <div>Wed</div>
            <div>Thu</div>
            <div>Fri</div>
            <div>Sat</div>
        </div>

        <!-- Calendar Days Grid -->
        <div class="grid grid-cols-7 auto-rows-fr divide-x divide-y divide-slate-100 text-xs">
            <template x-for="day in calendarDays" :key="day.dateString">
                <div class="min-h-[110px] p-2 hover:bg-slate-50/60 transition flex flex-col justify-between"
                     :class="{ 'bg-slate-50/40 text-slate-300': !day.isCurrentMonth, 'bg-blue-50/20 font-bold': day.isToday }">
                    <div class="flex items-center justify-between">
                        <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-xs"
                              :class="day.isToday ? 'bg-blue-600 text-white font-extrabold' : 'text-slate-700'"
                              x-text="day.dayNumber"></span>
                        <span x-show="day.events.length > 0" class="text-[10px] text-slate-400 font-medium" x-text="day.events.length + ' on leave'"></span>
                    </div>

                    <!-- Events on this day -->
                    <div class="mt-1.5 space-y-1 overflow-y-auto max-h-20">
                        <template x-for="ev in day.events" :key="ev.id">
                            <a :href="ev.url ? ev.url : '#'"
                               class="block px-2 py-1 rounded-lg text-[10px] truncate font-medium text-white shadow-xs hover:opacity-90 transition"
                               :style="'background-color: ' + ev.color"
                               :title="ev.title + ' (' + ev.start + ' to ' + ev.end + ')'">
                                <span x-text="ev.title"></span>
                            </a>
                        </template>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>

<script>
function leaveCalendar() {
    return {
        currentMonth: {{ $month }},
        currentYear: {{ $year }},
        selectedDepartment: '',
        selectedTeam: '',
        events: [],
        calendarDays: [],

        get monthName() {
            const date = new Date(this.currentYear, this.currentMonth - 1, 1);
            return date.toLocaleString('default', { month: 'long' });
        },

        init() {
            this.fetchEvents();
        },

        prevMonth() {
            if (this.currentMonth === 1) {
                this.currentMonth = 12;
                this.currentYear--;
            } else {
                this.currentMonth--;
            }
            this.fetchEvents();
        },

        nextMonth() {
            if (this.currentMonth === 12) {
                this.currentMonth = 1;
                this.currentYear++;
            } else {
                this.currentMonth++;
            }
            this.fetchEvents();
        },

        fetchEvents() {
            const url = `{{ route('calendar.events') }}?month=${this.currentMonth}&year=${this.currentYear}&department_id=${this.selectedDepartment}&team_id=${this.selectedTeam}`;
            fetch(url)
                .then(res => res.json())
                .then(data => {
                    this.events = data;
                    this.buildCalendarGrid();
                });
        },

        buildCalendarGrid() {
            const firstDayIndex = new Date(this.currentYear, this.currentMonth - 1, 1).getDay();
            const lastDay = new Date(this.currentYear, this.currentMonth, 0).getDate();
            const prevLastDay = new Date(this.currentYear, this.currentMonth - 1, 0).getDate();
            const todayStr = new Date().toISOString().split('T')[0];

            const days = [];

            // Prev month padding
            for (let i = firstDayIndex; i > 0; i--) {
                const dayNum = prevLastDay - i + 1;
                const m = this.currentMonth === 1 ? 12 : this.currentMonth - 1;
                const y = this.currentMonth === 1 ? this.currentYear - 1 : this.currentYear;
                const dStr = `${y}-${String(m).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
                days.push({
                    dayNumber: dayNum,
                    dateString: dStr,
                    isCurrentMonth: false,
                    isToday: dStr === todayStr,
                    events: this.getEventsForDate(dStr),
                });
            }

            // Current month days
            for (let i = 1; i <= lastDay; i++) {
                const dStr = `${this.currentYear}-${String(this.currentMonth).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                days.push({
                    dayNumber: i,
                    dateString: dStr,
                    isCurrentMonth: true,
                    isToday: dStr === todayStr,
                    events: this.getEventsForDate(dStr),
                });
            }

            // Next month padding to fill grid
            const totalCells = days.length;
            const remaining = (7 - (totalCells % 7)) % 7;
            for (let i = 1; i <= remaining; i++) {
                const m = this.currentMonth === 12 ? 1 : this.currentMonth + 1;
                const y = this.currentMonth === 12 ? this.currentYear + 1 : this.currentYear;
                const dStr = `${y}-${String(m).padStart(2, '0')}-${String(i).padStart(2, '0')}`;
                days.push({
                    dayNumber: i,
                    dateString: dStr,
                    isCurrentMonth: false,
                    isToday: dStr === todayStr,
                    events: this.getEventsForDate(dStr),
                });
            }

            this.calendarDays = days;
        },

        getEventsForDate(dateStr) {
            return this.events.filter(ev => {
                return dateStr >= ev.start && dateStr <= ev.end;
            });
        }
    }
}
</script>
@endsection
