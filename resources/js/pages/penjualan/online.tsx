import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import { useState } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';

interface PenjualanOnlineProps extends PageProps {
    penjualans: PaginatedData<Penjualan & {
        user: {
            id: number;
            name: string;
            email: string;
        };
        pelanggan?: {
            id: number;
            name: string;
            email: string;
        };
        detail_penjualans: Array<{
            id: number;
            barang_id: number;
            jumlah: number;
            harga_satuan: number;
            subtotal: number;
            catatan?: string;
            barang: {
                id: number;
                nama: string;
                kode_barang: string;
                kategori: string;
                harga_beli: number;
                harga_jual: number;
                satuan: string;
                gambar?: string;
            };
        }>;
    }>;
    filters?: {
        search?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
        sort?: string;
        direction?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Online',
        href: '/penjualan-online',
    },
];

export default function PenjualanOnline({ auth, penjualans, filters = {} }: PenjualanOnlineProps) {
    const [selectedTransaction, setSelectedTransaction] = useState<any>(null);
    const [showModal, setShowModal] = useState(false);
    const [actionType, setActionType] = useState<'confirm' | 'ready' | 'complete'>('confirm');

    const { data, setData, patch, processing, reset } = useForm({
        catatan_kasir: '',
    });

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

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'pending':
                return 'Menunggu Pembayaran';
            case 'dibayar':
                return 'Sudah Dibayar';
            case 'siap_pickup':
                return 'Siap Pickup';
            case 'selesai':
                return 'Selesai';
            case 'dibatalkan':
                return 'Dibatalkan';
            default:
                return status;
        }
    };

    const getNextAction = (status: string) => {
        switch (status) {
            case 'pending':
                return { action: 'confirm', label: 'Konfirmasi Pembayaran', color: 'bg-blue-600 hover:bg-blue-700' };
            case 'dibayar':
                return { action: 'ready', label: 'Siap Pickup', color: 'bg-green-600 hover:bg-green-700' };
            case 'siap_pickup':
                return { action: 'complete', label: 'Selesai', color: 'bg-purple-600 hover:bg-purple-700' };
            default:
                return null;
        }
    };

    const handleAction = (transaction: any, action: 'confirm' | 'ready' | 'complete') => {
        setSelectedTransaction(transaction);
        setActionType(action);
        setShowModal(true);
        reset();
    };

    const submitAction = (e: React.FormEvent) => {
        e.preventDefault();
        
        if (!selectedTransaction) return;

        const routes = {
            confirm: 'penjualan.confirm-payment',
            ready: 'penjualan.ready-pickup',
            complete: 'penjualan.complete',
        };

        patch(route(routes[actionType], selectedTransaction.id), {
            onSuccess: () => {
                setShowModal(false);
                setSelectedTransaction(null);
                reset();

                // Show appropriate success message
                switch (actionType) {
                    case 'confirm':
                        RiceStoreAlerts.info.paymentConfirmed(selectedTransaction.nomor_transaksi, selectedTransaction.total);
                        break;
                    case 'ready':
                        RiceStoreAlerts.info.orderReady(selectedTransaction.nomor_transaksi, 'Customer pickup');
                        break;
                    case 'complete':
                        RiceStoreAlerts.order.completed(selectedTransaction.nomor_transaksi);
                        break;
                }
            },
            onError: (errors) => {
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                } else {
                    SweetAlert.error.custom('Operation Failed', `Failed to ${actionType} the order. Please try again.`);
                }
            },
        });
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
                        {value || row.pelanggan?.name || 'Unknown'}
                    </div>
                    {row.telepon_pelanggan && (
                        <div className="text-xs text-gray-500">{row.telepon_pelanggan}</div>
                    )}
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
            key: 'pickup_method',
            label: 'Pickup',
            render: (value, row) => {
                const getPickupLabel = (method: string) => {
                    switch (method) {
                        case 'self': return 'Ambil Sendiri';
                        case 'grab': return 'Grab Driver';
                        case 'gojek': return 'Gojek Driver';
                        case 'other': return 'Orang Lain';
                        default: return method;
                    }
                };

                return (
                    <div>
                        <div className="text-sm font-medium text-gray-900">
                            {getPickupLabel(value)}
                        </div>
                        {value !== 'self' && row.pickup_person_name && (
                            <div className="text-xs text-gray-500">
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
        {
            key: 'status',
            label: 'Status',
            sortable: true,
            render: (value, row) => (
                <StatusBadge 
                    status={getStatusLabel(value)} 
                    variant={getStatusVariant(value)} 
                />
            ),
        },
        {
            key: 'actions',
            label: 'Aksi',
            render: (_, row) => {
                const nextAction = getNextAction(row.status);
                
                return (
                    <div className="flex space-x-2">
                        <Link
                            href={route('penjualan.show', row.id)}
                            className="inline-flex items-center px-2 py-1 border border-gray-300 shadow-sm text-xs font-medium rounded text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                        >
                            <Icons.view className="w-3 h-3 mr-1" />
                            Detail
                        </Link>
                        
                        {nextAction && (
                            <button
                                onClick={() => handleAction(row, nextAction.action as any)}
                                className={`inline-flex items-center px-2 py-1 border border-transparent text-xs font-medium rounded text-white ${nextAction.color} focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500`}
                            >
                                <Icons.check className="w-3 h-3 mr-1" />
                                {nextAction.label}
                            </button>
                        )}

                        {row.status === 'siap_pickup' && row.pickup_method !== 'self' && (
                            <Link
                                href={route('penjualan.receipt', row.id)}
                                target="_blank"
                                className="inline-flex items-center px-2 py-1 border border-orange-300 shadow-sm text-xs font-medium rounded text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                            >
                                <Icons.view className="w-3 h-3 mr-1" />
                                Receipt
                            </Link>
                        )}

                        {row.status === 'siap_pickup' && row.pickup_method !== 'self' && (
                            <Link
                                href={route('penjualan.receipt', row.id)}
                                target="_blank"
                                className="inline-flex items-center px-2 py-1 border border-orange-300 shadow-sm text-xs font-medium rounded text-orange-700 bg-orange-50 hover:bg-orange-100 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500"
                            >
                                <Icons.view className="w-3 h-3 mr-1" />
                                Receipt
                            </Link>
                        )}
                    </div>
                );
            },
        },
    ];

    const tableFilters: Filter[] = [
        {
            key: 'status',
            label: 'Semua Status',
            options: [
                { value: 'all', label: 'Semua Status' },
                { value: 'pending', label: 'Menunggu Pembayaran' },
                { value: 'dibayar', label: 'Sudah Dibayar' },
                { value: 'siap_pickup', label: 'Siap Pickup' },
                { value: 'selesai', label: 'Selesai' },
                { value: 'dibatalkan', label: 'Dibatalkan' },
            ],
        },
    ];

    const getActionTitle = () => {
        switch (actionType) {
            case 'confirm':
                return 'Konfirmasi Pembayaran';
            case 'ready':
                return 'Siap untuk Pickup';
            case 'complete':
                return 'Selesaikan Transaksi';
            default:
                return 'Update Status';
        }
    };

    const getActionDescription = () => {
        switch (actionType) {
            case 'confirm':
                return 'Konfirmasi bahwa pembayaran telah diterima dan diverifikasi.';
            case 'ready':
                return 'Tandai pesanan siap untuk diambil pelanggan.';
            case 'complete':
                return 'Tandai transaksi selesai karena pesanan telah diambil pelanggan.';
            default:
                return '';
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Transaksi Online" />

            <div className="py-12">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div className="mb-6">
                        <h3 className="text-lg font-medium text-gray-900">Transaksi Online</h3>
                        <p className="mt-1 text-sm text-gray-600">
                            Kelola transaksi online dari pelanggan yang sudah login.
                        </p>
                    </div>

                    <DataTable
                        data={penjualans}
                        columns={columns}
                        searchPlaceholder="Search by transaction number or customer..."
                        filters={tableFilters}
                        routeName="penjualan.online"
                        currentSearch={filters?.search}
                        currentFilters={{ status: filters?.status }}
                        currentSort={filters?.sort}
                        currentDirection={filters?.direction}
                        emptyState={{
                            title: 'No online transactions found',
                            description: 'No online transactions found matching your criteria.',
                        }}
                    />
                </div>
            </div>

            {/* Action Modal */}
            {showModal && selectedTransaction && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    {getActionTitle()}
                                </h3>
                                <button
                                    onClick={() => setShowModal(false)}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <Icons.delete className="w-5 h-5" />
                                </button>
                            </div>
                            
                            <div className="mb-4">
                                <p className="text-sm text-gray-600 mb-2">
                                    {getActionDescription()}
                                </p>
                                <div className="bg-gray-50 p-3 rounded">
                                    <p className="text-sm font-medium">
                                        {selectedTransaction.nomor_transaksi}
                                    </p>
                                    <p className="text-sm text-gray-600">
                                        {selectedTransaction.nama_pelanggan || selectedTransaction.pelanggan?.name}
                                    </p>
                                    <p className="text-sm font-semibold text-green-600">
                                        {formatCurrency(selectedTransaction.total)}
                                    </p>
                                </div>
                            </div>

                            <form onSubmit={submitAction}>
                                <div className="mb-4">
                                    <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Catatan Kasir (Opsional)
                                    </label>
                                    <textarea
                                        value={data.catatan_kasir || ''}
                                        onChange={(e) => setData('catatan_kasir', e.target.value)}
                                        rows={3}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                        placeholder="Tambahkan catatan jika diperlukan..."
                                    />
                                </div>

                                <div className="flex justify-end space-x-3">
                                    <button
                                        type="button"
                                        onClick={() => setShowModal(false)}
                                        className="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                    >
                                        Batal
                                    </button>
                                    <button
                                        type="submit"
                                        disabled={processing}
                                        className="px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 disabled:opacity-50"
                                    >
                                        {processing ? 'Memproses...' : 'Konfirmasi'}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
