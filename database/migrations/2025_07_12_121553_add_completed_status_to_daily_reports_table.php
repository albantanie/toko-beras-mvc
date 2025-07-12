<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add 'completed' status to daily_reports status enum
        if (DB::getDriverName() === 'pgsql') {
            // For PostgreSQL, drop and recreate constraint
            DB::statement("ALTER TABLE daily_reports DROP CONSTRAINT IF EXISTS daily_reports_status_check");
            DB::statement("ALTER TABLE daily_reports ADD CONSTRAINT daily_reports_status_check CHECK (status IN ('draft', 'submitted', 'approved', 'rejected', 'completed'))");
        } else {
            // For MySQL
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected', 'completed'])->default('submitted')->change();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original enum values
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE daily_reports DROP CONSTRAINT IF EXISTS daily_reports_status_check");
            DB::statement("ALTER TABLE daily_reports ADD CONSTRAINT daily_reports_status_check CHECK (status IN ('draft', 'submitted', 'approved', 'rejected'))");
        } else {
            Schema::table('daily_reports', function (Blueprint $table) {
                $table->enum('status', ['draft', 'submitted', 'approved', 'rejected'])->default('submitted')->change();
            });
        }
    }
};
