@extends('layouts.app')

@section('title', 'Apply for Leave - LeaveFlow')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6">
        <a href="{{ route('dashboard') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
            &larr; Back to Dashboard
        </a>
        <h1 class="text-2xl font-bold text-slate-900 tracking-tight mt-2">Apply for Leave</h1>
        <p class="text-xs text-slate-500">Working days, holidays, half-days, and team schedules are evaluated in real time.</p>
    </div>

    <div class="bg-white rounded-3xl p-6 sm:p-10 shadow-xs border border-slate-200"
         x-data="leaveApplicationForm(annualLeaveCalendarConfig)">
        <form action="{{ route('leave.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf

            @if($errors->has('general'))
                <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700" role="alert">
                    {{ $errors->first('general') }}
                </div>
            @endif

            <!-- Leave Type Selector -->
            <div>
                <label for="leave_type_id" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Leave Type <span class="text-rose-500">*</span></label>
                <select id="leave_type_id" name="leave_type_id" required x-model="leaveTypeId" @change="onLeaveTypeChange()" class="mt-1.5 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">-- Select Leave Type --</option>
                    @foreach($leaveTypes as $lt)
                        @php
                            $bal = $balances->get($lt->id);
                            $avail = $bal ? $bal->available_days : $lt->days_allowed;
                        @endphp
                        <option value="{{ $lt->id }}"
                                data-code="{{ $lt->code }}"
                                data-requires-attachment="{{ $lt->requires_attachment ? '1' : '0' }}"
                                data-has-document-rule="{{ ($lt->isMedicalLeave() || $lt->isMaternityLeave() || $lt->requires_attachment) ? '1' : '0' }}"
                                data-requires-reason="{{ $lt->requires_reason ? '1' : '0' }}"
                                data-is-emergency="{{ $lt->is_emergency_type ? '1' : '0' }}"
                                {{ old('leave_type_id', request('leave_type_id')) == $lt->id ? 'selected' : '' }}>
                            {{ $lt->name }} (Available: {{ $avail }} days)
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Annual Leave Calendar Picker -->
            <div x-show="isAnnualLeave" x-cloak class="max-w-lg mx-auto rounded-3xl border border-teal-200 bg-gradient-to-br from-teal-50 to-white p-3 sm:p-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-5">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900">Choose your annual leave dates</h2>
                        <p class="mt-1 text-xs text-slate-500">Without Emergency, annual leave can start today or within the next 2 days. Check Emergency for past dates or dates further ahead. Choose any later end date.</p>
                    </div>
                    <div class="flex items-center gap-1.5">
                        <button type="button" @click="changeCalendarMonth(-1)" aria-label="Previous month" class="h-8 w-8 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-teal-50">&lsaquo;</button>
                        <span class="min-w-36 text-center text-sm font-bold text-slate-800" x-text="calendarMonthLabel()"></span>
                        <button type="button" @click="changeCalendarMonth(1)" aria-label="Next month" class="h-8 w-8 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-teal-50">&rsaquo;</button>
                    </div>
                </div>

                <div class="grid grid-cols-7 gap-1.5 sm:gap-2 text-center">
                    <template x-for="weekday in weekdayLabels" :key="weekday">
                        <div class="py-2 text-[10px] sm:text-xs font-bold uppercase tracking-wider text-slate-400" x-text="weekday"></div>
                    </template>
                    <template x-for="cell in calendarCells()" :key="cell.key">
                        <button type="button"
                                @click="selectCalendarDate(cell.date)"
                                :disabled="!canSelectCalendarDate(cell.date)"
                                :class="calendarDayClasses(cell.date)"
                                :title="calendarDayTitle(cell.date)"
                                class="mx-auto h-8 w-8 sm:h-9 sm:w-9 rounded-lg text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-teal-500"
                                x-text="cell.day"></button>
                    </template>
                </div>

                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 text-[11px] font-medium text-slate-600">
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-[#1d9692]"></span>Approved leave</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-[#a01e22]"></span>Pending leave</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-slate-950"></span>Today</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded bg-teal-800"></span>Your selection</span>
                    <span class="inline-flex items-center gap-2"><span class="h-3 w-3 rounded border border-amber-200 bg-amber-50"></span>Public holiday</span>
                </div>

                <div class="mt-4 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-xs text-slate-600">
                    <span class="font-semibold text-slate-800">Selected:</span>
                    <span x-text="startDate ? formatCalendarDate(startDate) : 'Choose a start date'"></span>
                    <span x-show="startDate && !endDate" class="ml-1 text-slate-400">— choose an end date</span>
                    <span x-show="endDate"> to <span x-text="formatCalendarDate(endDate)"></span></span>
                    <button type="button" x-show="startDate || endDate" @click="clearCalendarSelection()" class="ml-3 font-semibold text-teal-700 hover:text-teal-900">Clear</button>
                    <span x-show="calendarSelectionError" x-text="calendarSelectionError" class="mt-2 block font-semibold text-rose-700"></span>
                </div>
                @error('start_date')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
                @error('end_date')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <!-- Date Range Pickers for other leave types; remain form fields for annual calendar -->
            <div x-show="!isAnnualLeave" x-cloak class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="start_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">Start Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="start_date" name="start_date" :required="!isAnnualLeave" x-model="startDate" @change="onStartDateChange()" value="{{ old('start_date', $defaultLeaveDate) }}" class="mt-1.5 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div>
                    <label for="end_date" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">End Date <span class="text-rose-500">*</span></label>
                    <input type="date" id="end_date" name="end_date" :required="!isAnnualLeave" x-model="endDate" @change="recalculate()" value="{{ old('end_date', $defaultLeaveDate) }}" class="mt-1.5 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs text-sm focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>

            <!-- Half-Day & Emergency Checkboxes Grid -->
            <div class="p-4 rounded-2xl bg-slate-50 border border-slate-200/80 space-y-4">
                <!-- Half Day Option (Req 4) -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex items-center">
                        <input id="is_half_day" name="is_half_day" type="checkbox" value="1" x-model="isHalfDay" @change="recalculate()" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-slate-300 rounded">
                        <label for="is_half_day" class="ml-2 block text-xs font-semibold text-slate-800">
                            Apply as Half Day (0.5 days)
                        </label>
                    </div>

                    <!-- Half Day Session Selector -->
                    <div x-show="isHalfDay" class="flex items-center gap-4 text-xs font-medium text-slate-700">
                        <label class="inline-flex items-center">
                            <input type="radio" name="half_day_type" value="morning" x-model="halfDayType" @change="recalculate()" class="text-blue-600 focus:ring-blue-500" checked>
                            <span class="ml-1.5">Morning Session</span>
                        </label>
                        <label class="inline-flex items-center">
                            <input type="radio" name="half_day_type" value="afternoon" x-model="halfDayType" @change="recalculate()" class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-1.5">Afternoon Session</span>
                        </label>
                    </div>
                </div>

                <hr class="border-slate-200">

                <!-- Requirement 3 & 27: Emergency Leave Exception -->
                <div class="flex items-start">
                    <input id="is_emergency" name="is_emergency" type="checkbox" value="1" x-model="isEmergency" @change="recalculate()" class="h-4 w-4 text-amber-600 focus:ring-amber-500 border-slate-300 rounded mt-0.5">
                    <div class="ml-2">
                        <label for="is_emergency" class="block text-xs font-bold text-slate-800">
                            Emergency Leave Exception?
                        </label>
                        <p class="text-[11px] text-slate-500">
                            For annual leave, check this to request a past start date or a start date more than 2 days away. Emergency requests bypass the normal date window and are routed with high priority.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Live leave day estimate and validation details -->
            <div x-show="calcResult !== null" x-cloak class="rounded-xl border border-slate-200 border-l-4 border-l-[#1d9692] bg-white p-4">
                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-baseline gap-2">
                        <span class="text-xs font-medium text-slate-500">Leave day estimate</span>
                        <span class="text-lg font-semibold text-slate-900" x-text="calcResult ? calcResult.total_days : '0.0'"></span>
                        <span class="text-xs text-slate-500">days</span>
                    </div>
                    <div class="text-xs text-slate-500 sm:text-right">
                        <span x-text="calcResult ? calcResult.working_days_count : '0'"></span> working days
                        <span class="mx-1 text-slate-300">·</span>
                        <span x-text="(calcResult ? calcResult.weekend_days_count : 0) + ' weekends, ' + (calcResult ? calcResult.holiday_days_count : 0) + ' public holidays excluded'"></span>
                    </div>
                </div>

                <!-- Annual Leave Date Window Warning -->
                <div x-show="calcResult && !calcResult.advance_notice_valid" class="mt-3 p-3 rounded-xl bg-rose-100/80 border border-rose-300 text-xs font-medium text-rose-800 flex items-start gap-2">
                    <svg class="w-4 h-4 text-rose-600 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                    <div>
                        <p class="font-bold">❌ Annual Leave Date Window</p>
                        <p x-text="calcResult ? calcResult.advance_notice_message : ''"></p>
                        <p class="mt-1 text-[11px] text-rose-700">Check the <strong>Emergency Leave Exception</strong> box to request this start date.</p>
                    </div>
                </div>

                <!-- Team Conflicts Warning (Req 14) -->
                <div x-show="calcResult && calcResult.team_conflicts && calcResult.team_conflicts.length > 0" class="mt-3 p-3 rounded-xl bg-amber-100/80 border border-amber-300 text-xs font-medium text-amber-900 space-y-1">
                    <div class="flex items-center gap-1.5 font-bold">
                        <span>⚠️ Team Conflict Detected:</span>
                        <span x-text="calcResult.team_conflicts.length + ' teammate(s) already scheduled on leave during these dates:'"></span>
                    </div>
                    <ul class="list-disc list-inside text-amber-800 text-[11px] space-y-0.5">
                        <template x-for="c in calcResult.team_conflicts" :key="c.employee_name">
                            <li>
                                <strong x-text="c.employee_name"></strong> (<span x-text="c.leave_type"></span>, <span x-text="c.start_date + ' - ' + c.end_date"></span>)
                            </li>
                        </template>
                    </ul>
                    <p x-show="calcResult.exceeds_team_limit" class="text-[11px] font-bold text-amber-950 mt-1">
                        Notice: Maximum team leave threshold (<span x-text="calcResult.max_team_on_leave"></span>) reached. Team Lead or HR override required.
                    </p>
                </div>

                <!-- Insufficient Balance Warning (Req 5) -->
                <div x-show="calcResult && !calcResult.has_sufficient_balance" class="mt-3 p-3 rounded-xl bg-rose-100/80 border border-rose-300 text-xs font-medium text-rose-800">
                    ⚠️ <strong>Insufficient Balance:</strong> You requested <span x-text="calcResult.total_days"></span> days, but only have <span x-text="calcResult.available_balance"></span> available days.
                </div>
            </div>

            <!-- Reason / Comment Field -->
            <div>
                <label for="reason" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Reason for Leave <span x-show="isEmergency || leaveTypeRequiresReason" class="text-rose-500">*</span>
                    <span x-show="!isEmergency && !leaveTypeRequiresReason" class="normal-case font-normal text-slate-400">(Optional)</span>
                </label>
                <textarea id="reason" name="reason" rows="3" :required="isEmergency || leaveTypeRequiresReason" maxlength="2000" placeholder="Add details if you wish..." class="mt-1.5 block w-full px-3.5 py-2.5 border border-slate-300 rounded-xl shadow-xs text-sm focus:ring-blue-500 focus:border-blue-500">{{ old('reason') }}</textarea>
                <p class="mt-1 text-[11px] text-slate-500" x-show="isEmergency || leaveTypeRequiresReason">A reason is required for this request.</p>
                @error('reason')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <!-- Supporting Document Field -->
            <div>
                <label for="attachment" class="block text-xs font-bold text-slate-700 uppercase tracking-wider">
                    Supporting Attachment <span x-show="leaveTypeHasDocumentRule" class="text-rose-600 font-semibold">(Document rules apply; hand delivery is allowed)</span>
                </label>
                <div class="mt-1.5 flex items-center gap-3">
                    <input type="file" id="attachment" name="attachment" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2.5 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-xl p-1">
                </div>
                <p class="text-[11px] text-slate-500 mt-1">Attach a supporting document here, or check below if you will deliver one to HR personally.</p>
                <p class="text-[11px] text-slate-400 mt-1">Accepted formats: PDF, JPG, PNG, DOC, DOCX (Max 10MB). Uploaded files are stored securely.</p>
                <label class="mt-3 flex items-start gap-2 text-xs text-slate-700">
                    <input type="checkbox" name="manual_attachment_expected" value="1" {{ old('manual_attachment_expected') ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 text-blue-600">
                    <span>I will deliver the supporting document to HR personally.</span>
                </label>
                @error('attachment')<p class="mt-2 text-xs text-rose-600">{{ $message }}</p>@enderror
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex items-center justify-end gap-3 border-t border-slate-100">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 rounded-xl border border-slate-200 text-slate-600 hover:bg-slate-50 text-xs font-semibold transition">
                    Cancel
                </a>
                <button type="submit"
                        :disabled="(isAnnualLeave && (!startDate || !endDate)) || (calcResult && !calcResult.advance_notice_valid)"
                        class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 disabled:bg-slate-300 disabled:cursor-not-allowed text-white text-xs font-semibold shadow-md shadow-blue-500/20 transition flex items-center gap-2">
                    <span x-text="isEmergency ? 'Submit Emergency Request' : 'Submit Leave Application'"></span>
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const annualLeaveCalendarConfig = {
    existingLeaveDates: @json($existingLeaveDates),
    holidays: @json($calendarHolidays),
    workingDayNumbers: @json($workingDayNumbers),
    maxAnnualStartDate: @json($maxAnnualStartDate),
    today: @json($todayDate),
};

function leaveApplicationForm(calendarConfig = {}) {
    return {
        leaveTypeId: @json(old('leave_type_id', request('leave_type_id', ''))),
        startDate: @json(old('start_date', $defaultLeaveDate)),
        endDate: @json(old('end_date', $defaultLeaveDate)),
        isHalfDay: false,
        halfDayType: 'morning',
        isEmergency: {{ old('is_emergency') ? 'true' : 'false' }},
        isAnnualLeave: false,
        leaveTypeHasDocumentRule: false,
        leaveTypeRequiresReason: false,
        calcResult: null,
        existingLeaveDates: calendarConfig.existingLeaveDates || {},
        calendarHolidays: calendarConfig.holidays || [],
        workingDayNumbers: calendarConfig.workingDayNumbers || [1, 2, 3, 4, 5],
        maxAnnualStartDate: calendarConfig.maxAnnualStartDate || '',
        today: calendarConfig.today || '',
        calendarYear: new Date().getFullYear(),
        calendarMonth: new Date().getMonth(),
        calendarSelectionError: '',
        weekdayLabels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],

        init() {
            this.updateTypeFlags();
            const select = document.getElementById('leave_type_id');
            const selectedOption = select.options[select.selectedIndex];
            if (selectedOption?.getAttribute('data-is-emergency') === '1') {
                this.isEmergency = true;
            }
            this.setCalendarMonthFromSelection();
            this.recalculate();
        },

        calendarMonthLabel() {
            return new Intl.DateTimeFormat('en', { month: 'long', year: 'numeric' })
                .format(new Date(this.calendarYear, this.calendarMonth, 1));
        },

        calendarCells() {
            const firstDay = new Date(this.calendarYear, this.calendarMonth, 1);
            const offset = (firstDay.getDay() + 6) % 7;
            const daysInMonth = new Date(this.calendarYear, this.calendarMonth + 1, 0).getDate();
            const cells = [];

            for (let i = 0; i < offset; i++) {
                cells.push({ key: `empty-${i}`, date: null, day: '' });
            }
            for (let day = 1; day <= daysInMonth; day++) {
                const date = this.calendarDateString(new Date(this.calendarYear, this.calendarMonth, day));
                cells.push({ key: date, date, day });
            }
            while (cells.length % 7 !== 0) {
                cells.push({ key: `empty-${cells.length}`, date: null, day: '' });
            }

            return cells;
        },

        calendarDateString(date) {
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        },

        formatCalendarDate(date) {
            if (!date) return '';
            return new Intl.DateTimeFormat('en', { day: 'numeric', month: 'short', year: 'numeric' })
                .format(new Date(`${date}T12:00:00`));
        },

        setCalendarMonthFromSelection() {
            const selectedDate = this.startDate ? new Date(`${this.startDate}T12:00:00`) : new Date();
            if (!Number.isNaN(selectedDate.getTime())) {
                this.calendarYear = selectedDate.getFullYear();
                this.calendarMonth = selectedDate.getMonth();
            }
        },

        changeCalendarMonth(offset) {
            const nextMonth = new Date(this.calendarYear, this.calendarMonth + offset, 1);
            this.calendarYear = nextMonth.getFullYear();
            this.calendarMonth = nextMonth.getMonth();
        },

        isCalendarWorkday(date) {
            const dayOfWeek = new Date(`${date}T12:00:00`).getDay();
            return dayOfWeek !== 0 && dayOfWeek !== 6 && this.workingDayNumbers.includes(dayOfWeek);
        },

        isCalendarBusy(date) {
            return Boolean(date && this.existingLeaveDates[date]);
        },

        isCalendarHoliday(date) {
            return Boolean(date && this.calendarHolidays.includes(date));
        },

        rangeHasExistingLeave(start, end) {
            let cursor = new Date(`${start}T12:00:00`);
            const lastDate = new Date(`${end}T12:00:00`);
            let checked = 0;
            while (cursor <= lastDate && checked < 370) {
                if (this.isCalendarBusy(this.calendarDateString(cursor))) return true;
                cursor.setDate(cursor.getDate() + 1);
                checked++;
            }
            return false;
        },

        canSelectCalendarDate(date) {
            if (!date || this.isCalendarBusy(date) || !this.isCalendarWorkday(date)) return false;

            const beginsNewRange = !this.startDate || Boolean(this.calendarSelectionError) || date < this.startDate;
            if (beginsNewRange && !this.isEmergency && this.isOutsideAnnualStartWindow(date)) {
                return false;
            }

            return true;
        },

        isOutsideAnnualStartWindow(date) {
            return Boolean(date && (date < this.today || (this.maxAnnualStartDate && date > this.maxAnnualStartDate)));
        },

        calendarDayClasses(date) {
            if (!date) return 'invisible pointer-events-none';
            const status = this.existingLeaveDates[date];
            if (status === 'approved') return 'bg-[#1d9692] text-white cursor-not-allowed';
            if (status === 'pending') return 'bg-[#a01e22] text-white cursor-not-allowed';
            if (date === this.startDate || (this.endDate && date === this.endDate)) return 'bg-teal-800 text-white shadow-sm ring-2 ring-teal-200';
            if (this.startDate && this.endDate && date > this.startDate && date < this.endDate) return 'bg-teal-100 text-teal-900';
            if (date === this.today) return 'bg-slate-950 text-white';
            const beginsNewRange = !this.startDate || Boolean(this.calendarSelectionError) || date < this.startDate;
            if (!this.isCalendarWorkday(date) || (beginsNewRange && !this.isEmergency && this.isOutsideAnnualStartWindow(date))) {
                return 'bg-slate-50 text-slate-300 cursor-not-allowed';
            }
            if (this.isCalendarHoliday(date)) return 'bg-amber-50 text-amber-800 ring-1 ring-inset ring-amber-200 hover:bg-amber-100';
            return 'bg-white text-slate-700 hover:bg-teal-50 hover:text-teal-900';
        },

        calendarDayTitle(date) {
            if (!date) return '';
            if (this.existingLeaveDates[date] === 'approved') return 'Approved leave already exists';
            if (this.existingLeaveDates[date] === 'pending') return 'Pending leave request already exists';
            if (!this.isCalendarWorkday(date)) return 'Non-working day';
            const beginsNewRange = !this.startDate || Boolean(this.calendarSelectionError) || date < this.startDate;
            if (beginsNewRange && !this.isEmergency && this.isOutsideAnnualStartWindow(date)) return 'Check Emergency Leave Exception to request a past start date or a date more than 2 days away';
            if (this.isCalendarHoliday(date)) return 'Public holiday; excluded from leave day count';
            return 'Select date';
        },

        selectCalendarDate(date) {
            if (!this.canSelectCalendarDate(date)) return;

            const beginsNewRange = !this.startDate || Boolean(this.calendarSelectionError) || date < this.startDate;
            if (beginsNewRange) {
                this.calendarSelectionError = '';
                this.startDate = date;
                this.endDate = '';
            } else if (this.rangeHasExistingLeave(this.startDate, date)) {
                this.calendarSelectionError = 'That range overlaps another leave request. Choose a different end date.';
                return;
            } else {
                this.endDate = date;
            }

            this.recalculate();
        },

        clearCalendarSelection() {
            this.startDate = '';
            this.endDate = '';
            this.calendarSelectionError = '';
            this.calcResult = null;
        },

        onStartDateChange() {
            if (this.endDate < this.startDate) {
                this.endDate = this.startDate;
            }
            this.recalculate();
        },

        onLeaveTypeChange() {
            const select = document.getElementById('leave_type_id');
            const opt = select.options[select.selectedIndex];
            this.isEmergency = opt?.getAttribute('data-is-emergency') === '1';
            this.updateTypeFlags();
            if (this.isAnnualLeave) this.setCalendarMonthFromSelection();
            this.recalculate();
        },

        updateTypeFlags() {
            const select = document.getElementById('leave_type_id');
            const opt = select.options[select.selectedIndex];
            if (opt) {
                this.isAnnualLeave = opt.getAttribute('data-code') === 'annual';
                this.leaveTypeHasDocumentRule = opt.getAttribute('data-has-document-rule') === '1';
                this.leaveTypeRequiresReason = opt.getAttribute('data-requires-reason') === '1';
            } else {
                this.isAnnualLeave = false;
                this.leaveTypeHasDocumentRule = false;
                this.leaveTypeRequiresReason = false;
            }
        },

        recalculate() {
            this.updateTypeFlags();

            if (!this.leaveTypeId || !this.startDate || !this.endDate) {
                this.calcResult = null;
                return;
            }

            fetch("{{ route('leave.calculate') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    "Accept": "application/json",
                },
                body: JSON.stringify({
                    leave_type_id: this.leaveTypeId,
                    start_date: this.startDate,
                    end_date: this.endDate,
                    is_half_day: this.isHalfDay,
                    half_day_type: this.halfDayType,
                    is_emergency: this.isEmergency,
                })
            })
            .then(res => res.json())
            .then(data => {
                this.calcResult = data;
            })
            .catch(err => {
                console.error("Calculation error:", err);
            });
        }
    }
}
</script>
@endsection
