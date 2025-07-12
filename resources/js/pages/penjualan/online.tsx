import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, BreadcrumbItem, Penjualan } from '@/types';
import DataTable, { Column, Filter, PaginatedData } from '@/components/data-table';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import { useState, useEffect } from 'react';
import { RiceStoreAlerts, SweetAlert } from '@/utils/sweetalert';
import { Button } from '@/components/ui/button';
import { Eye, CheckCircle, XCircle, Clock, X } from 'lucide-react';

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
    success?: string;
    error?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Transaksi Online',
        href: '/penjualan-online',
    },
];

export default function PenjualanOnline({ auth, penjualans, filters = {}, success, error }: PenjualanOnlineProps) {
    const [selectedTransaction, setSelectedTransaction] = useState<any>(null);
    const [showModal, setShowModal] = useState(false);
    const [showProofModal, setShowProofModal] = useState(false);
    const [actionType, setActionType] = useState<'confirm' | 'ready' | 'complete' | 'reject'>('confirm');

    const { data, setData, patch, processing, reset } = useForm({
        catatan_kasir: '',
        rejection_reason: '',
    });

    useEffect(() => {
        if (success) {
            SweetAlert.success.custom('Berhasil', success).then(() => {
                window.location.href = route('penjualan.online');
            });
        } else if (error) {
            SweetAlert.error.custom('Gagal', error);
        }
    }, [success, error]);

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

    const handleReject = (transaction: any) => {
        setSelectedTransaction(transaction);
        setActionType('reject');
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
            reject: 'penjualan.reject-payment',
        };

        // Prepare data based on action type
        const formData = actionType === 'reject' 
            ? { rejection_reason: data.rejection_reason }
            : { catatan_kasir: data.catatan_kasir };

        // All actions will redirect now, so handle them the same way
        patch(route(routes[actionType], selectedTransaction.id), formData, {
            onSuccess: () => {
                // Show SweetAlert2 after redirect
                setTimeout(() => {
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
                        case 'reject':
                            RiceStoreAlerts.order.paymentRejected(selectedTransaction.nomor_transaksi);
                            break;
                    }
                }, 100);
            },
            onError: (errors) => {
                console.error('Form submission errors:', errors);
                if (Object.keys(errors).length > 0) {
                    SweetAlert.error.validation(errors);
                } else {
                    SweetAlert.error.custom('Operation Failed', `Failed to ${actionType} the order. Please try again.`);
                }
            },
        });
    };

    const handleViewProof = () => {
        setShowProofModal(true);
    };

    const closeProofModal = () => {
        setShowProofModal(false);
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
                return 'Siap Pickup';
            case 'complete':
                return 'Selesai';
            case 'reject':
                return 'Tolak Bukti Pembayaran';
            default:
                return 'Action';
        }
    };

    const getActionDescription = () => {
        switch (actionType) {
            case 'confirm':
                return 'Konfirmasi bahwa pembayaran telah diterima dan valid.';
            case 'ready':
                return 'Tandai pesanan siap untuk diambil pelanggan.';
            case 'complete':
                return 'Tandai transaksi selesai karena pesanan telah diambil pelanggan.';
            case 'reject':
                return 'Tolak bukti pembayaran dan minta pelanggan upload ulang.';
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
                        searchPlaceholder="Cari berdasarkan nomor transaksi atau pelanggan..."
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
                                {actionType === 'reject' ? (
                                    <div className="mb-4">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Alasan Penolakan *
                                        </label>
                                        <textarea
                                            value={data.rejection_reason || ''}
                                            onChange={(e) => setData('rejection_reason', e.target.value)}
                                            rows={3}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-red-500 focus:border-red-500 sm:text-sm"
                                            placeholder="Jelaskan alasan penolakan bukti pembayaran..."
                                            required
                                        />
                                    </div>
                                ) : (
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
                                )}

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
                                        disabled={processing || (actionType === 'confirm' && selectedTransaction?.status === 'pending' && !selectedTransaction?.payment_proof)}
                                        className={`px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 ${
                                            actionType === 'reject' 
                                                ? 'bg-red-600 hover:bg-red-700 focus:ring-red-500' 
                                                : 'bg-indigo-600 hover:bg-indigo-700 focus:ring-indigo-500'
                                        }`}
                                    >
                                        {processing ? 'Memproses...' : 
                                         actionType === 'reject' ? 'Tolak Bukti' : 
                                         (actionType === 'confirm' && selectedTransaction?.status === 'pending' && !selectedTransaction?.payment_proof) 
                                            ? 'Menunggu Bukti Pembayaran' 
                                            : 'Konfirmasi'
                                        }
                                    </button>
                                </div>
                            </form>

                            {/* Payment Proof Section */}
                            {selectedTransaction.metode_pembayaran === 'transfer' && selectedTransaction.status === 'pending' && (
                                <div className="mt-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                    <h4 className="font-medium text-yellow-900 mb-2">Bukti Pembayaran</h4>
                                    {selectedTransaction.payment_proof ? (
                                        <div className="space-y-3">
                                            <div className="flex items-center gap-3">
                                                <div className="flex-1">
                                                    <p className="text-sm text-gray-600">
                                                        Bukti transfer telah diupload oleh pelanggan
                                                    </p>
                                                    <p className="text-xs text-gray-500">
                                                        {selectedTransaction.payment_proof}
                                                    </p>
                                                </div>
                                            </div>
                                            
                                            {/* Preview Bukti Pembayaran */}
                                            <div className="mt-3">
                                                <img
                                                    src={`/storage/${selectedTransaction.payment_proof}`}
                                                    alt="Bukti Pembayaran"
                                                    className="w-full max-w-xs h-auto rounded-lg border border-gray-300"
                                                    onError={(e) => {
                                                        e.currentTarget.style.display = 'none';
                                                    }}
                                                />
                                            </div>
                                            
                                            <div className="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleAction(selectedTransaction, 'confirm')}
                                                    className="bg-green-600 hover:bg-green-700"
                                                    disabled={selectedTransaction.status === 'pending' && !selectedTransaction.payment_proof}
                                                >
                                                    <CheckCircle className="w-4 h-4 mr-1" />
                                                    {selectedTransaction.status === 'pending' && !selectedTransaction.payment_proof 
                                                        ? 'Menunggu Bukti Pembayaran' 
                                                        : 'Konfirmasi Pembayaran'
                                                    }
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleReject(selectedTransaction)}
                                                    className="text-red-600 border-red-600 hover:bg-red-50"
                                                    disabled={selectedTransaction.status === 'pending' && !selectedTransaction.payment_proof}
                                                >
                                                    <XCircle className="w-4 h-4 mr-1" />
                                                    Tolak Bukti
                                                </Button>
                                            </div>
                                        </div>
                                    ) : (
                                        <div className="text-center py-4">
                                            <div className="text-yellow-600 mb-2">
                                                <Clock className="w-8 h-8 mx-auto" />
                                            </div>
                                            <p className="text-sm text-yellow-800 font-medium">
                                                Menunggu upload bukti pembayaran dari pelanggan
                                            </p>
                                            <p className="text-xs text-yellow-700 mt-1">
                                                Pelanggan akan diingatkan untuk upload bukti transfer
                                            </p>
                                            <div className="mt-3 p-2 bg-yellow-100 rounded border border-yellow-300">
                                                <p className="text-xs text-yellow-800">
                                                    <strong>Info:</strong> Tombol konfirmasi akan aktif setelah pelanggan upload bukti pembayaran
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Payment Status */}
                            {selectedTransaction.metode_pembayaran === 'transfer' && selectedTransaction.status !== 'pending' && (
                                <div className={`mt-4 p-3 rounded-lg ${
                                    selectedTransaction.status === 'dibayar' 
                                        ? 'bg-green-50 border border-green-200' 
                                        : 'bg-gray-50 border border-gray-200'
                                }`}>
                                    <div className="flex items-center gap-2">
                                        {selectedTransaction.status === 'dibayar' ? (
                                            <CheckCircle className="w-4 h-4 text-green-600" />
                                        ) : (
                                            <Clock className="w-4 h-4 text-gray-600" />
                                        )}
                                        <span className={`text-sm font-medium ${
                                            selectedTransaction.status === 'dibayar' ? 'text-green-800' : 'text-gray-800'
                                        }`}>
                                            {selectedTransaction.status === 'dibayar' ? 'Pembayaran Dikonfirmasi' : 'Menunggu Pembayaran'}
                                        </span>
                                    </div>
                                    {selectedTransaction.payment_proof && (
                                        <div className="mt-2">
                                            <p className="text-xs text-gray-600 mb-1">
                                                Bukti: {selectedTransaction.payment_proof}
                                            </p>
                                            <img
                                                src={`/storage/${selectedTransaction.payment_proof}`}
                                                alt="Bukti Pembayaran"
                                                className="w-24 h-24 object-cover rounded border border-gray-300"
                                                onError={(e) => {
                                                    e.currentTarget.style.display = 'none';
                                                }}
                                            />
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Payment Rejection Info */}
                            {selectedTransaction.payment_rejection_reason && (
                                <div className="mt-4 p-3 bg-red-50 border border-red-200 rounded-lg">
                                    <div className="flex items-center gap-2 mb-2">
                                        <XCircle className="w-4 h-4 text-red-600" />
                                        <span className="text-sm font-medium text-red-800">
                                            Bukti Pembayaran Ditolak
                                        </span>
                                    </div>
                                    <p className="text-sm text-red-700 mb-2">
                                        <strong>Alasan:</strong> {selectedTransaction.payment_rejection_reason}
                                    </p>
                                    {selectedTransaction.payment_rejected_at && (
                                        <p className="text-xs text-red-600">
                                            Ditolak pada: {formatDateTime(selectedTransaction.payment_rejected_at)}
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            )}

            {/* Payment Proof Modal */}
            {showProofModal && selectedTransaction && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
                        <div className="mt-3">
                            <div className="flex items-center justify-between mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Bukti Pembayaran
                                </h3>
                                <button
                                    onClick={closeProofModal}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <X className="w-5 h-5" />
                                </button>
                            </div>
                            
                            <div className="mb-4">
                                <p className="text-sm text-gray-600 mb-2">
                                    Bukti pembayaran untuk transaksi {selectedTransaction.nomor_transaksi}
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

                            <div className="mt-3">
                                <img
                                    src={`/storage/${selectedTransaction.payment_proof}`}
                                    alt="Bukti Pembayaran"
                                    className="w-full max-w-xs h-auto rounded-lg border border-gray-300"
                                    onError={(e) => {
                                        e.currentTarget.style.display = 'none';
                                    }}
                                />
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AppLayout>
    );
}
