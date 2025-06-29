import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import LineChart from '@/components/Charts/LineChart';
import DoughnutChart from '@/components/Charts/DoughnutChart';
import BarChart from '@/components/Charts/BarChart';
import { formatCurrency, formatCompactNumber, Icons } from '@/utils/formatters';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin Dashboard',
        href: '/admin/dashboard',
    },
];

interface AdminDashboardProps extends PageProps {
    todaysSalesTrend: Array<{
        hour: string;
        transactions: number;
        revenue: number;
    }>;
    paymentMethods: Array<{
        method: string;
        count: number;
        total: number;
    }>;
    salesSummary: {
        today: { revenue: number; transactions: number };
        yesterday: { revenue: number; transactions: number };
        this_month: { revenue: number; transactions: number };
        last_month: { revenue: number; transactions: number };
    };
    topProducts: Array<{
        nama: string;
        kategori: string;
        total_sold: number;
        total_revenue: number;
    }>;
    recentTransactions: any[];
}

export default function AdminDashboard({
    auth,
    todaysSalesTrend,
    paymentMethods,
    salesSummary,
    topProducts,
    recentTransactions
}: AdminDashboardProps) {
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');

    const handleDateFilter = () => {
        router.get(route('admin.dashboard'), {
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    // Prepare chart data
    const salesTrendData = {
        labels: todaysSalesTrend?.map(item => item.hour) || [],
        datasets: [
            {
                label: 'Revenue',
                data: todaysSalesTrend?.map(item => item.revenue) || [],
                borderColor: 'rgb(34, 197, 94)',
                backgroundColor: 'rgba(34, 197, 94, 0.1)',
                fill: true,
                tension: 0.4,
            },
            {
                label: 'Transactions',
                data: todaysSalesTrend?.map(item => item.transactions) || [],
                borderColor: 'rgb(59, 130, 246)',
                backgroundColor: 'rgba(59, 130, 246, 0.1)',
                fill: false,
                tension: 0.4,
                yAxisID: 'y1',
            },
        ],
    };

    const paymentMethodsData = {
        labels: paymentMethods?.map(item => item.method) || [],
        datasets: [
            {
                data: paymentMethods?.map(item => item.count) || [],
                backgroundColor: [
                    'rgba(34, 197, 94, 0.8)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(168, 85, 247, 0.8)',
                    'rgba(245, 158, 11, 0.8)',
                    'rgba(239, 68, 68, 0.8)',
                ],
                borderColor: [
                    'rgb(34, 197, 94)',
                    'rgb(59, 130, 246)',
                    'rgb(168, 85, 247)',
                    'rgb(245, 158, 11)',
                    'rgb(239, 68, 68)',
                ],
                borderWidth: 2,
            },
        ],
    };

    const salesTrendOptions = {
        scales: {
            y: {
                type: 'linear' as const,
                display: true,
                position: 'left' as const,
                title: {
                    display: true,
                    text: 'Revenue (Rp)',
                },
            },
            y1: {
                type: 'linear' as const,
                display: true,
                position: 'right' as const,
                title: {
                    display: true,
                    text: 'Transactions',
                },
                grid: {
                    drawOnChartArea: false,
                },
            },
        },
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
                    {/* Header */}
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <h3 className="text-2xl font-bold text-gray-900 mb-2">Admin Dashboard</h3>
                            <p className="text-gray-600">Selamat datang, Administrator! Kelola sistem toko beras dengan akses penuh.</p>
                        </div>
                    </div>

                    {/* Date Filter */}
                    <div className="bg-white overflow-hidden shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
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
                                <button
                                    onClick={handleDateFilter}
                                    className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Filter
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Stats Cards */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-green-100">
                                        <Icons.money className="h-6 w-6 text-green-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Revenue Hari Ini</p>
                                        <p className="text-xl font-bold text-gray-900 truncate" title={formatCurrency(salesSummary?.today?.revenue || 0)}>
                                            {formatCompactNumber(salesSummary?.today?.revenue || 0, 'currency')}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-blue-100">
                                        <Icons.transactions className="h-6 w-6 text-blue-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Transaksi Hari Ini</p>
                                        <p className="text-xl font-bold text-gray-900 truncate" title={(salesSummary?.today?.transactions || 0).toString()}>
                                            {formatCompactNumber(salesSummary?.today?.transactions || 0)}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-purple-100">
                                        <Icons.calendar className="h-6 w-6 text-purple-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Revenue Bulan Ini</p>
                                        <p className="text-xl font-bold text-gray-900 truncate" title={formatCurrency(salesSummary?.this_month?.revenue || 0)}>
                                            {formatCompactNumber(salesSummary?.this_month?.revenue || 0, 'currency')}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <div className="flex items-center">
                                    <div className="p-3 rounded-full bg-orange-100">
                                        <Icons.package className="h-6 w-6 text-orange-600" />
                                    </div>
                                    <div className="ml-4 flex-1 min-w-0">
                                        <p className="text-sm font-medium text-gray-600">Transaksi Bulan Ini</p>
                                        <p className="text-xl font-bold text-gray-900 truncate" title={(salesSummary?.this_month?.transactions || 0).toString()}>
                                            {formatCompactNumber(salesSummary?.this_month?.transactions || 0)}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Charts */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* Today's Sales Trend */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">Today's Sales Trend</h4>
                                <LineChart data={salesTrendData} options={salesTrendOptions} height={300} />
                            </div>
                        </div>

                        {/* Payment Methods */}
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h4 className="text-lg font-semibold text-gray-900 mb-4">Payment Methods</h4>
                                <DoughnutChart data={paymentMethodsData} height={300} />
                            </div>
                        </div>
                    </div>

                    {/* Quick Actions */}
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div className="bg-blue-50 p-6 rounded-lg">
                            <h4 className="font-semibold text-blue-800 mb-2">Kelola Pengguna</h4>
                            <p className="text-blue-600 mb-4">Tambah, edit, dan hapus pengguna sistem</p>
                            <a
                                href={route('admin.users.index')}
                                className="inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Kelola Pengguna
                            </a>
                        </div>

                        <div className="bg-green-50 p-6 rounded-lg">
                            <h4 className="font-semibold text-green-800 mb-2">Inventori</h4>
                            <p className="text-green-600 mb-4">Kelola stok beras dan produk</p>
                            <a
                                href={route('barang.index')}
                                className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Kelola Barang
                            </a>
                        </div>

                        <div className="bg-purple-50 p-6 rounded-lg">
                            <h4 className="font-semibold text-purple-800 mb-2">Laporan</h4>
                            <p className="text-purple-600 mb-4">Lihat laporan penjualan dan keuangan</p>
                            <a
                                href={route('laporan.index')}
                                className="inline-flex items-center px-4 py-2 bg-purple-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-purple-700 focus:bg-purple-700 active:bg-purple-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                            >
                                Lihat Laporan
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
