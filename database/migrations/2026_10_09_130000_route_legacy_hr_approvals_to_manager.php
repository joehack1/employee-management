<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('leave_applications')
            ->where('status', 'pending_hr')
            ->update([
                'status' => 'pending_manager',
                'current_approval_level' => 'manager',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Existing applications are not moved back into an HR approval queue.
    }
};
