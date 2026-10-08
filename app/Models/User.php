<?php

namespace App\Models;

use App\Notifications\ResetPassword;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'employee_number',
        'phone',
        'avatar',
        'is_active',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function employee(): HasOne
    {
        return $this->hasOne(Employee::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPassword($token));
    }

    public function isEmployee(): bool
    {
        return $this->role === 'employee';
    }

    public function isTeamLead(): bool
    {
        return in_array($this->role, ['team_lead', 'hr', 'administrator']);
    }

    public function isManager(): bool
    {
        return $this->role === 'manager';
    }

    public function isHr(): bool
    {
        return in_array($this->role, ['hr', 'administrator']);
    }

    public function isAdmin(): bool
    {
        return $this->role === 'administrator';
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === 'administrator';
    }
}
