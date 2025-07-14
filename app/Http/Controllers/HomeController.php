<?php

/**
 * ===================================================================
 * HOME CONTROLLER - CONTROLLER UNTUK HALAMAN PUBLIK DAN PELANGGAN
 * ===================================================================
 *
 * Controller ini menangani:
 * 1. Halaman utama dengan catalog produk beras
 * 2. Detail produk untuk pelanggan
 * 3. Dashboard pelanggan setelah login
 * 4. Order history dan tracking untuk pelanggan
 *
 * Target User: Pelanggan (role: pelanggan) dan pengunjung umum
 * ===================================================================
 */

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Barang;
use App\Models\Penjualan;

class HomeController extends Controller
{
    /**
     * ===================================================================
     * HALAMAN UTAMA - CATALOG PRODUK BERAS
     * ===================================================================
     *
     * Menampilkan catalog produk beras dengan fitur:
     * - Search berdasarkan nama, kode, atau kategori
     * - Filter berdasarkan kategori
     * - Sorting berdasarkan nama, harga, kategori, tanggal
     * - Pagination dengan 12 produk per halaman
     * - Statistics untuk hero section
     *
     * @param Request $request - Request dengan parameter search, filter, sort
     * @return Response - Halaman catalog dengan data produk
     */
    public function index(Request $request)
    {
        // Redirect authenticated users to their respective dashboards
        if (auth()->check()) {
            $user = auth()->user();

            // Check user roles and redirect accordingly
            if ($user->hasRole('owner')) {
                return redirect()->route('owner.dashboard');
            } elseif ($user->hasRole('admin')) {
                return redirect()->route('admin.dashboard');
            } elseif ($user->hasRole('kasir')) {
                return redirect()->route('kasir.dashboard');
            } elseif ($user->hasRole('karyawan')) {
                return redirect()->route('karyawan.dashboard');
            }
            // If user is pelanggan or has no specific role, continue to catalog
        }

        // Ambil parameter dari request untuk filtering dan sorting
        $search = $request->get('search');        // Kata kunci pencarian
        $kategori = $request->get('kategori');    // Filter kategori
        $sort = $request->get('sort', 'nama');    // Field untuk sorting (default: nama)
        $direction = $request->get('direction', 'asc'); // Arah sorting (asc/desc)

        // Query dasar: hanya tampilkan produk yang masih ada stoknya
        $query = Barang::where('stok', '>', 0);

        /**
         * FITUR PENCARIAN
         * Cari berdasarkan nama produk, kode barang, atau kategori
         */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")           // Cari di nama produk
                  ->orWhere('kode_barang', 'like', "%{$search}%")  // Cari di kode barang
                  ->orWhere('kategori', 'like', "%{$search}%");    // Cari di kategori
            });
        }

        /**
         * FILTER KATEGORI
         * Filter produk berdasarkan kategori yang dipilih
         */
        if ($kategori && $kategori !== 'all') {
            $query->where('kategori', $kategori);
        }

        /**
         * SORTING PRODUK
         * Urutkan produk berdasarkan field yang diizinkan
         */
        $allowedSorts = ['nama', 'harga_jual', 'kategori', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            // Default sorting berdasarkan nama secara ascending
            $query->orderBy('nama', 'asc');
        }

        /**
         * PAGINATION
         * Tampilkan 12 produk per halaman untuk tampilan catalog yang optimal
         * withQueryString() mempertahankan parameter search dan filter di pagination
         */
        $barangs = $query->paginate(12)->withQueryString();

        /**
         * AMBIL DAFTAR KATEGORI
         * Untuk dropdown filter kategori di frontend
         */
        $categories = Barang::select('kategori')
            ->distinct()                    // Ambil kategori unik
            ->whereNotNull('kategori')      // Hanya kategori yang tidak null
            ->where('kategori', '!=', '')   // Hanya kategori yang tidak kosong
            ->orderBy('kategori')           // Urutkan alfabetis
            ->pluck('kategori');

        /**
         * STATISTIK UNTUK HERO SECTION
         * Data statistik yang ditampilkan di bagian atas halaman
         */
        $stats = [
            // Total produk yang masih ada stoknya
            'total_products' => Barang::where('stok', '>', 0)->count(),

            // Total kategori produk yang tersedia
            'total_categories' => $categories->count(),

            // Total pelanggan yang terdaftar
            'total_customers' => \App\Models\User::whereHas('roles', function ($q) {
                $q->where('name', 'pelanggan');
            })->count(),
        ];

        /**
         * RENDER HALAMAN CATALOG
         * Kirim data ke frontend menggunakan Inertia.js
         */
        return Inertia::render('home/index', [
            'barangs' => $barangs,          // Data produk dengan pagination
            'categories' => $categories,     // Daftar kategori untuk filter
            'stats' => $stats,              // Statistik untuk hero section
            'filters' => [                  // Current filter state untuk maintain UI
                'search' => $search,
                'kategori' => $kategori,
                'sort' => $sort,
                'direction' => $direction,
            ],
            'cartCount' => $this->getCartCount(), // Jumlah item di keranjang
        ]);
    }

    /**
     * ===================================================================
     * DETAIL PRODUK
     * ===================================================================
     *
     * Menampilkan halaman detail produk dengan informasi lengkap:
     * - Informasi produk (nama, harga, stok, deskripsi, dll)
     * - Gambar produk
     * - Produk terkait dari kategori yang sama
     * - Tombol add to cart
     *
     * @param Barang $barang - Model produk yang akan ditampilkan
     * @return Response - Halaman detail produk
     */
    public function show(Barang $barang): Response
    {
        /**
         * AMBIL PRODUK TERKAIT
         * Tampilkan 4 produk lain dari kategori yang sama
         * untuk meningkatkan cross-selling
         */
        $relatedProducts = Barang::where('kategori', $barang->kategori)  // Kategori sama
            ->where('id', '!=', $barang->id)                            // Exclude produk saat ini
            ->where('stok', '>', 0)                                     // Hanya yang ada stoknya
            ->take(4)                                                   // Ambil maksimal 4 produk
            ->get();

        /**
         * RENDER HALAMAN DETAIL PRODUK
         * Kirim data produk dan produk terkait ke frontend
         */
        return Inertia::render('home/product-detail', [
            'barang' => $barang,                    // Data produk utama
            'relatedProducts' => $relatedProducts,  // Produk terkait untuk rekomendasi
        ]);
    }

    /**
     * ===================================================================
     * DASHBOARD PELANGGAN
     * ===================================================================
     *
     * Dashboard khusus untuk pelanggan yang sudah login dengan fitur:
     * - Overview order terbaru (5 order terakhir)
     * - Statistik order (total, pending, completed, total spent)
     * - Quick access ke fitur-fitur pelanggan
     *
     * @return Response - Dashboard pelanggan
     */
    public function dashboard(): Response
    {
        // Ambil data user yang sedang login
        $user = auth()->user();

        /**
         * AMBIL ORDER TERBARU PELANGGAN
         * Tampilkan 5 order terakhir dengan detail produk
         * untuk quick overview aktivitas belanja
         */
        $recentOrders = Penjualan::where('pelanggan_id', $user->id)
            ->with(['detailPenjualans.barang'])         // Load relasi detail dan produk
            ->orderBy('tanggal_transaksi', 'desc')      // Urutkan dari yang terbaru
            ->take(5)                                   // Ambil 5 order terakhir
            ->get();

        /**
         * STATISTIK ORDER PELANGGAN
         * Data statistik untuk dashboard cards
         */
        $orderStats = [
            // Total semua order pelanggan
            'total_orders' => Penjualan::where('pelanggan_id', $user->id)->count(),

            // Order yang masih pending/belum selesai
            'pending_orders' => Penjualan::where('pelanggan_id', $user->id)
                ->where('status', 'pending')->count(),

            // Order yang sudah completed
            'completed_orders' => Penjualan::where('pelanggan_id', $user->id)
                ->where('status', 'selesai')->count(),

            // Total uang yang sudah dibelanjakan
            'total_spent' => Penjualan::where('pelanggan_id', $user->id)
                ->where('status', 'selesai')->sum('total'),
        ];

        /**
         * RENDER DASHBOARD PELANGGAN
         * Kirim data order dan statistik ke frontend
         */
        return Inertia::render('home/dashboard', [
            'recentOrders' => $recentOrders,    // 5 order terbaru
            'orderStats' => $orderStats,        // Statistik order untuk cards
        ]);
    }

    /**
     * ===================================================================
     * DAFTAR ORDER PELANGGAN
     * ===================================================================
     *
     * Menampilkan semua order pelanggan dengan fitur:
     * - Pagination (10 order per halaman)
     * - Order history lengkap
     * - Status tracking untuk setiap order
     * - Detail produk dalam setiap order
     *
     * @return Response - Halaman daftar order pelanggan
     */
    public function orders(): Response
    {
        // Ambil data user yang sedang login
        $user = auth()->user();

        /**
         * AMBIL SEMUA ORDER PELANGGAN
         * Dengan pagination dan relasi detail produk
         */
        $orders = Penjualan::where('pelanggan_id', $user->id)
            ->with(['detailPenjualans.barang'])         // Load detail order dan produk
            ->orderBy('tanggal_transaksi', 'desc')      // Urutkan dari yang terbaru
            ->paginate(10);                             // 10 order per halaman

        /**
         * RENDER HALAMAN DAFTAR ORDER
         * Kirim data order dengan pagination ke frontend
         */
        return Inertia::render('home/orders', [
            'orders' => $orders,    // Data order dengan pagination
        ]);
    }

    /**
     * ===================================================================
     * DETAIL ORDER SPESIFIK
     * ===================================================================
     *
     * Menampilkan detail lengkap dari satu order tertentu dengan:
     * - Informasi order (nomor, tanggal, status, total)
     * - Detail semua produk dalam order
     * - Informasi pelanggan
     * - Security check untuk memastikan order milik user yang login
     *
     * @param Penjualan $order - Model order yang akan ditampilkan
     * @return Response - Halaman detail order
     */
    public function orderDetail(Penjualan $order): Response
    {
        /**
         * SECURITY CHECK
         * Pastikan order yang diakses adalah milik user yang sedang login
         * Mencegah akses unauthorized ke order orang lain
         */
        if ($order->pelanggan_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        /**
         * LOAD RELASI DATA
         * Load detail order, produk, dan informasi user
         */
        $order->load(['detailPenjualans.barang', 'user']);

        /**
         * RENDER HALAMAN DETAIL ORDER
         * Kirim data order lengkap ke frontend
         */
        return Inertia::render('home/order-detail', [
            'order' => $order,      // Data order lengkap dengan relasi
        ]);
    }

    /**
     * ===================================================================
     * UPLOAD ULANG BUKTI PEMBAYARAN
     * ===================================================================
     *
     * Menangani upload ulang bukti pembayaran untuk orders yang ditolak
     * - Validasi file upload
     * - Security check untuk memastikan order milik user
     * - Update status order kembali ke pending
     * - Clear rejection reason
     *
     * @param Request $request - Request dengan file bukti pembayaran
     * @param Penjualan $order - Model order yang akan diupdate
     * @return \Illuminate\Http\RedirectResponse - Redirect dengan flash message
     */
    public function uploadPaymentProof(Request $request, Penjualan $order): \Illuminate\Http\RedirectResponse
    {
        /**
         * SECURITY CHECK
         * Pastikan order yang diakses adalah milik user yang sedang login
         */
        if ($order->pelanggan_id !== auth()->id()) {
            return redirect()->back()->with('error', 'Unauthorized access to this order');
        }

        /**
         * VALIDASI REQUEST
         * Pastikan file bukti pembayaran diupload dengan benar
         */
        $request->validate([
            'payment_proof' => 'required|file|mimes:jpeg,jpg,png,pdf|max:2048',
        ], [
            'payment_proof.required' => 'Bukti pembayaran harus diupload',
            'payment_proof.file' => 'File yang diupload tidak valid',
            'payment_proof.mimes' => 'Format file harus JPEG, JPG, PNG, atau PDF',
            'payment_proof.max' => 'Ukuran file maksimal 2MB',
        ]);

        try {
            /**
             * UPLOAD FILE
             * Simpan file bukti pembayaran ke storage
             */
            $file = $request->file('payment_proof');
            $fileName = 'payment_proof_' . $order->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $filePath = $file->storeAs('payment_proofs', $fileName, 'public');

            /**
             * UPDATE ORDER
             * Update status order dan clear rejection reason
             */
            $order->update([
                'payment_proof' => $filePath,
                'status' => 'pending',
                'payment_rejection_reason' => null,
                'payment_rejected_at' => null,
                'payment_rejected_by' => null,
            ]);

            /**
             * LOG UNTUK AUDIT TRAIL
             */
            \Log::info('Payment proof re-uploaded', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'file_path' => $filePath,
            ]);

            return redirect()->route('user.orders')->with('success', 'Bukti pembayaran berhasil diupload ulang. Pesanan akan direview oleh kasir.');

        } catch (\Exception $e) {
            \Log::error('Failed to upload payment proof', [
                'order_id' => $order->id,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with('error', 'Gagal mengupload bukti pembayaran: ' . $e->getMessage());
        }
    }

    /**
     * ===================================================================
     * GET CART COUNT
     * ===================================================================
     *
     * Menghitung jumlah item di keranjang belanja
     * Digunakan untuk menampilkan badge di header
     *
     * @return int Jumlah item di keranjang
     */
    private function getCartCount(): int
    {
        $cart = session()->get('cart', []);
        $quantities = array_column($cart, 'quantity');
        // Ensure all quantities are integers
        $quantities = array_map('intval', $quantities);
        return array_sum($quantities);
    }
}
