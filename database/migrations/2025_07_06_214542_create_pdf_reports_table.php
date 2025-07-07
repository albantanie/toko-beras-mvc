<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Create the new PDF-based reports table with approval workflow
     */
    public function up(): void
    {
        Schema::create('pdf_reports', function (Blueprint $table) {
            $table->id();
            $table->string('title');                                    // Report title
            $table->enum('type', ['financial', 'stock']);               // Report type: financial or stock
            $table->date('report_date');                                // Date when report was generated
            $table->date('period_from')->nullable();                    // Period start (for financial reports)
            $table->date('period_to')->nullable();                      // Period end (for financial reports)
            $table->string('file_name');                               // PDF file name (date-based)
            $table->string('file_path');                               // Full path to PDF file in storage
            $table->enum('status', ['pending', 'approved', 'rejected'])
                  ->default('pending');                                 // Approval status
            $table->foreignId('generated_by')                          // User who generated the report (OWNER only)
                  ->constrained('users')
                  ->onDelete('cascade');
            $table->foreignId('approved_by')                           // User who approved/rejected (OWNER only)
                  ->nullable()
                  ->constrained('users')
                  ->onDelete('set null');
            $table->timestamp('approved_at')->nullable();               // When approved/rejected
            $table->text('approval_notes')->nullable();                 // Notes for approval/rejection
            $table->json('report_data')->nullable();                    // Summary data from the report
            $table->timestamps();

            // Indexes for better performance
            $table->index(['type', 'status']);
            $table->index(['generated_by', 'created_at']);
            $table->index('report_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pdf_reports');
    }
};
