import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Clock, CheckCircle, XCircle, FileText, User, Calendar, Eye, Check, X, Search, Filter } from 'lucide-react';
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
}

interface Props {
    reports: {
        data: ReportSubmission[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
        links: Array<{
            url: string | null;
            label: string;
            active: boolean;
        }>;
    };
    filters: {
        status: string;
        type: string;
        search: string;
        date_from: string;
        date_to: string;
        submitter: string;
        crosschecker: string;
    };
}

const breadcrumbs = [
    { title: 'Laporan', href: '/laporan' },
    { title: 'Owner Approval', href: '/report-submissions/owner-approval-list' },
];

export default function OwnerApprovalList({ reports, filters }: Props) {
    const [selectedReport, setSelectedReport] = useState<ReportSubmission | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [isApproveDialogOpen, setIsApproveDialogOpen] = useState(false);
    const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'owner_pending':
                return <Badge variant="secondary" className="bg-orange-100 text-orange-800"><Clock className="w-3 h-3 mr-1" />Menunggu Approval</Badge>;
            case 'approved':
                return <Badge variant="secondary" className="bg-green-100 text-green-800"><CheckCircle className="w-3 h-3 mr-1" />Disetujui</Badge>;
            case 'rejected':
                return <Badge variant="secondary" className="bg-red-100 text-red-800"><XCircle className="w-3 h-3 mr-1" />Ditolak</Badge>;
            case 'crosscheck_pending':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800"><Clock className="w-3 h-3 mr-1" />Menunggu Crosscheck</Badge>;
            case 'crosscheck_approved':
                return <Badge variant="secondary" className="bg-blue-100 text-blue-800"><CheckCircle className="w-3 h-3 mr-1" />Crosscheck Selesai</Badge>;
            case 'crosscheck_rejected':
                return <Badge variant="secondary" className="bg-red-100 text-red-800"><XCircle className="w-3 h-3 mr-1" />Crosscheck Ditolak</Badge>;
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

    const handleFilterChange = (key: string, value: string) => {
        router.get(route('report-submissions.owner-approval-list'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleAction = (report: ReportSubmission, type: 'approve' | 'reject') => {
        setSelectedReport(report);
        setApprovalNotes('');
        
        if (type === 'approve') {
            setIsApproveDialogOpen(true);
        } else {
            setIsRejectDialogOpen(true);
        }
    };

    const submitAction = () => {
        if (!selectedReport) return;

        const routeName = isApproveDialogOpen ? 'report-submissions.owner-approve' : 'report-submissions.owner-reject';
        const successMessage = isApproveDialogOpen ? 'Laporan berhasil diapprove' : 'Laporan berhasil direject';

        router.patch(route(routeName, selectedReport.id), {
            approval_notes: approvalNotes,
        }, {
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: successMessage,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                setIsApproveDialogOpen(false);
                setIsRejectDialogOpen(false);
            },
            onError: () => {
                Swal.fire({
                    title: 'Gagal!',
                    text: 'Terjadi kesalahan saat memproses laporan.',
                    icon: 'error'
                });
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Owner Approval Laporan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Owner Approval Laporan</h1>
                                <p className="text-gray-600">Review dan approve laporan yang sudah melalui crosscheck</p>
                            </div>
                            <Link href={route('laporan.index')}>
                                <Button variant="outline">
                                    <Eye className="w-4 h-4 mr-2" />
                                    Lihat Dashboard
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <Card className="mb-6">
                        <CardContent className="p-4">
                            <div className="space-y-4">
                                {/* Search and Basic Filters */}
                                <div className="flex items-center gap-4">
                                    <div className="flex items-center gap-2">
                                        <Filter className="w-4 h-4 text-gray-500" />
                                        <span className="text-sm font-medium text-gray-700">Filter:</span>
                                    </div>
                                    
                                    {/* Search */}
                                    <div className="flex-1 max-w-md">
                                        <div className="relative">
                                            <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 w-4 h-4 text-gray-400" />
                                            <Input
                                                type="text"
                                                placeholder="Cari laporan..."
                                                value={filters.search}
                                                onChange={(e) => handleFilterChange('search', e.target.value)}
                                                className="pl-10"
                                            />
                                        </div>
                                    </div>

                                    {/* Status Filter */}
                                    <Select value={filters.status} onValueChange={val => handleFilterChange('status', val)}>
                                        <SelectTrigger className="w-48">
                                            <SelectValue placeholder="Status" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Status</SelectItem>
                                            <SelectItem value="owner_pending">Menunggu Approval</SelectItem>
                                            <SelectItem value="approved">Disetujui</SelectItem>
                                            <SelectItem value="rejected">Ditolak</SelectItem>
                                            <SelectItem value="crosscheck_pending">Menunggu Crosscheck</SelectItem>
                                            <SelectItem value="crosscheck_approved">Crosscheck Selesai</SelectItem>
                                            <SelectItem value="crosscheck_rejected">Crosscheck Ditolak</SelectItem>
                                        </SelectContent>
                                    </Select>

                                    {/* Type Filter */}
                                    <Select value={filters.type} onValueChange={val => handleFilterChange('type', val)}>
                                        <SelectTrigger className="w-48">
                                            <SelectValue placeholder="Jenis Laporan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Jenis</SelectItem>
                                            <SelectItem value="penjualan_summary">Laporan Penjualan</SelectItem>
                                            <SelectItem value="barang_stok">Laporan Stok</SelectItem>
                                            <SelectItem value="keuangan">Laporan Keuangan</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Date Range and Role Filters */}
                                <div className="flex items-center gap-4">
                                    <div className="flex items-center gap-2">
                                        <Calendar className="w-4 h-4 text-gray-500" />
                                        <span className="text-sm font-medium text-gray-700">Periode:</span>
                                    </div>
                                    
                                    {/* Date From */}
                                    <div>
                                        <Input
                                            type="date"
                                            value={filters.date_from}
                                            onChange={(e) => handleFilterChange('date_from', e.target.value)}
                                            className="w-40"
                                        />
                                    </div>
                                    
                                    <span className="text-gray-500">s/d</span>
                                    
                                    {/* Date To */}
                                    <div>
                                        <Input
                                            type="date"
                                            value={filters.date_to}
                                            onChange={(e) => handleFilterChange('date_to', e.target.value)}
                                            className="w-40"
                                        />
                                    </div>

                                    {/* Submitter Filter */}
                                    <div className="flex items-center gap-2 ml-4">
                                        <User className="w-4 h-4 text-gray-500" />
                                        <span className="text-sm font-medium text-gray-700">Submitter:</span>
                                        <Select value={filters.submitter} onValueChange={val => handleFilterChange('submitter', val)}>
                                            <SelectTrigger className="w-40">
                                                <SelectValue placeholder="Semua" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Semua Submitter</SelectItem>
                                                <SelectItem value="kasir">Kasir</SelectItem>
                                                <SelectItem value="karyawan">Karyawan</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>

                                    {/* Crosschecker Filter */}
                                    <div className="flex items-center gap-2">
                                        <User className="w-4 h-4 text-gray-500" />
                                        <span className="text-sm font-medium text-gray-700">Crosschecker:</span>
                                        <Select value={filters.crosschecker} onValueChange={val => handleFilterChange('crosschecker', val)}>
                                            <SelectTrigger className="w-40">
                                                <SelectValue placeholder="Semua" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="all">Semua Crosschecker</SelectItem>
                                                <SelectItem value="kasir">Kasir</SelectItem>
                                                <SelectItem value="karyawan">Karyawan</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                </div>

                                {/* Summary */}
                                <div className="flex items-center justify-between pt-2 border-t border-gray-200">
                                    <div className="text-sm text-gray-600">
                                        Menampilkan {reports.data.length} dari {reports.total} laporan
                                    </div>
                                    <div className="text-sm text-gray-600">
                                        Halaman {reports.current_page} dari {reports.last_page}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Reports List */}
                    <div className="space-y-4">
                        {reports.data.length === 0 ? (
                            <Card>
                                <CardContent className="p-8 text-center">
                                    <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">Tidak ada laporan</h3>
                                    <p className="text-gray-600">Belum ada laporan yang perlu diapprove.</p>
                                </CardContent>
                            </Card>
                        ) : (
                            reports.data.map((report) => (
                                <Card key={report.id} className="hover:shadow-md transition-shadow">
                                    <CardContent className="p-6">
                                        <div className="flex items-start justify-between">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <h3 className="text-lg font-semibold text-gray-900">{report.title}</h3>
                                                    {getStatusBadge(report.status)}
                                                </div>
                                                
                                                <div className="text-sm text-gray-600 space-y-1 mb-4">
                                                    <p><strong>Jenis:</strong> {getReportTypeLabel(report.type)}</p>
                                                    <p><strong>Submitter:</strong> {report.submitter.name} ({report.submitter.email})</p>
                                                    {report.crosschecker && (
                                                        <p><strong>Crosschecker:</strong> {report.crosschecker.name}</p>
                                                    )}
                                                    <p>
                                                        <strong>Periode:</strong> {format(new Date(report.period_from), 'dd MMM yyyy', { locale: id })} - 
                                                        {format(new Date(report.period_to), 'dd MMM yyyy', { locale: id })}
                                                    </p>
                                                    <p>
                                                        <strong>Dibuat:</strong> {format(new Date(report.created_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                    </p>
                                                    {report.submitted_at && (
                                                        <p>
                                                            <strong>Disubmit:</strong> {format(new Date(report.submitted_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                        </p>
                                                    )}
                                                    {report.crosschecked_at && (
                                                        <p>
                                                            <strong>Crosscheck:</strong> {format(new Date(report.crosschecked_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                        </p>
                                                    )}
                                                    {report.approved_at && (
                                                        <p>
                                                            <strong>Approved:</strong> {format(new Date(report.approved_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                            {report.approver && ` oleh ${report.approver.name}`}
                                                        </p>
                                                    )}
                                                </div>

                                                {report.submission_notes && (
                                                    <div className="mt-3 p-3 bg-blue-50 rounded-md">
                                                        <p className="text-sm text-blue-700">
                                                            <strong>Catatan Submitter:</strong> {report.submission_notes}
                                                        </p>
                                                    </div>
                                                )}

                                                {report.crosscheck_notes && (
                                                    <div className="mt-3 p-3 bg-green-50 rounded-md">
                                                        <p className="text-sm text-green-700">
                                                            <strong>Catatan Crosscheck:</strong> {report.crosscheck_notes}
                                                        </p>
                                                    </div>
                                                )}

                                                {report.approval_notes && (
                                                    <div className="mt-3 p-3 bg-purple-50 rounded-md">
                                                        <p className="text-sm text-purple-700">
                                                            <strong>Catatan Approval:</strong> {report.approval_notes}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex items-center gap-2 ml-4">
                                                <Link href={route('report-submissions.show', report.id)}>
                                                    <Button variant="outline" size="sm">
                                                        <Eye className="w-4 h-4 mr-1" />
                                                        Detail
                                                    </Button>
                                                </Link>
                                                
                                                {report.status === 'owner_pending' && (
                                                    <>
                                                        <Button 
                                                            variant="outline" 
                                                            size="sm"
                                                            onClick={() => handleAction(report, 'approve')}
                                                            className="text-green-600 border-green-600 hover:bg-green-50"
                                                        >
                                                            <Check className="w-4 h-4 mr-1" />
                                                            Approve
                                                        </Button>
                                                        <Button 
                                                            variant="outline" 
                                                            size="sm"
                                                            onClick={() => handleAction(report, 'reject')}
                                                            className="text-red-600 border-red-600 hover:bg-red-50"
                                                        >
                                                            <X className="w-4 h-4 mr-1" />
                                                            Reject
                                                        </Button>
                                                    </>
                                                )}
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        )}
                    </div>

                    {/* Pagination */}
                    {reports.last_page > 1 && (
                        <div className="mt-6 flex justify-center">
                            <nav className="flex items-center space-x-2">
                                {reports.links.map((link, index) => (
                                    <Link
                                        key={index}
                                        href={link.url || '#'}
                                        className={`px-3 py-2 text-sm font-medium rounded-md ${
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'text-gray-500 hover:text-gray-700 hover:bg-gray-100'
                                        }`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </nav>
                        </div>
                    )}
                </div>
            </div>

            {/* Approve Dialog */}
            <Dialog open={isApproveDialogOpen} onOpenChange={setIsApproveDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Approve Laporan</DialogTitle>
                        <DialogDescription>
                            Setujui laporan "{selectedReport?.title}" yang dibuat oleh {selectedReport?.submitter.name}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-700 mb-1 block">Catatan (Opsional)</label>
                            <textarea
                                value={approvalNotes}
                                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setApprovalNotes(e.target.value)}
                                placeholder="Tambahkan catatan approval jika diperlukan..."
                                rows={3}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsApproveDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button onClick={submitAction} className="bg-green-600 hover:bg-green-700">
                            <Check className="w-4 h-4 mr-2" />
                            Approve
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reject Dialog */}
            <Dialog open={isRejectDialogOpen} onOpenChange={setIsRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Reject Laporan</DialogTitle>
                        <DialogDescription>
                            Tolak laporan "{selectedReport?.title}" yang dibuat oleh {selectedReport?.submitter.name}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-700 mb-1 block">Alasan Penolakan <span className="text-red-500">*</span></label>
                            <textarea
                                value={approvalNotes}
                                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setApprovalNotes(e.target.value)}
                                placeholder="Berikan alasan penolakan..."
                                rows={3}
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                required
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsRejectDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button 
                            onClick={submitAction} 
                            className="bg-red-600 hover:bg-red-700"
                            disabled={!approvalNotes.trim()}
                        >
                            <X className="w-4 h-4 mr-2" />
                            Reject
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
} 