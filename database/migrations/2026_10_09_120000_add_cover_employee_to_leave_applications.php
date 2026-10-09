<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->foreignId('cover_employee_id')->nullable()->after('employee_id')->constrained('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('leave_applications', function (Blueprint $table) {
            $table->dropConstrainedForeignId('cover_employee_id');
        });
    }
};
