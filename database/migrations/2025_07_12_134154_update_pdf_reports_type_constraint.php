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
        // Drop the existing constraint
        DB::statement('ALTER TABLE pdf_reports DROP CONSTRAINT pdf_reports_type_check');

        // Add new constraint with additional values
        DB::statement("ALTER TABLE pdf_reports ADD CONSTRAINT pdf_reports_type_check CHECK (type IN ('financial', 'stock', 'sales', 'penjualan'))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop the new constraint
        DB::statement('ALTER TABLE pdf_reports DROP CONSTRAINT pdf_reports_type_check');

        // Restore original constraint
        DB::statement("ALTER TABLE pdf_reports ADD CONSTRAINT pdf_reports_type_check CHECK (type IN ('financial', 'stock'))");
    }
};
