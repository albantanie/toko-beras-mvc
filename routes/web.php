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
use App\Http\Controllers\Admin\UserController;    // Controller untuk manajemen user admin
use App\Http\Controllers\UserController as GlobalUserController; // Controller untuk manajemen user global
use App\Http\Controllers\HomeController;          // Controller untuk halaman publik dan customer
use App\Http\Controllers\CartController;          // Controller untuk shopping cart
use App\Http\Controllers\ReceiptController;       // Controller untuk receipt dan pickup
use App\Http\Controllers\DashboardController;     // Controller untuk dashboard role-based
use App\Models\Role;                              // Model Role untuk konstanta role
use Illuminate\Support\Facades\Route;            // Laravel Route facade
use Inertia\Inertia;                             // Inertia.js untuk SPA dengan React
use Illuminate\Http\Request;
use App\Http\Controllers\ReportSubmissionController; // Controller untuk report submission

/**
 * ===================================================================
 * PUBLIC ROUTES - DAPAT DIAKSES TANPA LOGIN
 * ===================================================================
 * Routes yang dapat diakses oleh semua pengunjung tanpa perlu login
 * Termasuk: home page, product listing, cart management
 */

// Halaman utama - menampilkan catalog produk beras untuk pelanggan
Route::get('/', [HomeController::class, 'index'])->name('home');

// Detail produk - menampilkan informasi lengkap produk beras
Route::get('/product/{barang}', [HomeController::class, 'show'])->name('product.show');

