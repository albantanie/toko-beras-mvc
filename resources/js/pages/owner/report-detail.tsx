import React from 'react';
import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { 
    ArrowLeft, 
    FileText, 
    User, 
    Calendar, 
    CheckCircle, 
    XCircle,
    Download,
    DollarSign,
    Package,
    TrendingUp,
    BarChart3
} from 'lucide-react';

interface ReportData {
    summary?: {
        total_sales?: number;
        total_transactions?: number;
        total_stock_value?: number;
        total_movements?: number;
        period?: {
            month_name?: string;
            year?: number;
        };
    };
    transactions?: Array<{
        id: number;
        tanggal: string;
        total_amount: number;
        items_count: number;
        customer_name?: string;
    }>;
    stock_movements?: Array<{
        id: number;
        product_name: string;
        movement_type: string;
        quantity: number;
        price: number;
        total_value: number;
        date: string;
    }>;
}

interface Report {
    id: number;
    title: string;
    type: string;
    report_date: string;
    status: string;
    created_at: string;
    approval_notes?: string;
    generator: {
        name: string;
        email: string;
    };
    approver?: {
        name: string;
        email: string;
    };
}

interface Props {
    report: Report;
    report_data: ReportData | null;
}

const formatCurrency = (amount: number): string => {
    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        minimumFractionDigits: 0,
        maximumFractionDigits: 0,
    }).format(amount);
};

const getStatusColor = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'bg-yellow-100 text-yellow-800';
        case 'approved':
            return 'bg-green-100 text-green-800';
        case 'rejected':
            return 'bg-red-100 text-red-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
};

const getStatusLabel = (status: string): string => {
    switch (status) {
        case 'pending':
            return 'Menunggu Persetujuan';
        case 'approved':
            return 'Disetujui';
        case 'rejected':
            return 'Ditolak';
        default:
            return status;
    }
};

