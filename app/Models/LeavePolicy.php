<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LeavePolicy extends Model
{
    protected $fillable = [
        'name',
        'annual_days',
        'accrual_type',
        'monthly_accrual_rate',
        'max_carry_forward',
        'leave_year_type',
        'min_days_advance_notice',
        'max_team_on_leave',
        'approval_workflow',
        'is_default',
    ];

    protected function casts(): array
    {
        return [
            'annual_days' => 'decimal:2',
            'monthly_accrual_rate' => 'decimal:2',
            'max_carry_forward' => 'decimal:2',
            'min_days_advance_notice' => 'integer',
            'max_team_on_leave' => 'integer',
            'is_default' => 'boolean',
        ];
    }

    public function employees(): HasMany
    {
        return $this->hasMany(Employee::class);
    }
}
