<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PublicHoliday;
use App\Models\WorkingDay;
use Illuminate\Http\Request;

class PublicHolidayController extends Controller
{
    public function index()
    {
        $holidays = PublicHoliday::orderBy('date')->get();
        $workingDays = WorkingDay::orderBy('day_of_week')->get();

        return view('holidays.index', compact('holidays', 'workingDays'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'date' => 'required|date',
            'type' => 'required|in:public_holiday,company_shutdown',
            'is_recurring' => 'nullable|boolean',
        ]);

        $validated['is_recurring'] = $request->boolean('is_recurring');

        $holiday = PublicHoliday::create($validated);
        AuditLog::log('holiday_created', 'PublicHoliday', $holiday->id, "Added public holiday {$holiday->name} on {$holiday->date}");

        return back()->with('success', "Public holiday {$holiday->name} added.");
    }

    public function destroy($id)
    {
        $holiday = PublicHoliday::findOrFail($id);
        $name = $holiday->name;
        $holiday->delete();

        AuditLog::log('holiday_deleted', 'PublicHoliday', $id, "Deleted public holiday {$name}");

        return back()->with('success', "Public holiday {$name} removed.");
    }

    public function updateWorkingDays(Request $request)
    {
        $activeDays = $request->input('working_days', []); // array of day_of_week ints

        for ($i = 0; $i <= 6; $i++) {
            WorkingDay::where('day_of_week', $i)->update([
                'is_working_day' => in_array($i, [1, 2, 3, 4, 5], true)
                    && in_array((string) $i, $activeDays, true),
            ]);
        }

        AuditLog::log('working_days_updated', 'WorkingDay', 0, "Updated organizational working days configuration");

        return back()->with('success', 'Working calendar updated successfully.');
    }
}
