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
        'requires_reason',
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
            'requires_reason' => 'boolean',
            'is_emergency_type' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function getDisplayColorAttribute(): string
    {
        $legacyColors = [
            'blue' => '#1d9692',
            'emerald' => '#1d9692',
            'teal' => '#1d9692',
            'amber' => '#a01e22',
            'rose' => '#a01e22',
            'red' => '#a01e22',
            'purple' => '#7c3aed',
            'indigo' => '#4f46e5',
            'cyan' => '#06b6d4',
            'slate' => '#64748b',
        ];

        if (is_string($this->color) && preg_match('/^#[0-9a-fA-F]{6}$/', $this->color)) {
            return $this->color;
        }

        return $legacyColors[$this->color] ?? '#1d9692';
    }

    public function isMedicalLeave(): bool
    {
        $identifier = strtolower($this->code . ' ' . $this->name);

        return str_contains($identifier, 'sick') || str_contains($identifier, 'medical');
    }

    public function isMaternityLeave(): bool
    {
        return str_contains(strtolower($this->code . ' ' . $this->name), 'maternity');
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
