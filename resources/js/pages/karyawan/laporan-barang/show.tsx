import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { ArrowLeft, Send, Clock, CheckCircle, XCircle, AlertCircle, FileText, Calendar, User } from 'lucide-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import Swal from 'sweetalert2';

interface Report {
    id: number;
    title: string;
    type: string;
    status: 'draft' | 'pending_approval' | 'approved' | 'rejected';
    period_from: string;
    period_to: string;
    data: any;
    summary?: string;
    created_at: string;
    generator: {
        name: string;
    };
    approver?: {
        name: string;
    };
    approved_at?: string;
    approval_notes?: string;
}

interface Props {
    report: Report;
}

/**
 * Halaman detail laporan barang untuk karyawan
 * Menampilkan detail laporan dan opsi untuk submit approval
 */
export default function ShowLaporanBarang({ report }: Props) {
    // Handle submit for approval
    const handleSubmitForApproval = () => {
        Swal.fire({
            title: 'Submit untuk Approval?',
            text: 'Laporan akan dikirim ke Owner untuk disetujui. Anda tidak dapat mengedit laporan setelah disubmit.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Ya, Submit!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                router.patch(route('karyawan.laporan.barang.submit', report.id), {}, {
                    onSuccess: () => {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Laporan berhasil disubmit untuk approval.',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    onError: () => {
                        Swal.fire({
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat submit laporan.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    };

    // Get status badge
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'draft':
                return <Badge variant="secondary"><Clock className="w-3 h-3 mr-1" />Draft</Badge>;
            case 'pending_approval':
                return <Badge variant="outline"><AlertCircle className="w-3 h-3 mr-1" />Menunggu Approval</Badge>;
            case 'approved':
                return <Badge variant="default" className="bg-green-500"><CheckCircle className="w-3 h-3 mr-1" />Disetujui</Badge>;
            case 'rejected':
                return <Badge variant="destructive"><XCircle className="w-3 h-3 mr-1" />Ditolak</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    // Get report type label
    const getReportTypeLabel = (type: string) => {
        switch (type) {
            case 'barang_stok':
                return 'Laporan Stok Barang';
            case 'barang_movement':
                return 'Laporan Pergerakan Barang';
            case 'barang_performance':
                return 'Laporan Performa Barang';
            default:
                return type;
        }
    };

    // Render report data based on type
    const renderReportData = () => {
        if (!report.data) return null;

        switch (report.type) {
            case 'barang_stok':
                return (
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-blue-600">
                                        {report.data.summary?.total_items || 0}
                                    </div>
                                    <div className="text-sm text-gray-600">Total Item</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-green-600">
                                        {report.data.summary?.in_stock_items || 0}
                                    </div>
                                    <div className="text-sm text-gray-600">Item Tersedia</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-yellow-600">
                                        {report.data.summary?.low_stock_items || 0}
                                    </div>
                                    <div className="text-sm text-gray-600">Stok Rendah</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-red-600">
                                        {report.data.summary?.out_of_stock_items || 0}
                                    </div>
                                    <div className="text-sm text-gray-600">Stok Habis</div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                );

            case 'barang_movement':
                return (
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-blue-600">
                                        {report.data.summary?.total_items_tracked || 0}
                                    </div>
                                    <div className="text-sm text-gray-600">Item Terpantau</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-green-600">
                                        {report.data.summary?.total_quantity_sold || 0}
                                    </div>
                                    <div className="text-sm text-gray-600">Total Terjual</div>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent className="p-4">
                                    <div className="text-2xl font-bold text-purple-600">
                                        {formatCurrency(report.data.summary?.total_movement_value || 0)}
                                    </div>
                                    <div className="text-sm text-gray-600">Nilai Pergerakan</div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                );

            case 'barang_performance':
                return (
                    <div className="space-y-4">
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Top Performing Items</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {report.data.summary?.top_moving_items?.slice(0, 5).map((item: any, index: number) => (
                                        <div key={index} className="flex justify-between items-center py-2 border-b last:border-b-0">
                                            <span className="text-sm">{item.nama}</span>
                                            <span className="text-sm font-medium">{item.total_sold} terjual</span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-lg">Slow Moving Items</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    {report.data.summary?.slow_moving_items?.slice(0, 5).map((item: any, index: number) => (
                                        <div key={index} className="flex justify-between items-center py-2 border-b last:border-b-0">
                                            <span className="text-sm">{item.nama}</span>
                                            <span className="text-sm font-medium">{item.total_sold} terjual</span>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                );

            default:
                return (
                    <div className="p-4 bg-gray-50 rounded-md">
                        <p className="text-sm text-gray-600">Data laporan tidak dapat ditampilkan.</p>
                    </div>
                );
        }
    };

    return (
        <AppLayout>
            <Head title={`${report.title} - Laporan Barang`} />

            <div className="py-6">
                <div className="max-w-6xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center gap-4 mb-4">
                            <Link href={route('karyawan.laporan.barang.index')}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Kembali
                                </Button>
                            </Link>
                            <div className="flex-1">
                                <div className="flex items-center gap-3 mb-2">
                                    <h1 className="text-2xl font-bold text-gray-900">{report.title}</h1>
                                    {getStatusBadge(report.status)}
                                </div>
                                <p className="text-gray-600">{getReportTypeLabel(report.type)}</p>
                            </div>
                            {report.status === 'draft' && (
                                <Button onClick={handleSubmitForApproval}>
                                    <Send className="w-4 h-4 mr-2" />
                                    Submit untuk Approval
                                </Button>
                            )}
                        </div>
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
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <div className="flex items-center gap-2 text-gray-600 mb-1">
                                        <Calendar className="w-4 h-4" />
                                        Periode
                                    </div>
                                    <div className="font-medium">
                                        {report.type === 'barang_stok' ? 'Snapshot saat ini' : 
                                         `${format(new Date(report.period_from), 'dd MMM yyyy', { locale: id })} - ${format(new Date(report.period_to), 'dd MMM yyyy', { locale: id })}`}
                                    </div>
                                </div>
                                <div>
                                    <div className="flex items-center gap-2 text-gray-600 mb-1">
                                        <User className="w-4 h-4" />
                                        Dibuat oleh
                                    </div>
                                    <div className="font-medium">{report.generator.name}</div>
                                </div>
                                <div>
                                    <div className="text-gray-600 mb-1">Tanggal Dibuat</div>
                                    <div className="font-medium">
                                        {format(new Date(report.created_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                    </div>
                                </div>
                                {report.approved_at && (
                                    <div>
                                        <div className="text-gray-600 mb-1">Tanggal Disetujui</div>
                                        <div className="font-medium">
                                            {format(new Date(report.approved_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                        </div>
                                        {report.approver && (
                                            <div className="text-sm text-gray-500">oleh {report.approver.name}</div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Approval Notes */}
                    {report.approval_notes && (
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle className="text-lg">Catatan Approval</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className={`p-4 rounded-md ${
                                    report.status === 'approved' ? 'bg-green-50 text-green-800' : 'bg-red-50 text-red-800'
                                }`}>
                                    <p>{report.approval_notes}</p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Report Data */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Data Laporan</CardTitle>
                            <CardDescription>
                                Ringkasan dan detail data laporan barang
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {renderReportData()}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
