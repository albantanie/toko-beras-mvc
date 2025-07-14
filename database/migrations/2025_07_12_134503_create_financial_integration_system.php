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
        // Check if financial_accounts table exists before trying to modify it
        if (Schema::hasTable('akun_keuangan')) {
            // Tambahkan kolom untuk tracking financial integration
            Schema::table('akun_keuangan', function (Blueprint $table) {
                $table->boolean('auto_update_balance')->default(true)->after('is_active');
            });

            // Buat view untuk total cash summary
            if (DB::getDriverName() === 'sqlite') {
                // SQLite doesn't support CREATE OR REPLACE VIEW
                DB::statement("DROP VIEW IF EXISTS cash_summary");
                DB::statement("
                    CREATE VIEW cash_summary AS
                    SELECT
                        SUM(CASE WHEN account_type IN ('cash', 'bank') THEN current_balance ELSE 0 END) as total_cash,
                        SUM(CASE WHEN account_type = 'cash' THEN current_balance ELSE 0 END) as cash_balance,
                        SUM(CASE WHEN account_type = 'bank' THEN current_balance ELSE 0 END) as bank_balance,
                        SUM(CASE WHEN account_type = 'receivable' THEN current_balance ELSE 0 END) as total_receivables,
                        SUM(CASE WHEN account_type = 'payable' THEN current_balance ELSE 0 END) as total_payables
                    FROM akun_keuangan
                    WHERE is_active = 1
                ");
            } else {
                DB::statement("
                    CREATE OR REPLACE VIEW cash_summary AS
                    SELECT
                        SUM(CASE WHEN account_type IN ('cash', 'bank') THEN current_balance ELSE 0 END) as total_cash,
                        SUM(CASE WHEN account_type = 'cash' THEN current_balance ELSE 0 END) as cash_balance,
                        SUM(CASE WHEN account_type = 'bank' THEN current_balance ELSE 0 END) as bank_balance,
                        SUM(CASE WHEN account_type = 'receivable' THEN current_balance ELSE 0 END) as total_receivables,
                        SUM(CASE WHEN account_type = 'payable' THEN current_balance ELSE 0 END) as total_payables
                    FROM akun_keuangan
                    WHERE is_active = true
                ");
            }

            // Buat stored procedure untuk update balance (hanya untuk PostgreSQL)
            if (DB::getDriverName() === 'pgsql') {
                DB::statement("
                    CREATE OR REPLACE FUNCTION update_account_balance(
                        p_account_id BIGINT,
                        p_amount DECIMAL(15,2),
                        p_operation VARCHAR(10) DEFAULT 'add'
                    ) RETURNS DECIMAL(15,2) AS $$
                    DECLARE
                        new_balance DECIMAL(15,2);
                    BEGIN
                        IF p_operation = 'add' THEN
                            UPDATE akun_keuangan
                            SET current_balance = current_balance + p_amount,
                                updated_at = NOW()
                            WHERE id = p_account_id AND auto_update_balance = true;
                        ELSE
                            UPDATE akun_keuangan
                            SET current_balance = current_balance - p_amount,
                                updated_at = NOW()
                            WHERE id = p_account_id AND auto_update_balance = true;
                        END IF;

                        SELECT current_balance INTO new_balance
                        FROM akun_keuangan
                        WHERE id = p_account_id;

                        RETURN new_balance;
                    END;
                    $$ LANGUAGE plpgsql;
                ");
            }
            // SQLite doesn't support stored functions, so we'll handle this in the application layer
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP FUNCTION IF EXISTS update_account_balance(BIGINT, DECIMAL, VARCHAR)");
        DB::statement("DROP VIEW IF EXISTS cash_summary");
        
        if (Schema::hasTable('akun_keuangan')) {
            Schema::table('akun_keuangan', function (Blueprint $table) {
                $table->dropColumn(['auto_update_balance']);
            });
        }
    }
};
