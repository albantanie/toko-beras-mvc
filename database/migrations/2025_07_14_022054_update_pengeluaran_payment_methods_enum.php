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
        // Update existing data first
        DB::table('pengeluaran')->where('metode_pembayaran', 'transfer')->update(['metode_pembayaran' => 'transfer_bca']);
        DB::table('pengeluaran')->whereIn('metode_pembayaran', ['kartu_debit', 'kartu_kredit'])->update(['metode_pembayaran' => 'tunai']);

        // Drop and recreate the column with new enum values (PostgreSQL approach)
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn('metode_pembayaran');
        });

        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['tunai', 'transfer_bca'])->default('tunai')->after('kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop and recreate with original enum values
        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->dropColumn('metode_pembayaran');
        });

        Schema::table('pengeluaran', function (Blueprint $table) {
            $table->enum('metode_pembayaran', ['tunai', 'transfer', 'kartu_debit', 'kartu_kredit'])->default('tunai')->after('kategori');
        });

        // Revert data changes
        DB::table('pengeluaran')->where('metode_pembayaran', 'transfer_bca')->update(['metode_pembayaran' => 'transfer']);
    }
};
