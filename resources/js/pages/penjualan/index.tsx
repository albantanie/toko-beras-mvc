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
    uiPermissions?: {
        canCreateTransactions: boolean;
        canEditTransactions: boolean;
        canDeleteTransactions: boolean;
        canViewTransactionHistory: boolean;
        canProcessPayments: boolean;
        isOwner: boolean;
        isKasir: boolean;
        isAdmin: boolean;
    };
    userRole?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Penjualan',
        href: '/penjualan',
    },
];

export default function PenjualanIndex({ auth, penjualans, filters = {}, uiPermissions, userRole }: PenjualanIndexProps) {
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
            case 'pending':
                return 'warning';
            case 'dibayar':
                return 'info';
            case 'siap_pickup':
                return 'success';
            case 'selesai':
                return 'success';
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

        let label = '';
        switch (method) {
            case 'tunai':
                label = 'Tunai'; break;
            case 'transfer':
                label = 'Transfer'; break;
            case 'kartu_debit':
                label = 'Kartu Debit'; break;
            case 'kartu_kredit':
                label = 'Kartu Kredit'; break;
            default:
                label = 'Metode Lain'; // fallback label in Bahasa Indonesia
        }

        return (
            <span className={`inline-flex px-2 py-1 text-xs font-semibold rounded-full ${colors[method] || 'bg-gray-100 text-gray-800'}`}>
                {label}
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
                        {value || row.pelanggan?.name || 'Pelanggan Langsung'}
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
                    {row.user?.name || 'Tidak diketahui'}
                </div>
            ),
        },
        {
            key: 'total',
            label: 'Total & Jumlah Jenis',
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
            label: 'Metode Pembayaran',
            render: (value) => getPaymentMethodBadge(value),
        },
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            render: (value) => (
                <StatusBadge
                    status={
                        value === 'pending' ? 'Menunggu Pembayaran' :
                        value === 'dibayar' ? 'Sudah Dibayar' :
                        value === 'siap_pickup' ? 'Siap Pickup' :
                        value === 'selesai' ? 'Selesai' :
                        value === 'dibatalkan' ? 'Dibatalkan' :
                        'Status Tidak Diketahui'
                    }
                    variant={getStatusVariant(value)}
                />
            ),
        },
        {
            key: 'actions',
            label: 'Aksi',
            render: (_, row) => {
                const rowActions: any[] = [
                    {
                        label: 'Lihat Detail',
                        href: route('penjualan.show', row.id),
                        variant: 'secondary' as const,
                        icon: <Icons.view />,
                    }
                ];

                // Only KASIR and ADMIN can edit transactions
                if (uiPermissions?.canEditTransactions && row.status === 'pending') {
                    rowActions.push({
                        label: 'Ubah Transaksi',
                        href: route('penjualan.edit', row.id),
                        variant: 'primary' as const,
                        icon: <Icons.edit />,
                    });
                }

                // Only KASIR and ADMIN can delete transactions
                if (uiPermissions?.canDeleteTransactions) {
                    rowActions.push({
                        label: 'Hapus',
                        onClick: () => handleDelete(row.id, row.nomor_transaksi),
                        variant: 'danger' as const,
                        icon: <Icons.delete />,
                    });
                }

                return <ActionButtons actions={rowActions} />;
            },
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'status',
            label: 'Status',
            options: [
                { value: 'all', label: 'Semua Status' },
                { value: 'pending', label: 'Menunggu Pembayaran' },
                { value: 'selesai', label: 'Selesai' },
                { value: 'dibatalkan', label: 'Dibatalkan' },
            ],
        },
        {
            key: 'metode_pembayaran',
            label: 'Metode Pembayaran',
            options: [
                { value: '', label: 'Semua Metode Pembayaran' },
                { value: 'tunai', label: 'Tunai' },
                { value: 'transfer', label: 'Transfer' },
            ],
        },
    ];

    // Role-based actions - only show actions based on user permissions
    const actions = [];

    // Only KASIR and ADMIN can create new transactions
    if (uiPermissions?.canCreateTransactions) {
        actions.push({
            label: 'Transaksi Penjualan Baru',
            href: route('penjualan.create'),
            className: 'inline-flex items-center px-4 py-2 bg-green-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-green-700 focus:bg-green-700 active:bg-green-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.add,
        });
    }

    // All roles can view transaction history
    if (uiPermissions?.canViewTransactionHistory) {
        actions.push({
            label: 'Riwayat Transaksi',
            href: route('penjualan.history'),
            className: 'inline-flex items-center px-4 py-2 bg-blue-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 focus:bg-blue-700 active:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150',
            icon: Icons.history,
        });
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Penjualan" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Penjualan</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Kelola seluruh transaksi penjualan beras di toko Anda.
                        </p>
                    </div>

                    <DataTable
                        data={penjualans}
                        columns={columns}
                        searchPlaceholder="Cari transaksi berdasarkan nomor transaksi, nama pelanggan, atau nama kasir..."
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
                            title: 'Belum ada transaksi penjualan',
                            description: 'Belum ada data transaksi penjualan yang tercatat. Silakan buat transaksi penjualan baru untuk mulai mencatat penjualan.',
                            action: {
                                label: 'Transaksi Penjualan Baru',
                                href: route('penjualan.create'),
                            },
                        }}
                    />
                </div>
            </div>
        </AppLayout>
    );
}
