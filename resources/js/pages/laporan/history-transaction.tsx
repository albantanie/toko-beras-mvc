import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, formatDateTime, StatusBadge, ActionButtons, Icons } from '@/utils/formatters';
import BarChart from '@/components/Charts/BarChart';
import LineChart from '@/components/Charts/LineChart';

interface HistoryTransactionReportProps extends PageProps {
    penjualans: PaginatedData<Penjualan>;
    summary: {
        total_transactions: number;
        total_sales: number;
        total_profit: number;
        average_transaction: number;
        total_items_sold: number;
        pending_transactions: number;
        completed_transactions: number;
        cancelled_transactions: number;
    };
    charts: {
        daily_sales: Array<{
            date: string;
            label: string;
            sales: number;
            transactions: number;
        }>;
        payment_methods: Array<{
            method: string;
            count: number;
            total: number;
        }>;
        status_distribution: Array<{
            status: string;
            count: number;
            percentage: number;
        }>;
    };
    filters?: {
        search?: string;
        status?: string;
        metode_pembayaran?: string;
        date_from?: string;
        date_to?: string;
        sort?: string;
        direction?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Laporan',
        href: '/laporan',
    },
    {
        title: 'Laporan Riwayat Transaksi',
        href: '/laporan/history-transaction',
    },
];

