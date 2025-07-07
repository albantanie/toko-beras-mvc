<?php

/**
 * ===================================================================
 * BARANG CONTROLLER - CONTROLLER UNTUK MANAJEMEN INVENTORY/PRODUK
 * ===================================================================
 *
 * Controller ini menangani semua operasi CRUD untuk produk beras:
 * 1. Listing produk dengan search, filter, dan sorting
 * 2. Create produk baru dengan upload gambar
 * 3. Update produk dan gambar
 * 4. Delete produk dengan cleanup gambar
 * 5. Update stock produk
 * 6. Image compression dan thumbnail generation
 *
 * Akses: Admin, Owner, Karyawan
 * ===================================================================
 */

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Services\ImageCompressionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BarangController extends Controller
{
    /**
     * Service untuk kompresi dan manajemen gambar
     * Digunakan untuk mengoptimalkan ukuran file gambar produk
     */
    protected $imageService;

    /**
     * Constructor - Inject ImageCompressionService
     *
     * @param ImageCompressionService $imageService - Service untuk image processing
     */
    public function __construct(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }

    /**
     * ===================================================================
     * INDEX - DAFTAR SEMUA PRODUK
     * ===================================================================
     *
     * Menampilkan daftar produk dengan fitur:
     * - Search berdasarkan nama, kode, atau kategori
     * - Filter berdasarkan status stock (low stock, out of stock, inactive)
     * - Sorting berdasarkan berbagai field
     * - Pagination dengan 5 item per halaman
     * - Load relasi creator dan updater untuk audit trail
     *
     * @param Request $request - Request dengan parameter search, filter, sort
     * @return Response - Halaman daftar produk
     */
    public function index(Request $request): Response
    {
        // Ambil parameter dari request
        $search = $request->get('search');              // Kata kunci pencarian
        $filter = $request->get('filter', 'all');       // Filter status (all, low_stock, out_of_stock, inactive)
        $sort = $request->get('sort', 'nama');          // Field untuk sorting
        $direction = $request->get('direction', 'asc'); // Arah sorting (asc/desc)

        /**
         * QUERY BUILDER DENGAN RELASI
         * Load relasi creator dan updater untuk menampilkan siapa yang membuat/update produk
         */
        $query = Barang::with(['creator', 'updater']);

        /**
         * FITUR PENCARIAN
         * Menggunakan scope search() yang didefinisikan di Model Barang
         */
        if ($search) {
            $query->search($search);
        }

        /**
         * FILTER BERDASARKAN STATUS STOCK
         * Menggunakan scope yang didefinisikan di Model Barang
         */
        switch ($filter) {
            case 'low_stock':
                $query->lowStock();         // Produk dengan stok rendah
                break;
            case 'out_of_stock':
                $query->outOfStock();       // Produk yang habis stoknya
                break;
            case 'inactive':
                $query->where('is_active', false);  // Produk yang dinonaktifkan
                break;
            default:
                $query->active();           // Produk aktif (default)
                break;
        }

        // Tambahan filter untuk produk yang belum/sudah ada harga
        $priceStatus = $request->get('price_status', null); // 'no_price', 'has_price', null
        if ($priceStatus === 'no_price') {
            $query->where(function($q) {
                $q->whereNull('harga_beli')->orWhere('harga_beli', 0)
                  ->orWhereNull('harga_jual')->orWhere('harga_jual', 0);
            });
        } elseif ($priceStatus === 'has_price') {
            $query->whereNotNull('harga_beli')->where('harga_beli', '>', 0)
                  ->whereNotNull('harga_jual')->where('harga_jual', '>', 0);
        }

        /**
         * SORTING PRODUK
         * Urutkan berdasarkan field yang diizinkan dengan validasi role-based
         * Karyawan tidak boleh sort berdasarkan harga_beli (data finansial)
         */
        $currentUser = auth()->user();
        $isKaryawan = $currentUser && $currentUser->roles()->where('name', 'karyawan')->exists();

        $allowedSorts = ['nama', 'kode_barang', 'kategori', 'harga_jual', 'stok', 'created_at'];
        if (!$isKaryawan) {
            $allowedSorts[] = 'harga_beli'; // Hanya admin/owner yang bisa sort berdasarkan harga_beli
        }

        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            // Default sorting berdasarkan nama
            $query->orderBy('nama', 'asc');
        }

        /**
         * PAGINATION
         * Tampilkan 5 produk per halaman untuk manajemen yang mudah
         * withQueryString() mempertahankan parameter search dan filter
         */
        $barangs = $query->paginate(5)->withQueryString();

        /**
         * KONVERSI PAGINATION UNTUK INERTIA
         * Convert ke array untuk memastikan serialization yang proper
         * dan kompatibilitas dengan frontend React
         */
        $barangsArray = $barangs->toArray();

        /**
         * FILTER DATA BERDASARKAN ROLE
         * Sembunyikan data finansial dari karyawan untuk melindungi informasi sensitif
         */
        $currentUser = auth()->user();
        $isKaryawan = $currentUser && $currentUser->roles()->where('name', 'karyawan')->exists();

        if ($isKaryawan) {
            // Filter data produk untuk karyawan - hilangkan data finansial
            $barangsArray['data'] = array_map(function ($barang) {
                unset($barang['harga_beli']);  // Hapus harga beli
                // Harga jual tetap ditampilkan untuk referensi customer service
                return $barang;
            }, $barangsArray['data']);
        }

        /**
         * RENDER HALAMAN INDEX PRODUK
         * Kirim data produk dan filter state ke frontend
         */
        return Inertia::render('barang/index', [
            'barangs' => [
                'data' => $barangsArray['data'],        // Data produk (filtered untuk karyawan)
                'links' => $barangsArray['links'],      // Pagination links
                'meta' => [                             // Metadata pagination
                    'current_page' => $barangsArray['current_page'],
                    'from' => $barangsArray['from'],
                    'last_page' => $barangsArray['last_page'],
                    'per_page' => $barangsArray['per_page'],
                    'to' => $barangsArray['to'],
                    'total' => $barangsArray['total'],
                ]
            ],
            'filters' => [                              // Current filter state
                'search' => $search,
                'filter' => $filter,
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
        return Inertia::render('barang/create');
    }

    /**
     * Show the quick add form for rice products.
     */
    public function quickAdd(): Response
    {
        return Inertia::render('barang/quick-add');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        $isKaryawan = $user->hasRole('karyawan');
        $isAdmin = $user->hasRole('admin');
        $isOwner = $user->hasRole('owner');
        try {
            $rules = [
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'kategori' => 'required|string|max:255',
                'harga_beli' => 'nullable|numeric|min:0',
                'harga_jual' => 'nullable|numeric|min:0|gt:harga_beli',
                'stok' => ($isAdmin && !$isOwner) ? 'nullable' : 'required|integer|min:0',
                'stok_minimum' => 'required|integer|min:0',
                'satuan' => 'required|string|max:50',
                'berat_per_unit' => 'required|numeric|min:0.01',
                'kode_barang' => 'required|string|max:255|unique:barangs',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            ];
            $request->validate($rules, [
                'harga_jual.gt' => 'Harga jual harus lebih besar dari harga beli.',
                'kode_barang.unique' => 'Kode barang sudah digunakan.',
                'gambar.image' => 'File harus berupa gambar.',
                'gambar.max' => 'Ukuran gambar maksimal 2MB.',
            ]);
            $data = $request->all();
            $data['created_by'] = $user->id;
            $data['updated_by'] = $user->id;
            $data['is_active'] = $request->boolean('is_active', true);
            // Proteksi field sesuai role
            if ($isKaryawan && !$isOwner) {
                unset($data['harga_beli'], $data['harga_jual']);
            }
            if ($isAdmin && !$isOwner) {
                unset($data['stok']);
            }
            if ($request->hasFile('gambar')) {
                $data['gambar'] = $this->imageService->compressAndStore(
                    $request->file('gambar'), 'barang', 800, 600, 80
                );
                $this->imageService->createThumbnail($request->file('gambar'));
            }
            Barang::create($data);
            return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal menambahkan barang: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Barang $barang): Response
    {
        $barang->load(['creator', 'updater']);

        // Add formatted dates
        $barang->formatted_created_date = $barang->formatted_created_date;
        $barang->formatted_updated_date = $barang->formatted_updated_date;

        return Inertia::render('barang/show', [
            'barang' => $barang,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Barang $barang): Response
    {
        // Semua role (admin, owner, karyawan) bisa akses edit, field harga tetap di-protect di frontend
        return Inertia::render('barang/edit', [
            'barang' => $barang,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Barang $barang): RedirectResponse
    {
        $user = auth()->user();
        $isKaryawan = $user->hasRole('karyawan');
        $isAdmin = $user->hasRole('admin');
        $isOwner = $user->hasRole('owner');
        // Semua role bisa update, field harga tetap di-protect untuk karyawan
        try {
            $rules = [
                'nama' => 'required|string|max:255',
                'deskripsi' => 'nullable|string',
                'kategori' => 'required|string|max:255',
                'harga_beli' => ($isKaryawan && !$isOwner) ? 'nullable' : 'required|numeric|min:0',
                'harga_jual' => ($isKaryawan && !$isOwner) ? 'nullable' : 'required|numeric|min:0|gt:harga_beli',
                'stok' => ($isAdmin && !$isOwner) ? 'nullable' : 'required|integer|min:0',
                'stok_minimum' => 'required|integer|min:0',
                'satuan' => 'required|string|max:50',
                'berat_per_unit' => 'required|numeric|min:0.01',
                'kode_barang' => 'required|string|max:255|unique:barangs,kode_barang,' . $barang->id,
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
                'is_active' => 'boolean',
            ];
            $request->validate($rules, [
                'harga_jual.gt' => 'Harga jual harus lebih besar dari harga beli.',
                'kode_barang.unique' => 'Kode barang sudah digunakan.',
                'gambar.image' => 'File harus berupa gambar.',
                'gambar.max' => 'Ukuran gambar maksimal 2MB.',
            ]);
            $data = $request->all();
            $data['updated_by'] = $user->id;
            if ($isKaryawan && !$isOwner) {
                unset($data['harga_beli'], $data['harga_jual']);
            }
            if ($isAdmin && !$isOwner) {
                unset($data['stok']);
            }
            if ($request->hasFile('gambar')) {
                if ($barang->gambar) {
                    $this->imageService->deleteImage($barang->gambar);
                }
                $data['gambar'] = $this->imageService->compressAndStore(
                    $request->file('gambar'), 'barang', 800, 600, 80
                );
                $this->imageService->createThumbnail($request->file('gambar'));
            }
            $barang->update($data);
            return redirect()->route('barang.index')->with('success', 'Barang berhasil diupdate');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal mengupdate barang: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Barang $barang): RedirectResponse
    {
        $user = auth()->user();
        $isKaryawan = $user->hasRole('karyawan');
        $isAdmin = $user->hasRole('admin');
        $isOwner = $user->hasRole('owner');
        // Semua role bisa hapus barang, kecuali jika ada riwayat penjualan
        try {
            // Check if barang has sales history
            if ($barang->detailPenjualans()->exists()) {
                return redirect()->route('barang.index')
                    ->with('error', 'Barang tidak dapat dihapus karena memiliki riwayat penjualan');
            }

            // Delete image and thumbnail
            if ($barang->gambar) {
                $this->imageService->deleteImage($barang->gambar);
            }

            $barang->delete();

            return redirect()->route('barang.index')
                ->with('success', 'Barang berhasil dihapus');
                
        } catch (\Exception $e) {
            return redirect()->route('barang.index')
                ->with('error', 'Gagal menghapus barang: ' . $e->getMessage());
        }
    }

    /**
     * Update stock barang
     */
    public function updateStock(Request $request, Barang $barang)
    {
        $request->validate([
            'stok' => 'required|integer|min:0',
            'reason' => 'required|string|max:255',
            'type' => 'required|in:adjustment,correction,initial,in,return',
        ]);

        $oldStock = $barang->stok;
        $newStock = $request->stok;
        $difference = $newStock - $oldStock;

        if ($difference == 0) {
            return redirect()->back()->with('error', 'Tidak ada perubahan stok');
        }

        // Validate based on movement type
        switch ($request->type) {
            case 'initial':
                if ($oldStock > 0) {
                    return redirect()->back()->with('error', 'Stock awal hanya bisa diset untuk produk yang belum memiliki stok');
                }
                break;
            case 'in':
                if ($difference <= 0) {
                    return redirect()->back()->with('error', 'Stock masuk harus menambah stok (nilai positif)');
                }
                break;
            case 'return':
                if ($difference <= 0) {
                    return redirect()->back()->with('error', 'Retur barang harus menambah stok (nilai positif)');
                }
                break;
        }

        // Record stock movement
        $barang->recordStockMovement(
            type: $request->type,
            quantity: $difference,
            description: $request->reason,
            userId: auth()->id(),
            referenceType: null,
            referenceId: null,
            unitPrice: $barang->harga_jual,
            metadata: [
                'old_stock' => $oldStock,
                'new_stock' => $newStock,
                'reason' => $request->reason,
                'type' => $request->type,
                'manual_update' => true,
            ]
        );

        $typeLabels = [
            'adjustment' => 'Penyesuaian Stok',
            'correction' => 'Koreksi Sistem',
            'initial' => 'Stock Awal',
            'in' => 'Stock Masuk',
            'return' => 'Retur Barang',
        ];

        $typeLabel = $typeLabels[$request->type] ?? $request->type;

        return redirect()->back()->with('success', "Stok berhasil diperbarui dengan {$typeLabel} dan movement dicatat");
    }

    /**
     * Show stock movements for a specific barang
     */
    public function stockMovements(Request $request, Barang $barang): Response
    {
        $query = $barang->stockMovements()
            ->with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('type')) {
            $query->ofType($request->type);
        }

        if ($request->filled('search')) {
            $query->where('description', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->inPeriod($request->date_from, $request->date_to);
        }

        $movements = $query->paginate(20)->withQueryString();

        // Check if user can see price information (owner or admin only)
        $user = auth()->user();
        $canSeePrice = $user->hasRole('owner') || $user->hasRole('admin');
        $isKaryawan = $user->hasRole('karyawan');

        // Transform movements data based on user role
        $movements->getCollection()->transform(function ($movement) use ($canSeePrice, $isKaryawan) {
            $data = [
                'id' => $movement->id,
                'type' => $movement->type,
                'type_label' => $movement->type_label,
                'type_color' => $movement->type_color,
                'quantity' => $movement->quantity,
                'formatted_quantity' => $movement->formatted_quantity,
                'stock_before' => $movement->stock_before,
                'stock_after' => $movement->stock_after,
                'description' => $movement->description,
                'reference_description' => $movement->reference_description,
                'formatted_date' => $movement->formatted_date,
                'time_ago' => $movement->time_ago,
                'is_positive' => $movement->is_positive,
                'is_negative' => $movement->is_negative,
                'user' => [
                    'id' => $movement->user->id,
                    'name' => $movement->user->name,
                    'email' => $movement->user->email,
                ],
                'barang' => [
                    'id' => $movement->barang->id,
                    'nama' => $movement->barang->nama,
                    'kategori' => $movement->barang->kategori,
                    'satuan' => $movement->barang->satuan,
                ],
            ];

            // Only include price data for owner and admin
            if ($canSeePrice) {
                $data['unit_price'] = $movement->unit_price;
                $data['formatted_unit_price'] = $movement->formatted_unit_price;
                $data['total_value'] = $movement->total_value;
                $data['formatted_total_value'] = $movement->formatted_total_value;
            }

            return $data;
        });

        // Get summary
        $summary = $barang->getStockMovementSummary();

        return Inertia::render('barang/stock-movements', [
            'barang' => $barang,
            'movements' => $movements,
            'summary' => $summary,
            'filters' => $request->only(['type', 'search', 'date_from', 'date_to']),
            'user_role' => [
                'can_see_price' => $canSeePrice,
                'is_karyawan' => $isKaryawan,
            ],
        ]);
    }
}
