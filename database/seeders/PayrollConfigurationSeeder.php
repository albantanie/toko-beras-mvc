<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PayrollConfiguration;

class PayrollConfigurationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configurations = [
            // Basic Salaries
            [
                'config_key' => 'basic_salary_admin',
                'config_name' => 'Gaji Pokok Admin',
                'config_type' => 'salary',
                'config_category' => 'basic',
                'applies_to' => 'admin',
                'amount' => 5000000,
                'description' => 'Gaji pokok untuk posisi Administrator',
            ],
            [
                'config_key' => 'basic_salary_karyawan',
                'config_name' => 'Gaji Pokok Karyawan',
                'config_type' => 'salary',
                'config_category' => 'basic',
                'applies_to' => 'karyawan',
                'amount' => 3500000,
                'description' => 'Gaji pokok untuk posisi Karyawan',
            ],
            [
                'config_key' => 'basic_salary_kasir',
                'config_name' => 'Gaji Pokok Kasir',
                'config_type' => 'salary',
                'config_category' => 'basic',
                'applies_to' => 'kasir',
                'amount' => 3000000,
                'description' => 'Gaji pokok untuk posisi Kasir',
            ],

            // Overtime Configuration
            [
                'config_key' => 'overtime_rate',
                'config_name' => 'Tarif Lembur per Jam',
                'config_type' => 'rate',
                'config_category' => 'overtime',
                'applies_to' => 'all',
                'amount' => 25000,
                'description' => 'Tarif lembur per jam untuk semua karyawan',
            ],

            // Allowances
            [
                'config_key' => 'transport_allowance_admin',
                'config_name' => 'Tunjangan Transport Admin',
                'config_type' => 'allowance',
                'config_category' => 'allowance',
                'applies_to' => 'admin',
                'amount' => 200000,
                'description' => 'Tunjangan transport untuk Administrator',
            ],
            [
                'config_key' => 'meal_allowance_admin',
                'config_name' => 'Tunjangan Makan Admin',
                'config_type' => 'allowance',
                'config_category' => 'allowance',
                'applies_to' => 'admin',
                'amount' => 300000,
                'description' => 'Tunjangan makan untuk Administrator',
            ],
            [
                'config_key' => 'transport_allowance_karyawan',
                'config_name' => 'Tunjangan Transport Karyawan',
                'config_type' => 'allowance',
                'config_category' => 'allowance',
                'applies_to' => 'karyawan',
                'amount' => 150000,
                'description' => 'Tunjangan transport untuk Karyawan',
            ],
            [
                'config_key' => 'meal_allowance_karyawan',
                'config_name' => 'Tunjangan Makan Karyawan',
                'config_type' => 'allowance',
                'config_category' => 'allowance',
                'applies_to' => 'karyawan',
                'amount' => 150000,
                'description' => 'Tunjangan makan untuk Karyawan',
            ],
            [
                'config_key' => 'transport_allowance_kasir',
                'config_name' => 'Tunjangan Transport Kasir',
                'config_type' => 'allowance',
                'config_category' => 'allowance',
                'applies_to' => 'kasir',
                'amount' => 125000,
                'description' => 'Tunjangan transport untuk Kasir',
            ],
            [
                'config_key' => 'meal_allowance_kasir',
                'config_name' => 'Tunjangan Makan Kasir',
                'config_type' => 'allowance',
                'config_category' => 'allowance',
                'applies_to' => 'kasir',
                'amount' => 125000,
                'description' => 'Tunjangan makan untuk Kasir',
            ],

            // Tax Configuration
            [
                'config_key' => 'tax_rate',
                'config_name' => 'Tarif Pajak PPh 21',
                'config_type' => 'percentage',
                'config_category' => 'tax',
                'applies_to' => 'all',
                'percentage' => 5.0,
                'description' => 'Tarif pajak PPh 21 dalam persen',
            ],
            [
                'config_key' => 'tax_free_threshold',
                'config_name' => 'Batas Bebas Pajak',
                'config_type' => 'amount',
                'config_category' => 'tax',
                'applies_to' => 'all',
                'amount' => 4500000,
                'description' => 'Batas penghasilan bebas pajak per bulan',
            ],

            // Insurance Configuration
            [
                'config_key' => 'insurance_rate',
                'config_name' => 'Tarif BPJS',
                'config_type' => 'percentage',
                'config_category' => 'insurance',
                'applies_to' => 'all',
                'percentage' => 1.0,
                'description' => 'Tarif BPJS dalam persen dari gaji',
            ],
            [
                'config_key' => 'insurance_max_salary',
                'config_name' => 'Maksimal Gaji untuk BPJS',
                'config_type' => 'amount',
                'config_category' => 'insurance',
                'applies_to' => 'all',
                'amount' => 8000000,
                'description' => 'Maksimal gaji yang digunakan untuk perhitungan BPJS',
            ],

            // Bonus Configuration
            [
                'config_key' => 'performance_bonus_rate',
                'config_name' => 'Bonus Kinerja',
                'config_type' => 'percentage',
                'config_category' => 'bonus',
                'applies_to' => 'all',
                'percentage' => 10.0,
                'min_value' => 0,
                'max_value' => 1000000,
                'description' => 'Bonus kinerja maksimal 10% dari gaji pokok',
            ],
        ];

        foreach ($configurations as $config) {
            PayrollConfiguration::create($config);
        }
    }
}
