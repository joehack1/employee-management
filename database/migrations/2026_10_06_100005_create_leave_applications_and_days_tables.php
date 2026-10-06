<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_applications', function (Blueprint $table) {
            $table->id();
            $table->string('application_number', 50)->unique();
            $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained('leave_types')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 4, 2);
            $table->boolean('is_half_day')->default(false);
            $table->string('half_day_type', 20)->nullable(); // morning, afternoon
            $table->boolean('is_emergency')->default(false);
            $table->text('reason');
            $table->string('doctor_hospital_info')->nullable();
            $table->text('medical_reason')->nullable();
            $table->string('status', 40)->default('pending_team_lead'); 
            // draft, pending_team_lead, pending_hr, approved, rejected, cancelled, cancellation_requested, withdrawn, expired
            $table->text('rejection_reason')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('cancellation_reason')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('current_approval_level', 30)->default('team_lead');
            $table->integer('team_conflict_count')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });

        Schema::create('leave_application_days', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_application_id')->constrained('leave_applications')->cascadeOnDelete();
            $table->date('date');
            $table->string('day_type', 20)->default('full'); // full, morning, afternoon
            $table->boolean('is_working_day')->default(true);
            $table->decimal('weight', 3, 2)->default(1.0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_application_days');
        Schema::dropIfExists('leave_applications');
    }
};