export default function HistoryTransactionReport({ 
    auth, 
    penjualans, 
    summary, 
    charts, 
    filters = {} 
}: HistoryTransactionReportProps) {
    const getStatusVariant = (status: string) => {
        switch (status) {
            case 'selesai':
                return 'success';
            case 'pending':
                return 'warning';
            case 'dibatalkan':
                return 'danger';
            default:
                return 'default';
        }
    };

    const getPaymentMethodBadge = (method: string) => {
        const colors = {
            tunai: 'bg-green-100 text-green-800',
            transfer: 'bg-blue-100 text-blue-800',
            kartu_debit: 'bg-purple-100 text-purple-800',
            kartu_kredit: 'bg-orange-100 text-orange-800',
        };

        return (
            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${colors[method] || 'bg-gray-100 text-gray-800'}`}>
                {method.replace('_', ' ').toUpperCase()}
            </span>
        );
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
            key: 'total',
            label: 'Total & Items',
            sortable: true,
            render: (value, row) => {
                const totalItems = row.detail_penjualans?.reduce((sum, item) => sum + item.jumlah, 0) || 0;
                const itemTypes = row.detail_penjualans?.length || 0;

                return (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {formatCurrency(value)}
                        </div>
                        <div className="text-xs text-gray-500">
                            {totalItems} karung • {itemTypes} jenis
                        </div>
                    </div>
                );
            },
        },
        {
            key: 'metode_pembayaran',
            label: 'Pembayaran',
            render: (value) => getPaymentMethodBadge(value),
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            render: (value) => (
                <StatusBadge 
                    status={value.charAt(0).toUpperCase() + value.slice(1)} 
                    variant={getStatusVariant(value)} 
                />
            ),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (_, row) => (
                <ActionButtons actions={[
                    {
                        label: 'View',
                        href: route('penjualan.show', row.id),
                        variant: 'secondary' as const,
                        icon: Icons.view,
                    },
                ]} />
            ),
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'status',
            label: 'All Status',
            options: [
                { value: 'all', label: 'All Status' },
                { value: 'pending', label: 'Pending' },
                { value: 'selesai', label: 'Completed' },
                { value: 'dibatalkan', label: 'Cancelled' },
            ],
        },
        {
            key: 'metode_pembayaran',
            label: 'Payment Method',
            options: [
                { value: '', label: 'All Methods' },
                { value: 'tunai', label: 'Cash' },
                { value: 'transfer', label: 'Transfer' },
            ],
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Riwayat Transaksi" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Laporan Riwayat Transaksi</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Laporan komprehensif riwayat semua transaksi dengan analisis detail
                        </p>
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
                                        <Icons.money className="h-6 w-6 text-green-400" />
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
                                        <Icons.trendingUp className="h-6 w-6 text-blue-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Profit
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {formatCurrency(summary.total_profit)}
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
                                        <Icons.package className="h-6 w-6 text-purple-400" />
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Total Items
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

                    {/* Status Distribution Cards */}
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
                        <div className="bg-white overflow-hidden shadow rounded-lg">
                            <div className="p-5">
                                <div className="flex items-center">
                                    <div className="flex-shrink-0">
                                        <div className="h-6 w-6 bg-yellow-400 rounded-full"></div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Pending
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.pending_transactions}
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
                                        <div className="h-6 w-6 bg-green-400 rounded-full"></div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Completed
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.completed_transactions}
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
                                        <div className="h-6 w-6 bg-red-400 rounded-full"></div>
                                    </div>
                                    <div className="ml-5 w-0 flex-1">
                                        <dl>
                                            <dt className="text-sm font-medium text-gray-500 truncate">
                                                Cancelled
                                            </dt>
                                            <dd className="text-lg font-medium text-gray-900">
                                                {summary.cancelled_transactions}
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Charts */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                        <div className="bg-white p-6 rounded-lg shadow">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">Daily Sales Trend</h4>
                            <LineChart
                                data={{
                                    labels: charts.daily_sales.map(item => item.label),
                                    datasets: [
                                        {
                                            label: 'Sales',
                                            data: charts.daily_sales.map(item => item.sales),
                                            borderColor: 'rgb(59, 130, 246)',
                                            backgroundColor: 'rgba(59, 130, 246, 0.1)',
                                            fill: true,
                                        },
                                        {
                                            label: 'Transactions',
                                            data: charts.daily_sales.map(item => item.transactions),
                                            borderColor: 'rgb(16, 185, 129)',
                                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                                            fill: false,
                                        }
                                    ]
                                }}
                                height={300}
                            />
                        </div>

                        <div className="bg-white p-6 rounded-lg shadow">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">Payment Methods Distribution</h4>
                            <BarChart
                                data={{
                                    labels: charts.payment_methods.map(item => item.method.replace('_', ' ').toUpperCase()),
                                    datasets: [
                                        {
                                            label: 'Total Sales',
                                            data: charts.payment_methods.map(item => item.total),
                                            backgroundColor: [
                                                'rgba(59, 130, 246, 0.8)',
                                                'rgba(16, 185, 129, 0.8)',
                                                'rgba(168, 85, 247, 0.8)',
                                                'rgba(245, 158, 11, 0.8)',
                                            ],
                                            borderColor: [
                                                'rgb(59, 130, 246)',
                                                'rgb(16, 185, 129)',
                                                'rgb(168, 85, 247)',
                                                'rgb(245, 158, 11)',
                                            ],
                                            borderWidth: 1,
                                        }
                                    ]
                                }}
                                height={300}
                            />
                        </div>
                    </div>

                    {/* Transaction Table */}
                    <div className="bg-white shadow rounded-lg">
                        <div className="px-4 py-5 sm:p-6">
                            <h4 className="text-lg font-medium text-gray-900 mb-4">Detail Transaksi</h4>
                            <DataTable
                                data={penjualans}
                                columns={columns}
                                searchPlaceholder="Search by transaction number, customer, or cashier..."
                                filters={tableFilters}
                                routeName="laporan.history-transaction"
                                currentSearch={filters?.search}
                                currentFilters={{ 
                                    status: filters?.status,
                                    metode_pembayaran: filters?.metode_pembayaran 
                                }}
                                currentSort={filters?.sort}
                                currentDirection={filters?.direction}
                                emptyState={{
                                    title: 'No transactions found',
                                    description: 'No transaction history found for the selected criteria.',
                                }}
                            />
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
} 