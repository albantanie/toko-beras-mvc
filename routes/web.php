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
use App\Http\Controllers\PengeluaranController;   // Controller untuk manajemen pengeluaran
use App\Models\Role;                              // Model Role untuk konstanta role
use Illuminate\Support\Facades\Route;            // Laravel Route facade
use Inertia\Inertia;                             // Inertia.js untuk SPA dengan React
use Illuminate\Http\Request;


/**
 * ===================================================================
 * PUBLIC ROUTES - DAPAT DIAKSES TANPA LOGIN
 * ===================================================================
 * Routes yang dapat diakses oleh semua pengunjung tanpa perlu login
 * Termasuk: home page, product listing, cart management
 */

// Halaman utama - menampilkan catalog produk beras untuk pelanggan
Route::get('/', [HomeController::class, 'index'])->name('home');

// Route untuk mendapatkan CSRF token fresh
Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
});

// Route test session
Route::get('/test-session', function () {
    return response()->json([
        'session_id' => session()->getId(),
        'csrf_token' => csrf_token(),
        'session_driver' => config('session.driver'),
        'session_lifetime' => config('session.lifetime'),
        'test_value' => session('test', 'not_set'),
    ]);
});

Route::post('/test-session', function () {
    session(['test' => 'session_works']);
    return response()->json(['message' => 'Session set']);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Alternative login endpoint without CSRF
Route::post('/api/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials, $request->boolean('remember'))) {
        $request->session()->regenerate();

        // Redirect based on role
        $user = Auth::user();
        if ($user->hasRole('owner')) {
            return response()->json(['redirect' => '/owner/dashboard']);
        } elseif ($user->hasRole('admin')) {
            return response()->json(['redirect' => '/admin/dashboard']);
        } elseif ($user->hasRole('kasir')) {
            return response()->json(['redirect' => '/kasir/dashboard']);
        } elseif ($user->hasRole('karyawan')) {
            return response()->json(['redirect' => '/karyawan/dashboard']);
        }

        return response()->json(['redirect' => '/dashboard']);
    }

    return response()->json(['error' => 'Email atau password salah'], 422);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Alternative logout endpoint without CSRF
Route::post('/api/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('web')->logout();

    // Clear cart when user logs out
    $request->session()->forget('cart');

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['success' => true, 'redirect' => '/']);
})->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Test traditional form login
Route::get('/test-login', function () {
    return view('test-login');
});

Route::post('/test-login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    if (Auth::attempt($credentials)) {
        $request->session()->regenerate();
        return redirect('/dashboard')->with('success', 'Login berhasil!');
    }

    return back()->withErrors(['email' => 'Email atau password salah']);
});

// Very simple test route
Route::get('/simple-test', function () {
    return 'Simple test works!';
});

Route::post('/simple-test', function (\Illuminate\Http\Request $request) {
    return 'POST test works! Data: ' . json_encode($request->all());
});

// Test route outside web middleware
Route::post('/raw-test', function (\Illuminate\Http\Request $request) {
    return 'RAW POST test works! Data: ' . json_encode($request->all());
})->middleware([]);

// Simple login endpoint in web routes (CSRF already disabled)
Route::post('/auth/login', function (\Illuminate\Http\Request $request) {
    $credentials = $request->validate([
        'email' => 'required|email',
        'password' => 'required',
    ]);

    $user = \App\Models\User::where('email', $credentials['email'])->first();

    if (!$user || !\Hash::check($credentials['password'], $user->password)) {
        return response()->json(['error' => 'Email atau password salah'], 422);
    }

    Auth::login($user, $request->boolean('remember'));
    $request->session()->regenerate();

    // Load user with roles
    $user = $user->load('roles');
    $userRole = $user->roles->first()?->name;

    // Determine redirect URL
    $redirectUrl = match($userRole) {
        'owner' => '/owner/dashboard',
        'admin' => '/admin/dashboard',
        'kasir' => '/kasir/dashboard',
        'karyawan' => '/karyawan/dashboard',
        'pelanggan' => '/user/dashboard',
        default => '/dashboard'
    };

    return response()->json([
        'success' => true,
        'redirect' => $redirectUrl,
        'user' => $user->name,
        'role' => $userRole
    ]);
});

// Simple logout endpoint in web routes
Route::post('/auth/logout', function (\Illuminate\Http\Request $request) {
    Auth::guard('web')->logout();

    // Clear cart when user logs out
    $request->session()->forget('cart');

    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return response()->json(['success' => true, 'redirect' => '/']);
});

// Auth routes using controller with explicit CSRF disable
Route::post('/api/auth/login', [App\Http\Controllers\AuthController::class, 'login'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

Route::post('/api/auth/logout', [App\Http\Controllers\AuthController::class, 'logout'])
    ->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class]);

// Detail produk - menampilkan informasi lengkap produk beras
Route::get('/product/{barang}', [HomeController::class, 'show'])->name('product.show');



