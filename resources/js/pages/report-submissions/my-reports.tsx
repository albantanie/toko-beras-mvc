import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Clock, CheckCircle, XCircle, FileText, User, Calendar, Eye, Plus, Edit, Trash2 } from 'lucide-react';
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
    };
}

const breadcrumbs = [
    { title: 'Laporan', href: '/laporan' },
    { title: 'Laporan Saya', href: '/report-submissions/my-reports' },
];

export default function MyReports({ reports, filters }: Props) {
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

    const handleFilterChange = (key: string, value: string) => {
        router.get(route('report-submissions.my-reports'), {
            ...filters,
            [key]: value,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    const handleDelete = (reportId: number, reportTitle: string) => {
        Swal.fire({
            title: 'Konfirmasi Hapus',
            text: `Apakah Anda yakin ingin menghapus laporan "${reportTitle}"?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Ya, Hapus!',
            cancelButtonText: 'Batal'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('report-submissions.destroy', reportId), {
                    onSuccess: () => {
                        Swal.fire({
                            title: 'Berhasil!',
                            text: 'Laporan berhasil dihapus',
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    },
                    onError: () => {
                        Swal.fire({
                            title: 'Gagal!',
                            text: 'Terjadi kesalahan saat menghapus laporan.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Saya" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Laporan Saya</h1>
                                <p className="text-gray-600">Kelola laporan yang Anda buat</p>
                            </div>
                            <Link href={route('report-submissions.create')}>
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Buat Laporan Baru
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <Card className="mb-6">
                        <CardContent className="p-4">
                            <div className="flex items-center gap-4">
                                <div className="flex items-center gap-2">
                                    <FileText className="w-4 h-4 text-gray-500" />
                                    <span className="text-sm font-medium text-gray-700">Filter:</span>
                                </div>
                                <Select value={filters.status} onValueChange={val => handleFilterChange('status', val)}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="submitted">Submitted</SelectItem>
                                        <SelectItem value="crosscheck_pending">Menunggu Crosscheck</SelectItem>
                                        <SelectItem value="crosscheck_approved">Crosscheck Selesai</SelectItem>
                                        <SelectItem value="crosscheck_rejected">Crosscheck Ditolak</SelectItem>
                                        <SelectItem value="owner_pending">Menunggu Approval</SelectItem>
                                        <SelectItem value="approved">Disetujui</SelectItem>
                                        <SelectItem value="rejected">Ditolak</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Select value={filters.type} onValueChange={val => handleFilterChange('type', val)}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Jenis</SelectItem>
                                        <SelectItem value="penjualan_summary">Laporan Penjualan</SelectItem>
                                        <SelectItem value="barang_stok">Laporan Stok</SelectItem>
                                        <SelectItem value="keuangan">Laporan Keuangan</SelectItem>
                                    </SelectContent>
                                </Select>
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
                                    <p className="text-gray-600">Anda belum membuat laporan apapun.</p>
                                    <Link href={route('report-submissions.create')}>
                                        <Button className="mt-4">
                                            <Plus className="w-4 h-4 mr-2" />
                                            Buat Laporan Pertama
                                        </Button>
                                    </Link>
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
                                                            {report.crosschecker && ` oleh ${report.crosschecker.name}`}
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
                                                
                                                {(report.status === 'draft' || report.status === 'crosscheck_rejected' || report.status === 'rejected') && (
                                                    <Link href={route('report-submissions.edit', report.id)}>
                                                        <Button variant="outline" size="sm">
                                                            <Edit className="w-4 h-4 mr-1" />
                                                            Edit
                                                        </Button>
                                                    </Link>
                                                )}

                                                {(report.status === 'draft' || report.status === 'crosscheck_rejected' || report.status === 'rejected') && (
                                                    <Button 
                                                        variant="outline" 
                                                        size="sm"
                                                        onClick={() => handleDelete(report.id, report.title)}
                                                        className="text-red-600 border-red-600 hover:bg-red-50"
                                                    >
                                                        <Trash2 className="w-4 h-4 mr-1" />
                                                        Hapus
                                                    </Button>
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
        </AppLayout>
    );
} 