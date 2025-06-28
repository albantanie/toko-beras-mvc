import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import DataTable, { Column, PaginatedData } from '@/components/data-table';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import { useState } from 'react';

interface LaporanPenjualanProps extends PageProps {
    penjualans: PaginatedData<Penjualan>;
    summary: {
        total_transactions: number;
        total_sales: number;
        average_transaction: number;
        total_profit: number | null;
        total_items_sold: number;
    };
    sales_chart: Array<{
        date: string;
        label: string;
        sales: number;
    }>;
    filters?: {
        date_from?: string;
        date_to?: string;
        search?: string;
        sort?: string;
        direction?: string;
    };
    user_role?: {
        is_kasir: boolean;
        is_karyawan: boolean;
        is_admin_or_owner: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Laporan',
        href: '/laporan',
    },
    {
        title: 'Laporan Penjualan',
        href: '/laporan/penjualan',
    },
];

export default function LaporanPenjualan({ auth, penjualans, summary, sales_chart, filters = {}, user_role }: LaporanPenjualanProps) {
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    const handleDateFilter = () => {
        const params = new URLSearchParams(window.location.search);
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    };

    const columns: Column[] = [
        {
            key: 'nomor_transaksi',
            label: 'No. Transaksi',
            sortable: true,
            searchable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">{value}</div>
                    <div className="text-xs text-gray-500">
                        {formatDateTime(row.tanggal_transaksi)}
                    </div>
                </div>
            ),
        },
        {
            key: 'nama_pelanggan',
            label: 'Pelanggan',
            sortable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {value || row.pelanggan?.name || 'Walk-in Customer'}
                    </div>
                    {row.telepon_pelanggan && (
                        <div className="text-xs text-gray-500">{row.telepon_pelanggan}</div>
                    )}
                </div>
            ),
        },
        {
            key: 'user',
            label: 'Kasir',
            render: (_, row) => (
                <div className="text-sm text-gray-900">
                    {row.user?.name || 'Unknown'}
                </div>
            ),
        },
        {
            key: 'detail_penjualans',
            label: 'Items',
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {value?.length || 0} items
                    </div>
                    <div className="text-xs text-gray-500">
                        {value?.reduce((sum: number, item: any) => sum + item.jumlah, 0) || 0} qty
                    </div>
                </div>
            ),
        },
        {
            key: 'total',
            label: 'Total',
            sortable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">
                        {formatCurrency(value)}
                    </div>
                    <div className="text-xs text-gray-500">
                        {row.metode_pembayaran.replace('_', ' ').toUpperCase()}
                    </div>
                </div>
            ),
        },
        // Only show profit column for admin/owner
        ...(user_role?.is_admin_or_owner ? [{
            key: 'profit',
            label: 'Profit',
            render: (_, row) => {
                const profit = row.detail_penjualans?.reduce((sum: number, item: any) => {
                    const profitPerUnit = item.harga_satuan - (item.barang?.harga_beli || 0);
                    return sum + (profitPerUnit * item.jumlah);
                }, 0) || 0;

                return (
                    <div className="text-sm font-medium text-green-600">
                        {formatCurrency(profit)}
                    </div>
                );
            },
        }] : []),
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Penjualan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Laporan Penjualan</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Analisis dan detail transaksi penjualan toko beras.
                        </p>
                    </div>

                    {/* Date Filter */}
                    <div className="bg-white overflow-hidden shadow rounded-lg mb-6">
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

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-5 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.transactions className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Transaksi
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.total_transactions}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.money className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Penjualan
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {formatCurrency(summary.total_sales)}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.chart className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Rata-rata Transaksi
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {formatCurrency(summary.average_transaction)}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Only show profit card for admin/owner */}
                        {user_role?.is_admin_or_owner && summary.total_profit !== null && (
                            <div className="bg-white overflow-hidden shadow rounded-lg">
                                <div className="p-5">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <Icons.profit className="h-6 w-6 text-gray-400" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Total Profit
                                                </dt>
                                                <dd className="text-lg font-medium text-green-600">
                                                    {formatCurrency(summary.total_profit)}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.package className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Items Terjual
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.total_items_sold}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Transactions Table */}
                    <DataTable
                        data={penjualans}
                        columns={columns}
                        searchPlaceholder="Search by transaction number, customer, or cashier..."
                        routeName="laporan.penjualan"
                        currentSearch={filters?.search}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No sales data found',
                            description: 'No sales transactions found for the selected period.',
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
