import React from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { formatCurrency, formatDateTime } from '@/utils/formatters';
import { BreadcrumbItem, PageProps } from '@/types';
import { 
    FileText, 
    Clock, 
    CheckCircle, 
    AlertCircle, 
    Download, 
    Eye,
    BarChart3,
    TrendingUp,
    Users,
    Calendar
} from 'lucide-react';

interface PdfReport {
    id: number;
    type: 'sales' | 'stock';
    title: string;
    report_date: string;
    file_name: string;
    status: 'pending' | 'approved' | 'rejected';
    created_at: string;
    approved_at?: string;
    generator: {
        id: number;
        name: string;
    };
    approver?: {
        id: number;
        name: string;
    };
}

interface Props extends PageProps {
    summary?: {
        total_sales: number;
        total_transactions: number;
        total_stock_value: number;
        report_count: number;
        pending_approvals: number;
    };
    pending_reports?: PdfReport[];
    recent_reports?: PdfReport[];
    pending_count?: number;
    approved_count?: number;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard Owner', href: '/owner/dashboard' },
];

const statusLabels = {
    'pending': 'Menunggu Persetujuan',
    'approved': 'Disetujui',
    'rejected': 'Ditolak',
};

const statusColors = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
};

const typeLabels = {
    'sales': 'Laporan Penjualan',
    'stock': 'Laporan Stok',
};

export default function OwnerDashboard({
    summary = {
        total_sales: 0,
        total_transactions: 0,
        total_stock_value: 0,
        report_count: 0,
        pending_approvals: 0
    },
    pending_reports = [],
    recent_reports = [],
    pending_count = 0,
    approved_count = 0
}: Props) {
    const handleApproveReport = (reportId: number) => {
        router.post(`/owner/reports/${reportId}/approve`, {
            action: 'approve',
            approval_notes: 'Laporan disetujui'
        });
    };

    const handleRejectReport = (reportId: number) => {
        router.post(`/owner/reports/${reportId}/approve`, {
            action: 'reject',
            approval_notes: 'Laporan ditolak'
        });
    };

    const handleDownloadReport = (reportId: number) => {
        window.open(`/owner/download-report/${reportId}`, '_blank');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard Owner - Laporan PDF" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Dashboard Owner</h1>
                        <p className="text-gray-600">Kelola dan setujui laporan bulanan dari kasir dan karyawan</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            onClick={() => router.get('/owner/reports')}
                            className="bg-blue-600 hover:bg-blue-700"
                        >
                            <FileText className="w-4 h-4 mr-2" />
                            Semua Laporan
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
                                <AlertCircle className="w-4 h-4" />
                                Menunggu Persetujuan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-yellow-600">{pending_count}</div>
                            <p className="text-xs text-gray-500">Laporan pending</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
                                <CheckCircle className="w-4 h-4" />
                                Laporan Disetujui
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{approved_count}</div>
                            <p className="text-xs text-gray-500">Laporan approved</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
                                <BarChart3 className="w-4 h-4" />
                                Total Laporan
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{summary.report_count}</div>
                            <p className="text-xs text-gray-500">Laporan tersedia</p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600 flex items-center gap-2">
                                <TrendingUp className="w-4 h-4" />
                                Status Sistem
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">Aktif</div>
                            <p className="text-xs text-gray-500">Sistem berjalan normal</p>
                        </CardContent>
                    </Card>
                </div>

                {/* Pending Reports Section */}
                {pending_reports.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Clock className="w-5 h-5 text-yellow-600" />
                                Laporan Menunggu Persetujuan ({pending_count})
                            </CardTitle>
                            <CardDescription>
                                Laporan bulanan yang perlu disetujui atau ditolak
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {pending_reports.map((report) => (
                                    <div key={report.id} className="border rounded-lg p-4 bg-yellow-50">
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-2 mb-2">
                                                    <FileText className="w-4 h-4 text-gray-600" />
                                                    <h3 className="font-semibold">{report.title}</h3>
                                                    <Badge className={statusColors[report.status]}>
                                                        {statusLabels[report.status]}
                                                    </Badge>
                                                </div>
                                                <div className="text-sm text-gray-600 space-y-1">
                                                    <div className="flex items-center gap-2">
                                                        <Users className="w-4 h-4" />
                                                        Dibuat oleh: {report.generator.name}
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="w-4 h-4" />
                                                        Periode: {new Date(report.report_date).toLocaleDateString('id-ID', { 
                                                            year: 'numeric', 
                                                            month: 'long' 
                                                        })}
                                                    </div>
                                                    <div className="flex items-center gap-2">
                                                        <Clock className="w-4 h-4" />
                                                        Dibuat: {formatDateTime(report.created_at)}
                                                    </div>
                                                </div>
                                            </div>
                                            <div className="flex gap-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleDownloadReport(report.id)}
                                                >
                                                    <Eye className="w-4 h-4 mr-1" />
                                                    Lihat
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    onClick={() => handleApproveReport(report.id)}
                                                    className="bg-green-600 hover:bg-green-700"
                                                >
                                                    <CheckCircle className="w-4 h-4 mr-1" />
                                                    Setujui
                                                </Button>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => handleRejectReport(report.id)}
                                                >
                                                    Tolak
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Recent Approved Reports */}
                {recent_reports.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <CheckCircle className="w-5 h-5 text-green-600" />
                                Laporan Terbaru yang Disetujui
                            </CardTitle>
                            <CardDescription>
                                5 laporan terakhir yang sudah disetujui
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-3">
                                {recent_reports.map((report) => (
                                    <div key={report.id} className="flex justify-between items-center p-3 border rounded-lg bg-green-50">
                                        <div className="flex items-center gap-3">
                                            <FileText className="w-4 h-4 text-green-600" />
                                            <div>
                                                <p className="font-medium">{report.title}</p>
                                                <p className="text-sm text-gray-600">
                                                    {report.generator.name} • {new Date(report.report_date).toLocaleDateString('id-ID', { 
                                                        year: 'numeric', 
                                                        month: 'long' 
                                                    })}
                                                </p>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge className={statusColors[report.status]}>
                                                {statusLabels[report.status]}
                                            </Badge>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => handleDownloadReport(report.id)}
                                            >
                                                <Download className="w-4 h-4 mr-1" />
                                                Download
                                            </Button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Empty State */}
                {pending_reports.length === 0 && recent_reports.length === 0 && (
                    <Card>
                        <CardContent className="text-center py-12">
                            <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                            <h3 className="text-lg font-medium text-gray-900 mb-2">Belum Ada Laporan</h3>
                            <p className="text-gray-600 mb-4">
                                Belum ada laporan bulanan yang dibuat oleh kasir atau karyawan.
                            </p>
                            <Button
                                onClick={() => router.get('/owner/reports')}
                                variant="outline"
                            >
                                <FileText className="w-4 h-4 mr-2" />
                                Lihat Semua Laporan
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
