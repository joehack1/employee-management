<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    protected $fillable = [
        'user_id',
        'employee_number',
        'first_name',
        'last_name',
        'email',
        'phone',
        'gender',
        'department_id',
        'team_id',
        'job_title',
        'team_lead_id',
        'manager_id',
        'date_employed',
        'employment_status',
        'leave_policy_id',
        'annual_entitlement',
    ];

    protected function casts(): array
    {
        return [
            'date_employed' => 'date',
            'annual_entitlement' => 'decimal:2',
        ];
    }

    public function getFullNameAttribute(): string
    {
        return "{$this->first_name} {$this->last_name}";
    }

    public function annualLeaveEntitlement(?Carbon $asOf = null): float
    {
        $years = $this->date_employed?->diffInYears($asOf ?? Carbon::today()) ?? 0;

        return $years >= 7 ? 33.0 : ($years > 3 ? 27.0 : 21.0);
    }

    public function leaveTypesForGender($leaveTypes)
    {
        return $leaveTypes->reject(function (LeaveType $leaveType) {
            $identifier = strtolower($leaveType->code . ' ' . $leaveType->name);
            return ($this->gender === 'male' && str_contains($identifier, 'maternity'))
                || ($this->gender === 'female' && str_contains($identifier, 'paternity'));
        })->values();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function teamLead(): BelongsTo
    {
        return $this->belongsTo(User::class, 'team_lead_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public static function managerEmployeeIds(User $manager): array
    {
        $managerIds = User::query()
            ->where('role', 'manager')
            ->orderBy('id')
            ->pluck('id');

        return self::query()
            ->where(function ($query) use ($manager, $managerIds) {
                $query->where('manager_id', $manager->id);

                // Notifications fall back to the first manager for employees without
                // a valid manager assignment, so show those requests in the same queue.
                if ((int) $managerIds->first() === (int) $manager->id) {
                    $query->orWhereNull('manager_id')
                        ->orWhereNotIn('manager_id', $managerIds);
                }
            })
            ->pluck('id')
            ->all();
    }

    public function leavePolicy(): BelongsTo
    {
        return $this->belongsTo(LeavePolicy::class);
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(LeaveTransaction::class)->orderBy('id', 'desc');
    }

    public function leaveApplications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class)->orderBy('created_at', 'desc');
    }

    public static function coverageCandidatesFor(LeaveApplication $application, ?int $teamLeadId = null)
    {
        $employee = $application->employee;
        $query = self::query()
            ->with('user')
            ->where('employment_status', 'active')
            ->where('id', '!=', $employee->id)
            ->whereHas('user');

        if ($employee->team_id) {
            $query->where('team_id', $employee->team_id);
        } elseif ($employee->department_id) {
            $query->where('department_id', $employee->department_id);
        } elseif ($teamLeadId) {
            $query->where('team_lead_id', $teamLeadId);
        } else {
            return collect();
        }

        return $query->whereDoesntHave('leaveApplications', function ($applications) use ($application) {
            $applications->whereIn('status', ['approved', 'pending_team_lead', 'pending_manager', 'pending_hr'])
                ->whereDate('start_date', '<=', $application->end_date->toDateString())
                ->whereDate('end_date', '>=', $application->start_date->toDateString());
        })->orderBy('first_name')->orderBy('last_name')->get();
    }

    /**
     * Get Current Status: Working, On Leave, Pending Leave
     */
    public function getCurrentStatusAttribute(): string
    {
        $today = Carbon::today()->toDateString();

        $activeLeave = $this->leaveApplications()
            ->where('status', 'approved')
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        if ($activeLeave) {
            return 'on_leave';
        }

        $pendingLeave = $this->leaveApplications()
            ->whereIn('status', ['pending_team_lead', 'pending_manager', 'pending_hr'])
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        if ($pendingLeave) {
            return 'pending_leave';
        }

        return 'working';
    }
}
