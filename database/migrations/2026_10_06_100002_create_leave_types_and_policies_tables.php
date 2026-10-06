<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 30)->unique();
            $table->text('description')->nullable();
            $table->decimal('days_allowed', 5, 2)->default(21);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_attachment')->default(false);
            $table->integer('attachment_required_after_days')->default(0);
            $table->boolean('is_emergency_type')->default(false);
            $table->string('color', 20)->default('blue');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('leave_policies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->decimal('annual_days', 5, 2)->default(21);
            $table->string('accrual_type', 20)->default('upfront'); // upfront, monthly
            $table->decimal('monthly_accrual_rate', 4, 2)->default(1.75);
            $table->decimal('max_carry_forward', 5, 2)->default(5);
            $table->string('leave_year_type', 20)->default('calendar'); // calendar, anniversary
            $table->integer('min_days_advance_notice')->default(3);
            $table->integer('max_team_on_leave')->default(2);
            $table->string('approval_workflow', 30)->default('team_lead_then_hr'); // team_lead_then_hr, direct_hr, dept_head_then_hr
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('leave_types');
    }
};
