<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Penjualan;
use App\Models\Payroll;
use App\Observers\PenjualanObserver;
use App\Observers\PayrollObserver;

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
        Payroll::observe(PayrollObserver::class);
    }
}
