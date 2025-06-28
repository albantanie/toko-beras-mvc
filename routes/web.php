<?php

/**
 * ===================================================================
 * ROUTING CONFIGURATION UNTUK APLIKASI TOKO BERAS
 * ===================================================================
 *
 * File ini mendefinisikan semua routes (rute) untuk aplikasi Toko Beras
 * dengan sistem role-based access control yang lengkap
 *
 * Struktur Routing:
 * 1. Public Routes - Dapat diakses tanpa login
 * 2. Cart Routes - Shopping cart dengan checkout yang memerlukan auth
 * 3. Authenticated Routes - Memerlukan login dan verifikasi
 * 4. Role-based Routes - Akses berdasarkan role user
 *
 * Role yang didukung:
 * - Admin: Full access ke semua fitur sistem
 * - Owner: Access ke dashboard, laporan, dan manajemen
 * - Karyawan: Access ke inventory dan dashboard
 * - Kasir: Access ke penjualan dan transaksi
 * - Pelanggan: Access ke shopping dan order history
 * ===================================================================
 */

// Import semua Controller yang diperlukan untuk routing
use App\Http\Controllers\BarangController;        // Controller untuk manajemen barang/inventory
use App\Http\Controllers\LaporanController;       // Controller untuk laporan dan analytics
use App\Http\Controllers\PenjualanController;     // Controller untuk transaksi penjualan
use App\Http\Controllers\UserController;          // Controller untuk manajemen user
use App\Http\Controllers\HomeController;          // Controller untuk halaman publik dan customer
use App\Http\Controllers\CartController;          // Controller untuk shopping cart
use App\Http\Controllers\ReceiptController;       // Controller untuk receipt dan pickup
use App\Http\Controllers\DashboardController;     // Controller untuk dashboard role-based
use App\Models\Role;                              // Model Role untuk konstanta role
use Illuminate\Support\Facades\Route;            // Laravel Route facade
use Inertia\Inertia;                             // Inertia.js untuk SPA dengan React

/**
 * ===================================================================
 * PUBLIC ROUTES - DAPAT DIAKSES TANPA LOGIN
 * ===================================================================
 * Routes ini dapat diakses oleh siapa saja tanpa perlu authentication
 * Digunakan untuk halaman publik seperti catalog produk
 */

// Halaman utama - menampilkan catalog produk beras untuk pelanggan
Route::get('/', [HomeController::class, 'index'])->name('home');

// Detail produk - menampilkan informasi lengkap produk beras
Route::get('/product/{barang}', [HomeController::class, 'show'])->name('product.show');

/**
 * ===================================================================
 * SHOPPING CART ROUTES
 * ===================================================================
 * Routes untuk manajemen shopping cart dengan sistem hybrid:
 * - Browsing cart dapat dilakukan tanpa login
 * - Checkout memerlukan authentication untuk keamanan transaksi
 */
Route::prefix('cart')->name('cart.')->group(function () {
    // Melihat isi keranjang belanja - dapat diakses tanpa login
    Route::get('/', [CartController::class, 'index'])->name('index');

    // Menambah produk ke keranjang - dapat dilakukan tanpa login
    Route::post('/add', [CartController::class, 'add'])->name('add');

    // Update quantity produk di keranjang
    Route::patch('/update', [CartController::class, 'update'])->name('update');

    // Hapus produk dari keranjang
    Route::delete('/remove', [CartController::class, 'remove'])->name('remove');

    // Kosongkan seluruh keranjang
    Route::delete('/clear', [CartController::class, 'clear'])->name('clear');

    /**
     * CHECKOUT ROUTES - MEMERLUKAN AUTHENTICATION
     * Proses checkout memerlukan login untuk:
     * - Keamanan transaksi
     * - Tracking order history
     * - Customer information
     */
    Route::middleware('auth')->group(function () {
        // Halaman checkout - review order sebelum payment
        Route::get('/checkout', [CartController::class, 'checkout'])->name('checkout');

        // Proses checkout - create order dan payment
        Route::post('/checkout', [CartController::class, 'processCheckout'])->name('process-checkout');
    });
});

/**
 * ===================================================================
 * AUTHENTICATED ROUTES - MEMERLUKAN LOGIN DAN VERIFIKASI EMAIL
 * ===================================================================
 * Semua routes di dalam group ini memerlukan:
 * - Authentication (user harus login)
 * - Email verification (email harus sudah diverifikasi)
 */
