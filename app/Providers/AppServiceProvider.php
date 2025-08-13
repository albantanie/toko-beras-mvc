<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Penjualan;
use App\Models\DetailPenjualan;
use App\Models\Payroll;
use App\Models\Barang;
use App\Observers\PenjualanObserver;
use App\Observers\DetailPenjualanObserver;
use App\Observers\PayrollObserver;
use App\Observers\BarangObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Register model observers
        Penjualan::observe(PenjualanObserver::class);
        DetailPenjualan::observe(DetailPenjualanObserver::class);
        Payroll::observe(PayrollObserver::class);
        Barang::observe(BarangObserver::class);
    }
}
