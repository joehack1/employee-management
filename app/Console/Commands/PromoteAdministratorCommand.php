<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Console\Command;

class PromoteAdministratorCommand extends Command
{
    protected $signature = 'users:promote-administrator {email : Email of the existing account to promote}';

    protected $description = 'Promote an existing account to the system Administrator role';

    public function handle(): int
    {
        $user = User::where('email', $this->argument('email'))->first();

        if (!$user) {
            $this->error('No account was found with that email address.');

            return self::FAILURE;
        }

        if (!$user->is_active) {
            $this->error('The account is inactive. Activate it before promoting it.');

            return self::FAILURE;
        }

        if ($user->role === 'administrator') {
            $this->info("{$user->email} already has the Administrator role.");

            return self::SUCCESS;
        }

        $oldRole = $user->role;
        $user->role = 'administrator';
        $user->save();

        AuditLog::log(
            action: 'administrator_role_assigned',
            entityType: 'User',
            entityId: $user->id,
            description: "{$user->email} was promoted from {$oldRole} to Administrator by a console operator",
            oldValues: ['role' => $oldRole],
            newValues: ['role' => 'administrator'],
        );

        $this->info("{$user->email} is now an Administrator.");

        return self::SUCCESS;
    }
}
