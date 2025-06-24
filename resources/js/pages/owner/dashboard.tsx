import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import { formatCurrency, formatCompactNumber, ProductImage, Icons } from '@/utils/formatters';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Owner Dashboard',
        href: '/owner/dashboard',
    },
];

interface OwnerDashboardProps extends PageProps {
    comprehensiveSummary: {
        total_customers: number;
        total_products: number;
        low_stock_items: number;
        today_orders: number;
        pending_orders: number;
        month_revenue: number;
    };
    bestSellingProducts: Array<{
        id: number;
        nama: string;
        kategori: string;
        harga_jual: number;
        stok: number;
        gambar: string | null;
        total_terjual: number;
        total_revenue: number;
        total_transaksi: number;
        avg_price: number;
        revenue_per_unit: number;
    }>;
    recommendations: Array<{
        type: 'success' | 'warning' | 'danger' | 'info';
        icon: string;
        title: string;
        description: string;
        action: string;
        action_url: string;
        priority: 'critical' | 'high' | 'medium' | 'low';
    }>;
    lowStockItems: Array<{
        id: number;
        nama: string;
        kategori: string;
        stok: number;
        satuan: string;
        status: 'critical' | 'low';
    }>;
    customerInsights: {
        total_customers: number;
        active_customers: number;
        new_customers: number;
        avg_order_value: number;
        customer_retention_rate: number;
        top_customers: Array<{
            name: string;
            email: string;
            total_spent: number;
            total_orders: number;
            avg_order_value: number;
        }>;
    };
}

