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
        // Drop payroll configurations table - replaced with fixed salary system
        Schema::dropIfExists('konfigurasi_gaji');
        Schema::dropIfExists('payroll_configurations');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate table if needed (basic structure)
        Schema::create('konfigurasi_gaji', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique();
            $table->string('config_name');
            $table->enum('config_type', ['salary', 'allowance', 'deduction', 'rate', 'percentage', 'amount']);
            $table->enum('config_category', ['basic', 'overtime', 'bonus', 'allowance', 'tax', 'insurance', 'deduction']);
            $table->string('applies_to');
            $table->decimal('amount', 15, 2)->nullable();
            $table->decimal('percentage', 5, 2)->nullable();
            $table->decimal('min_value', 15, 2)->nullable();
            $table->decimal('max_value', 15, 2)->nullable();
            $table->text('description')->nullable();
            $table->json('conditions')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
};
