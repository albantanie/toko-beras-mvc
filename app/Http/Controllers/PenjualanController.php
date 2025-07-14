<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\DetailPenjualan;
use App\Models\FinancialAccount;
use App\Models\Penjualan;
use App\Models\User;
use App\Services\SalesFinancialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

/**
 * PenjualanController - Controller untuk mengelola transaksi penjualan
 *
 * Controller ini menangani semua operasi terkait transaksi penjualan:
 * - Listing transaksi dengan filter dan pencarian
 * - Pembuatan transaksi baru (offline dan online)
 * - Update status transaksi dan pembayaran
 * - Manajemen pickup dan receipt
 * - Validasi stok dan perhitungan total
 *
 * @package App\Http\Controllers
 */
class PenjualanController extends Controller
{
    protected $salesFinancialService;

    public function __construct(SalesFinancialService $salesFinancialService)
    {
        $this->salesFinancialService = $salesFinancialService;
    }
    /**
     * Menampilkan daftar transaksi penjualan
     *
     * Menampilkan listing transaksi dengan fitur:
     * - Pencarian berdasarkan nomor transaksi, nama pelanggan, atau kasir
     * - Filter berdasarkan status, metode pembayaran, dan tanggal
     * - Sorting berdasarkan berbagai field
     * - Pagination untuk performa yang optimal
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $metode_pembayaran = $request->get('metode_pembayaran');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');
        $sort = $request->get('sort', 'tanggal_transaksi');
        $direction = $request->get('direction', 'desc');

        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang']);

        // Search functionality
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Payment method filter
        if ($metode_pembayaran) {
            $query->where('metode_pembayaran', $metode_pembayaran);
        }

        // Date range filter
        if ($date_from) {
            $query->whereDate('tanggal_transaksi', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('tanggal_transaksi', '<=', $date_to);
        }

        // Sorting
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        // Pagination with 5 items per page
        $penjualans = $query->paginate(5)->withQueryString();

        // Debug logging untuk troubleshooting pagination
        \Log::info('Penjualan pagination debug', [
            'total_count' => $penjualans->total(),
            'current_page' => $penjualans->currentPage(),
            'per_page' => $penjualans->perPage(),
            'data_count' => count($penjualans->items()),
            'search' => $search,
            'status' => $status,
        ]);

        return Inertia::render('penjualan/index', [
            'penjualans' => $penjualans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'metode_pembayaran' => $metode_pembayaran,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Menampilkan history semua transaksi penjualan
     *
     * Menampilkan riwayat lengkap transaksi dengan fitur:
     * - Pencarian berdasarkan nomor transaksi, nama pelanggan, atau kasir
     * - Filter berdasarkan status, metode pembayaran, dan tanggal
     * - Sorting berdasarkan berbagai field
     * - Pagination untuk performa yang optimal
     * - Role-based access: kasir bisa edit/delete, admin/owner hanya view
     */
    public function history(Request $request): Response
    {
        // Ambil parameter dari request
        $search = $request->get('search');                    // Kata kunci pencarian
        $status = $request->get('status', 'all');             // Filter status transaksi
        $metode_pembayaran = $request->get('metode_pembayaran', ''); // Filter metode pembayaran
        $date_from = $request->get('date_from');              // Filter tanggal dari
        $date_to = $request->get('date_to');                  // Filter tanggal sampai
        $sort = $request->get('sort', 'tanggal_transaksi');   // Field untuk sorting
        $direction = $request->get('direction', 'desc');      // Arah sorting (asc/desc)

        /**
         * QUERY BUILDER DENGAN RELASI
         * Load relasi user, pelanggan, dan detail penjualan untuk menampilkan data lengkap
         */
        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang']);

        /**
         * FITUR PENCARIAN
         * Mencari berdasarkan nomor transaksi, nama pelanggan, atau nama kasir
         */
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', function ($q2) use ($search) {
                      $q2->where('name', 'like', "%{$search}%");
                  })
                  ->orWhereHas('user', function ($q3) use ($search) {
                      $q3->where('name', 'like', "%{$search}%");
                  });
            });
        }

        /**
         * FILTER BERDASARKAN STATUS TRANSAKSI
         * Filter berdasarkan status transaksi (pending, selesai, dibatalkan)
         */
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        /**
         * FILTER BERDASARKAN METODE PEMBAYARAN
         * Filter berdasarkan metode pembayaran yang digunakan
         */
        if ($metode_pembayaran) {
            $query->where('metode_pembayaran', $metode_pembayaran);
        }

        /**
         * FILTER BERDASARKAN TANGGAL
         * Filter transaksi berdasarkan rentang tanggal
         */
        if ($date_from) {
            $query->whereDate('tanggal_transaksi', '>=', $date_from);
        }
        if ($date_to) {
            $query->whereDate('tanggal_transaksi', '<=', $date_to);
        }

        /**
         * SORTING TRANSAKSI
         * Urutkan berdasarkan field yang diizinkan
         */
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        /**
         * PAGINATION
         * Ambil data dengan pagination untuk performa yang optimal
         */
        $penjualans = $query->paginate(10)->withQueryString();

        return Inertia::render('penjualan/history', [
            'penjualans' => $penjualans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'metode_pembayaran' => $metode_pembayaran,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): Response
    {
        $barangs = Barang::active()->orderBy('nama')->get();
        $pelanggans = User::whereHas('roles', function ($q) {
            $q->where('name', 'pelanggan');
        })->orderBy('name')->get();

        /**
         * FILTER DATA UNTUK KASIR
         * Sembunyikan harga_beli dari kasir untuk melindungi informasi sensitif
         */
        $currentUser = auth()->user();
        $isKasir = $currentUser && $currentUser->roles()->where('name', 'kasir')->exists();
        
        if ($isKasir) {
            $barangs = $barangs->map(function ($barang) {
                $barangArray = $barang->toArray();
                unset($barangArray['harga_beli']);
                return $barangArray;
            });
        }

        // Get financial accounts for payment method selection
        $financialAccounts = FinancialAccount::select('id', 'account_name', 'account_type', 'current_balance')
            ->whereIn('account_name', ['Kas Utama', 'Bank BCA'])
            ->where('is_active', true)
            ->get();

        return Inertia::render('penjualan/create', [
            'barangs' => $barangs,
            'pelanggans' => $pelanggans,
            'nomor_transaksi' => Penjualan::generateNomorTransaksi(),
            'financialAccounts' => $financialAccounts,
            'is_kasir' => $isKasir,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|in:tunai,transfer_bca',
            'catatan' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            // Create penjualan
            $penjualan = Penjualan::create([
                'nomor_transaksi' => 'TRX-' . date('Ymd') . '-' . str_pad(Penjualan::count() + 1, 4, '0', STR_PAD_LEFT),
                'nama_pelanggan' => $request->nama_pelanggan,
                'user_id' => auth()->id(),
                'subtotal' => 0,
                'total' => 0,
                'metode_pembayaran' => $request->metode_pembayaran,
                'status' => 'selesai',
                'catatan' => $request->catatan,
                'tanggal_transaksi' => now(),
            ]);

            $total = 0;

            // Process items with pessimistic locking
            foreach ($request->items as $item) {
                // Lock row barang untuk update - mencegah race condition
                $barang = Barang::lockForUpdate()->findOrFail($item['barang_id']);
                
                // Check stock availability after locking
                if ($barang->stok < $item['jumlah']) {
                    throw new \Exception("Stok tidak mencukupi untuk {$barang->nama}. Stok tersedia: {$barang->stok}");
                }

                // Create detail penjualan
                $detailPenjualan = DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $item['jumlah'] * $item['harga_satuan'],
                ]);

                $total += $detailPenjualan->subtotal;

                // Record stock movement dan kurangi stok sekaligus
                // recordStockMovement akan otomatis mengurangi stok untuk type 'out'
                $barang->recordStockMovement(
                    type: 'out',
                    quantity: $item['jumlah'], // Positive quantity, recordStockMovement akan handle pengurangan
                    description: "Penjualan - {$penjualan->nomor_transaksi}",
                    userId: auth()->id(),
                    referenceType: 'App\Models\DetailPenjualan',
                    referenceId: $detailPenjualan->id,
                    unitPrice: $item['harga_satuan'],
                    metadata: [
                        'penjualan_id' => $penjualan->id,
                        'detail_penjualan_id' => $detailPenjualan->id,
                        'customer_name' => $request->nama_pelanggan,
                        'metode_pembayaran' => $request->metode_pembayaran,
                    ]
                );
            }

            // Update total and set payment amount to exact total (no change)
            $penjualan->update([
                'subtotal' => $total,
                'total' => $total,
                'bayar' => $total,
                'kembalian' => 0
            ]);

            // Record and complete financial transaction (offline sales are immediately completed)
            try {
                $financialResult = $this->salesFinancialService->recordSalesTransaction($penjualan);

                // Since offline sales are immediately completed, also complete the transaction
                $completionResult = $this->salesFinancialService->completeTransaction($penjualan);

                \Log::info('Offline sales financial transaction created and completed', [
                    'penjualan_id' => $penjualan->id,
                    'transaction_id' => $financialResult['transaction']->id,
                    'amount' => $total,
                    'payment_method' => $request->metode_pembayaran,
                    'account_balance' => $financialResult['account']->current_balance,
                    'status' => 'completed_immediately'
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to record/complete sales financial transaction', [
                    'penjualan_id' => $penjualan->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the sale, just log the error
            }

            DB::commit();

            return redirect()->route('penjualan.show', $penjualan)
                ->with('success', 'Transaksi berhasil dibuat, stok diperbarui, dan keuangan tercatat');

        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Gagal membuat transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Penjualan $penjualan): Response
    {
        $penjualan->load(['user', 'pelanggan', 'detailPenjualans.barang']);

        /**
         * FILTER DATA UNTUK KASIR
         * Sembunyikan harga_beli dari kasir untuk melindungi informasi sensitif
         */
        $currentUser = auth()->user();
        $isKasir = $currentUser && $currentUser->roles()->where('name', 'kasir')->exists();
        
        $penjualanData = $penjualan->toArray();
        if ($isKasir && isset($penjualanData['detail_penjualans'])) {
            foreach ($penjualanData['detail_penjualans'] as &$detail) {
                if (isset($detail['barang']['harga_beli'])) {
                    unset($detail['barang']['harga_beli']);
                }
            }
        }

        return Inertia::render('penjualan/show', [
            'penjualan' => $penjualanData,
            'is_kasir' => $isKasir,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Penjualan $penjualan): Response
    {
        if ($penjualan->status !== 'pending') {
            return redirect()->route('penjualan.show', $penjualan)
                ->with('error', 'Hanya transaksi pending yang dapat diedit');
        }

        $penjualan->load(['detailPenjualans.barang']);
        $barangs = Barang::active()->orderBy('nama')->get();
        $pelanggans = User::whereHas('roles', function ($q) {
            $q->where('name', 'pelanggan');
        })->orderBy('name')->get();

        /**
         * FILTER DATA UNTUK KASIR
         * Sembunyikan harga_beli dari kasir untuk melindungi informasi sensitif
         */
        $currentUser = auth()->user();
        $isKasir = $currentUser && $currentUser->roles()->where('name', 'kasir')->exists();
        
        if ($isKasir) {
            $barangs = $barangs->map(function ($barang) {
                $barangArray = $barang->toArray();
                unset($barangArray['harga_beli']);
                return $barangArray;
            });
            
            // Also filter barang data in detail penjualans
            $penjualanArray = $penjualan->toArray();
            if (isset($penjualanArray['detail_penjualans'])) {
                foreach ($penjualanArray['detail_penjualans'] as &$detail) {
                    if (isset($detail['barang']['harga_beli'])) {
                        unset($detail['barang']['harga_beli']);
                    }
                }
            }
            $penjualan = $penjualanArray;
        }

        return Inertia::render('penjualan/edit', [
            'penjualan' => $penjualan,
            'barangs' => $barangs,
            'pelanggans' => $pelanggans,
            'is_kasir' => $isKasir,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->status !== 'pending') {
            return redirect()->route('penjualan.show', $penjualan)
                ->with('error', 'Hanya transaksi pending yang dapat diupdate');
        }

        $request->validate([
            'pelanggan_id' => 'nullable|exists:users,id',
            'nama_pelanggan' => 'required_without:pelanggan_id|string|max:255',
            'telepon_pelanggan' => 'nullable|string|max:20',
            'alamat_pelanggan' => 'nullable|string',
            'jenis_transaksi' => 'required|in:offline,online',
            'metode_pembayaran' => 'required|in:tunai,transfer_bca',
            'bayar' => 'nullable|numeric|min:0',
            'catatan' => 'nullable|string',
            'items' => 'required|array',
            'items.*.barang_id' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.catatan' => 'nullable|string',
        ], [
            'nama_pelanggan.required_without' => 'Nama pelanggan wajib diisi jika tidak memilih pelanggan terdaftar.',
            'items.required' => 'Minimal harus ada 1 produk dalam keranjang.',
            'items.*.barang_id.required' => 'Produk harus dipilih.',
            'items.*.barang_id.exists' => 'Produk yang dipilih tidak valid.',
            'items.*.jumlah.required' => 'Jumlah produk harus diisi.',
            'items.*.jumlah.min' => 'Jumlah produk minimal 1.',
            'items.*.harga_satuan.required' => 'Harga satuan harus diisi.',
            'items.*.harga_satuan.min' => 'Harga satuan tidak boleh negatif.',
        ]);

        DB::beginTransaction();

        try {
            // Jika items kosong, batalkan transaksi
            if (empty($request->items)) {
                // Kembalikan stok lama dan catat movement
                foreach ($penjualan->detailPenjualans as $detail) {
                    // Lock row barang untuk update - mencegah race condition
                    $barang = Barang::lockForUpdate()->find($detail->barang_id);
                    
                    $barang->recordStockMovement(
                        type: 'return',
                        quantity: $detail->jumlah,
                        description: "Pembatalan Transaksi - Pengembalian stok dari transaksi {$penjualan->nomor_transaksi}",
                        userId: auth()->id(),
                        referenceType: 'App\\Models\\Penjualan',
                        referenceId: $penjualan->id,
                        unitPrice: $detail->harga_satuan,
                        metadata: [
                            'penjualan_id' => $penjualan->id,
                            'detail_penjualan_id' => $detail->id,
                            'action' => 'cancel',
                            'quantity' => $detail->jumlah,
                            'price' => $detail->harga_satuan,
                            'customer_name' => $penjualan->nama_pelanggan,
                            'cancelled_by' => auth()->user()->name,
                        ]
                    );
                }
                // Hapus detail
                $penjualan->detailPenjualans()->delete();
                // Update status transaksi
                $penjualan->update([
                    'status' => 'dibatalkan',
                ]);
                DB::commit();
                return redirect()->route('penjualan.show', $penjualan)
                    ->with('success', 'Transaksi dibatalkan karena tidak ada barang dalam keranjang.');
            }

            // Store old details for stock movement tracking
            $oldDetails = $penjualan->detailPenjualans->keyBy('barang_id');
            
            // Restore stock from old items and record movement
            foreach ($penjualan->detailPenjualans as $detail) {
                // Lock row barang untuk update - mencegah race condition
                $barang = Barang::lockForUpdate()->find($detail->barang_id);
                
                // Record stock movement for returning old items
                $barang->recordStockMovement(
                    type: 'return',
                    quantity: $detail->jumlah, // Positive for stock return
                    description: "Edit Transaksi - Pengembalian stok dari transaksi {$penjualan->nomor_transaksi}",
                    userId: auth()->id(),
                    referenceType: 'App\Models\Penjualan',
                    referenceId: $penjualan->id,
                    unitPrice: $detail->harga_satuan,
                    metadata: [
                        'penjualan_id' => $penjualan->id,
                        'detail_penjualan_id' => $detail->id,
                        'action' => 'edit_return',
                        'old_quantity' => $detail->jumlah,
                        'old_price' => $detail->harga_satuan,
                        'customer_name' => $penjualan->nama_pelanggan,
                    ]
                );
            }

            // Delete old details
            $penjualan->detailPenjualans()->delete();

            // Calculate new totals
            $subtotal = 0;
            foreach ($request->items as $item) {
                $subtotal += $item['jumlah'] * $item['harga_satuan'];
            }

            $total = $subtotal;

            // Update penjualan
            $penjualan->update([
                'pelanggan_id' => $request->pelanggan_id,
                'nama_pelanggan' => $request->nama_pelanggan,
                'telepon_pelanggan' => $request->telepon_pelanggan,
                'alamat_pelanggan' => $request->alamat_pelanggan,
                'jenis_transaksi' => $request->jenis_transaksi,
                'metode_pembayaran' => $request->metode_pembayaran,
                'subtotal' => $total,
                'total' => $total,
                'bayar' => $total, // Payment amount is always equal to total
                'kembalian' => 0, // No change given
                'catatan' => $request->catatan,
            ]);

            // Create new detail penjualans and update stock
            foreach ($request->items as $item) {
                // Lock row barang untuk update - mencegah race condition
                $barang = Barang::lockForUpdate()->find($item['barang_id']);

                // Check stock availability after locking
                if ($barang->stok < $item['jumlah']) {
                    throw new \Exception("Stok {$barang->nama} tidak mencukupi. Stok tersedia: {$barang->stok} {$barang->satuan}");
                }

                // Create detail
                $detailPenjualan = DetailPenjualan::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'jumlah' => $item['jumlah'],
                    'harga_satuan' => $item['harga_satuan'],
                    'catatan' => $item['catatan'] ?? null,
                ]);

                // Record stock movement for new items dan kurangi stok sekaligus
                // recordStockMovement akan otomatis mengurangi stok untuk type 'out'
                $barang->recordStockMovement(
                    type: 'out',
                    quantity: $item['jumlah'], // Positive quantity, recordStockMovement akan handle pengurangan
                    description: "Edit Transaksi - Penjualan baru dari transaksi {$penjualan->nomor_transaksi}",
                    userId: auth()->id(),
                    referenceType: 'App\Models\DetailPenjualan',
                    referenceId: $detailPenjualan->id,
                    unitPrice: $item['harga_satuan'],
                    metadata: [
                        'penjualan_id' => $penjualan->id,
                        'detail_penjualan_id' => $detailPenjualan->id,
                        'action' => 'edit_new',
                        'new_quantity' => $item['jumlah'],
                        'new_price' => $item['harga_satuan'],
                        'customer_name' => $request->nama_pelanggan,
                        'metode_pembayaran' => $request->metode_pembayaran,
                        'old_quantity' => $oldDetails->get($item['barang_id'])?->jumlah ?? 0,
                        'old_price' => $oldDetails->get($item['barang_id'])?->harga_satuan ?? 0,
                    ]
                );
            }

            DB::commit();

            return redirect()->route('penjualan.show', $penjualan)
                ->with('success', 'Transaksi berhasil diupdate dan history movement dicatat');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->back()
                ->with('error', 'Gagal mengupdate transaksi: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Penjualan $penjualan): RedirectResponse
    {
        if ($penjualan->status === 'selesai') {
            return redirect()->route('penjualan.index')
                ->with('error', 'Transaksi yang sudah selesai tidak dapat dihapus');
        }

        DB::beginTransaction();

        try {
            // Restore stock and record movement if transaction was pending
            if ($penjualan->status === 'pending') {
                \Log::info('Restoring stock for pending transaction', ['penjualan_id' => $penjualan->id]);
                foreach ($penjualan->detailPenjualans as $detail) {
                    \Log::info('Processing detail for stock restoration', [
                        'detail_id' => $detail->id,
                        'barang_id' => $detail->barang_id,
                        'quantity' => $detail->jumlah
                    ]);

                    // Lock row barang untuk update - mencegah race condition
                    $barang = Barang::lockForUpdate()->find($detail->barang_id);

                    // Record stock movement for deletion
                    $barang->recordStockMovement(
                        type: 'return',
                        quantity: $detail->jumlah, // Positive for stock return
                        description: "Hapus Transaksi - Pengembalian stok dari transaksi {$penjualan->nomor_transaksi}",
                        userId: auth()->id(),
                        referenceType: 'App\Models\Penjualan',
                        referenceId: $penjualan->id,
                        unitPrice: $detail->harga_satuan,
                        metadata: [
                            'penjualan_id' => $penjualan->id,
                            'detail_penjualan_id' => $detail->id,
                            'action' => 'delete',
                            'quantity' => $detail->jumlah,
                            'price' => $detail->harga_satuan,
                            'customer_name' => $penjualan->nama_pelanggan,
                            'deleted_by' => auth()->user()->name,
                        ]
                    );

                    \Log::info('Stock movement recorded for deletion', [
                        'barang_id' => $detail->barang_id,
                        'new_stock' => $barang->fresh()->stok
                    ]);
                }
            } else {
                \Log::info('Skipping stock restoration - transaction not pending', [
                    'penjualan_id' => $penjualan->id,
                    'status' => $penjualan->status
                ]);
            }

            $penjualan->detailPenjualans()->delete();
            $penjualan->delete();

            DB::commit();

            return redirect()->route('penjualan.index')
                ->with('success', 'Transaksi berhasil dihapus dan history movement dicatat');

        } catch (\Exception $e) {
            DB::rollback();

            return redirect()->route('penjualan.index')
                ->with('error', 'Gagal menghapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Print receipt
     */
    public function print(Penjualan $penjualan): Response
    {
        $penjualan->load(['user', 'pelanggan', 'detailPenjualans.barang']);

        return Inertia::render('penjualan/print', [
            'penjualan' => $penjualan,
        ]);
    }

    /**
     * Show online orders for kasir to manage
     */
    public function onlineOrders(Request $request): Response
    {
        $search = $request->get('search');
        $status = $request->get('status', 'all');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');
        $sort = $request->get('sort', 'tanggal_transaksi');
        $direction = $request->get('direction', 'desc');

        $query = Penjualan::with(['user', 'pelanggan', 'detailPenjualans.barang'])
            ->where('jenis_transaksi', 'online');

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('nama_pelanggan', 'like', "%{$search}%")
                  ->orWhereHas('pelanggan', function ($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        // Status filter
        if ($status !== 'all') {
            $query->where('status', $status);
        }

        // Date range filter
        if ($date_from) {
            $query->whereDate('tanggal_transaksi', '>=', $date_from);
        }
        if ($date_to) {
            $query->whereDate('tanggal_transaksi', '<=', $date_to);
        }

        // Sorting
        $allowedSorts = ['nomor_transaksi', 'nama_pelanggan', 'total', 'tanggal_transaksi', 'status'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('tanggal_transaksi', 'desc');
        }

        // Pagination with 5 items per page
        $penjualans = $query->paginate(5)->withQueryString();

        return Inertia::render('penjualan/online', [
            'penjualans' => $penjualans,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Confirm payment for online order with payment proof
     */
    public function confirmPayment(Request $request, Penjualan $penjualan)
    {
        // Check if user is kasir or admin
        if (!auth()->user()->isKasir() && !auth()->user()->isAdmin()) {
            return Inertia::location(route('penjualan.online'));
        }

        // Validate payment proof exists for transfer payment
        if ($penjualan->metode_pembayaran === 'transfer_bca' && !$penjualan->payment_proof) {
            return Inertia::location(route('penjualan.online'));
        }

        try {
            $penjualan->update([
                'status' => 'dibayar',
                'user_id' => auth()->id(), // Assign to kasir who confirmed payment
                'payment_confirmed_at' => now(),
                'payment_confirmed_by' => auth()->id(),
            ]);

            // Confirm payment in financial system
            try {
                $this->salesFinancialService->confirmPayment($penjualan);

                \Log::info('Payment confirmed in financial system', [
                    'penjualan_id' => $penjualan->id,
                    'amount' => $penjualan->total,
                    'payment_method' => $penjualan->metode_pembayaran,
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to confirm payment in financial system', [
                    'penjualan_id' => $penjualan->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the confirmation, just log the error
            }

            // Log status change for synchronization tracking
            \Log::info('Payment confirmed for transaction', [
                'penjualan_id' => $penjualan->id,
                'kode_transaksi' => $penjualan->kode_transaksi,
                'old_status' => $penjualan->getOriginal('status'),
                'new_status' => 'dibayar',
                'confirmed_by' => auth()->user()->name,
                'confirmed_by_role' => auth()->user()->roles->first()->name ?? 'unknown',
                'timestamp' => now(),
                'customer_id' => $penjualan->pelanggan_id,
            ]);

            // Add flash message to session before redirect
            session()->flash('success', 'Pembayaran berhasil dikonfirmasi dan tercatat di sistem keuangan. Pesanan siap untuk diproses.');

            return Inertia::location(route('penjualan.online'));
        } catch (\Exception $e) {
            // Log error for debugging
            \Log::error('Failed to confirm payment', [
                'penjualan_id' => $penjualan->id,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            // Add error flash message to session before redirect
            session()->flash('error', 'Gagal mengkonfirmasi pembayaran: ' . $e->getMessage());

            return Inertia::location(route('penjualan.online'));
        }
    }

    /**
     * Reject payment proof for online order
     */
    public function rejectPayment(Request $request, Penjualan $penjualan)
    {
        // Check if user is kasir or admin
        if (!auth()->user()->isKasir() && !auth()->user()->isAdmin()) {
            return Inertia::location(route('penjualan.online'));
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:500',
        ]);

        try {
            $penjualan->update([
                'status' => 'pending',
                'payment_rejected_at' => now(),
                'payment_rejected_by' => auth()->id(),
                'payment_rejection_reason' => $validated['rejection_reason'],
            ]);

            // Reject payment in financial system
            try {
                $this->salesFinancialService->rejectPayment($penjualan, $validated['rejection_reason']);

                \Log::info('Payment rejected in financial system', [
                    'penjualan_id' => $penjualan->id,
                    'amount' => $penjualan->total,
                    'reason' => $validated['rejection_reason'],
                ]);
            } catch (\Exception $e) {
                \Log::error('Failed to reject payment in financial system', [
                    'penjualan_id' => $penjualan->id,
                    'error' => $e->getMessage()
                ]);
                // Don't fail the rejection, just log the error
            }

            \Log::info('Payment rejected', [
                'penjualan_id' => $penjualan->id,
                'rejection_reason' => $validated['rejection_reason'],
                'rejected_by' => auth()->id(),
            ]);

            // Add flash message to session before redirect
            session()->flash('success', 'Bukti pembayaran ditolak dan transaksi keuangan dibatalkan. Pelanggan akan diingatkan untuk upload ulang.');

            return Inertia::location(route('penjualan.online'));
        } catch (\Exception $e) {
            \Log::error('Failed to reject payment', [
                'penjualan_id' => $penjualan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Add error flash message to session before redirect
            session()->flash('error', 'Gagal menolak bukti pembayaran: ' . $e->getMessage());
            
            return Inertia::location(route('penjualan.online'));
        }
    }

    /**
     * Mark order as ready for pickup
     */
    public function readyPickup(Request $request, Penjualan $penjualan)
    {
        // Check if user is kasir or admin
        if (!auth()->user()->isKasir() && !auth()->user()->isAdmin()) {
            return Inertia::location(route('penjualan.online'));
        }

        // Validate that payment is confirmed
        if ($penjualan->status !== 'dibayar') {
            session()->flash('error', 'Hanya pesanan yang sudah dibayar yang dapat ditandai siap pickup.');
            return Inertia::location(route('penjualan.online'));
        }

        try {
            DB::beginTransaction();

            // Update status to ready for pickup and assign to current kasir
            $penjualan->update([
                'status' => 'siap_pickup',
                'user_id' => auth()->id(), // Assign to kasir who processed the order
                'pickup_time' => now(),
                'catatan' => $request->input('catatan_kasir'),
            ]);

            // Log the action
            \Log::info('Order marked ready for pickup', [
                'penjualan_id' => $penjualan->id,
                'ready_pickup_by' => auth()->user()->name,
                'catatan_kasir' => $request->input('catatan_kasir'),
            ]);

            DB::commit();

            // Add success flash message to session before redirect
            session()->flash('success', 'Pesanan berhasil ditandai siap untuk pickup.');

            return Inertia::location(route('penjualan.online'));

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Failed to mark order ready for pickup', [
                'penjualan_id' => $penjualan->id,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            // Add error flash message to session before redirect
            session()->flash('error', 'Gagal menandai pesanan siap pickup: ' . $e->getMessage());

            return Inertia::location(route('penjualan.online'));
        }
    }

    /**
     * Complete the order (mark as finished)
     */
    public function complete(Request $request, Penjualan $penjualan)
    {
        // Check if user is kasir or admin
        if (!auth()->user()->isKasir() && !auth()->user()->isAdmin()) {
            return Inertia::location(route('penjualan.online'));
        }

        // Validate that order is ready for pickup
        if ($penjualan->status !== 'siap_pickup') {
            session()->flash('error', 'Hanya pesanan yang siap pickup yang dapat diselesaikan.');
            return Inertia::location(route('penjualan.online'));
        }

        try {
            DB::beginTransaction();

            // Update status to completed and assign to current kasir
            $penjualan->update([
                'status' => 'selesai',
                'user_id' => auth()->id(), // Assign to kasir who completed the transaction
                'catatan' => $request->input('catatan_kasir'),
            ]);

            // Log the action
            \Log::info('Order completed', [
                'penjualan_id' => $penjualan->id,
                'completed_by' => auth()->user()->name,
                'catatan_kasir' => $request->input('catatan_kasir'),
            ]);

            DB::commit();

            // Add success flash message to session before redirect
            session()->flash('success', 'Pesanan berhasil diselesaikan.');

            return Inertia::location(route('penjualan.online'));

        } catch (\Exception $e) {
            DB::rollback();

            \Log::error('Failed to complete order', [
                'penjualan_id' => $penjualan->id,
                'error' => $e->getMessage(),
                'user' => auth()->user()->name,
            ]);

            // Add error flash message to session before redirect
            session()->flash('error', 'Gagal menyelesaikan pesanan: ' . $e->getMessage());

            return Inertia::location(route('penjualan.online'));
        }
    }
}