export default function OwnerDashboard({ 
    auth, 
    comprehensiveSummary, 
    bestSellingProducts, 
    recommendations, 
    lowStockItems, 
    customerInsights 
}: OwnerDashboardProps) {
    const getRecommendationIcon = (iconName: string) => {
        switch (iconName) {
            case 'alert-triangle': return <Icons.alertTriangle className="w-5 h-5" />;
            case 'trending-up': return <Icons.trendingUp className="w-5 h-5" />;
            case 'trending-down': return <Icons.trendingDown className="w-5 h-5" />;
            case 'sun': return <Icons.sun className="w-5 h-5" />;
            case 'users': return <Icons.users className="w-5 h-5" />;
            default: return <Icons.info className="w-5 h-5" />;
        }
    };

    const getRecommendationColor = (type: string) => {
        switch (type) {
            case 'success': return 'bg-green-50 border-green-200 text-green-800';
            case 'warning': return 'bg-yellow-50 border-yellow-200 text-yellow-800';
            case 'danger': return 'bg-red-50 border-red-200 text-red-800';
            case 'info': return 'bg-blue-50 border-blue-200 text-blue-800';
            default: return 'bg-gray-50 border-gray-200 text-gray-800';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Owner Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-2">Dashboard Owner - Business Overview</h3>
                            <p className="text-gray-600">Kelola bisnis toko beras dengan analitik lengkap dan rekomendasi cerdas</p>
                        </div>
                    </div>

                    {/* Business Summary Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-green-50 p-6 rounded-lg border border-green-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-green-100 rounded-lg">
                                    <Icons.money className="w-6 h-6 text-green-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-green-600">Revenue Bulan Ini</p>
                                    <p className="text-xl font-bold text-green-900 truncate" title={formatCurrency(comprehensiveSummary.month_revenue)}>
                                        {formatCompactNumber(comprehensiveSummary.month_revenue, 'currency')}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-blue-50 p-6 rounded-lg border border-blue-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-blue-100 rounded-lg">
                                    <Icons.shoppingCart className="w-6 h-6 text-blue-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-blue-600">Pesanan Hari Ini</p>
                                    <p className="text-xl font-bold text-blue-900 truncate" title={comprehensiveSummary.today_orders.toString()}>
                                        {formatCompactNumber(comprehensiveSummary.today_orders)}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-purple-50 p-6 rounded-lg border border-purple-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-purple-100 rounded-lg">
                                    <Icons.package className="w-6 h-6 text-purple-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-purple-600">Total Produk</p>
                                    <p className="text-xl font-bold text-purple-900 truncate" title={comprehensiveSummary.total_products.toString()}>
                                        {formatCompactNumber(comprehensiveSummary.total_products)}
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div className="bg-orange-50 p-6 rounded-lg border border-orange-200">
                            <div className="flex items-center">
                                <div className="p-2 bg-orange-100 rounded-lg">
                                    <Icons.alertTriangle className="w-6 h-6 text-orange-600" />
                                </div>
                                <div className="ml-4 flex-1 min-w-0">
                                    <p className="text-sm font-medium text-orange-600">Stok Rendah</p>
                                    <p className="text-xl font-bold text-orange-900 truncate" title={comprehensiveSummary.low_stock_items.toString()}>
                                        {formatCompactNumber(comprehensiveSummary.low_stock_items)}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Best Selling Products */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="flex items-center justify-between mb-6">
                                <h4 className="text-lg font-semibold text-gray-900">🏆 Produk Terlaris (30 Hari Terakhir)</h4>
                                <a 
                                    href={route('barang.index')} 
                                    className="text-indigo-600 hover:text-indigo-800 text-sm font-medium"
                                >
                                    Lihat Semua →
                                </a>
                            </div>
                            
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                {bestSellingProducts && bestSellingProducts.length > 0 ? (
                                    bestSellingProducts.slice(0, 4).map((product) => (
                                        <div key={product.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                                            <div className="flex items-center space-x-3 mb-3">
                                                <ProductImage
                                                    src={product.gambar || ''}
                                                    alt={product.nama}
                                                    className="h-12 w-12 rounded-lg object-cover"
                                                />
                                                <div className="flex-1 min-w-0">
                                                    <p className="text-sm font-medium text-gray-900 truncate">{product.nama}</p>
                                                    <p className="text-xs text-gray-500">{product.kategori}</p>
                                                </div>
                                            </div>
                                            
                                            <div className="space-y-2">
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Terjual:</span>
                                                    <span className="font-medium text-green-600 truncate" title={`${product.total_terjual} unit`}>
                                                        {formatCompactNumber(product.total_terjual)} unit
                                                    </span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Revenue:</span>
                                                    <span className="font-medium text-green-600 truncate" title={formatCurrency(product.total_revenue)}>
                                                        {formatCompactNumber(product.total_revenue, 'currency')}
                                                    </span>
                                                </div>
                                                <div className="flex justify-between text-sm">
                                                    <span className="text-gray-600">Transaksi:</span>
                                                    <span className="font-medium truncate" title={`${product.total_transaksi} transaksi`}>
                                                        {formatCompactNumber(product.total_transaksi)}x
                                                    </span>
                                                </div>
                                                <div className="pt-2 border-t">
                                                    <div className="flex justify-between text-xs">
                                                        <span className="text-gray-500">Stok:</span>
                                                        <span className={`font-medium truncate ${product.stok <= 10 ? 'text-red-600' : 'text-gray-700'}`} title={`${product.stok} unit`}>
                                                            {formatCompactNumber(product.stok)} unit
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="col-span-4 text-center py-8 text-gray-500">
                                        <Icons.package className="w-12 h-12 mx-auto mb-3 text-gray-400" />
                                        <p>Belum ada data penjualan dalam 30 hari terakhir</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Recommendations */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h4 className="text-lg font-semibold text-gray-900 mb-6">💡 Rekomendasi Bisnis</h4>
                            
                            <div className="space-y-4">
                                {recommendations && recommendations.length > 0 ? (
                                    recommendations.map((rec, index) => (
                                        <div key={index} className={`p-4 rounded-lg border ${getRecommendationColor(rec.type)}`}>
                                            <div className="flex items-start space-x-3">
                                                <div className="flex-shrink-0">
                                                    {getRecommendationIcon(rec.icon)}
                                                </div>
                                                <div className="flex-1 min-w-0">
                                                    <h5 className="text-sm font-medium">{rec.title}</h5>
                                                    <p className="text-sm mt-1 opacity-90">{rec.description}</p>
                                                    <a 
                                                        href={rec.action_url}
                                                        className="inline-flex items-center mt-2 text-sm font-medium hover:underline"
                                                    >
                                                        {rec.action} →
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                ) : (
                                    <div className="text-center py-8 text-gray-500">
                                        <Icons.checkmark className="w-12 h-12 mx-auto mb-3 text-green-500" />
                                        <p>Semua berjalan lancar! Tidak ada rekomendasi khusus saat ini.</p>
                                    </div>
                                )}
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-white p-6 rounded-lg border border-gray-200">
                            <h4 className="font-semibold text-gray-800 mb-2">📊 Laporan Bisnis</h4>
                            <p className="text-gray-600 mb-4">Analisis mendalam performa bisnis</p>
                            <a 
                                href={route('laporan.index')} 
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 transition"
                            >
                                Lihat Laporan
                            </a>
                        </div>
                        
                        <div className="bg-white p-6 rounded-lg border border-gray-200">
                            <h4 className="font-semibold text-gray-800 mb-2">📦 Kelola Inventori</h4>
                            <p className="text-gray-600 mb-4">Manajemen produk dan stok</p>
                            <a 
                                href={route('barang.index')} 
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 transition"
                            >
                                Kelola Barang
                            </a>
                        </div>
                        
                        <div className="bg-white p-6 rounded-lg border border-gray-200">
                            <h4 className="font-semibold text-gray-800 mb-2">💰 Monitor Penjualan</h4>
                            <p className="text-gray-600 mb-4">Pantau transaksi dan revenue</p>
                            <a 
                                href={route('penjualan.index')} 
                                className="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 transition"
                            >
                                Lihat Penjualan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
