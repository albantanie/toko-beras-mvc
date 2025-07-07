<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Drop the old report tables to make way for the new PDF-based reporting system
     */
    public function up(): void
    {
        // Drop report_submissions table first (has foreign key to reports)
        Schema::dropIfExists('report_submissions');

        // Drop reports table
        Schema::dropIfExists('reports');
    }

    /**
     * Reverse the migrations.
     * Recreate the old tables if needed (for rollback purposes)
     */
    public function down(): void
    {
        // Recreate reports table
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', [
                'penjualan_summary', 'penjualan_detail', 'transaksi_harian',
                'transaksi_kasir', 'barang_stok', 'barang_movement',
                'barang_performance', 'keuangan'
            ]);
            $table->json('data');
            $table->text('summary')->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->enum('status', ['draft', 'pending_approval', 'approved', 'rejected'])
                  ->default('draft');
            $table->foreignId('generated_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('file_path')->nullable();
            $table->timestamps();
        });

        // Recreate report_submissions table
        Schema::create('report_submissions', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->enum('type', [
                'penjualan_summary', 'penjualan_detail', 'transaksi_harian',
                'transaksi_kasir', 'barang_stok', 'barang_movement',
                'barang_performance', 'keuangan', 'dual_check'
            ]);
            $table->json('data');
            $table->text('summary')->nullable();
            $table->date('period_from');
            $table->date('period_to');
            $table->enum('status', [
                'draft', 'submitted', 'crosscheck_pending', 'crosscheck_approved',
                'crosscheck_rejected', 'owner_pending', 'approved', 'rejected'
            ])->default('draft');
            $table->foreignId('submitted_by')
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('crosscheck_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->foreignId('approved_by')
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('crosschecked_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('submission_notes')->nullable();
            $table->text('crosscheck_notes')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('file_path')->nullable();
            $table->string('crosscheck_file_path')->nullable();
            $table->json('pdf_paths')->nullable();
            $table->timestamps();
        });
    }
};