// System Validation Route - untuk testing CRUD systems
Route::get('/system/validate', [\App\Http\Controllers\SystemValidationController::class, 'validateAllSystems'])
    ->middleware('auth')
    ->name('system.validate');

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

        // Upload ulang bukti pembayaran untuk orders yang ditolak
        Route::post('orders/{order}/upload-payment-proof', [HomeController::class, 'uploadPaymentProof'])->name('upload-payment-proof');
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

        // User management - CRUD operations
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::get('users/create', [UserController::class, 'create'])->name('users.create');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::get('users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::post('users/{user}/update', [UserController::class, 'update'])->name('users.update.post');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
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

        /**
         * ===================================================================
         * OWNER EXCLUSIVE ROUTES - DASHBOARD & DOWNLOAD
         * ===================================================================
         * Routes khusus untuk owner: dashboard dan download laporan
         */
        // Download laporan dalam format PDF/Excel
        Route::get('download-report', [LaporanController::class, 'downloadReport'])->name('download-report');
    });

    /**
     * ===================================================================
     * KARYAWAN ROUTES
     * ===================================================================
     * Routes khusus untuk role karyawan dengan fokus inventory management
     * Fitur: Inventory dashboard, stock monitoring, laporan barang
     *
     * PEMBATASAN AKSES KARYAWAN:
     * - Hanya bisa manage barang (CRUD inventory)
     * - Hanya bisa generate laporan barang (perlu approval owner)
     * - TIDAK bisa akses penjualan, transaksi, atau laporan keuangan
     */
    Route::middleware(['role:' . Role::KARYAWAN])->prefix('karyawan')->name('karyawan.')->group(function () {
        // Dashboard karyawan - inventory overview, stock alerts, daily tasks
        Route::get('dashboard', [DashboardController::class, 'karyawanDashboard'])->name('dashboard');

        /**
         * LAPORAN BARANG UNTUK KARYAWAN
         * Karyawan hanya bisa generate laporan barang yang perlu approval owner
         */
        // Daftar laporan barang yang dibuat karyawan
        Route::get('laporan-barang', [LaporanController::class, 'laporanBarangKaryawan'])->name('laporan.barang.index');

        // Form create laporan barang baru
        Route::get('laporan-barang/create', function () {
            return Inertia::render('karyawan/laporan-barang/create');
        })->name('laporan.barang.create');

        // Generate laporan barang baru
        Route::post('laporan-barang/generate', [LaporanController::class, 'generateLaporanBarang'])->name('laporan.barang.generate');

        // Submit laporan untuk approval owner
        Route::patch('laporan-barang/{report}/submit', [LaporanController::class, 'submitForApproval'])->name('laporan.barang.submit');

        // View detail laporan barang
        Route::get('laporan-barang/{report}', [LaporanController::class, 'showLaporanBarang'])->name('laporan.barang.show');
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
     *
     * Pembagian akses:
     * - Admin & Owner: Full CRUD access + harga beli + financial data
     * - Karyawan: Full CRUD access tapi tidak bisa lihat harga beli/profit
     */

    // Routes untuk Admin, Owner, dan Karyawan - Full CRUD access
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        // CRUD barang - semua role bisa manage inventory
        Route::get('barang', [BarangController::class, 'index'])->name('barang.index');
        Route::get('barang/create', [BarangController::class, 'create'])->name('barang.create');
        Route::post('barang', [BarangController::class, 'store'])->name('barang.store');
        Route::get('barang/{barang}', [BarangController::class, 'show'])->name('barang.show');
        Route::get('barang/{barang}/edit', [BarangController::class, 'edit'])->name('barang.edit');
        Route::put('barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
        Route::post('barang/{barang}/update', [BarangController::class, 'update'])->name('barang.update.post');
        Route::delete('barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');

        // Stock movements - riwayat perubahan stok
        Route::get('barang/{barang}/stock-movements', [BarangController::class, 'stockMovements'])->name('barang.stock-movements');

        // Update stock barang - untuk adjustment stock manual
        Route::patch('barang/{barang}/stock', [BarangController::class, 'updateStock'])->name('barang.update-stock');
    });

    /**
     * ===================================================================
     * PENJUALAN (SALES) MANAGEMENT ROUTES
     * ===================================================================
     * Routes untuk manajemen penjualan dan transaksi
     * Akses: Admin, Owner, Kasir (TIDAK TERMASUK KARYAWAN)
     *
     * Fitur:
     * - Transaction processing (walk-in & online)
     * - Payment confirmation
     * - Order status management
     * - Receipt generation
     * - Pickup management
     *
     * CATATAN: Karyawan tidak perlu mengakses data transaksi penjualan
     */
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KASIR])->group(function () {
        // History transaksi - riwayat lengkap semua transaksi dengan role-based access
        Route::get('penjualan/history', [PenjualanController::class, 'history'])->name('penjualan.history');

        // Resource routes untuk CRUD penjualan (index, create, store, show, edit, update, destroy)
        Route::resource('penjualan', PenjualanController::class);
        Route::post('penjualan/{penjualan}/update', [PenjualanController::class, 'update'])->name('penjualan.update.post');

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

        // Reject bukti pembayaran
        Route::patch('penjualan/{penjualan}/reject-payment', [PenjualanController::class, 'rejectPayment'])->name('penjualan.reject-payment');

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
     * Akses: Admin, Owner, Karyawan (dengan pembatasan jenis laporan)
     *
     * Pembatasan akses:
     * - Admin/Owner: Semua jenis laporan
     * - Karyawan: Hanya laporan barang/inventory (tidak dapat akses laporan penjualan)
     * - Kasir: Tidak dapat akses sistem laporan
     *
     * Fitur:
     * - Sales reports (Admin/Owner only)
     * - Inventory reports (Admin/Owner/Karyawan)
     * - Business analytics
     * - Performance metrics
     * - Report generation and approval workflow
     */
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN])->prefix('laporan')->name('laporan.')->group(function () {
        // Index laporan - dashboard laporan dengan overview
        Route::get('/', [LaporanController::class, 'index'])->name('index');

        // Laporan penjualan - detailed sales analytics
        Route::get('penjualan', [LaporanController::class, 'penjualan'])->name('penjualan');

        // Laporan stok - inventory analytics dan stock movements
        Route::get('stok', [LaporanController::class, 'stok'])->name('stok');

        // ===== REPORT GENERATION & APPROVAL WORKFLOW =====
        
        // Daftar laporan yang sudah di-generate
        Route::get('reports', [LaporanController::class, 'reports'])->name('reports');
        
        // Generate laporan baru
        Route::post('generate', [LaporanController::class, 'generate'])->name('generate');
        
        // Show detail laporan
        Route::get('reports/{report}', [LaporanController::class, 'show'])->name('show');
        
        // Submit laporan untuk approval
        Route::patch('reports/{report}/submit', [LaporanController::class, 'submitForApproval'])->name('submit');
    });

    /**
     * ===================================================================
     * OWNER EXCLUSIVE ROUTES - APPROVAL WORKFLOW
     * ===================================================================
     * Routes khusus untuk owner: approve/reject laporan
     */
    Route::middleware(['role:' . Role::OWNER])->prefix('laporan')->name('laporan.')->group(function () {
        // Approve laporan
        Route::patch('reports/{report}/approve', [LaporanController::class, 'approve'])->name('approve');
        
        // Reject laporan
        Route::patch('reports/{report}/reject', [LaporanController::class, 'reject'])->name('reject');
    });

    /**
     * ===================================================================
     * ADMIN/OWNER EXCLUSIVE ROUTES - HISTORY TRANSACTION REPORT
     * ===================================================================
     * Routes khusus untuk admin/owner: laporan history transaksi komprehensif
     */
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER])->prefix('laporan')->name('laporan.')->group(function () {
        // Laporan history transaksi komprehensif dengan analytics
        Route::get('history-transaction', [LaporanController::class, 'historyTransaction'])->name('history-transaction');
    });

    /**
     * ===================================================================
     * DUAL CROSSCHECK REPORT SYSTEM ROUTES
     * ===================================================================
     * Routes untuk sistem dual crosscheck laporan dari kasir dan karyawan
     */
    Route::prefix('report-submissions')->name('report-submissions.')->group(function () {
        // SEMUA ROUTE STRING LITERAL HARUS DI ATAS ROUTE PARAMETER {reportSubmission}
        
        // Routes untuk kasir dan karyawan - string literal
        Route::middleware(['role:' . Role::KASIR . ',' . Role::KARYAWAN])->group(function () {
            Route::get('my-reports', [ReportSubmissionController::class, 'myReports'])->name('my-reports');
            Route::get('create', [ReportSubmissionController::class, 'create'])->name('create');
            Route::post('store', [ReportSubmissionController::class, 'store'])->name('store');
            Route::get('crosscheck-list', [ReportSubmissionController::class, 'crosscheckList'])->name('crosscheck-list');
        });

        // Routes untuk owner - string literal
        Route::middleware(['role:' . Role::OWNER])->group(function () {
            Route::get('owner-approval-list', [ReportSubmissionController::class, 'ownerApprovalList'])->name('owner-approval-list');
        });

        // Routes untuk admin - string literal
        Route::middleware(['role:' . Role::ADMIN])->group(function () {
            Route::get('admin-approval-list', [ReportSubmissionController::class, 'adminApprovalList'])->name('admin-approval-list');
        });

        // Route untuk download PDF
        Route::get('download-pdf/{path}', [ReportSubmissionController::class, 'downloadPdf'])->name('download-pdf')->where('path', '.*');

        // Route show untuk semua role yang relevan (kasir, karyawan, owner, admin)
        Route::middleware(['role:' . Role::KASIR . ',' . Role::KARYAWAN . ',' . Role::OWNER . ',' . Role::ADMIN])->group(function () {
            Route::get('{id}', [ReportSubmissionController::class, 'show'])->name('show')->where('id', '[0-9]+');
        });

        // ROUTE PARAMETER {reportSubmission} HARUS DI BAWAH SEMUA STRING LITERAL
        
        // Routes untuk kasir dan karyawan - dengan parameter
        Route::middleware(['role:' . Role::KASIR . ',' . Role::KARYAWAN])->group(function () {
            Route::patch('{id}/submit', [ReportSubmissionController::class, 'submitForCrosscheck'])->name('submit')->where('id', '[0-9]+');
            Route::get('{id}/edit', [ReportSubmissionController::class, 'edit'])->name('edit')->where('id', '[0-9]+');
            Route::put('{id}', [ReportSubmissionController::class, 'update'])->name('update')->where('id', '[0-9]+');
            Route::delete('{id}', [ReportSubmissionController::class, 'destroy'])->name('destroy')->where('id', '[0-9]+');
            Route::patch('{id}/crosscheck-approve', [ReportSubmissionController::class, 'crosscheckApprove'])->name('crosscheck-approve')->where('id', '[0-9]+');
            Route::patch('{id}/crosscheck-reject', [ReportSubmissionController::class, 'crosscheckReject'])->name('crosscheck-reject')->where('id', '[0-9]+');
        });

        // Routes untuk owner - dengan parameter
        Route::middleware(['role:' . Role::OWNER])->group(function () {
            Route::patch('{id}/owner-approve', [ReportSubmissionController::class, 'ownerApprove'])->name('owner-approve')->where('id', '[0-9]+');
            Route::patch('{id}/owner-reject', [ReportSubmissionController::class, 'ownerReject'])->name('owner-reject')->where('id', '[0-9]+');
        });

        // Routes untuk admin - dengan parameter
        Route::middleware(['role:' . Role::ADMIN])->group(function () {
            Route::patch('{id}/admin-approve', [ReportSubmissionController::class, 'adminApprove'])->name('admin-approve')->where('id', '[0-9]+');
            Route::patch('{id}/admin-reject', [ReportSubmissionController::class, 'adminReject'])->name('admin-reject')->where('id', '[0-9]+');
        });
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

/**
 * ===================================================================
 * DOCUMENTATION ROUTES
 * ===================================================================
 * Route untuk download dokumentasi PDF
 */
Route::get('/documentation/download', [\App\Http\Controllers\DocumentationController::class, 'downloadPdf'])->name('documentation.download');
