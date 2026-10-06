<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LeaveBalance extends Model
{
    protected $fillable = [
        'employee_id',
        'leave_type_id',
        'year',
        'entitled_days',
        'carried_forward_days',
        'manual_adjustment_days',
        'used_days',
        'pending_days',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'entitled_days' => 'decimal:2',
            'carried_forward_days' => 'decimal:2',
            'manual_adjustment_days' => 'decimal:2',
            'used_days' => 'decimal:2',
            'pending_days' => 'decimal:2',
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

    /**
     * Total effective entitlement (Entitled + Carried Forward + Manual Adjustments)
     */
    public function getTotalEntitledAttribute(): float
    {
        return (float) ($this->entitled_days + $this->carried_forward_days + $this->manual_adjustment_days);
    }

    /**
     * Available days remaining = (Total Entitled) - (Used) - (Pending)
     */
    public function getAvailableDaysAttribute(): float
    {
        $avail = $this->total_entitled - (float) $this->used_days - (float) $this->pending_days;
        return max(0, $avail);
    }

    /**
     * Remaining approved balance = (Total Entitled) - (Used)
     */
    public function getRemainingDaysAttribute(): float
    {
        $rem = $this->total_entitled - (float) $this->used_days;
        return max(0, $rem);
    }
}
