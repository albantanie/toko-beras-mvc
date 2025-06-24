import { Head, Link, useForm } from '@inertiajs/react';
import { PageProps, Penjualan } from '@/types';
import { formatCurrency, formatDateTime, ProductImage, Icons } from '@/utils/formatters';

interface VerifyReceiptProps extends PageProps {
    penjualan: Penjualan & {
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
                satuan: string;
                gambar?: string;
            };
        }>;
    };
}

export default function VerifyReceipt({ auth, penjualan }: VerifyReceiptProps) {
    const { data, setData, post, processing, errors } = useForm({
        verification_notes: '',
    });

    const handleVerifyAndComplete = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('receipt.verify', penjualan.receipt_code));
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
            <Head title={`Verifikasi Receipt - ${penjualan.receipt_code}`} />

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
                            
                            <div className="flex items-center space-x-4">
                                <Link
                                    href={route('penjualan.online')}
                                    className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
                                >
                                    Kembali ke Orders
                                </Link>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Verification Content */}
                <div className="py-8">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="bg-white shadow-lg rounded-lg">
                            <div className="p-8">
                                {/* Header */}
                                <div className="text-center mb-8 border-b pb-6">
                                    <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                        VERIFIKASI SURAT TANDA TERIMA
                                    </h1>
                                    <div className="text-2xl font-bold text-green-600 bg-green-50 border-2 border-green-200 rounded-lg py-3 px-6 inline-block">
                                        {penjualan.receipt_code}
                                    </div>
                                </div>

                                {/* Order Info */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Informasi Pesanan</h3>
                                        <div className="space-y-2">
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">No. Transaksi:</span>
                                                <span className="font-semibold">{penjualan.nomor_transaksi}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Tanggal Pesanan:</span>
                                                <span className="font-semibold">{formatDateTime(penjualan.tanggal_transaksi)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Status:</span>
                                                <span className="font-semibold text-green-600">Siap Pickup</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Total:</span>
                                                <span className="font-bold text-lg text-green-600">{formatCurrency(penjualan.total)}</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Informasi Pelanggan</h3>
                                        <div className="space-y-2">
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Nama:</span>
                                                <span className="font-semibold">{penjualan.nama_pelanggan}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Telepon:</span>
                                                <span className="font-semibold">{penjualan.telepon_pelanggan}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Metode Pickup:</span>
                                                <span className="font-semibold">{getPickupMethodLabel(penjualan.pickup_method)}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Pickup Person Info */}
                                {penjualan.pickup_method !== 'self' && (
                                    <div className="mb-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Informasi Pengambil</h3>
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Nama Pengambil:</span>
                                                <span className="font-semibold">{penjualan.pickup_person_name}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Telepon Pengambil:</span>
                                                <span className="font-semibold">{penjualan.pickup_person_phone}</span>
                                            </div>
                                        </div>
                                        {penjualan.pickup_notes && (
                                            <div className="mt-3">
                                                <span className="text-gray-600">Catatan:</span>
                                                <p className="font-semibold mt-1">{penjualan.pickup_notes}</p>
                                            </div>
                                        )}
                                    </div>
                                )}

                                {/* Order Items Summary */}
                                <div className="mb-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Ringkasan Pesanan</h3>
                                    <div className="bg-gray-50 rounded-lg p-4">
                                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div className="text-center">
                                                <div className="text-2xl font-bold text-gray-900">
                                                    {penjualan.detail_penjualans?.length || 0}
                                                </div>
                                                <div className="text-sm text-gray-600">Jenis Item</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-2xl font-bold text-gray-900">
                                                    {penjualan.detail_penjualans?.reduce((sum, item) => sum + item.jumlah, 0) || 0}
                                                </div>
                                                <div className="text-sm text-gray-600">Total Quantity</div>
                                            </div>
                                            <div className="text-center">
                                                <div className="text-2xl font-bold text-green-600">
                                                    {formatCurrency(penjualan.total)}
                                                </div>
                                                <div className="text-sm text-gray-600">Total Harga</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {/* Detailed Items */}
                                <div className="mb-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Detail Item</h3>
                                    <div className="space-y-3">
                                        {penjualan.detail_penjualans?.map((item) => (
                                            <div key={item.id} className="flex items-center space-x-4 p-3 border border-gray-200 rounded-lg">
                                                <ProductImage
                                                    src={item.barang.gambar}
                                                    alt={item.barang.nama}
                                                    className="w-12 h-12 object-cover rounded"
                                                />
                                                <div className="flex-1">
                                                    <div className="font-semibold">{item.barang.nama}</div>
                                                    <div className="text-sm text-gray-600">{item.barang.kode_barang}</div>
                                                </div>
                                                <div className="text-center">
                                                    <div className="font-semibold">{item.jumlah} {item.barang.satuan}</div>
                                                    <div className="text-sm text-gray-600">{formatCurrency(item.harga_satuan)}</div>
                                                </div>
                                                <div className="text-right">
                                                    <div className="font-bold text-green-600">{formatCurrency(item.subtotal)}</div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                {/* Verification Form */}
                                <form onSubmit={handleVerifyAndComplete} className="border-t pt-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Verifikasi Pengambilan</h3>
                                    
                                    <div className="mb-6">
                                        <label className="block text-sm font-medium text-gray-700 mb-2">
                                            Catatan Verifikasi (Opsional)
                                        </label>
                                        <textarea
                                            value={data.verification_notes}
                                            onChange={(e) => setData('verification_notes', e.target.value)}
                                            rows={3}
                                            className="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-green-500 focus:border-green-500"
                                            placeholder="Catatan tambahan untuk verifikasi pengambilan..."
                                        />
                                        {errors.verification_notes && (
                                            <p className="mt-1 text-sm text-red-600">{errors.verification_notes}</p>
                                        )}
                                    </div>

                                    <div className="bg-green-50 border border-green-200 rounded-lg p-4 mb-6">
                                        <div className="flex items-start">
                                            <Icons.check className="w-5 h-5 text-green-600 mt-0.5 mr-3" />
                                            <div>
                                                <h4 className="font-semibold text-green-800">Checklist Verifikasi:</h4>
                                                <ul className="text-sm text-green-700 mt-2 space-y-1">
                                                    <li>✓ Identitas pengambil telah diverifikasi</li>
                                                    <li>✓ Kode receipt sesuai dengan sistem</li>
                                                    <li>✓ Semua item telah disiapkan dan diperiksa</li>
                                                    <li>✓ Pelanggan/pengambil telah mengkonfirmasi kelengkapan pesanan</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="flex space-x-4">
                                        <button
                                            type="submit"
                                            disabled={processing}
                                            className="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-700 transition-colors disabled:opacity-50 flex items-center justify-center"
                                        >
                                            {processing ? (
                                                <>
                                                    <svg className="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                                        <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                                                        <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                                    </svg>
                                                    Memproses...
                                                </>
                                            ) : (
                                                <>
                                                    <Icons.check className="w-5 h-5 mr-2" />
                                                    Konfirmasi Pengambilan & Selesaikan Pesanan
                                                </>
                                            )}
                                        </button>
                                        
                                        <Link
                                            href={route('penjualan.online')}
                                            className="bg-gray-100 text-gray-700 px-6 py-3 rounded-lg font-semibold hover:bg-gray-200 transition-colors flex items-center justify-center"
                                        >
                                            Batal
                                        </Link>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
