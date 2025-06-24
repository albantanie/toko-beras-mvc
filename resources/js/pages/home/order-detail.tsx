import { Head, Link } from '@inertiajs/react';
import { PageProps, Penjualan } from '@/types';
import { formatCurrency, formatDateTime, StatusBadge, ProductImage, Icons } from '@/utils/formatters';

interface OrderDetailProps extends PageProps {
    order: Penjualan & {
        detail_penjualans: Array<{
            id: number;
            barang_id: number;
            jumlah: number;
            harga_satuan: number;
            subtotal: number;
            barang: {
                id: number;
                nama: string;
                kode_barang: string;
                kategori: string;
                satuan: string;
                gambar?: string;
            };
        }>;
        user: {
            id: number;
            name: string;
            email: string;
        };
    };
}

export default function OrderDetail({ auth, order }: OrderDetailProps) {
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

    const getStatusDescription = (status: string) => {
        switch (status) {
            case 'pending':
                return 'Pesanan Anda sedang menunggu konfirmasi pembayaran dari kasir. Silakan lakukan pembayaran sesuai metode yang dipilih.';
            case 'dibayar':
                return 'Pembayaran telah dikonfirmasi. Pesanan sedang dipersiapkan oleh tim kami.';
            case 'siap_pickup':
                return 'Pesanan sudah siap untuk diambil. Silakan datang ke toko atau kirim orang yang sudah ditentukan.';
            case 'selesai':
                return 'Pesanan telah selesai dan sudah diambil. Terima kasih telah berbelanja di Toko Beras.';
            case 'dibatalkan':
                return 'Pesanan telah dibatalkan.';
            default:
                return '';
        }
    };

    return (
        <>
            <Head title={`Detail Pesanan ${order.nomor_transaksi} - Toko Beras`} />

            <div className="min-h-screen bg-gray-50">
                {/* Header */}
                <header className="bg-white shadow-sm">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-16">
                            <div className="flex items-center">
                                <Link href="/" className="text-xl font-bold text-green-600">
                                    🌾 Toko Beras
                                </Link>
                            </div>
                            
                            <nav className="hidden md:flex space-x-8">
                                <Link href="/" className="text-gray-900 hover:text-green-600">
                                    Beranda
                                </Link>
                                <Link href={route('cart.index')} className="text-gray-900 hover:text-green-600">
                                    Keranjang
                                </Link>
                                <Link href={route('user.dashboard')} className="text-gray-900 hover:text-green-600">
                                    Dashboard
                                </Link>
                                <Link href={route('user.orders')} className="text-gray-900 hover:text-green-600">
                                    Pesanan Saya
                                </Link>
                                <Link
                                    href={route('logout')}
                                    method="post"
                                    as="button"
                                    className="bg-red-600 text-white px-4 py-2 rounded-md hover:bg-red-700"
                                >
                                    Logout
                                </Link>
                            </nav>
                        </div>
                    </div>
                </header>

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
                                    <Link href={route('user.orders')} className="text-gray-500 hover:text-gray-700">
                                        Pesanan Saya
                                    </Link>
                                </li>
                                <li>
                                    <span className="text-gray-400">/</span>
                                </li>
                                <li>
                                    <span className="text-gray-900 font-medium">
                                        {order.nomor_transaksi}
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>

                {/* Order Detail Content */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center mb-8">
                            <h1 className="text-3xl font-bold text-gray-900">
                                Detail Pesanan
                            </h1>
                            <Link
                                href={route('user.orders')}
                                className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
                            >
                                Kembali ke Pesanan
                            </Link>
                        </div>

                        <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                            {/* Order Info */}
                            <div className="lg:col-span-2">
                                {/* Order Header */}
                                <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                                    <div className="flex justify-between items-start mb-4">
                                        <div>
                                            <h2 className="text-xl font-semibold text-gray-900">
                                                {order.nomor_transaksi}
                                            </h2>
                                            <p className="text-gray-600">
                                                Dipesan pada {formatDateTime(order.tanggal_transaksi)}
                                            </p>
                                        </div>
                                        <StatusBadge
                                            status={getStatusLabel(order.status)}
                                            variant={getStatusVariant(order.status)}
                                        />
                                    </div>

                                    <div className="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                        <p className="text-blue-800">
                                            <strong>Status:</strong> {getStatusDescription(order.status)}
                                        </p>
                                    </div>
                                </div>

                                {/* Order Items */}
                                <div className="bg-white rounded-lg shadow-md p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Item Pesanan ({order.detail_penjualans?.length || 0} item)
                                    </h3>

                                    <div className="space-y-4">
                                        {order.detail_penjualans?.map((item) => (
                                            <div key={item.id} className="flex items-center space-x-4 border-b border-gray-200 pb-4">
                                                <ProductImage
                                                    src={item.barang.gambar}
                                                    alt={item.barang.nama}
                                                    className="w-16 h-16 object-cover rounded-lg"
                                                />
                                                
                                                <div className="flex-1">
                                                    <h4 className="text-lg font-semibold text-gray-900">
                                                        {item.barang.nama}
                                                    </h4>
                                                    <p className="text-sm text-gray-600">
                                                        {item.barang.kategori} • {item.barang.kode_barang}
                                                    </p>
                                                    <p className="text-sm text-gray-600">
                                                        {formatCurrency(item.harga_satuan)} × {item.jumlah} {item.barang.satuan}
                                                    </p>
                                                </div>

                                                <div className="text-right">
                                                    <p className="text-lg font-bold text-green-600">
                                                        {formatCurrency(item.subtotal)}
                                                    </p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </div>

                            {/* Order Summary & Info */}
                            <div className="lg:col-span-1">
                                {/* Order Summary */}
                                <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Ringkasan Pesanan
                                    </h3>

                                    <div className="space-y-3">
                                        <div className="flex justify-between">
                                            <span className="text-gray-600">Subtotal</span>
                                            <span className="font-semibold">{formatCurrency(order.subtotal)}</span>
                                        </div>
                                        {order.diskon > 0 && (
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Diskon</span>
                                                <span className="text-red-600">-{formatCurrency(order.diskon)}</span>
                                            </div>
                                        )}
                                        {order.pajak > 0 && (
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Pajak</span>
                                                <span className="font-semibold">{formatCurrency(order.pajak)}</span>
                                            </div>
                                        )}
                                        <div className="border-t pt-3">
                                            <div className="flex justify-between">
                                                <span className="text-lg font-semibold">Total</span>
                                                <span className="text-lg font-bold text-green-600">
                                                    {formatCurrency(order.total)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Payment & Pickup Info */}
                                <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Informasi Pembayaran & Pickup
                                    </h3>

                                    <div className="space-y-3">
                                        <div>
                                            <span className="text-gray-600">Metode Pembayaran:</span>
                                            <p className="font-semibold">
                                                {order.metode_pembayaran.replace('_', ' ').toUpperCase()}
                                            </p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Metode Pickup:</span>
                                            <p className="font-semibold">
                                                {getPickupMethodLabel(order.pickup_method)}
                                            </p>
                                        </div>
                                        {order.pickup_method !== 'self' && (
                                            <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-3">
                                                <p className="text-sm font-medium text-yellow-800 mb-2">
                                                    Informasi Pengambil:
                                                </p>
                                                <p className="text-sm text-yellow-700">
                                                    <strong>Nama:</strong> {order.pickup_person_name}
                                                </p>
                                                <p className="text-sm text-yellow-700">
                                                    <strong>Telepon:</strong> {order.pickup_person_phone}
                                                </p>
                                                {order.pickup_notes && (
                                                    <p className="text-sm text-yellow-700 mt-2">
                                                        <strong>Catatan:</strong> {order.pickup_notes}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Customer Info */}
                                <div className="bg-white rounded-lg shadow-md p-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">
                                        Informasi Pelanggan
                                    </h3>

                                    <div className="space-y-3">
                                        <div>
                                            <span className="text-gray-600">Nama:</span>
                                            <p className="font-semibold">{order.nama_pelanggan}</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Telepon:</span>
                                            <p className="font-semibold">{order.telepon_pelanggan}</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Alamat:</span>
                                            <p className="font-semibold">{order.alamat_pelanggan}</p>
                                        </div>
                                        {order.catatan && (
                                            <div>
                                                <span className="text-gray-600">Catatan:</span>
                                                <p className="font-semibold">{order.catatan}</p>
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
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
