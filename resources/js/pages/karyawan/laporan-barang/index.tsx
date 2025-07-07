import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Plus, Search, FileText, Clock, CheckCircle, XCircle, AlertCircle } from 'lucide-react';
import { format } from 'date-fns';
import { id } from 'date-fns/locale';

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
        links: any[];
        meta: any;
    };
    filters: {
        status: string;
        search: string;
    };
}

/**
 * Halaman daftar laporan barang untuk karyawan
 * Karyawan dapat melihat, membuat, dan submit laporan barang untuk approval owner
 */
export default function LaporanBarangIndex({ reports, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');

    // Handle search
    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get(route('karyawan.laporan.barang.index'), {
            search,
            status,
        }, {
            preserveState: true,
            replace: true,
        });
    };

    // Handle status filter
    const handleStatusChange = (newStatus: string) => {
        setStatus(newStatus);
        router.get(route('karyawan.laporan.barang.index'), {
            search,
            status: newStatus,
        }, {
            preserveState: true,
            replace: true,
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

    // Halaman ini tidak lagi digunakan, hanya owner yang bisa generate laporan
    return <div className="text-center text-red-500 mt-10">Akses laporan hanya untuk owner. Fitur ini sudah tidak tersedia untuk karyawan.</div>;

    return (
        <AppLayout>
            <Head title="Laporan Barang - Karyawan" />

            <div className="py-6">
                <div className="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex justify-between items-center">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Laporan Barang</h1>
                                <p className="text-gray-600">Kelola laporan barang dan inventory</p>
                            </div>
                            <Link href={route('karyawan.laporan.barang.create')}>
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Buat Laporan Baru
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Filters */}
                    <Card className="mb-6">
                        <CardHeader>
                            <CardTitle>Filter Laporan</CardTitle>
                            <CardDescription>
                                Cari dan filter laporan barang berdasarkan status
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleSearch} className="flex gap-4">
                                <div className="flex-1">
                                    <Input
                                        type="text"
                                        placeholder="Cari berdasarkan judul laporan..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full"
                                    />
                                </div>
                                <Select value={status} onValueChange={handleStatusChange}>
                                    <SelectTrigger className="w-48">
                                        <SelectValue placeholder="Pilih Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="pending_approval">Menunggu Approval</SelectItem>
                                        <SelectItem value="approved">Disetujui</SelectItem>
                                        <SelectItem value="rejected">Ditolak</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button type="submit">
                                    <Search className="w-4 h-4 mr-2" />
                                    Cari
                                </Button>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Reports List */}
                    <div className="space-y-4">
                        {reports.data.length === 0 ? (
                            <Card>
                                <CardContent className="text-center py-12">
                                    <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                    <h3 className="text-lg font-medium text-gray-900 mb-2">
                                        Belum ada laporan
                                    </h3>
                                    <p className="text-gray-600 mb-4">
                                        Mulai buat laporan barang pertama Anda
                                    </p>
                                    <Link href={route('karyawan.laporan.barang.create')}>
                                        <Button>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Buat Laporan Baru
                                        </Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        ) : (
                            reports.data.map((report) => (
                                <Card key={report.id} className="hover:shadow-md transition-shadow">
                                    <CardContent className="p-6">
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <div className="flex items-center gap-3 mb-2">
                                                    <h3 className="text-lg font-semibold text-gray-900">
                                                        {report.title}
                                                    </h3>
                                                    {getStatusBadge(report.status)}
                                                </div>
                                                <p className="text-sm text-gray-600 mb-2">
                                                    {getReportTypeLabel(report.type)}
                                                </p>
                                                <div className="text-sm text-gray-500 space-y-1">
                                                    <p>
                                                        Periode: {format(new Date(report.period_from), 'dd MMM yyyy', { locale: id })} - 
                                                        {format(new Date(report.period_to), 'dd MMM yyyy', { locale: id })}
                                                    </p>
                                                    <p>
                                                        Dibuat: {format(new Date(report.created_at), 'dd MMM yyyy HH:mm', { locale: id })}
                                                    </p>
                                                    {report.approved_at && (
                                                        <p>
                                                            Disetujui: {format(new Date(report.approved_at), 'dd MMM yyyy HH:mm', { locale: id })}
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
                                            <div className="flex gap-2 ml-4">
                                                <Link href={route('karyawan.laporan.barang.show', report.id)}>
                                                    <Button variant="outline" size="sm">
                                                        Lihat Detail
                                                    </Button>
                                                </Link>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            ))
                        )}
                    </div>

                    {/* Pagination */}
                    {reports.meta.last_page > 1 && (
                        <div className="mt-6 flex justify-center">
                            <div className="flex gap-2">
                                {reports.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        variant={link.active ? "default" : "outline"}
                                        size="sm"
                                        disabled={!link.url}
                                        onClick={() => link.url && router.visit(link.url)}
                                        dangerouslySetInnerHTML={{ __html: link.label }}
                                    />
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
