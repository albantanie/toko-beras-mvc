import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import BarChart from '@/components/Charts/BarChart';
import LineChart from '@/components/Charts/LineChart';
import { formatCurrency, formatCompactNumber, Icons } from '@/utils/formatters';
import { Badge } from '@/components/ui/badge';
import { Info } from 'lucide-react';
import { useState } from 'react';

// Use global route function
declare global {
    function route(name: string, params?: any): string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Karyawan Dashboard',
        href: '/karyawan/dashboard',
    },
];

interface KaryawanDashboardProps {
    stockLevels: Array<{
        category: string;
        total_products: number;
        total_stock: number;
    }>;
    lowStockItems: Array<{
        id: number;
        nama: string;
        kategori: string;
        stok: number;
        satuan: string;
        status: string;
    }>;

    inventorySummary: {
        total_products: number;
        low_stock_items: number;
        out_of_stock_items: number;
        total_stock_value: number;
        categories_count: number;
    };
    stockMovementTrend: Array<{
        date: string;
        day: string;
        stock_in: number;
        stock_out: number;
        net_movement: number;
    }>;
    recentMovements: Array<{
        id: number;
        type: string;
        type_label: string;
        type_color: string;
        quantity: number;
        formatted_quantity: string;
        stock_before: number;
        stock_after: number;
        description: string;
        formatted_date: string;
        time_ago: string;
        user: {
            name: string;
        };
        barang: {
            nama: string;
            satuan: string;
        };
    }>;
    todayMovements: {
        in: number;
        out: number;
        adjustments: number;
    };
    filters: {
        date_from: string;
        date_to: string;
    };
    periodInventorySummary: {
        total_products: number;
        stock_in: number;
        stock_out: number;
        adjustments: number;
    };

}

