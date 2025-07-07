import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { BreadcrumbItem } from '@/types';
import LineChart from '@/components/Charts/LineChart';
import DoughnutChart from '@/components/Charts/DoughnutChart';
import { formatCurrency, formatCompactNumber, formatDateTime, Icons } from '@/utils/formatters';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard Kasir',
        href: '/kasir/dashboard',
    },
];

interface KasirDashboardProps {
    todaysSalesTrend: Array<{
        hour: string;
        transactions: number;
        revenue: number;
    }>;
    todaysTransactionTypes: Array<{
        type: string;
        count: number;
        total: number;
    }>;
    pendingOrders: {
        orders: any[];
        status_breakdown: {
            pending: number;
            dibayar: number;
            siap_pickup: number;
        };
        urgent_count: number;
        total_pending: number;
        total_paid: number;
        total_ready: number;
    };
    todaysSummary: {
        total_transactions: number;
        total_revenue: number;
        online_orders: {
            total: number;
            pending: number;
            paid: number;
            ready: number;
            urgent: number;
        };
        walk_in_sales: number;
    };
}

export default function KasirDashboard({
    todaysSalesTrend,
    todaysTransactionTypes,
    pendingOrders,
    todaysSummary
}: KasirDashboardProps) {
    // Prepare chart data
    const salesTrendData = {
        labels: todaysSalesTrend?.map(item => item.hour) || [],
        datasets: [
            {
                label: 'Revenue (Rp)',
                data: todaysSalesTrend?.map(item => item.revenue) || [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4,
            },
        ],
    };

    const transactionTypesData = {
        labels: todaysTransactionTypes?.map(item => item.type) || [],
        datasets: [
            {
                data: todaysTransactionTypes?.map(item => item.count) || [],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                ],
                borderWidth: 2,
            },
        ],
    };

    return (
        <>
            <AppLayout breadcrumbs={breadcrumbs}>
                <Head title="Dashboard Kasir" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6 text-gray-900">
                            <h3 className="text-lg font-medium mb-6">Dashboard Kasir - Pusat Transaksi</h3>
                            
                            {/* Transaction Summary Cards */}
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                                <div className="bg-green-50 p-6 rounded-lg border border-green-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-green-100 rounded-lg">
                                            <svg className="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-green-600">Penjualan Hari Ini</p>
                                            <p className="text-xl font-bold text-green-900 truncate" title={formatCurrency(todaysSummary?.total_revenue || 0)}>
                                                {formatCompactNumber(todaysSummary?.total_revenue || 0, 'currency')}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-blue-50 p-6 rounded-lg border border-blue-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-blue-100 rounded-lg">
                                            <svg className="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-blue-600">Jumlah Transaksi</p>
                                            <p className="text-xl font-bold text-blue-900 truncate" title={(todaysSummary?.total_transactions || 0).toString()}>
                                                {formatCompactNumber(todaysSummary?.total_transactions || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-purple-50 p-6 rounded-lg border border-purple-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-purple-100 rounded-lg">
                                            <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-purple-600">Pesanan Online</p>
                                            <p className="text-xl font-bold text-purple-900 truncate" title={(todaysSummary?.online_orders?.total || 0).toString()}>
                                                {formatCompactNumber(todaysSummary?.online_orders?.total || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div className="bg-orange-50 p-6 rounded-lg border border-orange-200">
                                    <div className="flex items-center">
                                        <div className="p-2 bg-orange-100 rounded-lg">
                                            <svg className="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                                            </svg>
                                        </div>
                                        <div className="ml-4 flex-1 min-w-0">
                                            <p className="text-sm font-medium text-orange-600">Penjualan Langsung</p>
                                            <p className="text-xl font-bold text-orange-900 truncate" title={(todaysSummary?.walk_in_sales || 0).toString()}>
                                                {formatCompactNumber(todaysSummary?.walk_in_sales || 0)}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Quick Transaction Actions */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                                <div className="bg-gradient-to-r from-blue-500 to-purple-600 p-6 rounded-lg">
                                    <div className="text-center">
                                        <h4 className="text-xl font-bold text-white mb-2">Transaksi Baru</h4>
                                        <p className="text-blue-100 mb-4">Proses pembelian pelanggan langsung</p>
                                        <a
                                            href={route('penjualan.create')}
                                            className="inline-flex items-center px-6 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                            </svg>
                                            Mulai Transaksi
                                        </a>
                                    </div>
                                </div>

                                <div className="bg-gradient-to-r from-green-500 to-teal-600 p-6 rounded-lg">
                                    <div className="text-center">
                                        <h4 className="text-xl font-bold text-white mb-2">Pesanan Online</h4>
                                        <p className="text-green-100 mb-4">Kelola pesanan pelanggan online</p>
                                        <a
                                            href={route('penjualan.online')}
                                            className="inline-flex items-center px-6 py-3 bg-white text-green-600 font-semibold rounded-lg hover:bg-gray-100 transition duration-150 ease-in-out"
                                        >
                                            <svg className="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                            </svg>
                                            Kelola Pesanan
                                        </a>
                                    </div>
                                </div>
                            </div>

                            {/* Online Orders Overview */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-8">
                                <div className="flex items-center justify-between mb-6">
                                    <h4 className="text-xl font-semibold text-gray-800">📦 Ringkasan Pesanan Online</h4>
                                    <div className="flex items-center space-x-2">
                                        <span className="text-sm text-gray-500">Terakhir diperbarui:</span>
                                        <span className="text-sm font-medium text-gray-700">{new Date().toLocaleTimeString('id-ID')}</span>
                                    </div>
                                </div>

                                {/* Status Cards */}
                                <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                                    {/* Pending Orders */}
                                    <div className={`p-4 rounded-lg border-2 ${todaysSummary?.online_orders?.pending > 0 ? 'bg-yellow-50 border-yellow-300' : 'bg-gray-50 border-gray-200'}`}>
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Menunggu Pembayaran</p>
                                                <p className={`text-2xl font-bold ${todaysSummary?.online_orders?.pending > 0 ? 'text-yellow-700' : 'text-gray-500'}`}>
                                                    {todaysSummary?.online_orders?.pending || 0}
                                                </p>
                                            </div>
                                            <div className={`p-2 rounded-full ${todaysSummary?.online_orders?.pending > 0 ? 'bg-yellow-100' : 'bg-gray-100'}`}>
                                                <svg className={`w-6 h-6 ${todaysSummary?.online_orders?.pending > 0 ? 'text-yellow-600' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                        {todaysSummary?.online_orders?.urgent > 0 && (
                                            <div className="mt-2">
                                                <span className="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    ⚠️ {todaysSummary?.online_orders?.urgent} urgent
                                                </span>
                                            </div>
                                        )}
                                    </div>

                                    {/* Paid Orders */}
                                    <div className={`p-4 rounded-lg border-2 ${todaysSummary?.online_orders?.paid > 0 ? 'bg-blue-50 border-blue-300' : 'bg-gray-50 border-gray-200'}`}>
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Sudah Dibayar</p>
                                                <p className={`text-2xl font-bold ${todaysSummary?.online_orders?.paid > 0 ? 'text-blue-700' : 'text-gray-500'}`}>
                                                    {todaysSummary?.online_orders?.paid || 0}
                                                </p>
                                            </div>
                                            <div className={`p-2 rounded-full ${todaysSummary?.online_orders?.paid > 0 ? 'bg-blue-100' : 'bg-gray-100'}`}>
                                                <svg className={`w-6 h-6 ${todaysSummary?.online_orders?.paid > 0 ? 'text-blue-600' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Ready for Pickup */}
                                    <div className={`p-4 rounded-lg border-2 ${todaysSummary?.online_orders?.ready > 0 ? 'bg-green-50 border-green-300' : 'bg-gray-50 border-gray-200'}`}>
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Siap Pickup</p>
                                                <p className={`text-2xl font-bold ${todaysSummary?.online_orders?.ready > 0 ? 'text-green-700' : 'text-gray-500'}`}>
                                                    {todaysSummary?.online_orders?.ready || 0}
                                                </p>
                                            </div>
                                            <div className={`p-2 rounded-full ${todaysSummary?.online_orders?.ready > 0 ? 'bg-green-100' : 'bg-gray-100'}`}>
                                                <svg className={`w-6 h-6 ${todaysSummary?.online_orders?.ready > 0 ? 'text-green-600' : 'text-gray-400'}`} fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Total Orders */}
                                    <div className="p-4 rounded-lg border-2 bg-purple-50 border-purple-300">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium text-gray-600">Total Pesanan</p>
                                                <p className="text-2xl font-bold text-purple-700">
                                                    {todaysSummary?.online_orders?.total || 0}
                                                </p>
                                            </div>
                                            <div className="p-2 rounded-full bg-purple-100">
                                                <svg className="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                                                </svg>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Action Buttons */}
                                <div className="flex flex-wrap gap-3">
                                    <a
                                        href={route('penjualan.online')}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition duration-150 ease-in-out"
                                    >
                                        <svg className="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                                        </svg>
                                        Kelola Semua Pesanan
                                    </a>
                                    
                                    {todaysSummary?.online_orders?.urgent > 0 && (
                                        <span className="inline-flex items-center px-4 py-2 bg-red-100 text-red-800 text-sm font-medium rounded-lg">
                                            ⚠️ {todaysSummary?.online_orders?.urgent} Pesanan Urgent (≥1 jam)
                                        </span>
                                    )}
                                    
                                    {todaysSummary?.online_orders?.paid > 0 && (
                                        <span className="inline-flex items-center px-4 py-2 bg-blue-100 text-blue-800 text-sm font-medium rounded-lg">
                                            💳 {todaysSummary?.online_orders?.paid} Pesanan Perlu Konfirmasi
                                        </span>
                                    )}
                                    
                                    {todaysSummary?.online_orders?.ready > 0 && (
                                        <span className="inline-flex items-center px-4 py-2 bg-green-100 text-green-800 text-sm font-medium rounded-lg">
                                            📦 {todaysSummary?.online_orders?.ready} Pesanan Siap Pickup
                                        </span>
                                    )}
                                </div>
                            </div>

                            {/* Recent Orders That Need Attention */}
                            {pendingOrders?.orders && pendingOrders.orders.length > 0 && (
                                <div className="bg-white p-6 rounded-lg border border-gray-200 mb-8">
                                    <div className="flex items-center justify-between mb-6">
                                        <h4 className="text-xl font-semibold text-gray-800">🚨 Pesanan yang Perlu Ditindak</h4>
                                        <span className="text-sm text-gray-500">
                                            {pendingOrders.orders.length} pesanan menunggu
                                        </span>
                                    </div>

                                    <div className="overflow-x-auto">
                                        <table className="min-w-full divide-y divide-gray-200">
                                            <thead className="bg-gray-50">
                                                <tr>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID Pesanan</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                                    <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                                </tr>
                                            </thead>
                                            <tbody className="bg-white divide-y divide-gray-200">
                                                {pendingOrders.orders.slice(0, 5).map((order: any) => {
                                                    const isUrgent = order.status === 'pending' && 
                                                        new Date(order.tanggal_transaksi).getTime() < new Date().getTime() - (60 * 60 * 1000);
                                                    
                                                    const statusConfig = {
                                                        pending: { color: 'bg-yellow-100 text-yellow-800', text: 'Menunggu Pembayaran' },
                                                        dibayar: { color: 'bg-blue-100 text-blue-800', text: 'Sudah Dibayar' },
                                                        siap_pickup: { color: 'bg-green-100 text-green-800', text: 'Siap Pickup' },
                                                    };

                                                    const status = statusConfig[order.status as keyof typeof statusConfig] || statusConfig.pending;

                                                    return (
                                                        <tr key={order.id} className={isUrgent ? 'bg-red-50' : ''}>
                                                            <td className="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                #{order.id}
                                                                {isUrgent && (
                                                                    <span className="ml-2 inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                                        ⚠️ Urgent
                                                                    </span>
                                                                )}
                                                            </td>
                                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {order.nama_pelanggan || 'Pelanggan Langsung'}
                                                            </td>
                                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                                {order.detail_penjualans?.length || 0} item
                                                            </td>
                                                            <td className="px-4 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                                {formatCurrency(order.total)}
                                                            </td>
                                                            <td className="px-4 py-4 whitespace-nowrap">
                                                                <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${status.color}`}>
                                                                    {status.text}
                                                                </span>
                                                            </td>
                                                            <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                                {formatDateTime(order.tanggal_transaksi)}
                                                            </td>
                                                            <td className="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                                                <a
                                                                    href={route('penjualan.show', order.id)}
                                                                    className="text-blue-600 hover:text-blue-900 font-medium"
                                                                >
                                                                    Lihat Detail
                                                                </a>
                                                            </td>
                                                        </tr>
                                                    );
                                                })}
                                            </tbody>
                                        </table>
                                    </div>

                                    {pendingOrders.orders.length > 5 && (
                                        <div className="mt-4 text-center">
                                            <a
                                                href={route('penjualan.online')}
                                                className="text-blue-600 hover:text-blue-900 font-medium"
                                            >
                                                Lihat semua {pendingOrders.orders.length} pesanan →
                                            </a>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Sales Charts */}
                            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Tren Penjualan Hari Ini</h4>
                                    <LineChart data={salesTrendData} height={250} />
                                </div>

                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="text-lg font-semibold mb-4">Jenis Transaksi</h4>
                                    <DoughnutChart data={transactionTypesData} height={250} />
                                </div>
                            </div>

                            {/* Recent Transactions */}
                            <div className="bg-white p-6 rounded-lg border border-gray-200 mb-6">
                                <h4 className="text-lg font-semibold mb-4">Transaksi Terbaru</h4>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Waktu</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pelanggan</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Item</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total</th>
                                                <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pembayaran</th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            <tr>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">14:30</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">John Doe</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">3 item</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp 75,000</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Tunai</td>
                                            </tr>
                                            <tr>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">14:15</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Jane Smith</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">2 item</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Rp 45,000</td>
                                                <td className="px-6 py-4 whitespace-nowrap text-sm text-gray-900">Kartu</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {/* Quick Actions */}
                            <div className="grid grid-cols-1 gap-6">
                                <div className="bg-white p-6 rounded-lg border border-gray-200">
                                    <h4 className="font-semibold text-gray-800 mb-2">Riwayat Transaksi</h4>
                                    <p className="text-gray-600 mb-4">Lihat semua transaksi yang sudah selesai</p>
                                    <a
                                        href={route('penjualan.index')}
                                        className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                                    >
                                        Lihat Riwayat
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
