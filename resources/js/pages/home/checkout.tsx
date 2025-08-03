import { Head, Link, useForm } from '@inertiajs/react';
import { formatCurrency, ProductImage } from '@/utils/formatters';
import Header from '@/components/Header';

interface CheckoutProps {
    auth: {
        user: {
            id: number;
            name: string;
            email: string;
            roles: Array<{ name: string }>;
        };
    };
    cartItems: Array<{
        id: number;
        quantity: number;
        subtotal: number;
        barang: {
            id: number;
            nama: string;
            kode_barang: string;
            satuan: string;
            gambar: string;
            harga_jual: number;
        };
    }>;
    total: number;
    user: {
        name: string;
    };
    cartCount: number;
}

export default function Checkout({ auth, cartItems, total, user, cartCount }: CheckoutProps) {
    const { data, setData, post, processing, errors } = useForm({
        nama_pelanggan: user?.name || '',
        telepon_pelanggan: '',
        alamat_pelanggan: '',
        metode_pembayaran: 'tunai',
        payment_proof: null as File | null,
        pickup_method: 'self',
        pickup_person_name: '',
        pickup_person_phone: '',
        pickup_notes: '',
        catatan: '',
    });

    // Helper function untuk validasi nomor HP (hanya angka)
    const handlePhoneChange = (value: string, field: 'telepon_pelanggan' | 'pickup_person_phone') => {
        // Hanya izinkan angka, spasi, tanda +, tanda -, dan tanda kurung
        const cleanedValue = value.replace(/[^0-9+\-\s()]/g, '');
        setData(field, cleanedValue);
    };

    // Helper function untuk handle perubahan pickup method
    const handlePickupMethodChange = (method: string) => {
        setData(prevData => ({
            ...prevData,
            pickup_method: method,
            // Clear pickup person data jika pilih "Ambil Sendiri"
            pickup_person_name: method === 'self' ? '' : prevData.pickup_person_name,
            pickup_person_phone: method === 'self' ? '' : prevData.pickup_person_phone,
        }));
    };

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        // Kirim data sebagai FormData agar file terupload
        const formData = new FormData();
        formData.append('nama_pelanggan', data.nama_pelanggan);
        formData.append('telepon_pelanggan', data.telepon_pelanggan);
        formData.append('alamat_pelanggan', data.alamat_pelanggan);
        formData.append('metode_pembayaran', data.metode_pembayaran);
        if (data.payment_proof) formData.append('payment_proof', data.payment_proof);
        formData.append('pickup_method', data.pickup_method);
        formData.append('pickup_person_name', data.pickup_method === 'self' ? '' : data.pickup_person_name);
        formData.append('pickup_person_phone', data.pickup_method === 'self' ? '' : data.pickup_person_phone);
        formData.append('pickup_notes', data.pickup_notes);
        formData.append('catatan', data.catatan);
        
        // Inertia post dengan FormData
        post(route('cart.process-checkout'), {
            preserveScroll: true,
            forceFormData: true,
        });
    };

    const handleFileUpload = (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (file) {
            setData('payment_proof', file);
        }
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
                <div className="bg-white border-b border-gray-200">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                        <nav className="flex" aria-label="Navigasi Breadcrumb">
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
                                    <Link href="/cart" className="text-gray-500 hover:text-gray-700">
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

                    <form onSubmit={submit} className="lg:grid lg:grid-cols-3 lg:gap-8" encType="multipart/form-data">
                                <div className="lg:col-span-2">
                            <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Informasi Pelanggan
                                        </h2>

                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Nama Pelanggan *
                                                </label>
                                                <input
                                                    type="text"
                                                    value={data.nama_pelanggan}
                                                    onChange={(e) => setData('nama_pelanggan', e.target.value)}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
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
                                                    onChange={(e) => handlePhoneChange(e.target.value, 'telepon_pelanggan')}
                                                    placeholder="08123456789"
                                                    pattern="[0-9+\-\s()]+"
                                                    title="Hanya boleh berisi angka, spasi, +, -, dan tanda kurung"
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.telepon_pelanggan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.telepon_pelanggan}</p>
                                                )}
                                    </div>
                                            </div>

                                <div className="mt-4">
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                        Alamat *
                                                </label>
                                                <textarea
                                                    value={data.alamat_pelanggan}
                                                    onChange={(e) => setData('alamat_pelanggan', e.target.value)}
                                        rows={3}
                                        className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                    required
                                                />
                                                {errors.alamat_pelanggan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.alamat_pelanggan}</p>
                                                )}
                                            </div>
                            </div>

                            <div className="bg-white rounded-lg shadow-md p-6 mb-6">
                                <h2 className="text-lg font-semibold text-gray-900 mb-4">
                                    Metode Pembayaran & Pickup
                                </h2>

                                <div className="space-y-6">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Metode Pembayaran *
                                                </label>
                                                <div className="space-y-3">
                                            <label className="flex items-center">
                                                <input
                                                    type="radio"
                                                    name="metode_pembayaran"
                                                    value="tunai"
                                                    checked={data.metode_pembayaran === 'tunai'}
                                                    onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                    className="text-green-600 focus:ring-green-500"
                                                />
                                                <span className="ml-3">Cash (Bayar di Toko)</span>
                                            </label>
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="metode_pembayaran"
                                                            value="transfer_bca"
                                                            checked={data.metode_pembayaran === 'transfer_bca'}
                                                            onChange={(e) => setData('metode_pembayaran', e.target.value)}
                                                            className="text-green-600 focus:ring-green-500"
                                                        />
                                                        <span className="ml-3">Transfer Bank BCA</span>
                                                    </label>
                                                </div>
                                                {errors.metode_pembayaran && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.metode_pembayaran}</p>
                                                )}
                                            </div>

                                    {data.metode_pembayaran === 'transfer_bca' && (
                                        <div className="p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                            <h4 className="font-medium text-blue-900 mb-3">Informasi Transfer Bank</h4>
                                            <div className="space-y-2 text-sm mb-4">
                                                <div className="flex justify-between">
                                                    <span className="text-gray-600">Bank:</span>
                                                    <span className="font-medium">Bank Central Asia (BCA)</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-gray-600">No. Rekening:</span>
                                                    <span className="font-medium">1234567890</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-gray-600">Atas Nama:</span>
                                                    <span className="font-medium">Toko Beras Sejahtera</span>
                                                </div>
                                                <div className="flex justify-between">
                                                    <span className="text-gray-600">Jumlah Transfer:</span>
                                                    <span className="font-medium text-green-600">
                                                        {formatCurrency(total)}
                                                    </span>
                                                </div>
                                            </div>
                                            
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                                    Upload Bukti Transfer *
                                                </label>
                                                <div className="mt-1 flex justify-center px-6 pt-5 pb-6 border-2 border-gray-300 border-dashed rounded-md">
                                                    <label htmlFor="payment_proof_upload" className="w-full cursor-pointer">
                                                        <div className="space-y-1 text-center">
                                                            {data.payment_proof ? (
                                                                <div className="flex flex-col items-center justify-center">
                                                                    {data.payment_proof.type.startsWith('image/') ? (
                                                                        <img
                                                                            src={URL.createObjectURL(data.payment_proof)}
                                                                            alt={data.payment_proof.name}
                                                                            className="max-h-40 rounded shadow mb-2"
                                                                        />
                                                                    ) : (
                                                                        <div className="flex items-center justify-center">
                                                                            <svg className="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M5 13l4 4L19 7" />
                                                                            </svg>
                                                                            <span className="ml-2 text-sm text-green-600">
                                                                                {data.payment_proof.name}
                                                                            </span>
                                                                        </div>
                                                                    )}
                                                                    <span className="text-xs text-gray-500">{data.payment_proof.name}</span>
                                                                </div>
                                                            ) : (
                                                                <>
                                                                    <svg className="w-8 h-8 mb-4 text-gray-500" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 20 16">
                                                                        <path stroke="currentColor" strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M13 13h3a3 3 0 0 0 0-6h-.025A5.56 5.56 0 0 0 16 6.5 5.5 5.5 0 0 0 5.207 5.021C5.137 5.017 5.071 5 5 5a4 4 0 0 0 0 8h2.167M10 15V6m0 0L8 8m2-2 2 2"/>
                                                                    </svg>
                                                                    <p className="mb-2 text-sm text-gray-500">
                                                                        <span className="font-semibold">Klik untuk upload</span> atau drag and drop
                                                                    </p>
                                                                    <p className="text-xs text-gray-500">PNG, JPG, PDF (MAX. 5MB)</p>
                                                                </>
                                                            )}
                                                        </div>
                                                        <input
                                                            id="payment_proof_upload"
                                                            type="file"
                                                            name="payment_proof"
                                                            accept="image/*,.pdf"
                                                            onChange={handleFileUpload}
                                                            className="hidden"
                                                            required={data.metode_pembayaran === 'transfer_bca'}
                                                        />
                                                    </label>
                                                </div>
                                                {data.metode_pembayaran === 'transfer_bca' && !data.payment_proof && (
                                                    <p className="mt-1 text-sm text-red-600">
                                                        Bukti transfer wajib diupload untuk pembayaran transfer
                                                    </p>
                                                )}
                                                <p className="mt-2 text-xs text-gray-500">
                                                    Upload screenshot atau foto bukti transfer untuk mempercepat proses verifikasi
                                                </p>
                                            </div>
                                        </div>
                                    )}

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Metode Pickup *
                                                </label>
                                                <div className="space-y-3">
                                                    <label className="flex items-center">
                                                        <input
                                                            type="radio"
                                                            name="pickup_method"
                                                            value="self"
                                                            checked={data.pickup_method === 'self'}
                                                            onChange={(e) => handlePickupMethodChange(e.target.value)}
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
                                                            onChange={(e) => handlePickupMethodChange(e.target.value)}
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
                                                            onChange={(e) => handlePickupMethodChange(e.target.value)}
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
                                                            onChange={(e) => handlePickupMethodChange(e.target.value)}
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
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            {data.pickup_method === 'grab' && 'Nama Grab Driver *'}
                                                            {data.pickup_method === 'gojek' && 'Nama Gojek Driver *'}
                                                            {data.pickup_method === 'other' && 'Nama Pengambil *'}
                                                        </label>
                                                        <input
                                                            type="text"
                                                            value={data.pickup_person_name}
                                                            onChange={(e) => setData('pickup_person_name', e.target.value)}
                                                            placeholder={
                                                                data.pickup_method === 'grab' ? 'Nama driver Grab' :
                                                                data.pickup_method === 'gojek' ? 'Nama driver Gojek' :
                                                                'Nama orang yang mengambil'
                                                            }
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                            required={data.pickup_method !== 'self'}
                                                        />
                                                        {errors.pickup_person_name && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.pickup_person_name}</p>
                                                        )}
                                                    </div>

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                                            {data.pickup_method === 'grab' && 'Nomor HP Grab Driver *'}
                                                            {data.pickup_method === 'gojek' && 'Nomor HP Gojek Driver *'}
                                                            {data.pickup_method === 'other' && 'Nomor HP Pengambil *'}
                                                        </label>
                                                        <input
                                                            type="tel"
                                                            value={data.pickup_person_phone}
                                                            onChange={(e) => handlePhoneChange(e.target.value, 'pickup_person_phone')}
                                                            placeholder={
                                                                data.pickup_method === 'grab' ? 'HP driver Grab' :
                                                                data.pickup_method === 'gojek' ? 'HP driver Gojek' :
                                                                '08123456789'
                                                            }
                                                            pattern="[0-9+\-\s()]+"
                                                            title="Hanya boleh berisi angka, spasi, +, -, dan tanda kurung"
                                                    className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                            required={data.pickup_method !== 'self'}
                                                        />
                                                        {errors.pickup_person_phone && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.pickup_person_phone}</p>
                                                        )}
                                                    </div>
                                        </div>
                                    )}

                                                    <div>
                                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Catatan Pickup
                                                        </label>
                                                        <textarea
                                                            value={data.pickup_notes}
                                                            onChange={(e) => setData('pickup_notes', e.target.value)}
                                            rows={3}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                            placeholder={
                                                data.pickup_method === 'self' ? 'Contoh: Ambil di depan toko, pakai motor merah' :
                                                data.pickup_method === 'grab' ? 'Contoh: Driver pakai jaket hijau, motor Honda Beat' :
                                                data.pickup_method === 'gojek' ? 'Contoh: Driver pakai jaket hijau, motor Yamaha' :
                                                'Contoh: Ciri-ciri orang yang mengambil, kendaraan yang dipakai'
                                            }
                                                        />
                                                        {errors.pickup_notes && (
                                                            <p className="mt-1 text-sm text-red-600">{errors.pickup_notes}</p>
                                                        )}
                                                    </div>

                                            <div>
                                                <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Catatan Tambahan
                                                </label>
                                                <textarea
                                                    value={data.catatan}
                                                    onChange={(e) => setData('catatan', e.target.value)}
                                                    rows={3}
                                            className="w-full border-gray-300 rounded-md shadow-sm focus:ring-green-500 focus:border-green-500"
                                                    placeholder="Catatan khusus untuk pesanan Anda"
                                                />
                                                {errors.catatan && (
                                                    <p className="mt-1 text-sm text-red-600">{errors.catatan}</p>
                                                )}
                                            </div>
                                        </div>
                                    </div>
                                </div>

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

                                <div className="space-y-3 mb-6">
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

                                            <button
                                                type="submit"
                                                disabled={processing}
                                    className="w-full bg-green-600 text-white py-3 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed"
                                >
                                    {processing ? 'Memproses...' : 'Buat Pesanan'}
                                            </button>
                                            
                                <p className="mt-3 text-xs text-gray-500 text-center">
                                    Dengan membuat pesanan, Anda menyetujui syarat dan ketentuan kami
                                </p>
                                </div>
                            </div>
                        </form>
                    </div>
                </section>
            </div>
        </>
    );
}
