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
            ->whereIn('status', ['pending_team_lead', 'pending_hr'])
            ->where('start_date', '<=', $today)
            ->where('end_date', '>=', $today)
            ->first();

        if ($pendingLeave) {
            return 'pending_leave';
        }

        return 'working';
    }
}
