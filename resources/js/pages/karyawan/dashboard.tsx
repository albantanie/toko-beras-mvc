import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import BarChart from '@/components/Charts/BarChart';
import DoughnutChart from '@/components/Charts/DoughnutChart';
import LineChart from '@/components/Charts/LineChart';
import { formatCurrency, formatCompactNumber, Icons } from '@/utils/formatters';

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
        avg_stock: number;
    }>;
    lowStockItems: Array<{
        id: number;
        nama: string;
        kategori: string;
        stok: number;
        satuan: string;
        status: string;
    }>;
    categoriesDistribution: Array<{
        category: string;
        count: number;
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
}

export default function KaryawanDashboard({
    stockLevels,
    lowStockItems,
    categoriesDistribution,
    inventorySummary,
    stockMovementTrend
}: KaryawanDashboardProps) {
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

    const categoriesData = {
        labels: categoriesDistribution?.map(item => item.category) || [],
        datasets: [
            {
                data: categoriesDistribution?.map(item => item.count) || [],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                    'rgba(20, 184, 166, 0.8)',
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)',
                    'rgb(20, 184, 166)',
                ],
                borderWidth: 2,
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

    const inStockCount = inventorySummary?.total_products - inventorySummary?.low_stock_items - inventorySummary?.out_of_stock_items;

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Karyawan Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Dashboard Karyawan - Inventory Charts</h3>
                            
                            {/* Inventory Summary Cards */}
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
                                            <p className="text-xl font-bold text-blue-900 truncate" title={(inventorySummary?.total_products || 0).toString()}>
                                                {formatCompactNumber(inventorySummary?.total_products || 0)}
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
                                            <p className="text-xl font-bold text-green-900 truncate" title={(inStockCount || 0).toString()}>
                                                {formatCompactNumber(inStockCount || 0)}
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
                                            <p className="text-xl font-bold text-yellow-900 truncate" title={(inventorySummary?.low_stock_items || 0).toString()}>
                                                {formatCompactNumber(inventorySummary?.low_stock_items || 0)}
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
                                            <p className="text-xl font-bold text-red-900 truncate" title={(inventorySummary?.out_of_stock_items || 0).toString()}>
                                                {formatCompactNumber(inventorySummary?.out_of_stock_items || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Inventory Charts */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Stock Level Distribution</h4>
                                    <BarChart data={stockLevelsData} height={250} />
                                </div>

                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Product Categories</h4>
                                    <DoughnutChart data={categoriesData} height={250} />
                                </div>
                            </div>

                            {/* Stock Movement Chart */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <h4 className="text-lg font-semibold mb-4">Stock Movement Trend</h4>
                                <LineChart data={stockMovementData} height={250} />
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

                            {/* Inventory Summary */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">Inventory Value Summary</h4>
                                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                    <div className="text-center p-4 bg-green-50 rounded-lg">
                                        <p className="text-2xl font-bold text-green-600 truncate" title={formatCurrency(inventorySummary?.total_stock_value || 0)}>
                                            {formatCompactNumber(inventorySummary?.total_stock_value || 0, 'currency')}
                                        </p>
                                        <p className="text-sm text-green-700 mt-1">Total Stock Value</p>
                                    </div>
                                    <div className="text-center p-4 bg-blue-50 rounded-lg">
                                        <p className="text-2xl font-bold text-blue-600 truncate" title={(inventorySummary?.categories_count || 0).toString()}>
                                            {formatCompactNumber(inventorySummary?.categories_count || 0)}
                                        </p>
                                        <p className="text-sm text-blue-700 mt-1">Product Categories</p>
                                    </div>
                                    <div className="text-center p-4 bg-purple-50 rounded-lg">
                                        <p className="text-2xl font-bold text-purple-600 truncate" title={`${((inventorySummary?.low_stock_items || 0) / (inventorySummary?.total_products || 1) * 100).toFixed(1)}%`}>
                                            {((inventorySummary?.low_stock_items || 0) / (inventorySummary?.total_products || 1) * 100).toFixed(1)}%
                                        </p>
                                        <p className="text-sm text-purple-700 mt-1">Low Stock Ratio</p>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Manage Inventory</h4>
                                    <p className="text-gray-600 mb-4">Add, edit, or update product information</p>
                                    <a 
                                        href={route('barang.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Manage Products
                                    </a>
                                </div>
                                
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Process Sales</h4>
                                    <p className="text-gray-600 mb-4">Handle customer transactions</p>
                                    <a 
                                        href={route('penjualan.index')} 
                                        className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        View Transactions
                                    </a>
                                </div>
                                
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Stock Reports</h4>
                                    <p className="text-gray-600 mb-4">View detailed stock reports</p>
                                    <a 
                                        href={route('laporan.stok')} 
                                        className="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        View Reports
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            </AppLayout>
        </>
    );
}
