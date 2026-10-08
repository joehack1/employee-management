<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // The legacy admin role only carried HR access; normalize it to the HR role.
        DB::table('users')->where('role', 'admin')->update(['role' => 'hr']);
    }

    public function down(): void
    {
        // This normalization is intentionally one-way so current HR users are not
        // incorrectly reassigned to the removed legacy admin role during rollback.
    }
};
