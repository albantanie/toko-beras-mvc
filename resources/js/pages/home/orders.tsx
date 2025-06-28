import { Head, Link } from '@inertiajs/react';
import { PageProps, Penjualan } from '@/types';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import Header from '@/components/Header';

interface UserOrdersProps extends PageProps {
    orders: {
        data: Penjualan[];
        links: any[];
        meta: any;
    };
    cartCount: number;
}

export default function UserOrders({ auth, orders, cartCount }: UserOrdersProps) {
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

    const getPickupMethodLabel = (method: string) => {
        switch (method) {
            case 'self':
                return 'Ambil Sendiri';
            case 'grab':
                return 'Grab Driver';
            case 'gojek':
                return 'Gojek Driver';
            case 'other':
                return 'Orang Lain';
            default:
                return method;
        }
    };

    return (
        <>
            <Head title="Pesanan Saya - Toko Beras" />

            <div className="min-h-screen bg-gray-50">
                <Header
                    auth={auth}
                    cartCount={cartCount}
                    showSearch={false}
                    currentPage="orders"
                />

                {/* Breadcrumb */}
                <div className="bg-white border-b">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <nav className="flex" aria-label="Breadcrumb">
                            <ol className="flex items-center space-x-4">
                                <li>
                                    <Link href="/" className="text-gray-500 hover:text-gray-700">
                                        Beranda
                                    </Link>
                                </li>
                                <li>
                                    <span className="text-gray-400">/</span>
                                </li>
                                <li>
                                    <Link href={route('user.dashboard')} className="text-gray-500 hover:text-gray-700">
                                        Dashboard
                                    </Link>
                                </li>
                                <li>
                                    <span className="text-gray-400">/</span>
                                </li>
                                <li>
                                    <span className="text-gray-900 font-medium">
                                        Pesanan Saya
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>

                {/* Orders Content */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center mb-8">
                            <h1 className="text-3xl font-bold text-gray-900">
                                Pesanan Saya
                            </h1>
                            <Link
                                href="/"
                                className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center"
                            >
                                <Icons.add className="w-4 h-4 mr-2" />
                                Belanja Lagi
                            </Link>
                        </div>

                        {orders.data.length === 0 ? (
                            <div className="text-center py-12">
                                <div className="text-gray-500 text-lg mb-4">
                                    Belum ada pesanan
                                </div>
                                <Link
                                    href="/"
                                    className="inline-block bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700"
                                >
                                    Mulai Belanja
                                </Link>
                            </div>
                        ) : (
                            <>
                                <div className="space-y-6">
                                    {orders.data.map((order) => (
                                        <div
                                            key={order.id}
                                            className="bg-white rounded-lg shadow-md overflow-hidden"
                                        >
                                            <div className="p-6">
                                                <div className="flex justify-between items-start mb-4">
                                                    <div>
                                                        <Link
                                                            href={route('user.orders.show', order.id)}
                                                            className="text-xl font-semibold text-gray-900 hover:text-green-600"
                                                        >
                                                            {order.nomor_transaksi}
                                                        </Link>
                                                        <p className="text-sm text-gray-600 mt-1">
                                                            {formatDateTime(order.tanggal_transaksi)}
                                                        </p>
                                                    </div>
                                                    <StatusBadge
                                                        status={getStatusLabel(order.status)}
                                                        variant={getStatusVariant(order.status)}
                                                    />
                                                </div>

                                                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                                                    <div>
                                                        <p className="text-sm text-gray-600">Total Pesanan</p>
                                                        <p className="text-lg font-bold text-green-600">
                                                            {formatCurrency(order.total)}
                                                        </p>
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-600">Metode Pembayaran</p>
                                                        <p className="font-semibold">
                                                            {order.metode_pembayaran.replace('_', ' ').toUpperCase()}
                                                        </p>
                                                        {order.payment_proof && (
                                                            <div className="mt-1">
                                                                <span className="text-xs text-green-600 bg-green-50 px-2 py-1 rounded">
                                                                    ✓ Bukti pembayaran diupload
                                                                </span>
                                                            </div>
                                                        )}
                                                    </div>
                                                    <div>
                                                        <p className="text-sm text-gray-600">Metode Pickup</p>
                                                        <p className="font-semibold">
                                                            {getPickupMethodLabel(order.pickup_method)}
                                                        </p>
                                                        {order.pickup_method !== 'self' && order.pickup_person_name && (
                                                            <p className="text-sm text-gray-500">
                                                                oleh: {order.pickup_person_name}
                                                            </p>
                                                        )}
                                                    </div>
                                                </div>

                                                <div className="flex justify-between items-center">
                                                    <div>
                                                        <p className="text-sm text-gray-600">
                                                            {order.detail_penjualans?.length || 0} item(s)
                                                        </p>
                                                    </div>
                                                    <div className="flex space-x-3">
                                                        <Link
                                                            href={route('user.orders.show', order.id)}
                                                            className="inline-flex items-center text-green-600 hover:text-green-700 text-sm font-medium"
                                                        >
                                                            <Icons.view className="w-4 h-4 mr-1" />
                                                            Lihat Detail
                                                        </Link>
                                                        
                                                        {order.status === 'pending' && (
                                                            <div className="text-sm text-orange-600 bg-orange-50 px-3 py-1 rounded">
                                                                Menunggu konfirmasi pembayaran
                                                            </div>
                                                        )}
                                                        
                                                        {order.payment_rejection_reason && (
                                                            <div className="text-sm text-red-600 bg-red-50 px-3 py-1 rounded">
                                                                Bukti pembayaran ditolak
                                                            </div>
                                                        )}
                                                        
                                                        {order.status === 'siap_pickup' && (
                                                            <div className="text-sm text-green-600 bg-green-50 px-3 py-1 rounded">
                                                                Siap diambil
                                                            </div>
                                                        )}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                {/* Pagination */}
                                {orders.links && orders.links.length > 3 && (
                                    <div className="mt-8 flex justify-center">
                                        <nav className="flex space-x-2">
                                            {orders.links.map((link, index) => (
                                                <Link
                                                    key={index}
                                                    href={link.url || '#'}
                                                    className={`px-3 py-2 rounded-md text-sm font-medium ${
                                                        link.active
                                                            ? 'bg-green-600 text-white'
                                                            : link.url
                                                            ? 'bg-white text-gray-700 hover:bg-gray-50 border border-gray-300'
                                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                                    }`}
                                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                                />
                                            ))}
                                        </nav>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-800 text-white py-8">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
                        <p>&copy; 2024 Toko Beras. Semua hak dilindungi.</p>
                        <p className="mt-2 text-gray-400">
                            Beras berkualitas untuk keluarga Indonesia
                        </p>
                    </div>
                </footer>
            </div>
        </>
    );
}