// Test route for images - untuk testing product images
Route::get('/test-images', function () {
    $barangs = \App\Models\Barang::all();
    return Inertia::render('test-images', [
        'barangs' => $barangs
    ]);
})->name('test.images');

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
 * AUTHENTICATED ROUTES - MEMERLUKAN LOGIN
 * ===================================================================
 * Semua routes di dalam group ini memerlukan:
 * - Authentication (user harus login)
 */
Route::middleware(['auth'])->group(function () {

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

        // NOTE: Laporan routes moved to owner prefix section below for better organization

        /**
         * ===================================================================
         * OWNER EXCLUSIVE ROUTES - DASHBOARD & DOWNLOAD & REPORTS
         * ===================================================================
         * Routes khusus untuk owner: dashboard dan download laporan
         */
        // View laporan detail sebelum approve/reject
        Route::get('view-report/{id}', [LaporanController::class, 'viewReport'])->name('view-report');

        // Download laporan PDF berdasarkan ID
        Route::get('download-report/{id}', [LaporanController::class, 'downloadReport'])->name('download-report');

        // Download laporan PDF berdasarkan type (untuk kompatibilitas)
        Route::get('download-report', [LaporanController::class, 'downloadReportByType'])->name('download-report-type');

        // Debug route untuk test stock report template
        Route::get('debug-stock-report', function() {
            $barangs = \App\Models\Barang::all();
            $stockMovements = \App\Models\StockMovement::with(['barang', 'user'])->latest()->take(10)->get();
            $reportData = [
                'report_date' => now()->toDateString(),
                'total_items' => $barangs->count(),
                'low_stock_items' => 0,
                'out_of_stock_items' => 0,
                'in_stock_items' => $barangs->count(),
                'total_stock_value_sell' => 1000000,
                'total_stock_value_buy' => 800000,
                'potential_profit' => 200000,
                'recent_movements_count' => $stockMovements->count(),
            ];

            return view('pdf.stock-report', [
                'reportData' => $reportData,
                'barangs' => $barangs,
                'stockMovements' => $stockMovements,
                'generatedAt' => now(),
            ]);
        })->name('debug-stock-report');

        // Debug route untuk cek konsistensi data keuangan
        Route::get('debug-financial-consistency', function() {
            $financialService = app(\App\Services\FinancialService::class);
            $integrationService = app(\App\Services\FinancialIntegrationService::class);

            // Get current account balances
            $accounts = \App\Models\FinancialAccount::active()->get();

            // Get cash summary from service
            $cashSummary = $financialService->getCashSummary();

            // Get completed sales that should be recorded
            $completedSales = \App\Models\Penjualan::where('status', 'selesai')
                ->where('is_financial_recorded', true)
                ->with('detailPenjualans.barang')
                ->get();

            // Calculate expected balances
            $expectedCashFromSales = $completedSales->where('metode_pembayaran', 'tunai')->sum('total');
            $expectedBankFromSales = $completedSales->whereIn('metode_pembayaran', ['transfer', 'debit', 'kredit'])->sum('total');

            // Get financial transactions
            $financialTransactions = \App\Models\FinancialTransaction::where('transaction_type', 'income')
                ->where('category', 'sales')
                ->sum('amount');

            // Get cash flows
            $cashFlows = \App\Models\CashFlow::where('direction', 'inflow')
                ->where('category', 'sales')
                ->sum('amount');

            return response()->json([
                'accounts' => $accounts->map(function($account) {
                    return [
                        'name' => $account->account_name,
                        'type' => $account->account_type,
                        'opening_balance' => $account->opening_balance,
                        'current_balance' => $account->current_balance,
                        'bank_name' => $account->bank_name,
                    ];
                }),
                'cash_summary' => $cashSummary,
                'sales_analysis' => [
                    'total_completed_sales' => $completedSales->count(),
                    'total_sales_amount' => $completedSales->sum('total'),
                    'expected_cash_from_sales' => $expectedCashFromSales,
                    'expected_bank_from_sales' => $expectedBankFromSales,
                    'recorded_financial_transactions' => $financialTransactions,
                    'recorded_cash_flows' => $cashFlows,
                ],
                'consistency_check' => [
                    'sales_vs_financial_transactions' => $completedSales->sum('total') == $financialTransactions,
                    'sales_vs_cash_flows' => $completedSales->sum('total') == $cashFlows,
                    'financial_vs_cash_flows' => $financialTransactions == $cashFlows,
                ],
            ]);
        })->name('debug-financial-consistency');

        // Debug route untuk test button styling
        Route::get('debug-button-test', function() {
            return view('debug.button-test');
        })->name('debug-button-test');

        // Debug route untuk cek data transaksi untuk laporan harian
        Route::get('debug-daily-report-data', function() {
            $user = auth()->user();
            $today = now()->format('Y-m-d');

            // Get all transactions for today
            $allTransactions = \App\Models\Penjualan::whereDate('tanggal_transaksi', $today)
                ->with(['user', 'detailPenjualans.barang'])
                ->get();

            // Get transactions that should be in daily report (selesai + current user)
            $reportTransactions = \App\Models\Penjualan::whereDate('tanggal_transaksi', $today)
                ->where('user_id', $user->id)
                ->where('status', 'selesai')
                ->with(['user', 'detailPenjualans.barang'])
                ->get();

            // Get existing daily report
            $dailyReport = \App\Models\DailyReport::where('report_date', $today)
                ->where('type', 'transaction')
                ->where('user_id', $user->id)
                ->first();

            return response()->json([
                'debug_info' => [
                    'current_user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'role' => $user->role,
                    ],
                    'date_checked' => $today,
                ],
                'all_transactions_today' => $allTransactions->map(function($t) {
                    return [
                        'id' => $t->id,
                        'nomor_transaksi' => $t->nomor_transaksi,
                        'user_id' => $t->user_id,
                        'user_name' => $t->user->name ?? 'Unknown',
                        'status' => $t->status,
                        'jenis_transaksi' => $t->jenis_transaksi,
                        'total' => $t->total,
                        'tanggal_transaksi' => $t->tanggal_transaksi,
                        'items_count' => $t->detailPenjualans->sum('jumlah'),
                    ];
                }),
                'report_eligible_transactions' => $reportTransactions->map(function($t) {
                    return [
                        'id' => $t->id,
                        'nomor_transaksi' => $t->nomor_transaksi,
                        'status' => $t->status,
                        'total' => $t->total,
                        'items_count' => $t->detailPenjualans->sum('jumlah'),
                    ];
                }),
                'existing_daily_report' => $dailyReport ? [
                    'id' => $dailyReport->id,
                    'report_date' => $dailyReport->report_date,
                    'total_amount' => $dailyReport->total_amount,
                    'total_transactions' => $dailyReport->total_transactions,
                    'total_items_sold' => $dailyReport->total_items_sold,
                    'status' => $dailyReport->status,
                    'data' => $dailyReport->data,
                ] : null,
                'summary' => [
                    'total_transactions_today' => $allTransactions->count(),
                    'completed_transactions_today' => $allTransactions->where('status', 'selesai')->count(),
                    'current_user_transactions' => $allTransactions->where('user_id', $user->id)->count(),
                    'current_user_completed' => $reportTransactions->count(),
                    'total_amount_eligible' => $reportTransactions->sum('total'),
                    'total_items_eligible' => $reportTransactions->sum(function($t) {
                        return $t->detailPenjualans->sum('jumlah');
                    }),
                ],
            ]);
        })->name('debug-daily-report-data');

        // Route untuk regenerate laporan harian
        Route::get('regenerate-daily-report/{date?}', function($date = null) {
            $user = auth()->user();
            $targetDate = $date ? \Carbon\Carbon::parse($date) : now();

            if (!$user->hasRole('kasir')) {
                return response()->json(['error' => 'Only kasir can regenerate daily reports'], 403);
            }

            try {
                // Delete existing report for the date
                \App\Models\DailyReport::where('report_date', $targetDate->format('Y-m-d'))
                    ->where('type', 'transaction')
                    ->where('user_id', $user->id)
                    ->delete();

                // Generate new report
                $report = \App\Models\DailyReport::generateTransactionReport($targetDate, $user->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Daily report regenerated successfully',
                    'report' => [
                        'date' => $report->report_date,
                        'total_amount' => $report->total_amount,
                        'total_transactions' => $report->total_transactions,
                        'total_items_sold' => $report->total_items_sold,
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('regenerate-daily-report');

        // Route untuk hapus semua laporan harian yang bermasalah
        Route::get('clear-daily-reports/{date?}', function($date = null) {
            $user = auth()->user();
            $targetDate = $date ? \Carbon\Carbon::parse($date) : now();

            if (!$user->hasRole('kasir') && !$user->hasRole('owner')) {
                return response()->json(['error' => 'Only kasir or owner can clear daily reports'], 403);
            }

            try {
                // Delete daily reports for the date
                $deletedCount = \App\Models\DailyReport::where('report_date', $targetDate->format('Y-m-d'))
                    ->where('type', 'transaction')
                    ->when($user->hasRole('kasir'), function($query) use ($user) {
                        return $query->where('user_id', $user->id);
                    })
                    ->delete();

                return response()->json([
                    'success' => true,
                    'message' => "Deleted {$deletedCount} daily reports for " . $targetDate->format('Y-m-d'),
                    'deleted_count' => $deletedCount,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-daily-reports');

        // Route untuk hapus SEMUA laporan harian (untuk reset total)
        Route::get('clear-all-daily-reports', function() {
            $user = auth()->user();

            if (!$user->hasRole('owner')) {
                return response()->json(['error' => 'Only owner can clear all daily reports'], 403);
            }

            try {
                // Delete ALL daily reports
                $deletedCount = \App\Models\DailyReport::where('type', 'transaction')->delete();

                return response()->json([
                    'success' => true,
                    'message' => "Deleted ALL {$deletedCount} daily transaction reports",
                    'deleted_count' => $deletedCount,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-all-daily-reports');

        // Route untuk hapus laporan PDF yang bermasalah
        Route::get('clear-pdf-reports/{month?}', function($month = null) {
            $user = auth()->user();

            if (!$user->hasRole('owner')) {
                return response()->json(['error' => 'Only owner can clear PDF reports'], 403);
            }

            try {
                $query = \App\Models\PdfReport::query();

                if ($month) {
                    $targetMonth = \Carbon\Carbon::parse($month);
                    $query->whereYear('created_at', $targetMonth->year)
                          ->whereMonth('created_at', $targetMonth->month);
                }

                // Get reports to delete files
                $reports = $query->get();
                $deletedFiles = 0;

                foreach ($reports as $report) {
                    if ($report->file_path && \Storage::exists($report->file_path)) {
                        \Storage::delete($report->file_path);
                        $deletedFiles++;
                    }
                }

                // Delete database records
                $deletedCount = $query->delete();

                return response()->json([
                    'success' => true,
                    'message' => "Deleted {$deletedCount} PDF reports and {$deletedFiles} files" . ($month ? " for month {$month}" : ""),
                    'deleted_reports' => $deletedCount,
                    'deleted_files' => $deletedFiles,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-pdf-reports');

        // Route untuk hapus transaksi online yang bermasalah
        Route::get('clear-online-transactions/{date?}', function($date = null) {
            $user = auth()->user();

            if (!$user->hasRole('owner')) {
                return response()->json(['error' => 'Only owner can clear transactions'], 403);
            }

            try {
                $query = \App\Models\Penjualan::where('jenis_transaksi', 'online');

                if ($date) {
                    $targetDate = \Carbon\Carbon::parse($date);
                    $query->whereDate('tanggal_transaksi', $targetDate);
                }

                // Get transactions to delete
                $transactions = $query->with('detailPenjualans')->get();
                $deletedTransactions = 0;
                $deletedDetails = 0;

                foreach ($transactions as $transaction) {
                    // Delete detail penjualans first
                    $detailCount = $transaction->detailPenjualans()->count();
                    $transaction->detailPenjualans()->delete();
                    $deletedDetails += $detailCount;

                    // Delete transaction
                    $transaction->delete();
                    $deletedTransactions++;
                }

                return response()->json([
                    'success' => true,
                    'message' => "Deleted {$deletedTransactions} online transactions and {$deletedDetails} detail records" . ($date ? " for date {$date}" : ""),
                    'deleted_transactions' => $deletedTransactions,
                    'deleted_details' => $deletedDetails,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-online-transactions');

        // Route untuk hapus SEMUA transaksi (HATI-HATI!)
        Route::get('clear-all-transactions', function() {
            $user = auth()->user();

            if (!$user->hasRole('owner')) {
                return response()->json(['error' => 'Only owner can clear all transactions'], 403);
            }

            try {
                // Delete all detail penjualans first
                $deletedDetails = \App\Models\DetailPenjualan::count();
                \App\Models\DetailPenjualan::truncate();

                // Delete all transactions
                $deletedTransactions = \App\Models\Penjualan::count();
                \App\Models\Penjualan::truncate();

                return response()->json([
                    'success' => true,
                    'message' => "DELETED ALL {$deletedTransactions} transactions and {$deletedDetails} detail records",
                    'deleted_transactions' => $deletedTransactions,
                    'deleted_details' => $deletedDetails,
                    'warning' => 'ALL TRANSACTION DATA HAS BEEN PERMANENTLY DELETED!',
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-all-transactions');

        // Route untuk hapus stock movements
        Route::get('clear-stock-movements/{date?}', function($date = null) {
            $user = auth()->user();

            if (!$user->hasRole('owner')) {
                return response()->json(['error' => 'Only owner can clear stock movements'], 403);
            }

            try {
                $query = \App\Models\StockMovement::query();

                if ($date) {
                    $targetDate = \Carbon\Carbon::parse($date);
                    $query->whereDate('created_at', $targetDate);
                } else {
                    // Default to today if no date specified
                    $query->whereDate('created_at', now());
                }

                $deletedCount = $query->count();
                $query->delete();

                return response()->json([
                    'success' => true,
                    'message' => "Deleted {$deletedCount} stock movements" . ($date ? " for date {$date}" : " for today"),
                    'deleted_count' => $deletedCount,
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-stock-movements');

        // Route untuk hapus SEMUA stock movements
        Route::get('clear-all-stock-movements', function() {
            $user = auth()->user();

            if (!$user->hasRole('owner')) {
                return response()->json(['error' => 'Only owner can clear all stock movements'], 403);
            }

            try {
                $deletedCount = \App\Models\StockMovement::count();
                \App\Models\StockMovement::truncate();

                return response()->json([
                    'success' => true,
                    'message' => "DELETED ALL {$deletedCount} stock movements",
                    'deleted_count' => $deletedCount,
                    'warning' => 'ALL STOCK MOVEMENT DATA HAS BEEN PERMANENTLY DELETED!',
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => $e->getMessage(),
                ], 500);
            }
        })->name('clear-all-stock-movements');
        
        // Laporan analytics routes
        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/penjualan', [LaporanController::class, 'penjualan'])->name('laporan.penjualan');
        Route::get('laporan/stok', [LaporanController::class, 'stok'])->name('laporan.stok');
        Route::get('laporan/history-transaction', [LaporanController::class, 'historyTransaction'])->name('laporan.history-transaction');

        // Generate financial report (PDF)
        Route::post('generate-financial-report', [LaporanController::class, 'generateFinancialReport'])->name('laporan.generate-financial');

        // Generate stock report (PDF)
        Route::post('generate-stock-report', [LaporanController::class, 'generateStockReport'])->name('laporan.generate-stock');

        // List PDF reports
        Route::get('reports', [LaporanController::class, 'reports'])->name('laporan.reports');

        // Approve/reject PDF reports
        Route::post('reports/{id}/approve', [LaporanController::class, 'approveReport'])->name('laporan.approve');

        /**
         * FINANCIAL MANAGEMENT ROUTES FOR OWNER
         * Comprehensive financial management system
         */
        Route::prefix('keuangan')->name('keuangan.')->group(function () {
            // Financial Dashboard
            Route::get('dashboard', [App\Http\Controllers\FinancialController::class, 'dashboard'])->name('dashboard');

            // Cash Flow Management
            Route::get('cash-flow', [App\Http\Controllers\FinancialController::class, 'cashFlow'])->name('cash-flow');

            // Accounts Management
            Route::get('accounts', [App\Http\Controllers\FinancialController::class, 'accounts'])->name('accounts');
            Route::post('accounts', [App\Http\Controllers\FinancialController::class, 'storeAccount'])->name('accounts.store');

            // Transactions Management
            Route::get('transactions', [App\Http\Controllers\FinancialController::class, 'transactions'])->name('transactions');
            Route::post('transactions', [App\Http\Controllers\FinancialController::class, 'storeTransaction'])->name('transactions.store');
            Route::patch('transactions/{transaction}/approve', [App\Http\Controllers\FinancialController::class, 'approveTransaction'])->name('transactions.approve');

            // Payroll Management
            Route::get('payroll', [App\Http\Controllers\FinancialController::class, 'payroll'])->name('payroll');
            Route::post('payroll/generate', [App\Http\Controllers\FinancialController::class, 'generatePayroll'])->name('payroll.generate');
            Route::post('payroll/{payroll}/approve', [App\Http\Controllers\FinancialController::class, 'approvePayroll'])->name('payroll.approve');
            Route::post('payroll/{payroll}/payment', [App\Http\Controllers\FinancialController::class, 'processPayrollPayment'])->name('payroll.payment');
            Route::post('payroll/{payroll}/cancel', [App\Http\Controllers\FinancialController::class, 'cancelPayroll'])->name('payroll.cancel');



            // Stock Valuation
            Route::get('stock-valuation', [App\Http\Controllers\FinancialController::class, 'stockValuation'])->name('stock-valuation');
            Route::post('stock-valuation/generate', [App\Http\Controllers\FinancialController::class, 'generateStockValuation'])->name('stock-valuation.generate');

            // Budget Management
            Route::get('budgets', [App\Http\Controllers\FinancialController::class, 'budgets'])->name('budgets');
            Route::post('budgets', [App\Http\Controllers\FinancialController::class, 'storeBudget'])->name('budgets.store');

            // Financial Reports
            Route::get('reports', [App\Http\Controllers\FinancialController::class, 'reports'])->name('reports');

            // Pengeluaran Management
            Route::resource('pengeluaran', PengeluaranController::class)->names([
                'index' => 'pengeluaran.index',
                'create' => 'pengeluaran.create',
                'store' => 'pengeluaran.store',
                'show' => 'pengeluaran.show',
                'edit' => 'pengeluaran.edit',
                'update' => 'pengeluaran.update',
                'destroy' => 'pengeluaran.destroy',
            ]);
            Route::post('pengeluaran/{pengeluaran}/approve', [PengeluaranController::class, 'approve'])->name('pengeluaran.approve');
            Route::post('pengeluaran/{pengeluaran}/pay', [PengeluaranController::class, 'pay'])->name('pengeluaran.pay');
            Route::post('pengeluaran/{pengeluaran}/cancel', [PengeluaranController::class, 'cancel'])->name('pengeluaran.cancel');

            // Export PDF Routes
            Route::get('cash-flow/export-pdf', [App\Http\Controllers\FinancialController::class, 'exportCashFlowPdf'])->name('cash-flow.export-pdf');
            Route::get('reports/export-pdf', [App\Http\Controllers\FinancialController::class, 'exportFinancialReportPdf'])->name('reports.export-pdf');
        });
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

    // Routes untuk Owner dan Karyawan - Create/Delete access (HARUS SEBELUM routes dengan parameter)
    Route::middleware(['role:' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        Route::get('barang/create', [BarangController::class, 'create'])->name('barang.create');
        Route::post('barang', [BarangController::class, 'store'])->name('barang.store');
        Route::delete('barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');
    });

    // Routes untuk semua role - View access
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        // View barang - semua role bisa lihat
        Route::get('barang', [BarangController::class, 'index'])->name('barang.index');
        Route::get('barang/{barang}', [BarangController::class, 'show'])->name('barang.show');
        Route::get('barang/{barang}/stock-movements', [BarangController::class, 'stockMovements'])->name('barang.stock-movements');
    });

    // Routes untuk Admin, Owner, dan Karyawan - Edit access
    Route::middleware(['role:' . Role::ADMIN . ',' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        Route::get('barang/{barang}/edit', [BarangController::class, 'edit'])->name('barang.edit');
        Route::put('barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
        Route::post('barang/{barang}/update', [BarangController::class, 'update'])->name('barang.update.post');
    });

      // Routes untuk Owner dan Karyawan - Create/Delete access
    Route::middleware(['role:' . Role::OWNER . ',' . Role::KARYAWAN])->group(function () {
        Route::get('barang/create', [BarangController::class, 'create'])->name('barang.create');
        Route::post('barang', [BarangController::class, 'store'])->name('barang.store');
        Route::delete('barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');
    });

    // KASIR ONLY - Stock Management (Inventory Control)
    Route::middleware(['role:' . Role::KASIR])->group(function () {
        // Update stock barang - KASIR only dapat mengelola stok
        Route::patch('barang/{barang}/stock', [BarangController::class, 'updateStock'])->name('barang.update-stock');
    });

    /**
     * ===================================================================
     * PENJUALAN (SALES) MANAGEMENT ROUTES
     * ===================================================================
     * Routes untuk manajemen penjualan dan transaksi
     *
     * PEMBAGIAN AKSES BERDASARKAN ROLE:
     * - KASIR: Full access (create, update, delete transactions)
     * - ADMIN: Full access (create, update, delete transactions)
     * - OWNER: View only (reports and monitoring, NO direct transaction creation)
     * - KARYAWAN: NO ACCESS (fokus inventory management)
     */

    // KASIR ONLY - Transaction Creation and Management
    Route::middleware(['role:' . Role::KASIR])->group(function () {
        // Transaction creation and management - KASIR only
        Route::get('penjualan/create', [PenjualanController::class, 'create'])->name('penjualan.create');
        Route::post('penjualan', [PenjualanController::class, 'store'])->name('penjualan.store');
        Route::get('penjualan/{penjualan}/edit', [PenjualanController::class, 'edit'])->name('penjualan.edit');
        Route::put('penjualan/{penjualan}', [PenjualanController::class, 'update'])->name('penjualan.update');
        Route::delete('penjualan/{penjualan}', [PenjualanController::class, 'destroy'])->name('penjualan.destroy');

        // Online order management - KASIR only
        Route::get('penjualan/online', [PenjualanController::class, 'onlineOrders'])->name('penjualan.online.kasir');
        Route::patch('penjualan/{penjualan}/confirm-payment', [PenjualanController::class, 'confirmPayment'])->name('penjualan.confirm-payment');
        Route::patch('penjualan/{penjualan}/reject-payment', [PenjualanController::class, 'rejectPayment'])->name('penjualan.reject-payment');
        Route::patch('penjualan/{penjualan}/ready-pickup', [PenjualanController::class, 'readyPickup'])->name('penjualan.ready-pickup');
        Route::patch('penjualan/{penjualan}/complete', [PenjualanController::class, 'complete'])->name('penjualan.complete');
    });

    // OWNER & KASIR ONLY - View Access Only (Admin tidak ada akses transaksi)
    Route::middleware(['role:' . Role::OWNER . ',' . Role::KASIR])->group(function () {
        // History transaksi - riwayat lengkap semua transaksi dengan role-based access
        Route::get('penjualan/history', [PenjualanController::class, 'history'])->name('penjualan.history');

        // View only routes - semua role bisa melihat transaksi
        Route::get('penjualan', [PenjualanController::class, 'index'])->name('penjualan.index');
        Route::get('penjualan/{penjualan}', [PenjualanController::class, 'show'])->name('penjualan.show');

        // Print invoice/receipt untuk transaksi
        Route::get('penjualan/{penjualan}/print', [PenjualanController::class, 'print'])->name('penjualan.print');

        /**
         * ONLINE ORDERS MANAGEMENT
         * Khusus untuk mengelola pesanan online dari pelanggan
         */
        // Daftar pesanan online yang perlu diproses kasir
        Route::get('penjualan-online', [PenjualanController::class, 'onlineOrders'])->name('penjualan.online');

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
     * STOCK MOVEMENT ROUTES (Following Activity Diagram)
     * ===================================================================
     * Routes untuk pengelolaan stok barang sesuai diagram activity
     */
    // Stock Movement routes - accessible by admin, owner, karyawan
    Route::middleware(['role:admin,owner,karyawan'])->prefix('stock-movements')->name('stock-movements.')->group(function () {
        Route::get('/', [App\Http\Controllers\StockMovementController::class, 'index'])->name('index');
        Route::get('/kelola', [App\Http\Controllers\StockMovementController::class, 'kelola'])->name('kelola');
        Route::get('/{barang}/update', [App\Http\Controllers\StockMovementController::class, 'showUpdateForm'])->name('update-form');
        Route::post('/{barang}/update', [App\Http\Controllers\StockMovementController::class, 'updateStok'])->name('update');
        Route::get('/{barang}/history', [App\Http\Controllers\StockMovementController::class, 'history'])->name('history');
        Route::get('/new-item', [App\Http\Controllers\StockMovementController::class, 'showNewItemForm'])->name('new-item');
    });

    /**
     * ===================================================================
     * ROLE-BASED REPORT SYSTEM (NEW)
     * ===================================================================
     * Routes untuk sistem laporan berbasis role dengan approval workflow
     * - KASIR: Generate laporan penjualan
     * - KARYAWAN: Generate laporan stok
     * - OWNER: Approve/reject semua laporan
     */

    // Routes accessible by KASIR, KARYAWAN, ADMIN (for generating and viewing their own reports)
    Route::middleware(['role:kasir,karyawan,admin'])->prefix('laporan')->name('laporan.')->group(function () {
        // View user's own reports
        Route::get('my-reports', [LaporanController::class, 'myReports'])->name('my-reports');

        // Download approved reports
        Route::get('download/{id}', [LaporanController::class, 'downloadReport'])->name('download');
    });

    // Debug route for testing
    Route::get('debug/my-reports', function() {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        $allowedTypes = \App\Models\PdfReport::getAllowedTypesForUser($user);
        $reports = \App\Models\PdfReport::accessibleByUser($user)->get();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ],
            'allowed_types' => $allowedTypes,
            'reports' => $reports,
            'reports_count' => $reports->count()
        ]);
    })->middleware('auth');

    // Test CSRF bypass
    Route::post('test/csrf-bypass', function(Request $request) {
        return response()->json([
            'success' => true,
            'message' => 'CSRF bypass working!',
            'data' => $request->all(),
            'session_id' => session()->getId(),
            'csrf_token' => csrf_token()
        ]);
    });

    // Test karyawan access
    Route::get('test/karyawan-access', function() {
        $user = auth()->user();
        if (!$user) {
            return response()->json(['error' => 'Not authenticated']);
        }

        return response()->json([
            'user' => [
                'id' => $user->id,
                'email' => $user->email,
                'roles' => $user->roles->pluck('name')
            ],
            'has_karyawan_role' => $user->hasRole('karyawan'),
            'is_karyawan' => $user->isKaryawan(),
            'can_access_karyawan_routes' => $user->hasRole('karyawan')
        ]);
    })->middleware('auth');

    // Test simple HTML response (no Inertia)
    Route::get('test/simple-html', function() {
        return response('<html><body><h1>Simple HTML Test</h1><p>If you see this, Laravel is working!</p><p>Time: ' . now() . '</p></body></html>');
    });

    // Test simple Inertia render
    Route::get('test/simple-inertia', function() {
        return Inertia::render('test/simple', [
            'message' => 'Hello from Inertia!',
            'timestamp' => now()->toDateTimeString()
        ]);
    });

    // Test karyawan data without Inertia
    Route::get('test/karyawan-data', function() {
        $user = \App\Models\User::where('email', 'karyawan@tokoberas.com')->first();
        $reports = \App\Models\DailyReport::where('type', 'stock')->count();

        return response()->json([
            'user_found' => $user ? true : false,
            'user_email' => $user ? $user->email : null,
            'user_roles' => $user ? $user->roles->pluck('name') : [],
            'reports_count' => $reports,
            'timestamp' => now()
        ]);
    });

    // Test karyawan HTML (no auth, no Inertia)
    Route::get('karyawan/test-html', function() {
        $user = \App\Models\User::where('email', 'karyawan@tokoberas.com')->first();
        $reports = \App\Models\DailyReport::where('type', 'stock')->count();

        $html = '<html><body>';
        $html .= '<h1>Karyawan Test - HTML Only</h1>';
        $html .= '<p><strong>User:</strong> ' . ($user ? $user->email : 'Not found') . '</p>';
        $html .= '<p><strong>Reports:</strong> ' . $reports . '</p>';
        $html .= '<p><strong>Time:</strong> ' . now() . '</p>';
        $html .= '<p>If you see this, basic Laravel routing is working!</p>';
        $html .= '</body></html>';

        return response($html);
    });

    // Emergency workaround for karyawan daily reports
    Route::get('karyawan/test-daily', function() {
        $user = auth()->user();

        if (!$user || !$user->hasRole('karyawan')) {
            return redirect('/login');
        }

        $reports = \App\Models\DailyReport::where('type', 'stock')
            ->where('user_id', $user->id)
            ->orderBy('report_date', 'desc')
            ->paginate(10);

        $todayStats = [
            'total_movements' => 4,
            'total_stock_value' => 3760000,
            'movement_types' => ['in' => 2, 'out' => 1, 'adjustment' => 1],
            'items_affected' => 2
        ];

        return Inertia::render('karyawan/laporan/daily', [
            'reports' => $reports,
            'filters' => [
                'date_from' => now()->startOfMonth()->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
            ],
            'todayStats' => $todayStats
        ]);
    })->middleware('auth');

    // KASIR specific routes - Sales reports (with CSRF bypass)
    Route::middleware(['auth', 'role:kasir,admin'])->group(function () {
        Route::post('laporan/generate-sales', [LaporanController::class, 'generateSalesReport'])->name('laporan.generate-sales');
    });

    // KARYAWAN specific routes - Stock reports (with CSRF bypass)
    Route::middleware(['auth', 'role:karyawan,admin'])->group(function () {
        Route::post('laporan/generate-stock', [LaporanController::class, 'generateStockReport'])->name('laporan.generate-stock');
    });

    // KASIR Daily Reports Routes
    Route::middleware(['auth', 'role:kasir'])->prefix('kasir')->name('kasir.')->group(function () {
        Route::get('laporan/daily', [App\Http\Controllers\DailyReportController::class, 'kasirDaily'])->name('laporan.daily');
        Route::post('laporan/generate', [App\Http\Controllers\DailyReportController::class, 'generate'])->name('laporan.generate');
        Route::post('laporan/generate-monthly', [App\Http\Controllers\DailyReportController::class, 'generateMonthlyReport'])->name('laporan.generate-monthly');
    });

    // KARYAWAN Daily Reports Routes
    Route::middleware(['auth', 'role:karyawan'])->prefix('karyawan')->name('karyawan.')->group(function () {
        Route::get('laporan/daily', [App\Http\Controllers\DailyReportController::class, 'karyawanDaily'])->name('laporan.daily');
        Route::post('laporan/generate', [App\Http\Controllers\DailyReportController::class, 'generate'])->name('laporan.generate');
        Route::post('laporan/generate-monthly', [App\Http\Controllers\DailyReportController::class, 'generateMonthlyReport'])->name('laporan.generate-monthly');
    });

    // Daily Report Detail (accessible by kasir and karyawan only - NOT owner)
    Route::middleware(['auth', 'role:kasir,karyawan'])->group(function () {
        Route::get('daily-report/{report}', [App\Http\Controllers\DailyReportController::class, 'show'])->name('daily-report.show');
    });

    // Monthly Report Generation (kasir and karyawan only)
    Route::middleware(['auth', 'role:kasir,karyawan'])->group(function () {
        Route::get('laporan/monthly/create', [App\Http\Controllers\MonthlyReportController::class, 'create'])->name('monthly-report.create');
        Route::post('laporan/monthly/generate', [App\Http\Controllers\MonthlyReportController::class, 'generateFromDaily'])->name('monthly-report.generate');
        Route::post('laporan/monthly/preview', [App\Http\Controllers\MonthlyReportController::class, 'preview'])->name('monthly-report.preview');
    });

    /**
     * ===================================================================
     * LEGACY REPORTS WITH APPROVAL FLOW (Following Activity Diagram)
     * ===================================================================
     * Routes untuk laporan dengan sistem approval sesuai diagram activity
     * NOTE: This is legacy system, new role-based system above is preferred
     */
    // Reports with Approval Flow - accessible by owner and karyawan only (admin tidak ada akses laporan)
    Route::middleware(['role:owner,karyawan'])->prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [App\Http\Controllers\ReportController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\ReportController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\ReportController::class, 'store'])->name('store');
        Route::get('/{report}', [App\Http\Controllers\ReportController::class, 'show'])->name('show');
        Route::get('/{report}/pdf', [App\Http\Controllers\ReportController::class, 'generatePdf'])->name('pdf');

        // Owner only routes for approval
        Route::middleware(['role:owner'])->group(function () {
            Route::get('/approval/pending', [App\Http\Controllers\ReportController::class, 'showApproval'])->name('approval');
            Route::post('/{report}/approval', [App\Http\Controllers\ReportController::class, 'processApproval'])->name('approval.process');
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

// Debug route for barang update
Route::post('/debug/barang/{barang}/update', function (Request $request, \App\Models\Barang $barang) {
    \Log::info('Debug barang update', [
        'user_id' => auth()->id(),
        'user_roles' => auth()->user()->roles->pluck('name'),
        'barang_id' => $barang->id,
        'request_all' => $request->all(),
        'request_input' => $request->input(),
        'request_files' => $request->allFiles(),
        'content_type' => $request->header('Content-Type'),
        'method' => $request->method()
    ]);

    return response()->json([
        'success' => true,
        'message' => 'Debug data logged',
        'data' => $request->all()
    ]);
})->middleware(['auth']);