export default function KaryawanDashboard({
    stockLevels,
    lowStockItems,
    inventorySummary,
    periodInventorySummary,
    stockMovementTrend,
    recentMovements,
    todayMovements,
    filters
}: KaryawanDashboardProps) {
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleDateFilter = () => {
        // Validasi input tanggal
        if (!dateFrom || !dateTo) {
            alert('Silakan pilih tanggal dari dan sampai terlebih dahulu');
            return;
        }

        if (dateFrom > dateTo) {
            alert('Tanggal dari tidak boleh lebih besar dari tanggal sampai');
            return;
        }



        router.get('/karyawan/dashboard', {
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleResetFilter = () => {
        setDateFrom('');
        setDateTo('');
        router.get('/karyawan/dashboard', {}, {
            preserveState: true,
            replace: true,
        });
    };

    // Helper function to get badge variant based on movement type
    const getTypeBadgeVariant = (type: string) => {
        switch (type) {
            case 'in':
            case 'return':
                return 'default';
            case 'out':
            case 'damage':
                return 'destructive';
            case 'adjustment':
            case 'correction':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    // Prepare chart data
    const stockLevelsData = {
        labels: stockLevels?.map(item => item.category) || [],
        datasets: [
            {
                label: 'Total Stock',
                data: stockLevels?.map(item => item.total_stock) || [],
                backgroundColor: 'rgba(34, 197, 94, 0.8)',
                borderColor: 'rgb(34, 197, 94)',
                borderWidth: 1,
            },
            {
                label: 'Products Count',
                data: stockLevels?.map(item => item.total_products) || [],
                backgroundColor: 'rgba(59, 130, 246, 0.8)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1,
            },
        ],
    };



    const stockMovementData = {
        labels: stockMovementTrend?.map(item => item.day) || [],
        datasets: [
            {
                label: 'Stock In',
                data: stockMovementTrend?.map(item => item.stock_in) || [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: false,
                tension: 0.4,
            },
            {
                label: 'Stock Out',
                data: stockMovementTrend?.map(item => item.stock_out) || [],
                borderColor: 'rgb(239, 68, 68)',
                backgroundColor: 'rgba(239, 68, 68, 0.1)',
                fill: false,
                tension: 0.4,
            },
        ],
    };



    // Add error handling for empty data
    const hasChartData = stockLevels && stockLevels.length > 0;

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Karyawan Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Dashboard Karyawan - Inventory Charts</h3>
                            
                            {/* Date Filter */}
                            <div className="bg-white overflow-hidden shadow rounded-lg mb-6">
                                <div className="px-4 py-5 sm:p-6">
                                    <div className="flex items-center justify-between mb-4">
                                        <h3 className="text-lg font-medium text-gray-900">Filter Periode</h3>
                                        {(dateFrom || dateTo) && (
                                            <span className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                Filter Aktif
                                            </span>
                                        )}
                                    </div>
                                    <div className="flex flex-wrap items-end gap-4">
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Dari Tanggal
                                            </label>
                                            <input
                                                type="date"
                                                value={dateFrom}
                                                onChange={(e) => setDateFrom(e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-sm font-medium text-gray-700">
                                                Sampai Tanggal
                                            </label>
                                            <input
                                                type="date"
                                                value={dateTo}
                                                onChange={(e) => setDateTo(e.target.value)}
                                                className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                            />
                                        </div>
                                        <div className="flex gap-2">
                                            <button
                                                onClick={handleDateFilter}
                                                className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                            >
                                                Filter
                                            </button>
                                            <button
                                                onClick={handleResetFilter}
                                                className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                            >
                                                Reset
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>



                            {/* Period Movement Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                <div className="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-blue-600">Total Items</p>
                                            <p className="text-xl font-bold text-blue-900 truncate" title={(periodInventorySummary?.total_products || 0).toString()}>
                                                {formatCompactNumber(periodInventorySummary?.total_products || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-green-50 p-6 rounded-lg border border-green-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-green-100 rounded-lg">
                                            <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-green-600">In Stock</p>
                                            <p className="text-xl font-bold text-green-900 truncate" title={(periodInventorySummary?.stock_in || 0).toString()}>
                                                {formatCompactNumber(periodInventorySummary?.stock_in || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-yellow-50 p-6 rounded-lg border border-yellow-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-yellow-100 rounded-lg">
                                            <svg className="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-yellow-600">Low Stock</p>
                                            <p className="text-xl font-bold text-yellow-900 truncate" title={(periodInventorySummary?.stock_out || 0).toString()}>
                                                {formatCompactNumber(periodInventorySummary?.stock_out || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-red-50 p-6 rounded-lg border border-red-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-red-100 rounded-lg">
                                            <svg className="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-red-600">Out of Stock</p>
                                            <p className="text-xl font-bold text-red-900 truncate" title={(periodInventorySummary?.adjustments || 0).toString()}>
                                                {formatCompactNumber(Math.abs(periodInventorySummary?.adjustments || 0))}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Enhanced Inventory Chart */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <div className="mb-6">
                                    <h4 className="text-xl font-semibold text-gray-900 mb-2">Distribusi Stok per Kategori</h4>
                                    <p className="text-sm text-gray-600 mb-4">
                                        Grafik ini menampilkan perbandingan total stok dan jumlah produk untuk setiap kategori beras.
                                        Batang hijau menunjukkan total stok (dalam satuan), sedangkan batang biru menunjukkan jumlah produk yang tersedia.
                                    </p>

                                    {/* Chart Legend */}
                                    <div className="flex flex-wrap gap-4 mb-4">
                                        <div className="flex items-center gap-2">
                                            <div className="w-4 h-4 bg-green-500 rounded"></div>
                                            <span className="text-sm text-gray-700">Total Stok (Satuan)</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <div className="w-4 h-4 bg-blue-500 rounded"></div>
                                            <span className="text-sm text-gray-700">Jumlah Produk</span>
                                        </div>
                                    </div>

                                    {/* Summary Stats */}
                                    {hasChartData && (
                                        <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
                                            <div className="bg-green-50 p-3 rounded-lg border border-green-200">
                                                <p className="text-xs text-green-600 font-medium">TOTAL STOK</p>
                                                <p className="text-lg font-bold text-green-700">
                                                    {formatCompactNumber(stockLevels.reduce((sum, item) => sum + item.total_stock, 0))}
                                                </p>
                                            </div>
                                            <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                                <p className="text-xs text-blue-600 font-medium">TOTAL PRODUK</p>
                                                <p className="text-lg font-bold text-blue-700">
                                                    {stockLevels.reduce((sum, item) => sum + item.total_products, 0)}
                                                </p>
                                            </div>
                                            <div className="bg-purple-50 p-3 rounded-lg border border-purple-200">
                                                <p className="text-xs text-purple-600 font-medium">KATEGORI</p>
                                                <p className="text-lg font-bold text-purple-700">
                                                    {stockLevels.length}
                                                </p>
                                            </div>

                                        </div>
                                    )}
                                </div>

                                {hasChartData ? (
                                    <div>
                                        <BarChart data={stockLevelsData} height={300} />
                                        <div className="mt-4 text-xs text-gray-500 text-center">
                                            💡 Tip: Kategori dengan stok tinggi namun produk sedikit mungkin memiliki produk dengan stok besar per item
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-12">
                                        <div className="text-gray-400 mb-4">
                                            <Icons.warning className="w-16 h-16 mx-auto" />
                                        </div>
                                        <h3 className="text-lg font-medium text-gray-700 mb-2">Tidak Ada Data Stok</h3>
                                        <p className="text-gray-500 mb-4">Belum ada data stok untuk ditampilkan dalam grafik</p>
                                        <Link
                                            href={route('barang.index')}
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            Kelola Produk
                                        </Link>
                                    </div>
                                )}
                            </div>

                            {/* Enhanced Stock Movement Chart */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <div className="mb-6">
                                    <h4 className="text-xl font-semibold text-gray-900 mb-2">Tren Pergerakan Stok Harian</h4>
                                    <p className="text-sm text-gray-600 mb-4">
                                        Grafik ini menampilkan tren pergerakan stok masuk dan keluar selama periode tertentu.
                                        Garis hijau menunjukkan stok masuk (pembelian, retur), sedangkan garis merah menunjukkan stok keluar (penjualan, kerusakan).
                                    </p>

                                    {/* Movement Legend */}
                                    <div className="flex flex-wrap gap-4 mb-4">
                                        <div className="flex items-center gap-2">
                                            <div className="w-4 h-1 bg-green-500 rounded"></div>
                                            <span className="text-sm text-gray-700">Stok Masuk</span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <div className="w-4 h-1 bg-red-500 rounded"></div>
                                            <span className="text-sm text-gray-700">Stok Keluar</span>
                                        </div>
                                    </div>

                                    {/* Today's Movement Summary */}
                                    <div className="grid grid-cols-3 gap-4 mb-4">
                                        <div className="bg-green-50 p-3 rounded-lg border border-green-200">
                                            <p className="text-xs text-green-600 font-medium">MASUK HARI INI</p>
                                            <p className="text-lg font-bold text-green-700">
                                                {formatCompactNumber(todayMovements?.in || 0)}
                                            </p>
                                        </div>
                                        <div className="bg-red-50 p-3 rounded-lg border border-red-200">
                                            <p className="text-xs text-red-600 font-medium">KELUAR HARI INI</p>
                                            <p className="text-lg font-bold text-red-700">
                                                {formatCompactNumber(todayMovements?.out || 0)}
                                            </p>
                                        </div>
                                        <div className="bg-blue-50 p-3 rounded-lg border border-blue-200">
                                            <p className="text-xs text-blue-600 font-medium">PENYESUAIAN</p>
                                            <p className="text-lg font-bold text-blue-700">
                                                {formatCompactNumber(todayMovements?.adjustments || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                {hasChartData && stockMovementTrend && stockMovementTrend.length > 0 ? (
                                    <div>
                                        <LineChart data={stockMovementData} height={300} />
                                        <div className="mt-4 text-xs text-gray-500 text-center">
                                            📊 Analisis: Perhatikan pola pergerakan untuk mengoptimalkan manajemen stok
                                        </div>
                                    </div>
                                ) : (
                                    <div className="text-center py-12">
                                        <div className="text-gray-400 mb-4">
                                            <Icons.warning className="w-16 h-16 mx-auto" />
                                        </div>
                                        <h3 className="text-lg font-medium text-gray-700 mb-2">Tidak Ada Data Pergerakan</h3>
                                        <p className="text-gray-500 mb-4">Belum ada data pergerakan stok untuk periode ini</p>
                                        <Link
                                            href={route('stok.history')}
                                            className="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors"
                                        >
                                            Lihat Riwayat Stok
                                        </Link>
                                    </div>
                                )}
                            </div>

                            {/* Low Stock Alerts */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h4 className="text-lg font-semibold text-gray-900">Low Stock Alerts</h4>
                                    <Link
                                        href={route('barang.index')}
                                        className="text-blue-600 hover:text-blue-700 text-sm font-medium"
                                    >
                                        View All Products
                                    </Link>
                                </div>

                                {lowStockItems && lowStockItems.length > 0 ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {lowStockItems.slice(0, 6).map((item) => (
                                            <div key={item.id} className={`p-4 rounded-lg border-2 ${
                                                item.status === 'critical'
                                                    ? 'bg-red-50 border-red-200'
                                                    : 'bg-yellow-50 border-yellow-200'
                                            }`}>
                                                <div className="flex justify-between items-start mb-2">
                                                    <h5 className={`font-semibold ${
                                                        item.status === 'critical' ? 'text-red-900' : 'text-yellow-900'
                                                    }`}>
                                                        {item.nama}
                                                    </h5>
                                                    <span className={`text-xs px-2 py-1 rounded-full ${
                                                        item.status === 'critical'
                                                            ? 'bg-red-100 text-red-800'
                                                            : 'bg-yellow-100 text-yellow-800'
                                                    }`}>
                                                        {item.status === 'critical' ? 'Critical' : 'Low'}
                                                    </span>
                                                </div>
                                                <p className={`text-sm mb-2 ${
                                                    item.status === 'critical' ? 'text-red-600' : 'text-yellow-600'
                                                }`}>
                                                    {item.kategori}
                                                </p>
                                                <p className={`text-lg font-bold ${
                                                    item.status === 'critical' ? 'text-red-600' : 'text-yellow-600'
                                                }`}>
                                                    {item.stok} {item.satuan} remaining
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <div className="text-green-600 mb-2">
                                            <Icons.check className="w-12 h-12 mx-auto" />
                                        </div>
                                        <p className="text-green-600 font-semibold">All products are well stocked!</p>
                                    </div>
                                )}
                            </div>



                            {/* Quick Actions */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <div className="flex items-center mb-3">
                                        <Icons.package className="h-6 w-6 text-blue-600 mr-2" />
                                        <h4 className="font-semibold text-gray-800">Kelola Produk & Inventaris</h4>
                                    </div>
                                    <p className="text-gray-600 mb-4">
                                        Tambah, edit, atau perbarui informasi produk, harga, dan kelola stok barang
                                    </p>
                                    <Link
                                        href={route('barang.index')}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <Icons.package className="w-4 h-4 mr-2" />
                                        Kelola Barang
                                    </Link>
                                </div>

                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <div className="flex items-center mb-3">
                                        <Icons.fileText className="h-6 w-6 text-green-600 mr-2" />
                                        <h4 className="font-semibold text-gray-800">Laporan Stok</h4>
                                    </div>
                                    <p className="text-gray-600 mb-4">
                                        Buat dan kelola laporan stok, lihat riwayat pergerakan inventaris
                                    </p>
                                    <Link
                                        href={route('laporan.my-reports')}
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        <Icons.fileText className="w-4 h-4 mr-2" />
                                        Lihat Laporan
                                    </Link>
                                </div>
                            </div>

                            {/* Stock Movement Guide */}
                            <div className="bg-blue-50 p-6 rounded-lg border border-blue-200 mb-6">
                                <h4 className="text-lg font-semibold text-blue-800 mb-4 flex items-center gap-2">
                                    <Info className="w-5 h-5" />
                                    Panduan Pergerakan Stock
                                </h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <h5 className="font-semibold text-green-600">✅ Pergerakan Positif (+)</h5>
                                        <div className="text-sm space-y-1 text-blue-700">
                                            <div><strong>Stock Masuk (in):</strong> Barang dari supplier</div>
                                            <div><strong>Retur (return):</strong> Pengembalian dari pelanggan</div>
                                            <div><strong>Penyesuaian (adjustment):</strong> Koreksi stok naik</div>
                                        </div>
                                    </div>
                                    <div className="space-y-2">
                                        <h5 className="font-semibold text-red-600">❌ Pergerakan Negatif (-)</h5>
                                        <div className="text-sm space-y-1 text-blue-700">
                                            <div><strong>Stock Keluar (out):</strong> Penjualan barang</div>
                                            <div><strong>Kerusakan (damage):</strong> Barang rusak/hilang</div>
                                            <div><strong>Penyesuaian (adjustment):</strong> Koreksi stok turun</div>
                                        </div>
                                    </div>
                                </div>
                                <div className="mt-4 p-3 bg-yellow-100 border border-yellow-300 rounded-lg">
                                    <p className="text-sm text-yellow-800">
                                        <strong>💡 Catatan:</strong> Nilai PLUS menambah stok, MINUS mengurangi stok.
                                        Stock Sebelum/Sesudah menunjukkan jumlah stok sebelum dan sesudah pergerakan.
                                    </p>
                                </div>
                            </div>

                            {/* Recent Movements */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h4 className="text-lg font-semibold text-gray-900">Recent Stock Movements</h4>
                                </div>

                                {recentMovements && recentMovements.length > 0 ? (
                                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                        {recentMovements.slice(0, 6).map((movement) => (
                                            <div key={movement.id} className="p-4 rounded-lg border border-gray-200 hover:shadow-md transition-shadow">
                                                <div className="flex justify-between items-start mb-2">
                                                    <Badge variant={getTypeBadgeVariant(movement.type)}>
                                                        {movement.type_label}
                                                    </Badge>
                                                    <span className={`text-sm font-medium ${
                                                        movement.quantity >= 0 ? 'text-green-600' : 'text-red-600'
                                                    }`}>
                                                        {movement.formatted_quantity}
                                                    </span>
                                                </div>
                                                <p className="text-sm text-gray-700 mb-1 font-medium">
                                                    {movement.barang.nama}
                                                </p>
                                                <p className="text-sm text-gray-600 mb-2">
                                                    {movement.description}
                                                </p>
                                                <div className="flex justify-between items-center text-xs text-gray-500">
                                                    <span>{movement.user.name}</span>
                                                    <span>{movement.time_ago}</span>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8">
                                        <div className="text-gray-400 mb-2">
                                            <svg className="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                            </svg>
                                        </div>
                                        <p className="text-gray-500 font-medium">No recent movements found</p>
                                    </div>
                                )}
                            </div>

                            {/* Today's Movements */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <div className="flex justify-between items-center mb-4">
                                    <h4 className="text-lg font-semibold text-gray-900">Today's Stock Movements</h4>
                                </div>

                                {todayMovements && (
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                        <div className="text-center p-4 bg-green-50 rounded-lg border border-green-200">
                                            <p className="text-2xl font-bold text-green-600">
                                                +{todayMovements.in}
                                            </p>
                                            <p className="text-sm text-green-700 mt-1">Stock In</p>
                                        </div>
                                        <div className="text-center p-4 bg-red-50 rounded-lg border border-red-200">
                                            <p className="text-2xl font-bold text-red-600">
                                                -{todayMovements.out}
                                            </p>
                                            <p className="text-sm text-red-700 mt-1">Stock Out</p>
                                        </div>
                                        <div className="text-center p-4 bg-blue-50 rounded-lg border border-blue-200">
                                            <p className="text-2xl font-bold text-blue-600">
                                                {todayMovements.adjustments}
                                            </p>
                                            <p className="text-sm text-blue-700 mt-1">Adjustments</p>
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </AppLayout>
        </>
    );
}
