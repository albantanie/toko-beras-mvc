<?php

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
    protected $imageService;

    public function __construct(ImageCompressionService $imageService)
    {
        $this->imageService = $imageService;
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $filter = $request->get('filter', 'all');
        $sort = $request->get('sort', 'nama');
        $direction = $request->get('direction', 'asc');

        $query = Barang::with(['creator', 'updater']);

        if ($search) {
            $query->search($search);
        }

        switch ($filter) {
            case 'low_stock':
                $query->lowStock();
                break;
            case 'out_of_stock':
                $query->outOfStock();
                break;
            case 'inactive':
                $query->where('is_active', false);
                break;
            default:
                $query->active();
                break;
        }

        // Sorting
        $allowedSorts = ['nama', 'kode_barang', 'kategori', 'harga_beli', 'harga_jual', 'stok', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('nama', 'asc');
        }

        // Pagination with query string preservation
        $barangs = $query->paginate(5)->withQueryString();



        // Convert pagination to array to ensure proper serialization
        $barangsArray = $barangs->toArray();

        return Inertia::render('barang/index', [
            'barangs' => [
                'data' => $barangsArray['data'],
                'links' => $barangsArray['links'],
                'meta' => [
                    'current_page' => $barangsArray['current_page'],
                    'from' => $barangsArray['from'],
                    'last_page' => $barangsArray['last_page'],
                    'per_page' => $barangsArray['per_page'],
                    'to' => $barangsArray['to'],
                    'total' => $barangsArray['total'],
                ]
            ],
            'filters' => [
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
        $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'required|string|max:255',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'required|integer|min:0',
            'satuan' => 'required|string|max:50',
            'berat_per_unit' => 'required|numeric|min:0.01',
            'kode_barang' => 'required|string|max:255|unique:barangs',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $data = $request->all();
        $data['created_by'] = auth()->id();
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('gambar')) {
            // Compress and store image
            $data['gambar'] = $this->imageService->compressAndStore(
                $request->file('gambar'),
                'barang',
                800,  // max width
                600,  // max height
                80    // quality (80%)
            );

            // Also create thumbnail
            $this->imageService->createThumbnail($request->file('gambar'));
        }

        Barang::create($data);

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil ditambahkan');
    }

    /**
     * Display the specified resource.
     */
    public function show(Barang $barang): Response
    {
        $barang->load(['creator', 'updater', 'detailPenjualans.penjualan']);

        return Inertia::render('barang/show', [
            'barang' => $barang,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Barang $barang): Response
    {
        return Inertia::render('barang/edit', [
            'barang' => $barang,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Barang $barang): RedirectResponse
    {
        $request->validate([
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'kategori' => 'required|string|max:255',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'stok' => 'required|integer|min:0',
            'stok_minimum' => 'required|integer|min:0',
            'satuan' => 'required|string|max:50',
            'berat_per_unit' => 'required|numeric|min:0.01',
            'kode_barang' => 'required|string|max:255|unique:barangs,kode_barang,' . $barang->id,
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'is_active' => 'boolean',
        ]);

        $data = $request->all();
        $data['updated_by'] = auth()->id();

        if ($request->hasFile('gambar')) {
            // Delete old image and thumbnail
            if ($barang->gambar) {
                $this->imageService->deleteImage($barang->gambar);
            }

            // Compress and store new image
            $data['gambar'] = $this->imageService->compressAndStore(
                $request->file('gambar'),
                'barang',
                800,  // max width
                600,  // max height
                80    // quality (80%)
            );

            // Also create thumbnail
            $this->imageService->createThumbnail($request->file('gambar'));
        }

        $barang->update($data);

        return redirect()->route('barang.index')
            ->with('success', 'Barang berhasil diupdate');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Barang $barang): RedirectResponse
    {
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
    }

    /**
     * Update stock
     */
    public function updateStock(Request $request, Barang $barang): RedirectResponse
    {
        $request->validate([
            'stok' => 'required|integer|min:0',
            'catatan' => 'nullable|string',
        ]);

        $barang->update([
            'stok' => $request->stok,
            'updated_by' => auth()->id(),
        ]);

        return redirect()->route('barang.show', $barang)
            ->with('success', 'Stok berhasil diupdate');
    }
}
