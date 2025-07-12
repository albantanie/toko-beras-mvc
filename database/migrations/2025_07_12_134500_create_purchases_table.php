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
        Schema::create('purchases', function (Blueprint $table) {
            $table->id();
            $table->string('purchase_code', 30)->unique(); // e.g., 'PUR-20250712-001'
            $table->foreignId('user_id')->constrained('users'); // User yang melakukan pembelian
            $table->string('supplier_name'); // Nama supplier
            $table->string('supplier_phone')->nullable();
            $table->text('supplier_address')->nullable();
            $table->date('purchase_date');
            $table->enum('status', ['pending', 'received', 'cancelled'])->default('pending');
            $table->enum('payment_method', ['tunai', 'transfer', 'kredit'])->default('tunai');
            $table->enum('payment_status', ['unpaid', 'partial', 'paid'])->default('unpaid');
            $table->decimal('subtotal', 15, 2);
            $table->decimal('discount', 15, 2)->default(0);
            $table->decimal('tax', 15, 2)->default(0);
            $table->decimal('total', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
            $table->date('due_date')->nullable(); // Untuk pembelian kredit
            $table->text('notes')->nullable();
            $table->boolean('is_financial_recorded')->default(false); // Apakah sudah dicatat ke keuangan
            $table->timestamps();

            $table->index(['purchase_date', 'status']);
            $table->index(['supplier_name']);
            $table->index('purchase_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchases');
    }
};
