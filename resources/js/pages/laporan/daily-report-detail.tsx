import React from 'react';
import { Head, Link } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, FileText, Calendar, User, TrendingUp, Package, BarChart3, Clock, CreditCard, DollarSign, ShoppingCart, Truck, AlertTriangle, CheckCircle } from 'lucide-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

interface DailyReport {
    id: number;
    report_date: string;
    type: 'transaction' | 'stock';
    total_amount?: number;
    total_transactions?: number;
    total_items_sold?: number;
    total_stock_movements?: number;
    total_stock_value?: number;
    status: 'draft' | 'completed';
    notes?: string;
    data: any;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
}

interface Props {
    report: DailyReport;
}

// Transaction Detail Component
const TransactionDetailSection = ({ report, formatCurrency }: { report: DailyReport; formatCurrency: (amount: number) => string }) => {
    const data = report.data;

    return (
        <div className="space-y-6">
            {/* Summary Section */}
            {data?.summary && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BarChart3 className="w-5 h-5" />
                            Ringkasan Penjualan
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div className="bg-green-50 p-4 rounded-lg">
                                <div className="flex items-center gap-2 mb-2">
                                    <DollarSign className="w-5 h-5 text-green-600" />
                                    <span className="font-medium text-green-800">Total Penjualan</span>
                                </div>
                                <p className="text-2xl font-bold text-green-600">
                                    {formatCurrency(data.summary.total_amount || 0)}
                                </p>
                            </div>
                            <div className="bg-blue-50 p-4 rounded-lg">
                                <div className="flex items-center gap-2 mb-2">
                                    <ShoppingCart className="w-5 h-5 text-blue-600" />
                                    <span className="font-medium text-blue-800">Total Transaksi</span>
                                </div>
                                <p className="text-2xl font-bold text-blue-600">
                                    {data.summary.total_transactions || 0}
                                </p>
                            </div>
                            <div className="bg-purple-50 p-4 rounded-lg">
                                <div className="flex items-center gap-2 mb-2">
                                    <Package className="w-5 h-5 text-purple-600" />
                                    <span className="font-medium text-purple-800">Item Terjual</span>
                                </div>
                                <p className="text-2xl font-bold text-purple-600">
                                    {data.summary.total_items_sold || 0}
                                </p>
                            </div>
                        </div>

                        {/* Payment Methods */}
                        {data.summary.payment_methods && (
                            <div className="mt-6">
                                <h4 className="font-semibold mb-3">Metode Pembayaran</h4>
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-3">
                                    <div className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <span className="flex items-center gap-2">
                                            <CreditCard className="w-4 h-4" />
                                            Transfer
                                        </span>
                                        <span className="font-semibold">{data.summary.payment_methods.transfer || 0} transaksi</span>
                                    </div>
                                    <div className="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                        <span className="flex items-center gap-2">
                                            <DollarSign className="w-4 h-4" />
                                            Tunai
                                        </span>
                                        <span className="font-semibold">{data.summary.payment_methods.tunai || 0} transaksi</span>
                                    </div>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Transactions List */}
            {data?.transactions && data.transactions.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <ShoppingCart className="w-5 h-5" />
                            Daftar Transaksi ({data.transactions.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4 max-h-96 overflow-y-auto">
                            {data.transactions.map((transaction: any, index: number) => (
                                <div key={index} className="border rounded-lg p-4 bg-gray-50">
                                    <div className="flex justify-between items-start mb-3">
                                        <div>
                                            <p className="font-semibold text-lg">#{transaction.nomor_transaksi}</p>
                                            <p className="text-sm text-gray-600 flex items-center gap-1">
                                                <Clock className="w-4 h-4" />
                                                {new Date(transaction.tanggal_transaksi).toLocaleString('id-ID')}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <p className="text-lg font-bold text-green-600">
                                                {formatCurrency(transaction.total)}
                                            </p>
                                            <Badge variant="outline" className="mt-1">
                                                {transaction.metode_pembayaran}
                                            </Badge>
                                        </div>
                                    </div>

                                    {/* Transaction Items */}
                                    {transaction.items && transaction.items.length > 0 && (
                                        <div className="mt-3">
                                            <h5 className="font-medium mb-2">Item yang dibeli:</h5>
                                            <div className="space-y-2">
                                                {transaction.items.map((item: any, itemIndex: number) => (
                                                    <div key={itemIndex} className="flex justify-between items-center text-sm bg-white p-2 rounded">
                                                        <span>{item.barang_nama}</span>
                                                        <span>{item.jumlah} x {formatCurrency(item.harga_satuan)} = {formatCurrency(item.subtotal)}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
};

// Stock Detail Component
const StockDetailSection = ({ report, formatCurrency }: { report: DailyReport; formatCurrency: (amount: number) => string }) => {
    const data = report.data;

    return (
        <div className="space-y-6">
            {/* Consistency Check Section */}
            {data?.consistency_check && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            {data.consistency_check.is_consistent ? (
                                <CheckCircle className="w-5 h-5 text-green-600" />
                            ) : (
                                <AlertTriangle className="w-5 h-5 text-red-600" />
                            )}
                            Validasi Konsistensi Data
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className={`p-4 rounded-lg ${data.consistency_check.is_consistent ? 'bg-green-50 border border-green-200' : 'bg-red-50 border border-red-200'}`}>
                            <div className="flex items-center gap-2 mb-2">
                                {data.consistency_check.is_consistent ? (
                                    <CheckCircle className="w-5 h-5 text-green-600" />
                                ) : (
                                    <AlertTriangle className="w-5 h-5 text-red-600" />
                                )}
                                <span className={`font-medium ${data.consistency_check.is_consistent ? 'text-green-800' : 'text-red-800'}`}>
                                    {data.consistency_check.message}
                                </span>
                            </div>
                            <div className="grid grid-cols-2 gap-4 text-sm mt-3">
                                <div>
                                    <span className="text-gray-600">Total Transaksi:</span>
                                    <span className="font-medium ml-2">{data.consistency_check.total_transactions}</span>
                                </div>
                                <div>
                                    <span className="text-gray-600">Inkonsistensi:</span>
                                    <span className={`font-medium ml-2 ${data.consistency_check.total_inconsistencies > 0 ? 'text-red-600' : 'text-green-600'}`}>
                                        {data.consistency_check.total_inconsistencies}
                                    </span>
                                </div>
                            </div>
                        </div>

                        {/* Show inconsistencies if any */}
                        {data.inconsistencies && data.inconsistencies.length > 0 && (
                            <div className="mt-4">
                                <h4 className="font-medium text-red-800 mb-2">Detail Inkonsistensi:</h4>
                                <div className="space-y-2">
                                    {data.inconsistencies.map((inconsistency: any, index: number) => (
                                        <div key={index} className="p-3 bg-red-50 border border-red-200 rounded-lg">
                                            <p className="text-sm text-red-800">{inconsistency.message}</p>
                                            <div className="text-xs text-red-600 mt-1">
                                                Barang: {inconsistency.barang_nama} | Qty: {inconsistency.quantity_sold} karung
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            )}

            {/* Summary Section */}
            {data?.summary && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <BarChart3 className="w-5 h-5" />
                            Ringkasan Stok
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div className="bg-blue-50 p-4 rounded-lg">
                                <div className="flex items-center gap-2 mb-2">
                                    <Truck className="w-5 h-5 text-blue-600" />
                                    <span className="font-medium text-blue-800">Total Pergerakan</span>
                                </div>
                                <p className="text-2xl font-bold text-blue-600">
                                    {data.summary.total_movements || 0}
                                </p>
                            </div>
                            <div className="bg-green-50 p-4 rounded-lg">
                                <div className="flex items-center gap-2 mb-2">
                                    <DollarSign className="w-5 h-5 text-green-600" />
                                    <span className="font-medium text-green-800">Nilai Stok</span>
                                </div>
                                <p className="text-2xl font-bold text-green-600">
                                    {formatCurrency(data.summary.total_value || 0)}
                                </p>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            )}

            {/* Stock Movements List */}
            {data?.movements && data.movements.length > 0 && (
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Package className="w-5 h-5" />
                            Pergerakan Stok ({data.movements.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4 max-h-96 overflow-y-auto">
                            {data.movements.map((movement: any, index: number) => (
                                <div key={index} className="border rounded-lg p-4 bg-gray-50">
                                    <div className="flex justify-between items-start mb-3">
                                        <div>
                                            <p className="font-semibold text-lg">{movement.barang_nama}</p>
                                            <p className="text-sm text-gray-600 flex items-center gap-1">
                                                <Clock className="w-4 h-4" />
                                                {new Date(movement.created_at).toLocaleString('id-ID')}
                                            </p>
                                        </div>
                                        <div className="text-right">
                                            <Badge
                                                variant={movement.quantity > 0 ? 'default' : 'destructive'}
                                                className="mb-2"
                                            >
                                                {movement.type === 'in' ? 'Stock Masuk' :
                                                 movement.type === 'out' ? 'Stock Keluar' :
                                                 movement.type === 'return' ? 'Retur' :
                                                 movement.type === 'damage' ? 'Kerusakan' :
                                                 movement.type === 'adjustment' ? 'Penyesuaian' :
                                                 movement.type === 'correction' ? 'Koreksi' :
                                                 movement.type}
                                            </Badge>
                                            <p className="text-lg font-bold">
                                                {movement.quantity > 0 ? '+' : ''}{movement.quantity} karung
                                            </p>
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="text-gray-600">Stok Sebelum:</span>
                                            <p className="font-medium">{movement.stock_before || 0} karung</p>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Stok Sesudah:</span>
                                            <p className="font-medium">{movement.stock_after || 0} karung</p>
                                        </div>
                                    </div>

                                    {movement.keterangan && (
                                        <div className="mt-3 p-2 bg-white rounded text-sm">
                                            <span className="text-gray-600">Keterangan:</span>
                                            <p>{movement.keterangan}</p>
                                        </div>
                                    )}
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            )}
        </div>
    );
};

export default function DailyReportDetail({ report }: Props) {
    const formatDateTime = (dateString: string) => {
        return format(new Date(dateString), 'dd MMMM yyyy, HH:mm', { locale: id });
    };

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
                return 'bg-blue-100 text-blue-800';
            case 'draft':
                return 'bg-gray-100 text-gray-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusLabel = (status: string) => {
        switch (status) {
            case 'completed':
                return 'Selesai';
            case 'draft':
                return 'Draft';
            default:
                return 'Unknown';
        }
    };

    const isTransactionReport = report.type === 'transaction';
    const reportTitle = isTransactionReport ? 'Detail Laporan Transaksi Harian' : 'Detail Laporan Stok Harian';
    const backUrl = isTransactionReport ? '/kasir/laporan/daily' : '/karyawan/laporan/daily';
    const ReportIcon = isTransactionReport ? TrendingUp : Package;

    return (
        <AppLayout>
            <Head title={reportTitle} />
            <div className="max-w-6xl mx-auto py-8">
                {/* Header */}
                <div className="flex items-center justify-between mb-6">
                    <div className="flex items-center gap-4">
                        <Link href={backUrl}>
                            <Button variant="outline" size="sm">
                                <ArrowLeft className="w-4 h-4 mr-2" />
                                Kembali
                            </Button>
                        </Link>
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <ReportIcon className="w-8 h-8 text-blue-600" />
                                <h1 className="text-2xl font-bold">{reportTitle}</h1>
                            </div>
                            <p className="text-gray-600">
                                {format(new Date(report.report_date), 'dd MMMM yyyy', { locale: id })}
                            </p>
                        </div>
                    </div>
                    <Badge className={getStatusColor(report.status)}>
                        {getStatusLabel(report.status)}
                    </Badge>
                </div>

                {/* Report Info */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="w-5 h-5" />
                            Informasi Laporan
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <div className="text-sm text-gray-600 font-medium">Tanggal Laporan</div>
                                <div className="text-lg font-semibold">
                                    {format(new Date(report.report_date), 'dd MMMM yyyy', { locale: id })}
                                </div>
                            </div>
                            <div>
                                <div className="text-sm text-gray-600 font-medium">Dibuat Oleh</div>
                                <div className="text-lg font-semibold flex items-center gap-2">
                                    <User className="w-4 h-4" />
                                    {report.user.name}
                                </div>
                            </div>
                            <div>
                                <div className="text-sm text-gray-600 font-medium">Dibuat Pada</div>
                                <div className="text-lg font-semibold flex items-center gap-2">
                                    <Calendar className="w-4 h-4" />
                                    {formatDateTime(report.created_at)}
                                </div>
                            </div>
                        </div>
                        {report.notes && (
                            <div className="mt-4 p-3 bg-gray-50 rounded-lg">
                                <div className="text-sm text-gray-600 font-medium mb-1">Catatan</div>
                                <div className="text-sm">{report.notes}</div>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                {isTransactionReport ? (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm text-gray-600">Total Penjualan</p>
                                        <p className="text-2xl font-bold text-green-600">
                                            {formatCurrency(report.total_amount || 0)}
                                        </p>
                                    </div>
                                    <TrendingUp className="w-8 h-8 text-green-600" />
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm text-gray-600">Total Transaksi</p>
                                        <p className="text-2xl font-bold text-blue-600">
                                            {report.total_transactions || 0}
                                        </p>
                                    </div>
                                    <BarChart3 className="w-8 h-8 text-blue-600" />
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm text-gray-600">Item Terjual</p>
                                        <p className="text-2xl font-bold text-purple-600">
                                            {report.total_items_sold || 0}
                                        </p>
                                    </div>
                                    <Package className="w-8 h-8 text-purple-600" />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm text-gray-600">Total Pergerakan Stok</p>
                                        <p className="text-2xl font-bold text-blue-600">
                                            {report.total_stock_movements || 0}
                                        </p>
                                    </div>
                                    <Package className="w-8 h-8 text-blue-600" />
                                </div>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardContent className="p-6">
                                <div className="flex items-center justify-between">
                                    <div>
                                        <p className="text-sm text-gray-600">Nilai Stok</p>
                                        <p className="text-2xl font-bold text-green-600">
                                            {formatCurrency(report.total_stock_value || 0)}
                                        </p>
                                    </div>
                                    <TrendingUp className="w-8 h-8 text-green-600" />
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                )}

                {/* Detailed Data */}
                {isTransactionReport ? (
                    <TransactionDetailSection report={report} formatCurrency={formatCurrency} />
                ) : (
                    <StockDetailSection report={report} formatCurrency={formatCurrency} />
                )}
            </div>
        </AppLayout>
    );
}
