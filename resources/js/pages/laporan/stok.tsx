import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Barang } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, StatusBadge, ProductImage, Icons } from '@/utils/formatters';
import { useState } from 'react';

interface LaporanStokProps extends PageProps {
    barangs: PaginatedData<Barang>;
    summary: {
        total_items: number;
        low_stock_items: number;
        out_of_stock_items: number;
        in_stock_items: number;
        total_stock_value_sell: number;
        total_stock_value_buy?: number;
        potential_profit?: number;
    };
    filters?: {
        search?: string;
        filter?: string;
        sort?: string;
        direction?: string;
    };
    user_role?: {
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
        title: 'Laporan Stok',
        href: '/owner/laporan/stok',
    },
];

export default function LaporanStok({ auth, barangs, summary, filters = {}, user_role }: LaporanStokProps) {
    const [dateFrom, setDateFrom] = useState('');
    const [dateTo, setDateTo] = useState('');

    const handleDateFilter = () => {
        router.get(route('owner.laporan.stok'), {
            ...filters,
            date_from: dateFrom,
            date_to: dateTo,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const getStockStatus = (stok: number, stokMinimum: number) => {
        if (stok <= 0) {
            return { label: 'Out of Stock', variant: 'danger' as const };
        } else if (stok <= stokMinimum) {
            return { label: 'Low Stock', variant: 'warning' as const };
        } else {
            return { label: 'In Stock', variant: 'success' as const };
        }
    };

    const columns: Column[] = [
        {
            key: 'gambar',
            label: 'Image',
            render: (value, row) => (
                <ProductImage 
                    src={value} 
                    alt={row.nama}
                    className="h-12 w-12 rounded-lg object-cover"
                />
            ),
        },
        {
            key: 'nama',
            label: 'Product',
            sortable: true,
            searchable: true,
            render: (value, row) => (
                <div>
                    <div className="text-sm font-medium text-gray-900">{value}</div>
                    <div className="text-xs text-gray-500">{row.kode_barang}</div>
                    <div className="text-xs text-gray-500">{row.kategori}</div>
                </div>
            ),
        },
        {
            key: 'stok',
            label: 'Stock',
            sortable: true,
            render: (value, row) => {
                const status = getStockStatus(value, row.stok_minimum);
                return (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {value} {row.satuan}
                        </div>
                        <div className="text-xs text-gray-500">
                            Min: {row.stok_minimum} {row.satuan}
                        </div>
                        <StatusBadge status={status.label} variant={status.variant} />
                    </div>
                );
            },
        },
        // Only show purchase price for admin/owner
        ...(user_role?.is_admin_or_owner ? [{
            key: 'harga_beli',
            label: 'Buy Price',
            sortable: true,
            render: (value) => (
                <div className="text-sm text-gray-900">
                    {formatCurrency(value)}
                </div>
            ),
        }] : []),
        {
            key: 'harga_jual',
            label: 'Sell Price',
            sortable: true,
            render: (value) => (
                <div className="text-sm text-gray-900">
                    {formatCurrency(value)}
                </div>
            ),
        },
        // Only show profit per unit for admin/owner
        ...(user_role?.is_admin_or_owner ? [{
            key: 'profit_per_unit',
            label: 'Profit/Unit',
            render: (value) => (
                <div className="text-sm font-medium text-green-600">
                    {formatCurrency(value)}
                </div>
            ),
        }] : []),
        // Only show stock value buy for admin/owner
        ...(user_role?.is_admin_or_owner ? [{
            key: 'nilai_stok_beli',
            label: 'Stock Value (Buy)',
            render: (value) => (
                <div className="text-sm text-gray-900">
                    {formatCurrency(value)}
                </div>
            ),
        }] : []),
        {
            key: 'nilai_stok_jual',
            label: 'Stock Value (Sell)',
            render: (value) => (
                <div className="text-sm font-medium text-blue-600">
                    {formatCurrency(value)}
                </div>
            ),
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'filter',
            label: 'All Items',
            options: [
                { value: 'all', label: 'All Items' },
                { value: 'in_stock', label: 'In Stock' },
                { value: 'low_stock', label: 'Low Stock' },
                { value: 'out_of_stock', label: 'Out of Stock' },
            ],
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Stok" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Laporan Stok</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Analisis dan monitoring stok barang toko beras.
                        </p>
                    </div>

                    {/* Summary Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <Icons.package className="h-6 w-6 text-gray-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Items
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.total_items}
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
                                        <Icons.warning className="h-6 w-6 text-yellow-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Low Stock
                                            </dt>
                                            <dd className="text-lg font-medium text-yellow-600">
                                                {summary.low_stock_items}
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
                                        <Icons.alert className="h-6 w-6 text-red-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Out of Stock
                                            </dt>
                                            <dd className="text-lg font-medium text-red-600">
                                                {summary.out_of_stock_items}
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
                                        <Icons.check className="h-6 w-6 text-green-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                In Stock
                                            </dt>
                                            <dd className="text-lg font-medium text-green-600">
                                                {summary.in_stock_items}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Value Summary Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                        {/* Only show stock value buy for admin/owner */}
                        {user_role?.is_admin_or_owner && summary.total_stock_value_buy !== undefined && (
                            <div className="bg-white overflow-hidden shadow rounded-lg">
                                <div className="p-5">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <Icons.money className="h-6 w-6 text-blue-400" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Stock Value (Buy)
                                                </dt>
                                                <dd className="text-lg font-medium text-blue-600">
                                                    {formatCurrency(summary.total_stock_value_buy)}
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
                                        <Icons.money className="h-6 w-6 text-purple-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Stock Value (Sell)
                                            </dt>
                                            <dd className="text-lg font-medium text-purple-600">
                                                {formatCurrency(summary.total_stock_value_sell)}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {/* Only show potential profit for admin/owner */}
                        {user_role?.is_admin_or_owner && summary.potential_profit !== undefined && (
                            <div className="bg-white overflow-hidden shadow rounded-lg">
                                <div className="p-5">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <Icons.profit className="h-6 w-6 text-green-400" />
                                        </div>
                                        <div className="ml-5 w-0 flex-1">
                                            <dl>
                                                <dt className="text-sm font-medium text-gray-500 truncate">
                                                    Potential Profit
                                                </dt>
                                                <dd className="text-lg font-medium text-green-600">
                                                    {formatCurrency(summary.potential_profit)}
                                                </dd>
                                            </dl>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
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

                    <div className="flex items-center gap-2 mb-4">
                        <a
                            href="/owner/download-report?type=stok"
                            target="_blank"
                            rel="noopener noreferrer"
                            className="inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <Icons.download className="w-4 h-4 mr-2" />
                            Download PDF
                        </a>
                    </div>

                    {/* Stock Table */}
                    <DataTable
                        data={barangs}
                        columns={columns}
                        searchPlaceholder="Search by product name, code, or category..."
                        filters={tableFilters}
                        routeName="laporan.stok"
                        currentSearch={filters?.search}
                        currentFilters={{ filter: filters?.filter }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No stock data found',
                            description: 'No products found matching your criteria.',
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
