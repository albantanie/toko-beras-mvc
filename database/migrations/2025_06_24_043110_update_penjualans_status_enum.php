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
        // For PostgreSQL, we need to use raw SQL to modify enum
        if (DB::getDriverName() === 'pgsql') {
            // Drop existing constraint
            DB::statement("ALTER TABLE penjualans DROP CONSTRAINT IF EXISTS penjualans_status_check");

            // Change column type to varchar temporarily
            DB::statement("ALTER TABLE penjualans ALTER COLUMN status TYPE VARCHAR(20)");

            // Add new constraint with additional values
            DB::statement("ALTER TABLE penjualans ADD CONSTRAINT penjualans_status_check CHECK (status IN ('pending', 'dibayar', 'siap_pickup', 'selesai', 'dibatalkan'))");
        } else {
            // For MySQL
            Schema::table('penjualans', function (Blueprint $table) {
                $table->enum('status', ['pending', 'dibayar', 'siap_pickup', 'selesai', 'dibatalkan'])->default('pending')->change();
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
            DB::statement("ALTER TABLE penjualans DROP CONSTRAINT IF EXISTS penjualans_status_check");
            DB::statement("ALTER TABLE penjualans ADD CONSTRAINT penjualans_status_check CHECK (status IN ('pending', 'selesai', 'dibatalkan'))");
        } else {
            Schema::table('penjualans', function (Blueprint $table) {
                $table->enum('status', ['pending', 'selesai', 'dibatalkan'])->default('pending')->change();
            });
        }
    }
};
