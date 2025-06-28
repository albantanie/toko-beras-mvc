import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Clock, CheckCircle, XCircle, FileText, User, Calendar, Eye } from 'lucide-react';
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
    created_at: string;
    generator: {
        name: string;
        email: string;
    };
    approver?: {
        name: string;
    };
    approved_at?: string;
    approval_notes?: string;
}

interface Props {
    reports: {
        data: Report[];
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
    };
}

const breadcrumbs = [
    { title: 'Laporan', href: '/laporan' },
    { title: 'Approval', href: '/laporan/approvals' },
];

export default function Approvals({ reports, filters }: Props) {
    const [selectedReport, setSelectedReport] = useState<Report | null>(null);
    const [approvalNotes, setApprovalNotes] = useState('');
    const [isApprovalDialogOpen, setIsApprovalDialogOpen] = useState(false);
    const [isRejectDialogOpen, setIsRejectDialogOpen] = useState(false);
    const [actionType, setActionType] = useState<'approve' | 'reject'>('approve');

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'pending_approval':
                return <Badge variant="secondary" className="bg-yellow-100 text-yellow-800"><Clock className="w-3 h-3 mr-1" />Menunggu</Badge>;
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
            case 'barang_karyawan': return 'Laporan Barang Karyawan';
            case 'keuangan': return 'Laporan Keuangan';
            default: return type;
        }
    };

    const handleFilterChange = (key: string, value: string) => {
        router.get(route('laporan.approvals'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleAction = (report: Report, type: 'approve' | 'reject') => {
        setSelectedReport(report);
        setActionType(type);
        setApprovalNotes('');
        
        if (type === 'approve') {
            setIsApprovalDialogOpen(true);
        } else {
            setIsRejectDialogOpen(true);
        }
    };

    const submitAction = () => {
        if (!selectedReport) return;

        const routeName = actionType === 'approve' ? 'laporan.approve' : 'laporan.reject';
        
        router.patch(route(routeName, selectedReport.id), {
            approval_notes: approvalNotes,
        }, {
            onSuccess: () => {
                Swal.fire({
                    title: 'Berhasil!',
                    text: `Laporan berhasil ${actionType === 'approve' ? 'disetujui' : 'ditolak'}.`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
                setIsApprovalDialogOpen(false);
                setIsRejectDialogOpen(false);
            },
            onError: () => {
                Swal.fire({
                    title: 'Gagal!',
                    text: `Terjadi kesalahan saat ${actionType === 'approve' ? 'menyetujui' : 'menolak'} laporan.`,
                    icon: 'error'
                });
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Approval Laporan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Approval Laporan</h1>
                                <p className="text-gray-600">Review dan approve laporan yang dibuat oleh karyawan dan admin</p>
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
                            <div className="flex gap-4">
                                <div className="flex-1">
                                    <label className="text-sm font-medium text-gray-700 mb-1 block">Status</label>
                                    <Select value={filters.status} onValueChange={(value) => handleFilterChange('status', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Status</SelectItem>
                                            <SelectItem value="pending_approval">Menunggu Approval</SelectItem>
                                            <SelectItem value="approved">Disetujui</SelectItem>
                                            <SelectItem value="rejected">Ditolak</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex-1">
                                    <label className="text-sm font-medium text-gray-700 mb-1 block">Jenis Laporan</label>
                                    <Select value={filters.type} onValueChange={(value) => handleFilterChange('type', value)}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="all">Semua Jenis</SelectItem>
                                            <SelectItem value="penjualan_summary">Laporan Penjualan</SelectItem>
                                            <SelectItem value="barang_stok">Laporan Stok</SelectItem>
                                            <SelectItem value="barang_movement">Laporan Pergerakan</SelectItem>
                                            <SelectItem value="barang_performance">Laporan Performa</SelectItem>
                                            <SelectItem value="transaksi_harian">Laporan Transaksi</SelectItem>
                                        </SelectContent>
                                    </Select>
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
                                                    <p><strong>Generator:</strong> {report.generator.name} ({report.generator.email})</p>
                                                    <p>
                                                        <strong>Periode:</strong> {format(new Date(report.period_from), 'dd MMM yyyy', { locale: id })} - 
                                                        {format(new Date(report.period_to), 'dd MMM yyyy', { locale: id })}
                                                    </p>
                                                    <p>
                                                        <strong>Dibuat:</strong> {format(new Date(report.created_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                    </p>
                                                    {report.approved_at && (
                                                        <p>
                                                            <strong>Disetujui:</strong> {format(new Date(report.approved_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                            {report.approver && ` oleh ${report.approver.name}`}
                                                        </p>
                                                    )}
                                                </div>

                                                {report.approval_notes && (
                                                    <div className="mt-3 p-3 bg-gray-50 rounded-md">
                                                        <p className="text-sm text-gray-700">
                                                            <strong>Catatan:</strong> {report.approval_notes}
                                                        </p>
                                                    </div>
                                                )}
                                            </div>

                                            <div className="flex items-center gap-2 ml-4">
                                                <Link href={route('laporan.show', report.id)}>
                                                    <Button variant="outline" size="sm">
                                                        <Eye className="w-4 h-4 mr-1" />
                                                        Detail
                                                    </Button>
                                                </Link>
                                                
                                                {report.status === 'pending_approval' && (
                                                    <>
                                                        <Button 
                                                            variant="outline" 
                                                            size="sm"
                                                            onClick={() => handleAction(report, 'approve')}
                                                            className="text-green-600 border-green-600 hover:bg-green-50"
                                                        >
                                                            <CheckCircle className="w-4 h-4 mr-1" />
                                                            Approve
                                                        </Button>
                                                        <Button 
                                                            variant="outline" 
                                                            size="sm"
                                                            onClick={() => handleAction(report, 'reject')}
                                                            className="text-red-600 border-red-600 hover:bg-red-50"
                                                        >
                                                            <XCircle className="w-4 h-4 mr-1" />
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
                                        className={`px-3 py-2 text-sm rounded-md ${
                                            link.active
                                                ? 'bg-blue-600 text-white'
                                                : 'bg-white text-gray-700 border border-gray-300 hover:bg-gray-50'
                                        } ${!link.url ? 'opacity-50 cursor-not-allowed' : ''}`}
                                    >
                                        {link.label}
                                    </Link>
                                ))}
                            </nav>
                        </div>
                    )}
                </div>
            </div>

            {/* Approval Dialog */}
            <Dialog open={isApprovalDialogOpen} onOpenChange={setIsApprovalDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Approve Laporan</DialogTitle>
                        <DialogDescription>
                            Setujui laporan "{selectedReport?.title}" yang dibuat oleh {selectedReport?.generator.name}
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
                        <Button variant="outline" onClick={() => setIsApprovalDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button onClick={submitAction} className="bg-green-600 hover:bg-green-700">
                            <CheckCircle className="w-4 h-4 mr-2" />
                            Approve
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Reject Dialog */}
            <Dialog open={isRejectDialogOpen} onOpenChange={setIsRejectDialogOpen}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Tolak Laporan</DialogTitle>
                        <DialogDescription>
                            Tolak laporan "{selectedReport?.title}" yang dibuat oleh {selectedReport?.generator.name}
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <label className="text-sm font-medium text-gray-700 mb-1 block">Alasan Penolakan *</label>
                            <textarea
                                value={approvalNotes}
                                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setApprovalNotes(e.target.value)}
                                placeholder="Berikan alasan penolakan laporan..."
                                rows={3}
                                required
                                className="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setIsRejectDialogOpen(false)}>
                            Batal
                        </Button>
                        <Button 
                            onClick={submitAction} 
                            disabled={!approvalNotes.trim()}
                            className="bg-red-600 hover:bg-red-700"
                        >
                            <XCircle className="w-4 h-4 mr-2" />
                            Reject
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
