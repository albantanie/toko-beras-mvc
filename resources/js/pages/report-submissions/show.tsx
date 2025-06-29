import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Clock, CheckCircle, XCircle, FileText, User, Calendar, Eye, ArrowLeft, Send, Check, X } from 'lucide-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';
import Swal from 'sweetalert2';

interface ReportSubmission {
    id: number;
    title: string;
    type: string;
    status: string;
    period_from: string;
    period_to: string;
    created_at: string;
    submitted_at?: string;
    crosschecked_at?: string;
    approved_at?: string;
    submitter: {
        name: string;
        email: string;
    };
    crosschecker?: {
        name: string;
    };
    approver?: {
        name: string;
    };
    submission_notes?: string;
    crosscheck_notes?: string;
    approval_notes?: string;
    summary?: string;
    data?: any;
}

interface Props {
    report: ReportSubmission;
    pdfPaths: Record<string, string>;
}

const breadcrumbs = [
    { title: 'Laporan', href: '/laporan' },
    { title: 'Laporan Saya', href: '/report-submissions/my-reports' },
    { title: 'Detail Laporan', href: '#' },
];

export default function ShowReport({ report, pdfPaths }: Props) {
    const [isSubmitDialogOpen, setIsSubmitDialogOpen] = useState(false);
    const [submissionNotes, setSubmissionNotes] = useState('');

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'draft':
                return <Badge variant="secondary" className="bg-gray-100 text-gray-800"><Clock className="w-3 h-3 mr-1" />Draft</Badge>;
            case 'submitted':
                return <Badge variant="secondary" className="bg-blue-100 text-blue-800"><Clock className="w-3 h-3 mr-1" />Submitted</Badge>;
            case 'crosscheck_pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800"><Clock className="w-3 h-3 mr-1" />Menunggu Crosscheck</Badge>;
            case 'crosscheck_approved':
                return <Badge variant="secondary" className="bg-green-100 text-green-800"><CheckCircle className="w-3 h-3 mr-1" />Crosscheck Selesai</Badge>;
            case 'crosscheck_rejected':
                return <Badge variant="secondary" className="bg-red-100 text-red-800"><XCircle className="w-3 h-3 mr-1" />Crosscheck Ditolak</Badge>;
            case 'owner_pending':
                return <Badge variant="secondary" className="bg-orange-100 text-orange-800"><Clock className="w-3 h-3 mr-1" />Menunggu Approval</Badge>;
            case 'approved':
                return <Badge variant="secondary" className="bg-green-100 text-green-800"><CheckCircle className="w-3 h-3 mr-1" />Disetujui</Badge>;
            case 'rejected':
                return <Badge variant="secondary" className="bg-red-100 text-red-800"><XCircle className="w-3 h-3 mr-1" />Ditolak</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const getReportTypeLabel = (type: string) => {
        switch (type) {
            case 'penjualan_summary': return 'Laporan Ringkasan Penjualan';
            case 'penjualan_detail': return 'Laporan Detail Penjualan';
            case 'transaksi_harian': return 'Laporan Transaksi Harian';
            case 'transaksi_kasir': return 'Laporan Performa Kasir';
            case 'barang_stok': return 'Laporan Stok Barang';
            case 'barang_movement': return 'Laporan Pergerakan Barang';
            case 'barang_performance': return 'Laporan Performa Barang';
            case 'keuangan': return 'Laporan Keuangan';
            default: return type;
        }
    };

    const handleSubmit = () => {
        router.patch(route('report-submissions.submit', report.id), {
            submission_notes: submissionNotes,
        }, {
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Laporan berhasil disubmit untuk crosscheck',
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                setIsSubmitDialogOpen(false);
            },
            onError: () => {
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat submit laporan.',
                    icon: 'error'
                });
            }
        });
    };

    const canSubmit = report.status === 'draft';
    const canEdit = report.status === 'draft' || report.status === 'crosscheck_rejected' || report.status === 'rejected';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Detail Laporan - ${report.title}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">{report.title}</h1>
                                <p className="text-gray-600">Detail laporan dan status approval</p>
                            </div>
                            <div className="flex items-center gap-2">
                                <Link href={route('report-submissions.my-reports')}>
                                    <Button variant="outline">
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Kembali
                                    </Button>
                                </Link>
                                {canSubmit && (
                                    <Button onClick={() => setIsSubmitDialogOpen(true)}>
                                        <Send className="w-4 h-4 mr-2" />
                                        Submit untuk Crosscheck
                                    </Button>
                                )}
                                {canEdit && (
                                    <Link href={route('report-submissions.edit', report.id)}>
                                        <Button variant="outline">
                                            <FileText className="w-4 h-4 mr-2" />
                                            Edit
                                        </Button>
                                    </Link>
                                )}
                            </div>
                        </div>
                    </div>

                    <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        {/* Main Content */}
                        <div className="lg:col-span-2 space-y-6">
                            {/* Report Details */}
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <FileText className="w-5 h-5" />
                                        Informasi Laporan
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div className="flex items-center gap-3">
                                        <h3 className="text-lg font-semibold text-gray-900">{report.title}</h3>
                                        {getStatusBadge(report.status)}
                                    </div>
                                    
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="font-medium text-gray-700">Jenis Laporan:</span>
                                            <p className="text-gray-900">{getReportTypeLabel(report.type)}</p>
                                        </div>
                                        <div>
                                            <span className="font-medium text-gray-700">Status:</span>
                                            <p className="text-gray-900">{getStatusBadge(report.status)}</p>
                                        </div>
                                        <div>
                                            <span className="font-medium text-gray-700">Periode Dari:</span>
                                            <p className="text-gray-900">{format(new Date(report.period_from), 'dd MMMM yyyy', { locale: id })}</p>
                                        </div>
                                        <div>
                                            <span className="font-medium text-gray-700">Periode Sampai:</span>
                                            <p className="text-gray-900">{format(new Date(report.period_to), 'dd MMMM yyyy', { locale: id })}</p>
                                        </div>
                                    </div>

                                    {report.summary && (
                                        <div>
                                            <span className="font-medium text-gray-700">Ringkasan:</span>
                                            <p className="text-gray-900 mt-1">{report.summary}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>

                            {/* PDF Documents */}
                            {Object.keys(pdfPaths).length > 0 && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle className="flex items-center gap-2">
                                            <FileText className="w-5 h-5" />
                                            Dokumen PDF
                                        </CardTitle>
                                        <CardDescription>
                                            Download laporan dalam format PDF
                                        </CardDescription>
                                    </CardHeader>
                                    <CardContent>
                                        <div className="space-y-3">
                                            {pdfPaths.penjualan && (
                                                <div className="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                                                            <FileText className="w-5 h-5 text-blue-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-blue-900">Laporan Penjualan</p>
                                                            <p className="text-sm text-blue-700">
                                                                Periode {format(new Date(report.period_from), 'dd MMM yyyy', { locale: id })} - {format(new Date(report.period_to), 'dd MMM yyyy', { locale: id })}
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <a 
                                                        href={route('report-submissions.download-pdf', pdfPaths.penjualan)}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-blue-700 bg-white border border-blue-300 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                                                    >
                                                        <FileText className="w-4 h-4 mr-2" />
                                                        Download
                                                    </a>
                                                </div>
                                            )}
                                            
                                            {pdfPaths.stok && (
                                                <div className="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                                                    <div className="flex items-center gap-3">
                                                        <div className="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                                                            <FileText className="w-5 h-5 text-green-600" />
                                                        </div>
                                                        <div>
                                                            <p className="font-medium text-green-900">Laporan Stok Barang</p>
                                                            <p className="text-sm text-green-700">
                                                                Status stok terkini
                                                            </p>
                                                        </div>
                                                    </div>
                                                    <a 
                                                        href={route('report-submissions.download-pdf', pdfPaths.stok)}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center px-3 py-2 text-sm font-medium text-green-700 bg-white border border-green-300 rounded-md hover:bg-green-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                    >
                                                        <FileText className="w-4 h-4 mr-2" />
                                                        Download
                                                    </a>
                                                </div>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Timeline */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Timeline Laporan</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-4">
                                        <div className="flex items-start gap-3">
                                            <div className="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                                <FileText className="w-4 h-4 text-blue-600" />
                                            </div>
                                            <div className="flex-1">
                                                <p className="font-medium text-gray-900">Laporan Dibuat</p>
                                                <p className="text-sm text-gray-600">
                                                    {format(new Date(report.created_at), 'dd MMM yyyy HH:mm', { locale: id })} oleh {report.submitter.name}
                                                </p>
                                            </div>
                                        </div>

                                        {report.submitted_at && (
                                            <div className="flex items-start gap-3">
                                                <div className="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                                    <Send className="w-4 h-4 text-yellow-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="font-medium text-gray-900">Disubmit untuk Crosscheck</p>
                                                    <p className="text-sm text-gray-600">
                                                        {format(new Date(report.submitted_at), 'dd MMM yyyy HH:mm', { locale: id })} oleh {report.submitter.name}
                                                    </p>
                                                </div>
                                            </div>
                                        )}

                                        {report.crosschecked_at && (
                                            <div className="flex items-start gap-3">
                                                <div className="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                                    <Check className="w-4 h-4 text-green-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="font-medium text-gray-900">Crosscheck Selesai</p>
                                                    <p className="text-sm text-gray-600">
                                                        {format(new Date(report.crosschecked_at), 'dd MMM yyyy HH:mm', { locale: id })} oleh {report.crosschecker?.name}
                                                    </p>
                                                </div>
                                            </div>
                                        )}

                                        {report.approved_at && (
                                            <div className="flex items-start gap-3">
                                                <div className="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                                    <CheckCircle className="w-4 h-4 text-purple-600" />
                                                </div>
                                                <div className="flex-1">
                                                    <p className="font-medium text-gray-900">Approval Final</p>
                                                    <p className="text-sm text-gray-600">
                                                        {format(new Date(report.approved_at), 'dd MMM yyyy HH:mm', { locale: id })} oleh {report.approver?.name}
                                                    </p>
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Notes */}
                            {(report.submission_notes || report.crosscheck_notes || report.approval_notes) && (
                                <Card>
                                    <CardHeader>
                                        <CardTitle>Catatan</CardTitle>
                                    </CardHeader>
                                    <CardContent className="space-y-4">
                                        {report.submission_notes && (
                                            <div className="p-3 bg-blue-50 rounded-md">
                                                <p className="text-sm font-medium text-blue-800 mb-1">Catatan Submitter:</p>
                                                <p className="text-sm text-blue-700">{report.submission_notes}</p>
                                            </div>
                                        )}
                                        {report.crosscheck_notes && (
                                            <div className="p-3 bg-green-50 rounded-md">
                                                <p className="text-sm font-medium text-green-800 mb-1">Catatan Crosscheck:</p>
                                                <p className="text-sm text-green-700">{report.crosscheck_notes}</p>
                                            </div>
                                        )}
                                        {report.approval_notes && (
                                            <div className="p-3 bg-purple-50 rounded-md">
                                                <p className="text-sm font-medium text-purple-800 mb-1">Catatan Approval:</p>
                                                <p className="text-sm text-purple-700">{report.approval_notes}</p>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            )}
                        </div>

                        {/* Sidebar */}
                        <div className="space-y-6">
                            {/* Status Card */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Status</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-center">
                                        {getStatusBadge(report.status)}
                                    </div>
                                </CardContent>
                            </Card>

                            {/* People Involved */}
                            <Card>
                                <CardHeader>
                                    <CardTitle>Orang Terlibat</CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <div>
                                        <p className="text-sm font-medium text-gray-700">Submitter</p>
                                        <p className="text-sm text-gray-900">{report.submitter.name}</p>
                                        <p className="text-xs text-gray-500">{report.submitter.email}</p>
                                    </div>
                                    {report.crosschecker && (
                                        <div>
                                            <p className="text-sm font-medium text-gray-700">Crosschecker</p>
                                            <p className="text-sm text-gray-900">{report.crosschecker.name}</p>
                                        </div>
                                    )}
                                    {report.approver && (
                                        <div>
                                            <p className="text-sm font-medium text-gray-700">Approver</p>
                                            <p className="text-sm text-gray-900">{report.approver.name}</p>
                                        </div>
                                    )}
                                </CardContent>
                            </Card>
                        </div>
                    </div>
                </div>
            </div>

            {/* Submit Dialog */}
            <Dialog open={isSubmitDialogOpen} onOpenChange={setIsSubmitDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Submit Laporan untuk Crosscheck</DialogTitle>
                        <DialogDescription>
                            Submit laporan "{report.title}" untuk proses crosscheck oleh kasir/karyawan lain
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-700 mb-1 block">Catatan (Opsional)</label>
                            <Textarea
                                value={submissionNotes}
                                onChange={(e) => setSubmissionNotes(e.target.value)}
                                placeholder="Tambahkan catatan untuk crosschecker..."
                                rows={3}
                                className="w-full"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsSubmitDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button onClick={handleSubmit}>
                            <Send className="w-4 h-4 mr-2" />
                            Submit
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
} 