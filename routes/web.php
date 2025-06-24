<?php

use App\Http\Controllers\BarangController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ReceiptController;
use App\Http\Controllers\DashboardController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Public routes
Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/product/{barang}', [HomeController::class, 'show'])->name('product.show');

// Cart routes (accessible without login for browsing, but checkout requires login)
Route::prefix('cart')->name('cart.')->group(function () {
    Route::get('/', [CartController::class, 'index'])->name('index');
    Route::post('/add', [CartController::class, 'add'])->name('add');
    Route::patch('/update', [CartController::class, 'update'])->name('update');
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');

    // Checkout requires authentication
    Route::middleware('auth')->group(function () {
        Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');
        Route::post('/checkout', [CartController::class, 'processCheckout'])->name('process-checkout');
    });
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Default dashboard - redirect based on role
    Route::get('dashboard', function () {
        $user = auth()->user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        } elseif ($user->isOwner()) {
            return redirect()->route('owner.dashboard');
        } elseif ($user->isKaryawan()) {
            return redirect()->route('karyawan.dashboard');
        } elseif ($user->isKasir()) {
            return redirect()->route('kasir.dashboard');
        } else {
            // Regular user (pelanggan) dashboard
            return redirect()->route('user.dashboard');
        }
    })->name('dashboard');

    // User (pelanggan) routes
    Route::middleware(['role:pelanggan'])->prefix('user')->name('user.')->group(function () {
        Route::get('dashboard', [HomeController::class, 'dashboard'])->name('dashboard');
        Route::get('orders', [HomeController::class, 'orders'])->name('orders');
        Route::get('orders/{order}', [HomeController::class, 'orderDetail'])->name('orders.show');
    });

    // Admin routes
    Route::middleware(['role:' . Role::ADMIN])->prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

        // User management routes for admin
        Route::get('users/quick-add', [\App\Http\Controllers\Admin\UserController::class, 'quickAdd'])->name('users.quick-add');
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    });

    // Owner routes
    Route::middleware(['role:' . Role::OWNER])->prefix('owner')->name('owner.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'ownerDashboard'])->name('dashboard');

        // Laporan routes for owner
        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/penjualan', [LaporanController::class, 'penjualan'])->name('laporan.penjualan');
        Route::get('laporan/stok', [LaporanController::class, 'stok'])->name('laporan.stok');
    });

    // Karyawan routes
    Route::middleware(['role:' . Role::KARYAWAN])->prefix('karyawan')->name('karyawan.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'karyawanDashboard'])->name('dashboard');
    });

    // Kasir routes
    Route::middleware(['role:' . Role::KASIR])->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'kasirDashboard'])->name('dashboard');
    });



    // Barang routes (admin, owner, karyawan)
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        Route::get('barang/quick-add', [BarangController::class, 'quickAdd'])->name('barang.quick-add');
        Route::resource('barang', BarangController::class);
        Route::patch('barang/{barang}/stock', [BarangController::class, 'updateStock'])->name('barang.update-stock');
    });

    // Penjualan routes (admin, owner, karyawan, kasir)
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN . ',' . Role::KASIR])->group(function () {
        Route::resource('penjualan', PenjualanController::class);
        Route::get('penjualan/{penjualan}/print', [PenjualanController::class, 'print'])->name('penjualan.print');

        // Online orders management for kasir
        Route::get('penjualan-online', [PenjualanController::class, 'onlineOrders'])->name('penjualan.online');
        Route::patch('penjualan/{penjualan}/confirm-payment', [PenjualanController::class, 'confirmPayment'])->name('penjualan.confirm-payment');
        Route::patch('penjualan/{penjualan}/ready-pickup', [PenjualanController::class, 'readyPickup'])->name('penjualan.ready-pickup');
        Route::patch('penjualan/{penjualan}/complete', [PenjualanController::class, 'complete'])->name('penjualan.complete');

        // Receipt routes
        Route::get('penjualan/{penjualan}/receipt', [ReceiptController::class, 'generatePickupReceipt'])->name('penjualan.receipt');
        Route::get('receipt/{receiptCode}', [ReceiptController::class, 'showPickupReceipt'])->name('receipt.show');
        Route::post('receipt/{receiptCode}/verify', [ReceiptController::class, 'verifyAndComplete'])->name('receipt.verify');
    });

    // Laporan routes (admin, owner)
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER])->prefix('laporan')->name('laporan.')->group(function () {
        Route::get('/', [LaporanController::class, 'index'])->name('index');
        Route::get('penjualan', [LaporanController::class, 'penjualan'])->name('penjualan');
        Route::get('stok', [LaporanController::class, 'stok'])->name('stok');
    });
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
