<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role', 30)->default('employee')->after('email'); // employee, team_lead, hr, manager, administrator
            $table->string('employee_number', 50)->nullable()->unique()->after('role');
            $table->string('phone', 50)->nullable()->after('employee_number');
            $table->string('avatar')->nullable()->after('phone');
            $table->boolean('is_active')->default(true)->after('avatar');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'employee_number', 'phone', 'avatar', 'is_active']);
        });
    }
};
