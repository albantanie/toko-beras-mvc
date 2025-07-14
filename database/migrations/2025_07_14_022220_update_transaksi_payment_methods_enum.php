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
        if (DB::getDriverName() === 'sqlite') {
            // SQLite doesn't support DROP CONSTRAINT, so we'll handle this differently
            // First update existing data
            DB::table('transaksi')->where('metode_pembayaran', 'transfer')->update(['metode_pembayaran' => 'transfer_bca']);
            DB::table('transaksi')->whereIn('metode_pembayaran', ['kartu_debit', 'kartu_kredit'])->update(['metode_pembayaran' => 'tunai']);

            // For SQLite, we'll just update the data and rely on application-level validation
        } else {
            // Drop existing constraint first (PostgreSQL/MySQL)
            DB::statement('ALTER TABLE transaksi DROP CONSTRAINT IF EXISTS transaksi_metode_pembayaran_check');

            // Drop and recreate the column with new enum values
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('metode_pembayaran');
            });

            Schema::table('transaksi', function (Blueprint $table) {
                $table->enum('metode_pembayaran', ['tunai', 'transfer_bca'])->default('tunai')->after('status');
            });

            // Update existing data after column recreation
            DB::table('transaksi')->where('metode_pembayaran', 'transfer')->update(['metode_pembayaran' => 'transfer_bca']);
            DB::table('transaksi')->whereIn('metode_pembayaran', ['kartu_debit', 'kartu_kredit'])->update(['metode_pembayaran' => 'tunai']);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // For SQLite, just revert the data changes
            DB::table('transaksi')->where('metode_pembayaran', 'transfer_bca')->update(['metode_pembayaran' => 'transfer']);
        } else {
            // Drop and recreate with original enum values
            Schema::table('transaksi', function (Blueprint $table) {
                $table->dropColumn('metode_pembayaran');
            });

            Schema::table('transaksi', function (Blueprint $table) {
                $table->enum('metode_pembayaran', ['tunai', 'transfer', 'kartu_debit', 'kartu_kredit'])->default('tunai')->after('status');
            });

            // Revert data changes
            DB::table('transaksi')->where('metode_pembayaran', 'transfer_bca')->update(['metode_pembayaran' => 'transfer']);
        }
    }
};