Route::middleware(['auth', 'verified'])->group(function () {

    /**
     * DEFAULT DASHBOARD ROUTER
     * ===================================================================
     * Route ini berfungsi sebagai router utama yang mengarahkan user
     * ke dashboard yang sesuai berdasarkan role mereka
     *
     * Flow:
     * 1. Cek role user yang sedang login
     * 2. Redirect ke dashboard yang sesuai dengan role
     * 3. Fallback ke user dashboard untuk role pelanggan
     */
    Route::get('dashboard', function () {
        // Ambil data user yang sedang login
        $user = auth()->user();

        // Redirect berdasarkan role user dengan prioritas tertinggi ke terendah
        if ($user->isAdmin()) {
            // Admin: Full access dashboard dengan analytics dan user management
            return redirect()->route('admin.dashboard');
        } elseif ($user->isOwner()) {
            // Owner: Business dashboard dengan laporan dan overview
            return redirect()->route('owner.dashboard');
        } elseif ($user->isKaryawan()) {
            // Karyawan: Inventory dashboard dengan stock management
            return redirect()->route('karyawan.dashboard');
        } elseif ($user->isKasir()) {
            // Kasir: Transaction dashboard dengan sales management
            return redirect()->route('kasir.dashboard');
        } else {
            // Pelanggan: Customer dashboard dengan order history
            return redirect()->route('user.dashboard');
        }
    })->name('dashboard');

    /**
     * ===================================================================
     * USER (PELANGGAN) ROUTES
     * ===================================================================
     * Routes khusus untuk role pelanggan dengan akses terbatas
     * Fitur: Dashboard, order history, order tracking
     */
    Route::middleware(['role:pelanggan'])->prefix('user')->name('user.')->group(function () {
        // Dashboard pelanggan - menampilkan order history dan status
        Route::get('dashboard', [HomeController::class, 'dashboard'])->name('dashboard');

        // Daftar semua order pelanggan dengan pagination dan filter
        Route::get('orders', [HomeController::class, 'orders'])->name('orders');

        // Detail order spesifik - tracking status dan informasi lengkap
        Route::get('orders/{order}', [HomeController::class, 'orderDetail'])->name('orders.show');
    });

    /**
     * ===================================================================
     * ADMIN ROUTES
     * ===================================================================
     * Routes khusus untuk role admin dengan full system access
     * Fitur: User management, system analytics, full CRUD operations
     */
    Route::middleware(['role:' . Role::ADMIN])->prefix('admin')->name('admin.')->group(function () {
        // Dashboard admin - analytics, statistics, system overview
        Route::get('dashboard', [DashboardController::class, 'adminDashboard'])->name('dashboard');

        /**
         * USER MANAGEMENT ROUTES
         * Admin dapat mengelola semua user dalam sistem
         */
        // Quick add user - form cepat untuk menambah user baru
        Route::get('users/quick-add', [\App\Http\Controllers\Admin\UserController::class, 'quickAdd'])->name('users.quick-add');

        // Resource routes untuk CRUD user (index, create, store, show, edit, update, destroy)
        Route::resource('users', \App\Http\Controllers\Admin\UserController::class);
    });

    /**
     * ===================================================================
     * OWNER ROUTES
     * ===================================================================
     * Routes khusus untuk role owner dengan akses business intelligence
     * Fitur: Business dashboard, comprehensive reports, analytics
     */
    Route::middleware(['role:' . Role::OWNER])->prefix('owner')->name('owner.')->group(function () {
        // Dashboard owner - business metrics, KPI, overview
        Route::get('dashboard', [DashboardController::class, 'ownerDashboard'])->name('dashboard');

        /**
         * LAPORAN (REPORTS) ROUTES FOR OWNER
         * Owner memiliki akses ke semua laporan bisnis
         */
        // Index laporan - overview semua jenis laporan
        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');

        // Laporan penjualan - sales analytics, revenue, trends
        Route::get('laporan/penjualan', [LaporanController::class, 'penjualan'])->name('laporan.penjualan');

        // Laporan stok - inventory analytics, stock levels, movements
        Route::get('laporan/stok', [LaporanController::class, 'stok'])->name('laporan.stok');
    });

    /**
     * ===================================================================
     * KARYAWAN ROUTES
     * ===================================================================
     * Routes khusus untuk role karyawan dengan fokus inventory management
     * Fitur: Inventory dashboard, stock monitoring
     */
    Route::middleware(['role:' . Role::KARYAWAN])->prefix('karyawan')->name('karyawan.')->group(function () {
        // Dashboard karyawan - inventory overview, stock alerts, daily tasks
        Route::get('dashboard', [DashboardController::class, 'karyawanDashboard'])->name('dashboard');
    });

    /**
     * ===================================================================
     * KASIR ROUTES
     * ===================================================================
     * Routes khusus untuk role kasir dengan fokus transaction management
     * Fitur: Sales dashboard, transaction processing, payment handling
     */
    Route::middleware(['role:' . Role::KASIR])->prefix('kasir')->name('kasir.')->group(function () {
        // Dashboard kasir - sales overview, pending transactions, daily summary
        Route::get('dashboard', [DashboardController::class, 'kasirDashboard'])->name('dashboard');
    });

    /**
     * ===================================================================
     * BARANG (INVENTORY) MANAGEMENT ROUTES
     * ===================================================================
     * Routes untuk manajemen inventory/barang
     * Akses: Admin, Owner, Karyawan
     *
     * Pembagian akses:
     * - Admin: Full CRUD access
     * - Owner: Full CRUD access + analytics
     * - Karyawan: CRUD access untuk operational needs
     */
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        // Resource routes untuk CRUD barang (index, create, store, show, edit, update, destroy)
        Route::resource('barang', BarangController::class);

        // Update stock barang - untuk adjustment stock manual
        Route::patch('barang/{barang}/stock', [BarangController::class, 'updateStock'])->name('barang.update-stock');
    });

    /**
     * ===================================================================
     * PENJUALAN (SALES) MANAGEMENT ROUTES
     * ===================================================================
     * Routes untuk manajemen penjualan dan transaksi
     * Akses: Admin, Owner, Karyawan, Kasir
     *
     * Fitur:
     * - Transaction processing (walk-in & online)
     * - Payment confirmation
     * - Order status management
     * - Receipt generation
     * - Pickup management
     */
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN . ',' . Role::KASIR])->group(function () {
        // Resource routes untuk CRUD penjualan (index, create, store, show, edit, update, destroy)
        Route::resource('penjualan', PenjualanController::class);

        // Print invoice/receipt untuk transaksi
        Route::get('penjualan/{penjualan}/print', [PenjualanController::class, 'print'])->name('penjualan.print');

        /**
         * ONLINE ORDERS MANAGEMENT
         * Khusus untuk mengelola pesanan online dari pelanggan
         */
        // Daftar pesanan online yang perlu diproses kasir
        Route::get('penjualan-online', [PenjualanController::class, 'onlineOrders'])->name('penjualan.online');

        // Konfirmasi pembayaran pesanan online
        Route::patch('penjualan/{penjualan}/confirm-payment', [PenjualanController::class, 'confirmPayment'])->name('penjualan.confirm-payment');

        // Tandai pesanan siap untuk pickup
        Route::patch('penjualan/{penjualan}/ready-pickup', [PenjualanController::class, 'readyPickup'])->name('penjualan.ready-pickup');

        // Complete transaksi setelah pickup
        Route::patch('penjualan/{penjualan}/complete', [PenjualanController::class, 'complete'])->name('penjualan.complete');

        /**
         * RECEIPT MANAGEMENT ROUTES
         * Sistem receipt untuk pickup verification
         */
        // Generate receipt untuk pickup (QR code + receipt code)
        Route::get('penjualan/{penjualan}/receipt', [ReceiptController::class, 'generatePickupReceipt'])->name('penjualan.receipt');

        // Show receipt berdasarkan receipt code
        Route::get('receipt/{receiptCode}', [ReceiptController::class, 'showPickupReceipt'])->name('receipt.show');

        // Verify receipt dan complete pickup
        Route::post('receipt/{receiptCode}/verify', [ReceiptController::class, 'verifyAndComplete'])->name('receipt.verify');
    });

    /**
     * ===================================================================
     * LAPORAN (REPORTS) ROUTES
     * ===================================================================
     * Routes untuk laporan dan analytics
     * Akses: Admin, Owner (management level)
     *
     * Fitur:
     * - Sales reports
     * - Inventory reports
     * - Business analytics
     * - Performance metrics
     */
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER])->prefix('laporan')->name('laporan.')->group(function () {
        // Index laporan - dashboard laporan dengan overview
        Route::get('/', [LaporanController::class, 'index'])->name('index');

        // Laporan penjualan - detailed sales analytics
        Route::get('penjualan', [LaporanController::class, 'penjualan'])->name('penjualan');

        // Laporan stok - inventory analytics dan stock movements
        Route::get('stok', [LaporanController::class, 'stok'])->name('stok');
    });
});

/**
 * ===================================================================
 * ADDITIONAL ROUTE FILES
 * ===================================================================
 * Include file routing tambahan untuk modularitas
 */

// Include routes untuk settings dan konfigurasi user
require __DIR__.'/settings.php';

// Include routes untuk authentication (login, register, password reset, dll)
require __DIR__.'/auth.php';
