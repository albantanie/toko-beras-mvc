import { Head, Link } from '@inertiajs/react';
import { PageProps, Penjualan } from '@/types';
import { formatCurrency, formatDateTime, Icons } from '@/utils/formatters';

interface PickupReceiptProps extends PageProps {
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
            };
        }>;
    };
    receiptCode: string;
}

export default function PickupReceipt({ auth, penjualan, receiptCode }: PickupReceiptProps) {
    const handlePrint = () => {
        window.print();
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
            <Head title={`Surat Tanda Terima - ${penjualan.nomor_transaksi}`} />

            <div className="min-h-screen bg-gray-50 print:bg-white">
                {/* Header - Hidden on print */}
                <header className="bg-white shadow-sm print:hidden">
                    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="flex justify-between items-center h-16">
                            <div className="flex items-center">
                                <Link href="/" className="text-xl font-bold text-green-600">
                                    🌾 Toko Beras
                                </Link>
                            </div>
                            
                            <div className="flex items-center space-x-4">
                                <button
                                    onClick={handlePrint}
                                    className="bg-green-600 text-white px-4 py-2 rounded-md hover:bg-green-700 flex items-center"
                                >
                                    <Icons.view className="w-4 h-4 mr-2" />
                                    Print Receipt
                                </button>
                                <Link
                                    href={route('penjualan.online')}
                                    className="bg-gray-600 text-white px-4 py-2 rounded-md hover:bg-gray-700"
                                >
                                    Kembali
                                </Link>
                            </div>
                        </div>
                    </div>
                </header>

                {/* Receipt Content */}
                <div className="py-8 print:py-0">
                    <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
                        <div className="bg-white shadow-lg rounded-lg print:shadow-none print:rounded-none">
                            <div className="p-8 print:p-6">
                                {/* Header */}
                                <div className="text-center mb-8 border-b pb-6">
                                    <h1 className="text-3xl font-bold text-gray-900 mb-2">
                                        🌾 TOKO BERAS
                                    </h1>
                                    <p className="text-gray-600 mb-4">
                                        Jl. Contoh No. 123, Jakarta Selatan<br />
                                        Telp: (021) 1234-5678 | Email: info@tokoberas.com
                                    </p>
                                    <div className="text-2xl font-bold text-red-600 bg-red-50 border-2 border-red-200 rounded-lg py-3 px-6 inline-block">
                                        SURAT TANDA TERIMA PESANAN
                                    </div>
                                </div>

                                {/* Receipt Info */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                    <div>
                                        <h3 className="text-lg font-semibold text-gray-900 mb-4">Informasi Pesanan</h3>
                                        <div className="space-y-2">
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">No. Transaksi:</span>
                                                <span className="font-semibold">{penjualan.nomor_transaksi}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Kode Receipt:</span>
                                                <span className="font-bold text-red-600 text-lg">{receiptCode}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Tanggal Pesanan:</span>
                                                <span className="font-semibold">{formatDateTime(penjualan.tanggal_transaksi)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span className="text-gray-600">Status:</span>
                                                <span className="font-semibold text-green-600">Siap Pickup</span>
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

                                {/* Pickup Person Info (if not self pickup) */}
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

                                {/* Order Items */}
                                <div className="mb-8">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Detail Pesanan</h3>
                                    <div className="overflow-x-auto">
                                        <table className="w-full border-collapse border border-gray-300">
                                            <thead>
                                                <tr className="bg-gray-50">
                                                    <th className="border border-gray-300 px-4 py-2 text-left">Produk</th>
                                                    <th className="border border-gray-300 px-4 py-2 text-center">Qty</th>
                                                    <th className="border border-gray-300 px-4 py-2 text-right">Harga</th>
                                                    <th className="border border-gray-300 px-4 py-2 text-right">Subtotal</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {penjualan.detail_penjualans?.map((item) => (
                                                    <tr key={item.id}>
                                                        <td className="border border-gray-300 px-4 py-2">
                                                            <div>
                                                                <div className="font-semibold">{item.barang.nama}</div>
                                                                <div className="text-sm text-gray-600">{item.barang.kode_barang}</div>
                                                            </div>
                                                        </td>
                                                        <td className="border border-gray-300 px-4 py-2 text-center">
                                                            {item.jumlah} {item.barang.satuan}
                                                        </td>
                                                        <td className="border border-gray-300 px-4 py-2 text-right">
                                                            {formatCurrency(item.harga_satuan)}
                                                        </td>
                                                        <td className="border border-gray-300 px-4 py-2 text-right font-semibold">
                                                            {formatCurrency(item.subtotal)}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                            <tfoot>
                                                <tr className="bg-gray-50">
                                                    <td colSpan={3} className="border border-gray-300 px-4 py-2 text-right font-semibold">
                                                        TOTAL:
                                                    </td>
                                                    <td className="border border-gray-300 px-4 py-2 text-right font-bold text-lg">
                                                        {formatCurrency(penjualan.total)}
                                                    </td>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                {/* Instructions */}
                                <div className="border-t pt-6">
                                    <h3 className="text-lg font-semibold text-gray-900 mb-4">Petunjuk Pengambilan</h3>
                                    <div className="bg-red-50 border border-red-200 rounded-lg p-4">
                                        <div className="space-y-2 text-sm">
                                            <p className="font-semibold text-red-800">PENTING - WAJIB DIBACA:</p>
                                            <ul className="list-disc list-inside space-y-1 text-red-700">
                                                <li>Tunjukkan surat tanda terima ini kepada kasir saat pengambilan</li>
                                                <li>Kode receipt: <strong>{receiptCode}</strong> harus sesuai dengan sistem</li>
                                                <li>Pengambil harus menunjukkan identitas yang sesuai dengan data di atas</li>
                                                <li>Pesanan hanya dapat diambil oleh orang yang tercantum dalam surat ini</li>
                                                <li>Periksa kelengkapan pesanan sebelum meninggalkan toko</li>
                                                <li>Simpan surat ini sebagai bukti pengambilan pesanan</li>
                                            </ul>
                                        </div>
                                    </div>
                                </div>

                                {/* Footer */}
                                <div className="mt-8 pt-6 border-t text-center">
                                    <p className="text-sm text-gray-600">
                                        Terima kasih telah berbelanja di Toko Beras<br />
                                        Dicetak pada: {formatDateTime(new Date().toISOString())}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {/* Print Styles */}
            <style jsx>{`
                @media print {
                    body { margin: 0; }
                    .print\\:hidden { display: none !important; }
                    .print\\:bg-white { background-color: white !important; }
                    .print\\:shadow-none { box-shadow: none !important; }
                    .print\\:rounded-none { border-radius: 0 !important; }
                    .print\\:py-0 { padding-top: 0 !important; padding-bottom: 0 !important; }
                    .print\\:p-6 { padding: 1.5rem !important; }
                }
            `}</style>
        </>
    );
}
