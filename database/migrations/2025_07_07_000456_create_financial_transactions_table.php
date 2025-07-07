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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_code', 30)->unique(); // e.g., 'TXN-20250707-001'
            $table->enum('transaction_type', ['income', 'expense', 'transfer', 'adjustment']);
            $table->string('category'); // e.g., 'sales', 'salary', 'utilities', 'inventory'
            $table->string('subcategory')->nullable(); // e.g., 'basic_salary', 'overtime', 'bonus'
            $table->decimal('amount', 15, 2);
            $table->foreignId('from_account_id')->nullable()->constrained('financial_accounts');
            $table->foreignId('to_account_id')->nullable()->constrained('financial_accounts');
            $table->string('reference_type')->nullable(); // e.g., 'penjualan', 'payroll', 'purchase'
            $table->unsignedBigInteger('reference_id')->nullable(); // ID dari tabel referensi
            $table->text('description');
            $table->date('transaction_date');
            $table->enum('status', ['pending', 'completed', 'cancelled'])->default('pending');
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamp('approved_at')->nullable();
            $table->json('metadata')->nullable(); // Additional data
            $table->timestamps();

            $table->index(['transaction_type', 'status']);
            $table->index(['transaction_date', 'status']);
            $table->index(['reference_type', 'reference_id']);
            $table->index('transaction_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
