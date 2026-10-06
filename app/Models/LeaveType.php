<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeaveType extends Model
{
    protected $fillable = [
        'name',
        'code',
        'description',
        'days_allowed',
        'is_paid',
        'requires_attachment',
        'attachment_required_after_days',
        'is_emergency_type',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'days_allowed' => 'decimal:2',
            'is_paid' => 'boolean',
            'requires_attachment' => 'boolean',
            'attachment_required_after_days' => 'integer',
            'is_emergency_type' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function balances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    public function applications(): HasMany
    {
        return $this->hasMany(LeaveApplication::class);
    }
}
