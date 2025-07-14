<?php

namespace App\Services;

use App\Models\Payroll;
use App\Models\User;
use App\Models\FinancialTransaction;
use App\Models\FinancialAccount;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollService
{
    /**
     * Generate monthly payroll for a specific user
     */
    public function generateMonthlyPayroll($userId, $year, $month)
    {
        $user = User::findOrFail($userId);

        // Check if payroll already exists
        $existingPayroll = Payroll::where('user_id', $userId)
            ->where('year', $year)
            ->where('month', $month)
            ->first();

        if ($existingPayroll) {
            throw new \Exception('Payroll for this period already exists');
        }

        // Get payroll configuration for user's role
        $role = $user->roles->first();
        $config = PayrollConfiguration::where('role', $role->name)->first();

        if (!$config) {
            throw new \Exception('Payroll configuration not found for role: ' . $role->name);
        }

        // Calculate salary
        $baseSalary = $config->base_salary;
        $allowances = $config->allowances ?? 0;
        $deductions = $config->deductions ?? 0;
        $totalSalary = $baseSalary + $allowances - $deductions;

        // Create payroll record
        $payroll = Payroll::create([
            'user_id' => $userId,
            'month' => $month,
            'year' => $year,
            'base_salary' => $baseSalary,
            'allowances' => $allowances,
            'deductions' => $deductions,
            'total_salary' => $totalSalary,
            'payment_status' => 'unpaid',
            'generated_at' => now(),
        ]);

        return $payroll;
    }

    /**
     * Generate payroll for a specific period
     */
    public function generatePayroll($period, $userIds = null)
    {
        $periodStart = Carbon::parse($period . '-01');
        $periodEnd = $periodStart->copy()->endOfMonth();
        
        // Get users to generate payroll for
        $users = $userIds ? 
            User::whereIn('id', $userIds)->get() : 
            User::whereHas('roles', function($query) {
                $query->whereIn('name', ['admin', 'karyawan', 'kasir']);
            })->get();

        $payrolls = [];

        foreach ($users as $user) {
            // Check if payroll already exists for this period
            $existingPayroll = Payroll::where('user_id', $user->id)
                ->where('period_month', $period)
                ->first();

            if ($existingPayroll) {
                continue; // Skip if already exists
            }

            $payrollData = $this->calculatePayroll($user, $periodStart, $periodEnd);
            
            $payroll = Payroll::create([
                'payroll_code' => $this->generatePayrollCode($period, $user->id),
                'user_id' => $user->id,
                'period_month' => $period,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'basic_salary' => $payrollData['basic_salary'],
                'overtime_hours' => $payrollData['overtime_hours'],
                'overtime_rate' => $payrollData['overtime_rate'],
                'overtime_amount' => $payrollData['overtime_amount'],
                'bonus_amount' => $payrollData['bonus_amount'],
                'allowance_amount' => $payrollData['allowance_amount'],
                'deduction_amount' => $payrollData['deduction_amount'],
                'gross_salary' => $payrollData['gross_salary'],
                'tax_amount' => $payrollData['tax_amount'],
                'insurance_amount' => $payrollData['insurance_amount'],
                'other_deductions' => $payrollData['other_deductions'],
                'net_salary' => $payrollData['net_salary'],
                'status' => Payroll::STATUS_DRAFT, // Default status: menunggu persetujuan
                'breakdown' => $payrollData['breakdown'],
            ]);

            $payrolls[] = $payroll;
        }

        return $payrolls;
    }

    /**
     * Calculate payroll for a user
     */
    private function calculatePayroll($user, $periodStart, $periodEnd)
    {
        // Get base salary based on role
        $baseSalary = $this->getBaseSalary($user);
        
        // Calculate overtime
        $overtimeData = $this->calculateOvertime($user, $periodStart, $periodEnd);
        
        // Calculate bonus
        $bonus = $this->calculateBonus($user, $periodStart, $periodEnd);
        
        // Calculate allowances
        $allowance = $this->calculateAllowance($user);
        
        // Calculate deductions
        $deductions = $this->calculateDeductions($user);
        
        // Calculate gross salary
        $grossSalary = $baseSalary + $overtimeData['amount'] + $bonus + $allowance;
        
        // No taxes or insurance for rice store - keep it simple
        $tax = 0;
        $insurance = 0;

        // Net salary = Gross salary (no deductions)
        $netSalary = $grossSalary;

        return [
            'basic_salary' => $baseSalary,
            'overtime_hours' => $overtimeData['hours'],
            'overtime_rate' => $overtimeData['rate'],
            'overtime_amount' => $overtimeData['amount'],
            'bonus_amount' => $bonus,
            'allowance_amount' => $allowance,
            'deduction_amount' => $deductions,
            'gross_salary' => $grossSalary,
            'tax_amount' => 0,
            'insurance_amount' => 0,
            'other_deductions' => 0,
            'net_salary' => $netSalary,
            'breakdown' => [
                'basic_salary_details' => $this->getBaseSalaryDetails($user),
                'overtime_details' => $overtimeData,
                'bonus_details' => $this->getBonusDetails($user, $periodStart, $periodEnd),
                'allowance_details' => $this->getAllowanceDetails($user),
                'deduction_details' => $this->getDeductionDetails($user),
                'tax_details' => ['note' => 'Tidak ada pajak untuk toko beras'],
                'insurance_details' => ['note' => 'Tidak ada BPJS untuk toko beras'],
            ],
        ];
    }

    /**
     * Get base salary for user - Fixed rates for rice store
     */
    private function getBaseSalary($user)
    {
        $role = $user->roles->first()->name;

        // Fixed salary rates for rice store
        $salaries = [
            'admin' => 5000000,    // Rp 5.000.000
            'karyawan' => 3500000, // Rp 3.500.000
            'kasir' => 3000000,    // Rp 3.000.000
        ];

        return $salaries[$role] ?? 3000000;
    }

    /**
     * Calculate overtime - Simplified for rice store
     */
    private function calculateOvertime($user, $periodStart, $periodEnd)
    {
        // Rice store doesn't use overtime system
        // All work is considered regular hours
        return [
            'hours' => 0,
            'rate' => 0,
            'amount' => 0,
        ];
    }

    /**
     * Calculate bonus
     */
    private function calculateBonus($user, $periodStart, $periodEnd)
    {
        // Performance-based bonus calculation
        // For now, return 0
        return 0;
    }

    /**
     * Calculate allowance - Fixed rates for rice store
     */
    private function calculateAllowance($user)
    {
        $role = $user->roles->first()->name;

        // Fixed allowance rates for rice store
        $allowances = [
            'admin' => 800000,    // Transport (500k) + Meal (300k)
            'karyawan' => 500000, // Transport (300k) + Meal (200k)
            'kasir' => 400000,    // Transport (250k) + Meal (150k)
        ];

        return $allowances[$role] ?? 400000;
    }

    /**
     * Calculate deductions - No deductions for rice store
     */
    private function calculateDeductions($user)
    {
        // Rice store uses fixed salary - no deductions
        return 0;
    }

    /**
     * Calculate tax - No tax for rice store
     */
    private function calculateTax($grossSalary)
    {
        // Rice store doesn't handle tax - keep it simple
        return 0;
    }

    /**
     * Calculate insurance - No insurance for rice store
     */
    private function calculateInsurance($grossSalary)
    {
        // Rice store doesn't handle BPJS - keep it simple
        return 0;
    }

    /**
     * Process payroll payment
     */
    public function processPayment($payrollId, $accountId, $userId, $notes = null)
    {
        return DB::transaction(function() use ($payrollId, $accountId, $userId, $notes) {
            $payroll = Payroll::findOrFail($payrollId);

            // Validasi status menggunakan method baru
            if (!$payroll->canBePaid()) {
                throw new \Exception('Gaji harus disetujui terlebih dahulu sebelum dapat dibayar');
            }

            // Validasi saldo akun
            $account = FinancialAccount::findOrFail($accountId);
            if ($account->current_balance < $payroll->net_salary) {
                $saldoFormatted = 'Rp ' . number_format($account->current_balance, 0, ',', '.');
                $gajiFormatted = 'Rp ' . number_format($payroll->net_salary, 0, ',', '.');
                throw new \Exception("Saldo akun {$account->account_name} tidak mencukupi untuk pembayaran gaji. Saldo saat ini: {$saldoFormatted}, Gaji yang harus dibayar: {$gajiFormatted}");
            }

            // Create financial transaction untuk pengeluaran gaji
            $transaction = FinancialTransaction::create([
                'transaction_code' => $this->generateTransactionCode(),
                'transaction_type' => 'expense',
                'category' => 'payroll',
                'subcategory' => 'salary_payment',
                'amount' => $payroll->net_salary,
                'from_account_id' => $accountId,
                'reference_type' => 'payroll',
                'reference_id' => $payroll->id,
                'description' => "Pembayaran gaji {$payroll->user->name} periode {$payroll->period_month}" .
                               ($notes ? " - {$notes}" : ""),
                'transaction_date' => now()->toDateString(),
                'status' => 'completed',
                'created_by' => $userId,
                'approved_by' => $userId,
                'approved_at' => now(),
                'metadata' => [
                    'payroll_code' => $payroll->payroll_code,
                    'employee_name' => $payroll->user->name,
                    'period' => $payroll->period_month,
                    'basic_salary' => $payroll->basic_salary,
                    'overtime_amount' => $payroll->overtime_amount,
                    'bonus_amount' => $payroll->bonus_amount,
                    'allowance_amount' => $payroll->allowance_amount,
                    'gross_salary' => $payroll->gross_salary,
                    'tax_amount' => $payroll->tax_amount,
                    'insurance_amount' => $payroll->insurance_amount,
                    'deduction_amount' => $payroll->deduction_amount,
                    'net_salary' => $payroll->net_salary,
                ],
            ]);

            // Update account balance
            $account->updateBalance($payroll->net_salary, 'subtract');

            // Mark payroll as paid dengan link ke transaksi
            $payroll->markAsPaid($userId, $transaction->id);

            \Log::info('Payroll payment completed', [
                'payroll_id' => $payroll->id,
                'transaction_id' => $transaction->id,
                'amount' => $payroll->net_salary,
                'account_balance_after' => $account->fresh()->current_balance
            ]);

            return [
                'payroll' => $payroll->fresh(),
                'transaction' => $transaction,
                'account' => $account->fresh(),
            ];
        });
    }

    /**
     * Generate payroll code
     */
    private function generatePayrollCode($period, $userId)
    {
        $count = Payroll::where('period_month', $period)->count() + 1;
        return "PAY-{$period}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Generate transaction code
     */
    private function generateTransactionCode()
    {
        $date = now()->format('Ymd');
        $count = FinancialTransaction::whereDate('created_at', now())->count() + 1;
        return "TXN-{$date}-" . str_pad($count, 3, '0', STR_PAD_LEFT);
    }

    // Detail methods for breakdown
    private function getBaseSalaryDetails($user)
    {
        return [
            'role' => $user->roles->first()->name,
            'monthly_rate' => $this->getBaseSalary($user),
        ];
    }

    private function getBonusDetails($user, $periodStart, $periodEnd)
    {
        return [
            'performance_bonus' => 0,
            'attendance_bonus' => 0,
            'special_bonus' => 0,
        ];
    }

    private function getAllowanceDetails($user)
    {
        $role = $user->roles->first()->name;

        $details = [
            'admin' => [
                'transport_allowance' => 500000,
                'meal_allowance' => 300000,
            ],
            'karyawan' => [
                'transport_allowance' => 300000,
                'meal_allowance' => 200000,
            ],
            'kasir' => [
                'transport_allowance' => 250000,
                'meal_allowance' => 150000,
            ],
        ];

        return $details[$role] ?? $details['kasir'];
    }

    private function getDeductionDetails($user)
    {
        return [
            'late_deduction' => 0,
            'absence_deduction' => 0,
            'other_deduction' => 0,
        ];
    }

    private function getTaxDetails($grossSalary)
    {
        return [
            'note' => 'Toko beras tidak menangani pajak PPh',
            'tax_amount' => 0,
        ];
    }

    private function getInsuranceDetails($grossSalary)
    {
        return [
            'note' => 'Toko beras tidak menangani BPJS',
            'bpjs_amount' => 0,
        ];
    }
}
