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
        Schema::create('payroll_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('config_key')->unique(); // e.g., 'basic_salary_admin', 'overtime_rate', 'tax_rate'
            $table->string('config_name'); // e.g., 'Gaji Pokok Admin', 'Tarif Lembur'
            $table->string('config_type'); // 'salary', 'allowance', 'deduction', 'rate', 'percentage'
            $table->string('config_category'); // 'basic', 'overtime', 'bonus', 'allowance', 'tax', 'insurance'
            $table->string('applies_to')->nullable(); // 'all', 'admin', 'karyawan', 'kasir' or specific user_id
            $table->decimal('amount', 15, 2)->nullable(); // Fixed amount
            $table->decimal('percentage', 8, 2)->nullable(); // Percentage value
            $table->decimal('min_value', 15, 2)->nullable(); // Minimum value
            $table->decimal('max_value', 15, 2)->nullable(); // Maximum value
            $table->text('description')->nullable();
            $table->json('conditions')->nullable(); // Additional conditions
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['config_type', 'is_active']);
            $table->index(['applies_to', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payroll_configurations');
    }
};
