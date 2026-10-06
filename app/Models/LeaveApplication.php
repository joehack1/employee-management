<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveApplication extends Model
{
    protected $fillable = [
        'application_number',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'total_days',
        'is_half_day',
        'half_day_type',
        'is_emergency',
        'reason',
        'doctor_hospital_info',
        'medical_reason',
        'status',
        'rejection_reason',
        'rejected_by',
        'cancellation_reason',
        'cancelled_at',
        'cancelled_by',
        'current_approval_level',
        'team_conflict_count',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'total_days' => 'decimal:2',
            'is_half_day' => 'boolean',
            'is_emergency' => 'boolean',
            'team_conflict_count' => 'integer',
            'submitted_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    public function applicationDays(): HasMany
    {
        return $this->hasMany(LeaveApplicationDay::class);
    }

    public function approvals(): HasMany
    {
        return $this->hasMany(LeaveApproval::class)->orderBy('created_at', 'asc');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(LeaveComment::class)->orderBy('created_at', 'asc');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(LeaveAttachment::class);
    }

    public function rejectedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function getStatusBadgeAttribute(): array
    {
        return match($this->status) {
            'draft' => ['label' => 'Draft', 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
            'pending_team_lead' => ['label' => 'Pending Team Lead', 'class' => 'bg-amber-100 text-amber-800 border-amber-300'],
            'pending_manager' => ['label' => 'Pending Manager', 'class' => 'bg-teal-100 text-teal-800 border-teal-300'],
            'pending_hr' => ['label' => 'Pending HR', 'class' => 'bg-blue-100 text-blue-800 border-blue-300'],
            'approved' => ['label' => 'Approved', 'class' => 'bg-emerald-100 text-emerald-800 border-emerald-300'],
            'rejected' => ['label' => 'Rejected', 'class' => 'bg-rose-100 text-rose-800 border-rose-300'],
            'cancelled' => ['label' => 'Cancelled', 'class' => 'bg-gray-200 text-gray-700 border-gray-400'],
            'cancellation_requested' => ['label' => 'Cancellation Requested', 'class' => 'bg-orange-100 text-orange-800 border-orange-300'],
            'withdrawn' => ['label' => 'Withdrawn', 'class' => 'bg-slate-100 text-slate-700 border-slate-300'],
            default => ['label' => ucfirst($this->status), 'class' => 'bg-gray-100 text-gray-800 border-gray-300'],
        };
    }
}
