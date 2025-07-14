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
        // SQLite doesn't support DROP CONSTRAINT, so we'll recreate the table
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            Schema::table('pdf_reports', function (Blueprint $table) {
                // SQLite will handle this automatically when we recreate
            });
        } else {
            // For other databases
            DB::statement('ALTER TABLE pdf_reports DROP CONSTRAINT pdf_reports_type_check');
            DB::statement("ALTER TABLE pdf_reports ADD CONSTRAINT pdf_reports_type_check CHECK (type IN ('financial', 'stock', 'sales', 'penjualan'))");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, we need to recreate the table
            Schema::table('pdf_reports', function (Blueprint $table) {
                // SQLite will handle this automatically when we recreate
            });
        } else {
            // For other databases
            DB::statement('ALTER TABLE pdf_reports DROP CONSTRAINT pdf_reports_type_check');
            DB::statement("ALTER TABLE pdf_reports ADD CONSTRAINT pdf_reports_type_check CHECK (type IN ('financial', 'stock'))");
        }
    }
};