export default function ReportDetail({ report, report_data }: Props) {
    const isTransactionReport = report.type === 'transaction';
    const isStockReport = report.type === 'stock';

    const handleApprove = () => {
        if (confirm('Apakah Anda yakin ingin menyetujui laporan ini?')) {
            router.post(`/owner/reports/${report.id}/approve`, {
                action: 'approve',
                approval_notes: 'Laporan disetujui'
            });
        }
    };

    const handleReject = () => {
        const notes = prompt('Masukkan alasan penolakan (opsional):');
        if (notes !== null) {
            router.post(`/owner/reports/${report.id}/approve`, {
                action: 'reject',
                approval_notes: notes || 'Laporan ditolak'
            });
        }
    };

    const handleDownload = () => {
        window.open(`/owner/download-report/${report.id}`, '_blank');
    };

    return (
        <AppLayout>
            <Head title={`Detail ${report.title}`} />
            
            <div className="max-w-6xl mx-auto py-8 space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={() => router.get('/owner/dashboard')}
                        >
                            <ArrowLeft className="w-4 h-4 mr-2" />
                            Kembali ke Dashboard
                        </Button>
                        <div>
                            <div className="flex items-center gap-3 mb-2">
                                <FileText className="w-8 h-8 text-blue-600" />
                                <h1 className="text-2xl font-bold">{report.title}</h1>
                            </div>
                            <p className="text-gray-600">
                                {format(new Date(report.report_date), 'dd MMMM yyyy', { locale: id })}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center gap-3">
                        <Badge className={getStatusColor(report.status)}>
                            {getStatusLabel(report.status)}
                        </Badge>
                        {report.status === 'pending' && (
                            <div className="flex gap-2">
                                <Button
                                    onClick={handleApprove}
                                    className="bg-green-600 hover:bg-green-700"
                                >
                                    <CheckCircle className="w-4 h-4 mr-2" />
                                    Setujui
                                </Button>
                                <Button
                                    variant="destructive"
                                    onClick={handleReject}
                                >
                                    <XCircle className="w-4 h-4 mr-2" />
                                    Tolak
                                </Button>
                            </div>
                        )}
                        <Button
                            variant="outline"
                            onClick={handleDownload}
                        >
                            <Download className="w-4 h-4 mr-2" />
                            Download PDF
                        </Button>
                    </div>
                </div>

                {/* Report Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <User className="w-5 h-5" />
                            Informasi Laporan
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <p className="text-sm text-gray-600">Dibuat oleh</p>
                                <p className="font-medium">{report.generator.name}</p>
                                <p className="text-sm text-gray-500">{report.generator.email}</p>
                            </div>
                            <div>
                                <p className="text-sm text-gray-600">Tanggal Dibuat</p>
                                <p className="font-medium">
                                    {format(new Date(report.created_at), 'dd MMMM yyyy HH:mm', { locale: id })}
                                </p>
                            </div>
                            {report.approver && (
                                <>
                                    <div>
                                        <p className="text-sm text-gray-600">Disetujui oleh</p>
                                        <p className="font-medium">{report.approver.name}</p>
                                        <p className="text-sm text-gray-500">{report.approver.email}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-600">Catatan</p>
                                        <p className="font-medium">{report.approval_notes || '-'}</p>
                                    </div>
                                </>
                            )}
                        </div>
                    </CardContent>
                </Card>

                {/* Summary Cards */}
                {report_data?.summary && (
                    <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                        {isTransactionReport && (
                            <>
                                <Card>
                                    <CardContent className="p-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm text-gray-600">Total Penjualan</p>
                                                <p className="text-2xl font-bold text-green-600">
                                                    {formatCurrency(report_data.summary.total_sales || 0)}
                                                </p>
                                            </div>
                                            <DollarSign className="w-8 h-8 text-green-600" />
                                        </div>
                                    </CardContent>
                                </Card>
                                <Card>
                                    <CardContent className="p-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm text-gray-600">Total Transaksi</p>
                                                <p className="text-2xl font-bold text-blue-600">
                                                    {report_data.summary.total_transactions || 0}
                                                </p>
                                            </div>
                                            <BarChart3 className="w-8 h-8 text-blue-600" />
                                        </div>
                                    </CardContent>
                                </Card>
                            </>
                        )}
                        {isStockReport && (
                            <>
                                <Card>
                                    <CardContent className="p-6">
                                        <div className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm text-gray-600">Total Pergerakan</p>
                                                <p className="text-2xl font-bold text-blue-600">
                                                    {report_data.summary.total_movements || 0}
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
                                                    {formatCurrency(report_data.summary.total_stock_value || 0)}
                                                </p>
                                            </div>
                                            <TrendingUp className="w-8 h-8 text-green-600" />
                                        </div>
                                    </CardContent>
                                </Card>
                            </>
                        )}
                    </div>
                )}

                {/* Jika tidak ada summary data */}
                {!report_data?.summary && (
                    <Card>
                        <CardContent className="p-6 text-center">
                            <div className="text-gray-500">
                                <BarChart3 className="w-12 h-12 mx-auto mb-4 text-gray-400" />
                                <p>Data ringkasan tidak tersedia</p>
                                <p className="text-sm">Detail lengkap tersedia dalam file PDF</p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Detailed Data Preview */}
                <Card>
                    <CardHeader>
                        <CardTitle>
                            {isTransactionReport ? 'Preview Data Transaksi' : 'Preview Data Stok'}
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="text-center py-8 text-gray-500">
                            <FileText className="w-12 h-12 mx-auto mb-4 text-gray-400" />
                            <p className="mb-2">Detail data lengkap tersedia dalam file PDF</p>
                            <Button
                                variant="outline"
                                onClick={handleDownload}
                                className="mt-2"
                            >
                                <Download className="w-4 h-4 mr-2" />
                                Download PDF untuk Melihat Detail
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
