<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('report_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('title');                                // Judul laporan
            $table->enum('type', [
                'penjualan_summary', 'penjualan_detail', 'transaksi_harian', 
                'transaksi_kasir', 'barang_stok', 'barang_movement', 
                'barang_performance', 'keuangan', 'dual_check'
            ]); // Jenis laporan
            $table->json('data');                                   // Data laporan dalam JSON
            $table->text('summary')->nullable();                   // Ringkasan laporan
            $table->date('period_from');                           // Periode awal laporan
            $table->date('period_to');                             // Periode akhir laporan
            
            // Dual Crosscheck System
            $table->foreignId('submitted_by')                      // User yang submit (kasir/karyawan)
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('crosscheck_by')                     // User yang crosscheck (karyawan/kasir)
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->foreignId('approved_by')                       // Owner yang approve
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            
            // Status workflow
            $table->enum('status', [
                'draft',                    // Draft awal
                'submitted',                // Sudah disubmit untuk crosscheck
                'crosscheck_pending',       // Menunggu crosscheck
                'crosscheck_approved',      // Sudah di-crosscheck
                'crosscheck_rejected',      // Ditolak saat crosscheck
                'owner_pending',            // Menunggu approval owner
                'approved',                 // Disetujui owner
                'rejected'                  // Ditolak owner
            ])->default('draft');
            
            // Timestamps untuk tracking
            $table->timestamp('submitted_at')->nullable();         // Waktu submit
            $table->timestamp('crosschecked_at')->nullable();      // Waktu crosscheck
            $table->timestamp('approved_at')->nullable();          // Waktu approval owner
            
            // Notes untuk setiap tahap
            $table->text('submission_notes')->nullable();          // Catatan submitter
            $table->text('crosscheck_notes')->nullable();          // Catatan crosschecker
            $table->text('approval_notes')->nullable();            // Catatan owner
            
            // File attachments
            $table->string('file_path')->nullable();               // Path file laporan
            $table->string('crosscheck_file_path')->nullable();    // Path file crosscheck
            
            $table->timestamps();
            
            // Indexes
            $table->index(['status', 'type']);
            $table->index(['submitted_by', 'status']);
            $table->index(['crosscheck_by', 'status']);
            $table->index(['approved_by', 'status']);
            $table->index('period_from');
            $table->index('period_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('report_submissions');
    }
};
