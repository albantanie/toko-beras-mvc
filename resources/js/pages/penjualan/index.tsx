import AppLayout from '@/layouts/app-layout';
import { Head, router } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, formatDateTime, StatusBadge, ActionButtons, Icons } from '@/utils/formatters';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface PenjualanIndexProps extends PageProps {
    penjualans: PaginatedData<Penjualan>;
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
        title: 'Transaksi Penjualan',
        href: '/penjualan',
    },
];

export default function PenjualanIndex({ auth, penjualans, filters = {} }: PenjualanIndexProps) {
    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString('id-ID', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
        });
    };

    const handleDelete = (id: number, orderNumber: string) => {
        RiceStoreAlerts.transaction.confirmDelete(orderNumber).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('penjualan.destroy', id), {
                    onSuccess: () => {
                        RiceStoreAlerts.transaction.deleted(orderNumber);
                    },
                    onError: () => {
                        SweetAlert.error.delete(`transaction ${orderNumber}`);
                    }
                });
            }
        });
    };

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
                <ActionButtons
                    actions={[
                        {
                            label: 'View',
                            href: route('penjualan.show', row.id),
                            variant: 'secondary',
                            icon: Icons.view,
                        },
                        ...(row.status === 'pending' ? [{
                            label: 'Edit',
                            href: route('penjualan.edit', row.id),
                            variant: 'primary' as const,
                            icon: Icons.edit,
                        }] : []),
                        {
                            label: 'Delete',
                            onClick: () => handleDelete(row.id, row.nomor_transaksi),
                            variant: 'danger' as const,
                            icon: Icons.delete,
                        },
                    ]}
                />
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
                { value: 'kartu_debit', label: 'Debit Card' },
                { value: 'kartu_kredit', label: 'Credit Card' },
            ],
        },
    ];

    const actions = [
        {
            label: 'New Transaction',
            href: route('penjualan.create'),
            className: 'inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaksi Penjualan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Transaksi Penjualan</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Kelola semua transaksi penjualan toko beras.
                        </p>
                    </div>

                    <DataTable
                        data={penjualans}
                        columns={columns}
                        searchPlaceholder="Search by transaction number, customer, or cashier..."
                        filters={tableFilters}
                        actions={actions}
                        routeName="penjualan.index"
                        currentSearch={filters?.search}
                        currentFilters={{ 
                            status: filters?.status,
                            metode_pembayaran: filters?.metode_pembayaran 
                        }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No transactions found',
                            description: 'Get started by creating a new sales transaction.',
                            action: {
                                label: 'New Transaction',
                                href: route('penjualan.create'),
                            },
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
