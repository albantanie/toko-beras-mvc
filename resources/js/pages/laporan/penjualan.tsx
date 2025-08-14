import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import DataTable, { Column, PaginatedData } from '@/components/data-table';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import { useState } from 'react';

// Use global route function
declare global {
    function route(name: string, params?: any): string;
}

interface LaporanPenjualanProps extends PageProps {
    penjualans: PaginatedData<Penjualan>;
    summary: {
        total_transactions: number;
        total_sales: number;
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
        href: '/owner/laporan',
    },
    {
        title: 'Laporan Penjualan',
        href: '/owner/laporan/penjualan',
    },
];

export default function LaporanPenjualan({ auth, penjualans, summary, sales_chart, filters = {}, user_role }: LaporanPenjualanProps) {
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

        // Gunakan Inertia router untuk mempertahankan state
        router.get(route('owner.laporan.penjualan'), {
            date_from: dateFrom,
            date_to: dateTo,
            // Preserve existing filters
            search: filters?.search || '',
            sort: filters?.sort || 'tanggal_transaksi',
            direction: filters?.direction || 'desc',
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const handleResetFilter = () => {
        setDateFrom('');
        setDateTo('');

        router.get(route('owner.laporan.penjualan'), {}, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    const columns: Column[] = [
        {
            key: 'nomor_transaksi',
            label: 'No. Transaksi',
            sortable: true,
            searchable: true,
            className: 'w-40 min-w-[160px]',
            render: (value, row) => (
                <div className="truncate">
                    <div className="text-sm font-medium text-gray-900 truncate">{value}</div>
                    <div className="text-xs text-gray-500 truncate">
                        {formatDateTime(row.tanggal_transaksi)}
                    </div>
                </div>
            ),
        },
        {
            key: 'nama_pelanggan',
            label: 'Pelanggan',
            sortable: true,
            className: 'w-48 min-w-[192px]',
            render: (value, row) => (
                <div className="truncate">
                    <div className="text-sm font-medium text-gray-900 truncate">
                        {value || row.pelanggan?.name || 'Walk-in Customer'}
                    </div>
                    {row.telepon_pelanggan && (
                        <div className="text-xs text-gray-500 truncate">{row.telepon_pelanggan}</div>
                    )}
                </div>
            ),
        },
        {
            key: 'user',
            label: 'Kasir',
            className: 'w-32 min-w-[128px]',
            render: (_, row) => (
                <div className="text-sm text-gray-900 truncate">
                    {row.user?.name || 'Unknown'}
                </div>
            ),
        },
        {
            key: 'detail_penjualans',
            label: 'Items',
            className: 'w-20 min-w-[80px] text-center',
            render: (value, row) => (
                <div className="text-center">
                    <div className="text-sm font-medium text-gray-900">
                        {value?.length || 0}
                    </div>
                    <div className="text-xs text-gray-500">
                        {value?.reduce((sum: number, item: any) => sum + item.jumlah, 0) || 0} qty
                    </div>
                </div>
            ),
        },
        {
            key: 'pickup_method',
            label: 'Pickup',
            className: 'w-32 min-w-[128px]',
            render: (value, row) => {
                const getPickupLabel = (method: string) => {
                    switch (method) {
                        case 'self': return 'Ambil Sendiri';
                        case 'grab': return 'Grab';
                        case 'gojek': return 'Gojek';
                        case 'other': return 'Lainnya';
                        default: return 'Ambil Sendiri';
                    }
                };

                const getPickupColor = (method: string) => {
                    switch (method) {
                        case 'self': return 'bg-blue-100 text-blue-800';
                        case 'grab': return 'bg-green-100 text-green-800';
                        case 'gojek': return 'bg-purple-100 text-purple-800';
                        case 'other': return 'bg-gray-100 text-gray-800';
                        default: return 'bg-blue-100 text-blue-800';
                    }
                };

                return (
                    <div>
                        <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPickupColor(value || 'self')}`}>
                            {getPickupLabel(value || 'self')}
                        </span>
                        {row.pickup_person_name && (
                            <div className="text-xs text-gray-500 mt-1">
                                {row.pickup_person_name}
                            </div>
                        )}
                    </div>
                );
            },
        },
        {
            key: 'total',
            label: 'Total',
            sortable: true,
            className: 'w-36 min-w-[144px] text-right',
            render: (value, row) => (
                <div className="text-right">
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
            className: 'w-32 min-w-[128px] text-right',
            render: (_, row) => {
                const profit = row.detail_penjualans?.reduce((sum: number, item: any) => {
                    const profitPerUnit = item.harga_satuan - (item.barang?.harga_beli || 0);
                    return sum + (profitPerUnit * item.jumlah);
                }, 0) || 0;

                return (
                    <div className="text-sm font-medium text-green-600 text-right">
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
                                <button
                                    onClick={handleResetFilter}
                                    className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >
                                    Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* Explanation Panel */}
                    <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                        <div className="text-sm text-blue-800">
                            <div className="font-semibold mb-2">📊 Penjelasan Data Laporan Penjualan:</div>
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs">
                                <ul className="space-y-1">
                                    <li>• <strong>Total Transaksi:</strong> Jumlah transaksi yang selesai (status: selesai)</li>
                                    <li>• <strong>Total Penjualan:</strong> Jumlah rupiah dari semua transaksi selesai</li>
                                    <li>• <strong>Item Terjual:</strong> Total karung beras yang terjual</li>
                                </ul>
                                <ul className="space-y-1">
                                    <li>• <strong>Total Profit:</strong> Keuntungan = Harga Jual - Harga Beli</li>
                                    <li>• <strong>Data Balance:</strong> Harus sama dengan laporan kasir</li>
                                    <li>• <strong>Filter Periode:</strong> Berdasarkan tanggal transaksi</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
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
                                            <dd className="text-lg font-medium text-gray-900" title={formatCurrency(summary.total_sales)}>
                                                {summary.total_sales >= 1000000
                                                    ? formatCurrency(summary.total_sales)
                                                    : summary.total_sales >= 1000
                                                    ? formatCurrency(summary.total_sales)
                                                    : formatCurrency(summary.total_sales)
                                                }
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
                                        <Icons.package className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Item Terjual
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.total_items_sold} karung
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
                                                <dd className="text-lg font-medium text-green-600" title={formatCurrency(summary.total_profit)}>
                                                    {summary.total_profit >= 1000000
                                                                                                            ? formatCurrency(summary.total_profit)
                                                    : summary.total_profit >= 1000
                                                    ? formatCurrency(summary.total_profit)
                                                    : formatCurrency(summary.total_profit)
                                                    }
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}


                    </div>

                    <div className="flex items-center gap-2 mb-4">
                        <a
                            href={`/owner/download-report?type=penjualan${dateFrom ? `&date_from=${dateFrom}` : ''}${dateTo ? `&date_to=${dateTo}` : ''}`}
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <Icons.download className="w-4 h-4 mr-2" />
                            Download PDF
                        </a>
                    </div>

                    {/* Transactions Table */}
                    <div className="overflow-x-auto">
                        <style jsx>{`
                            .sales-table {
                                min-width: 1200px;
                            }
                            .sales-table th,
                            .sales-table td {
                                white-space: nowrap;
                                overflow: hidden;
                                text-overflow: ellipsis;
                            }
                        `}</style>
                        <DataTable
                            data={penjualans}
                            columns={columns}
                            searchPlaceholder="Cari berdasarkan nomor transaksi, pelanggan, atau kasir..."
                            routeName="owner.laporan.penjualan"
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
            </div>
        </AppLayout>
    );
}
