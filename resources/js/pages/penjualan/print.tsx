import { Head } from '@inertiajs/react';
import { PageProps, Penjualan } from '@/types';
import { formatCurrency, formatDateTime } from '@/utils/formatters';
import { useEffect } from 'react';
import { route } from 'ziggy-js';

interface PenjualanPrintProps extends PageProps {
    penjualan: Penjualan & {
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
            };
        }>;
    };
}

export default function PenjualanPrint({ penjualan }: PenjualanPrintProps) {
    useEffect(() => {
        // Auto print when page loads
        window.print();
    }, []);

    const totalItems = penjualan.detail_penjualans?.reduce((total, item) => total + item.jumlah, 0) || 0;

    return (
        <>
            <Head title={`Print Receipt - ${penjualan.nomor_transaksi}`} />
            
            <div className="max-w-2xl mx-auto p-8 bg-white print:p-0 print:max-w-none">
                {/* Header */}
                <div className="text-center mb-8 border-b-2 border-gray-300 pb-6">
                    <h1 className="text-2xl font-bold text-gray-900 mb-2">TOKO BERAS BERKAH</h1>
                    <p className="text-sm text-gray-600">
                        Jl. Raya Beras No. 123, Jakarta Selatan<br />
                        Telp: (021) 1234-5678 | Email: info@tokoberasberkah.com
                    </p>
                </div>

                {/* Transaction Info */}
                <div className="mb-6">
                    <div className="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p><strong>No. Transaksi:</strong> {penjualan.nomor_transaksi}</p>
                            <p><strong>Tanggal:</strong> {formatDateTime(penjualan.tanggal_transaksi)}</p>
                            <p><strong>Kasir:</strong> {penjualan.user?.name || 'Unknown'}</p>
                        </div>
                        <div>
                            <p><strong>Pelanggan:</strong> {penjualan.nama_pelanggan || penjualan.pelanggan?.name || 'Walk-in Customer'}</p>
                            {penjualan.telepon_pelanggan && (
                                <p><strong>Telepon:</strong> {penjualan.telepon_pelanggan}</p>
                            )}
                            <p><strong>Pembayaran:</strong> {penjualan.metode_pembayaran.replace('_', ' ').toUpperCase()}</p>
                        </div>
                    </div>
                </div>

                {/* Items Table */}
                <div className="mb-6">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b-2 border-gray-300">
                                <th className="text-left py-2">Item</th>
                                <th className="text-center py-2">Qty</th>
                                <th className="text-right py-2">Harga</th>
                                <th className="text-right py-2">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            {penjualan.detail_penjualans?.map((item, index) => (
                                <tr key={item.id} className="border-b border-gray-200">
                                    <td className="py-2">
                                        <div>
                                            <div className="font-medium">{item.barang.nama}</div>
                                            <div className="text-xs text-gray-500">
                                                {item.barang.kode_barang} • {item.barang.kategori}
                                            </div>
                                            {item.catatan && (
                                                <div className="text-xs text-gray-400 italic">
                                                    Note: {item.catatan}
                                                </div>
                                            )}
                                        </div>
                                    </td>
                                    <td className="text-center py-2">
                                        {item.jumlah} {item.barang.satuan}
                                    </td>
                                    <td className="text-right py-2">
                                        {formatCurrency(item.harga_satuan)}
                                    </td>
                                    <td className="text-right py-2 font-medium">
                                        {formatCurrency(item.subtotal)}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Summary */}
                <div className="border-t-2 border-gray-300 pt-4">
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm">Total Items:</span>
                        <span className="text-sm font-medium">{totalItems}</span>
                    </div>
                    <div className="flex justify-between items-center mb-2">
                        <span className="text-sm">Subtotal:</span>
                        <span className="text-sm">{formatCurrency(penjualan.subtotal)}</span>
                    </div>
                    {penjualan.diskon > 0 && (
                        <div className="flex justify-between items-center mb-2">
                            <span className="text-sm">Diskon:</span>
                            <span className="text-sm text-red-600">-{formatCurrency(penjualan.diskon)}</span>
                        </div>
                    )}
                    {penjualan.pajak > 0 && (
                        <div className="flex justify-between items-center mb-2">
                            <span className="text-sm">Pajak:</span>
                            <span className="text-sm">{formatCurrency(penjualan.pajak)}</span>
                        </div>
                    )}
                    <div className="flex justify-between items-center mb-4 text-lg font-bold border-t border-gray-300 pt-2">
                        <span>TOTAL:</span>
                        <span>{formatCurrency(penjualan.total)}</span>
                    </div>
                    
                    {penjualan.bayar && (
                        <>
                            <div className="flex justify-between items-center mb-2">
                                <span className="text-sm">Bayar:</span>
                                <span className="text-sm font-medium">{formatCurrency(penjualan.bayar)}</span>
                            </div>
                            <div className="flex justify-between items-center mb-4">
                                <span className="text-sm">Kembalian:</span>
                                <span className="text-sm font-medium text-green-600">{formatCurrency(penjualan.kembalian || 0)}</span>
                            </div>
                        </>
                    )}
                </div>

                {/* Footer */}
                <div className="text-center mt-8 pt-6 border-t border-gray-300">
                    <p className="text-sm text-gray-600 mb-2">
                        Terima kasih atas kunjungan Anda!
                    </p>
                    <p className="text-xs text-gray-500">
                        Barang yang sudah dibeli tidak dapat dikembalikan kecuali ada kesepakatan khusus.
                    </p>
                    <div className="mt-4 text-xs text-gray-400">
                        <p>Dicetak pada: {formatDateTime(new Date().toISOString())}</p>
                        <p>Status: {penjualan.status.toUpperCase()}</p>
                    </div>
                </div>

                {/* Print Button (hidden when printing) */}
                <div className="mt-8 text-center print:hidden">
                    <button
                        onClick={() => window.print()}
                        className="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 mr-3"
                    >
                        Print Receipt
                    </button>
                    <button
                        onClick={() => {
                            // Try to close window, if it fails, redirect to penjualan index
                            try {
                                window.close();
                                // If window.close() doesn't work (e.g., not opened by script), redirect
                                setTimeout(() => {
                                    if (!window.closed) {
                                        window.location.href = route('penjualan.index');
                                    }
                                }, 100);
                            } catch (error) {
                                window.location.href = route('penjualan.index');
                            }
                        }}
                        className="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md shadow-sm text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                    >
                        Close
                    </button>
                </div>
            </div>

            <style jsx>{`
                @media print {
                    body {
                        font-size: 12px;
                    }
                    .print\\:hidden {
                        display: none !important;
                    }
                    .print\\:p-0 {
                        padding: 0 !important;
                    }
                    .print\\:max-w-none {
                        max-width: none !important;
                    }
                }
            `}</style>
        </>
    );
}
