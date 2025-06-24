<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use App\Models\Barang;
use App\Models\Penjualan;

class HomeController extends Controller
{
    /**
     * Show the home page with product catalog
     */
    public function index(Request $request): Response
    {
        $search = $request->get('search');
        $kategori = $request->get('kategori');
        $sort = $request->get('sort', 'nama');
        $direction = $request->get('direction', 'asc');

        $query = Barang::where('stok', '>', 0); // Only show products in stock

        // Search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('nama', 'like', "%{$search}%")
                  ->orWhere('kode_barang', 'like', "%{$search}%")
                  ->orWhere('kategori', 'like', "%{$search}%");
            });
        }

        // Category filter
        if ($kategori && $kategori !== 'all') {
            $query->where('kategori', $kategori);
        }

        // Sorting
        $allowedSorts = ['nama', 'harga_jual', 'kategori', 'created_at'];
        if (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $direction);
        } else {
            $query->orderBy('nama', 'asc');
        }

        // Pagination with 12 items per page for catalog view
        $barangs = $query->paginate(12)->withQueryString();

        // Get available categories
        $categories = Barang::select('kategori')
            ->distinct()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->orderBy('kategori')
            ->pluck('kategori');

        // Get some stats for hero section
        $stats = [
            'total_products' => Barang::where('stok', '>', 0)->count(),
            'total_categories' => $categories->count(),
            'total_customers' => \App\Models\User::whereHas('roles', function ($q) {
                $q->where('name', 'pelanggan');
            })->count(),
        ];

        return Inertia::render('home/index', [
            'barangs' => $barangs,
            'categories' => $categories,
            'stats' => $stats,
            'filters' => [
                'search' => $search,
                'kategori' => $kategori,
                'sort' => $sort,
                'direction' => $direction,
            ],
        ]);
    }

    /**
     * Show product detail
     */
    public function show(Barang $barang): Response
    {
        // Get related products (same category)
        $relatedProducts = Barang::where('kategori', $barang->kategori)
            ->where('id', '!=', $barang->id)
            ->where('stok', '>', 0)
            ->take(4)
            ->get();

        return Inertia::render('home/product-detail', [
            'barang' => $barang,
            'relatedProducts' => $relatedProducts,
        ]);
    }

    /**
     * Show user dashboard (for logged in customers)
     */
    public function dashboard(): Response
    {
        $user = auth()->user();

        // Get user's recent orders
        $recentOrders = Penjualan::where('pelanggan_id', $user->id)
            ->with(['detailPenjualans.barang'])
            ->orderBy('tanggal_transaksi', 'desc')
            ->take(5)
            ->get();

        // Get user's order statistics
        $orderStats = [
            'total_orders' => Penjualan::where('pelanggan_id', $user->id)->count(),
            'pending_orders' => Penjualan::where('pelanggan_id', $user->id)->where('status', 'pending')->count(),
            'completed_orders' => Penjualan::where('pelanggan_id', $user->id)->where('status', 'selesai')->count(),
            'total_spent' => Penjualan::where('pelanggan_id', $user->id)->where('status', 'selesai')->sum('total'),
        ];

        return Inertia::render('home/dashboard', [
            'recentOrders' => $recentOrders,
            'orderStats' => $orderStats,
        ]);
    }

    /**
     * Show user orders
     */
    public function orders(): Response
    {
        $user = auth()->user();

        $orders = Penjualan::where('pelanggan_id', $user->id)
            ->with(['detailPenjualans.barang'])
            ->orderBy('tanggal_transaksi', 'desc')
            ->paginate(10);

        return Inertia::render('home/orders', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show specific order detail
     */
    public function orderDetail(Penjualan $order): Response
    {
        // Check if order belongs to current user
        if ($order->pelanggan_id !== auth()->id()) {
            abort(403, 'Unauthorized access to this order');
        }

        $order->load(['detailPenjualans.barang', 'user']);

        return Inertia::render('home/order-detail', [
            'order' => $order,
        ]);
    }
}
