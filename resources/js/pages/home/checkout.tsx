import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, Barang, User } from '@/types';
import { formatCurrency, ProductImage, Icons } from '@/utils/formatters';
import Header from '@/components/Header';

interface CartItem {
    id: number;
    barang: Barang;
    quantity: number;
    subtotal: number;
}

interface CheckoutProps extends PageProps {
    cartItems: CartItem[];
    total: number;
    user: User;
    cartCount: number;
}

export default function Checkout({ auth, cartItems, total, user, cartCount }: CheckoutProps) {
    const { data, setData, post, processing, errors } = useForm({
        nama_pelanggan: user.name || '',
        telepon_pelanggan: '',
        alamat_pelanggan: '',
        metode_pembayaran: 'transfer',
        pickup_method: 'self',
        pickup_person_name: '',
        pickup_person_phone: '',
        pickup_notes: '',
        catatan: '',
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('cart.process-checkout'));
    };

    return (
        <>
            <Head title="Checkout - Toko Beras" />

            <div className="min-h-screen bg-gray-50">
                <Header
                    auth={auth}
                    cartCount={cartCount}
                    showSearch={false}
                    currentPage="other"
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
                                    <Link href={route('cart.index')} className="text-gray-500 hover:text-gray-700">
                                        Keranjang
                                    </Link>
                                </li>
                                <li>
                                    <span className="text-gray-400">/</span>
                                </li>
                                <li>
                                    <span className="text-gray-900 font-medium">
                                        Checkout
                                    </span>
                                </li>
                            </ol>
                        </nav>
                    </div>
                </div>

                {/* Checkout Content */}
                <section className="py-12">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <h1 className="text-3xl font-bold text-gray-900 mb-8">
                            Checkout
                        </h1>

                        <form onSubmit={submit}>
                            <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
                                {/* Checkout Form */}
                                <div className="lg:col-span-2">
                                    <div className="bg-white rounded-lg shadow-md p-6">
                                        <h2 className="text-lg font-semibold text-gray-900 mb-6">
                                            Informasi Pengiriman
                                        </h2>

                                        <div className="space-y-6">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Nama Lengkap *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.nama_pelanggan}
                                                    onChange={(e) => setData('nama_pelanggan', e.target.value)}
                                                    className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.nama_pelanggan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.nama_pelanggan}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Nomor Telepon *
                                                </label>
                                                <input
                                                    type="tel"
                                                    value={data.telepon_pelanggan}
                                                    onChange={(e) => setData('telepon_pelanggan', e.target.value)}
                                                    className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                    placeholder="08xxxxxxxxxx"
                                                    required
                                                />
                                                {errors.telepon_pelanggan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.telepon_pelanggan}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Alamat Lengkap *
                                                </label>
                                                <textarea
                                                    value={data.alamat_pelanggan}
                                                    onChange={(e) => setData('alamat_pelanggan', e.target.value)}
                                                    rows={4}
                                                    className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                    placeholder="Masukkan alamat lengkap termasuk kode pos"
                                                    required
                                                />
                                                {errors.alamat_pelanggan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.alamat_pelanggan}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Metode Pembayaran *
                                                </label>
                                                <div className="space-y-3">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="metode_pembayaran"
                                                            value="transfer"
                                                            checked={data.metode_pembayaran === 'transfer'}
                                                            onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Transfer Bank</span>
                                                    </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="metode_pembayaran"
                                                            value="kartu_debit"
                                                            checked={data.metode_pembayaran === 'kartu_debit'}
                                                            onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Kartu Debit</span>
                                                    </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="metode_pembayaran"
                                                            value="kartu_kredit"
                                                            checked={data.metode_pembayaran === 'kartu_kredit'}
                                                            onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Kartu Kredit</span>
                                                    </label>
                                                </div>
                                                {errors.metode_pembayaran && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.metode_pembayaran}</p>
                                                )}
                                            </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Metode Pengambilan *
                                                </label>
                                                <div className="space-y-3">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="pickup_method"
                                                            value="self"
                                                            checked={data.pickup_method === 'self'}
                                                            onChange={(e) => setData('pickup_method', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Ambil Sendiri</span>
                                                    </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="pickup_method"
                                                            value="grab"
                                                            checked={data.pickup_method === 'grab'}
                                                            onChange={(e) => setData('pickup_method', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Grab Driver</span>
                                                    </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="pickup_method"
                                                            value="gojek"
                                                            checked={data.pickup_method === 'gojek'}
                                                            onChange={(e) => setData('pickup_method', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Gojek Driver</span>
                                                    </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="pickup_method"
                                                            value="other"
                                                            checked={data.pickup_method === 'other'}
                                                            onChange={(e) => setData('pickup_method', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Orang Lain</span>
                                                    </label>
                                                </div>
                                                {errors.pickup_method && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.pickup_method}</p>
                                                )}
                                            </div>

                                            {data.pickup_method !== 'self' && (
                                                <div className="space-y-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                                    <div className="text-sm text-yellow-800 font-medium">
                                                        Informasi Pengambil Pesanan
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Nama Pengambil *
                                                        </label>
                                                        <input
                                                            type="text"
                                                            value={data.pickup_person_name}
                                                            onChange={(e) => setData('pickup_person_name', e.target.value)}
                                                            className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                            placeholder="Nama lengkap pengambil pesanan"
                                                            required={data.pickup_method !== 'self'}
                                                        />
                                                        {errors.pickup_person_name && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.pickup_person_name}</p>
                                                        )}
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Nomor HP Pengambil *
                                                        </label>
                                                        <input
                                                            type="tel"
                                                            value={data.pickup_person_phone}
                                                            onChange={(e) => setData('pickup_person_phone', e.target.value)}
                                                            className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                            placeholder="08xxxxxxxxxx"
                                                            required={data.pickup_method !== 'self'}
                                                        />
                                                        {errors.pickup_person_phone && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.pickup_person_phone}</p>
                                                        )}
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            Catatan Pengambilan
                                                        </label>
                                                        <textarea
                                                            value={data.pickup_notes}
                                                            onChange={(e) => setData('pickup_notes', e.target.value)}
                                                            rows={2}
                                                            className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                            placeholder="Catatan khusus untuk pengambilan pesanan"
                                                        />
                                                        {errors.pickup_notes && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.pickup_notes}</p>
                                                        )}
                                                    </div>

                                                    <div className="text-sm text-yellow-700 bg-yellow-100 p-3 rounded">
                                                        <strong>Penting:</strong> Kasir akan memberikan surat tanda terima yang harus ditunjukkan saat pengambilan pesanan.
                                                    </div>
                                                </div>
                                            )}

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Catatan Pesanan (Opsional)
                                                </label>
                                                <textarea
                                                    value={data.catatan}
                                                    onChange={(e) => setData('catatan', e.target.value)}
                                                    rows={3}
                                                    className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                                    placeholder="Catatan khusus untuk pesanan Anda"
                                                />
                                                {errors.catatan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.catatan}</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Order Summary */}
                                <div className="lg:col-span-1">
                                    <div className="bg-white rounded-lg shadow-md p-6 sticky top-6">
                                        <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                            Ringkasan Pesanan
                                        </h2>

                                        <div className="space-y-4 mb-6">
                                            {cartItems.map((item) => (
                                                <div key={item.id} className="flex items-center space-x-3">
                                                    <ProductImage
                                                        src={item.barang.gambar}
                                                        alt={item.barang.nama}
                                                        className="w-12 h-12 object-cover rounded"
                                                    />
                                                    <div className="flex-1">
                                                        <p className="text-sm font-medium text-gray-900">
                                                            {item.barang.nama}
                                                        </p>
                                                        <p className="text-sm text-gray-600">
                                                            {item.quantity} × {formatCurrency(item.barang.harga_jual)}
                                                        </p>
                                                    </div>
                                                    <p className="text-sm font-semibold text-gray-900">
                                                        {formatCurrency(item.subtotal)}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="space-y-3 mb-6 border-t pt-4">
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Subtotal</span>
                                                <span className="font-semibold">{formatCurrency(total)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Ongkos Kirim</span>
                                                <span className="text-green-600">Gratis</span>
                                            </div>
                                            <div className="border-t pt-3">
                                                <div className="flex justify-between">
                                                    <span className="text-lg font-semibold">Total</span>
                                                    <span className="text-lg font-bold text-green-600">
                                                        {formatCurrency(total)}
                                                    </span>
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-3">
                                            <button
                                                type="submit"
                                                disabled={processing}
                                                className="w-full bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center justify-center"
                                            >
                                                {processing ? (
                                                    <>
                                                        <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                            <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                            <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                        </svg>
                                                        Memproses Pesanan...
                                                    </>
                                                ) : (
                                                    <>
                                                        <Icons.check className="w-5 h-5 mr-2" />
                                                        Buat Pesanan
                                                    </>
                                                )}
                                            </button>
                                            
                                            <Link
                                                href={route('cart.index')}
                                                className="block w-full bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold text-center hover:bg-gray-200 transition-colors"
                                            >
                                                Kembali ke Keranjang
                                            </Link>
                                        </div>

                                        <div className="mt-6 text-sm text-gray-600">
                                            <p className="flex items-center">
                                                <Icons.check className="w-4 h-4 text-green-600 mr-2" />
                                                Pesanan akan diproses setelah pembayaran dikonfirmasi
                                            </p>
                                            <p className="flex items-center mt-1">
                                                <Icons.check className="w-4 h-4 text-green-600 mr-2" />
                                                Anda akan mendapat notifikasi status pesanan
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </form>
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
