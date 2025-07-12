import React, { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { formatCurrency, formatDateTime } from '@/utils/formatters';
import { BreadcrumbItem, PageProps } from '@/types';
import { Plus, Filter, Eye, Calendar, Download, Search, Package, RefreshCw, BarChart3 } from 'lucide-react';
import Swal from 'sweetalert2';

interface DailyReport {
    id: number;
    report_date: string;
    type: string;
    total_stock_movements: number;
    total_stock_value: number;
    status: 'draft' | 'completed';
    notes?: string;
    created_at: string;
    user: {
        id: number;
        name: string;
    };
}

interface TodayStats {
    total_movements: number;
    total_stock_value: number;
    movement_types: Record<string, number>;
    items_affected: number;
}

interface Props extends PageProps {
    reports: {
        data: DailyReport[];
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
        date_from?: string;
        date_to?: string;
        status?: string;
    };
    todayStats: TodayStats;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/karyawan/dashboard' },
    { title: 'Laporan Stok Harian', href: '/karyawan/laporan/daily' },
];

const statusLabels = {
    'draft': 'Draft',
    'completed': 'Selesai',
};

const statusColors = {
    'draft': 'bg-gray-100 text-gray-800',
    'completed': 'bg-blue-100 text-blue-800',
};

export default function KaryawanDailyReports({ reports, filters = {}, todayStats }: Props) {
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [showGenerateModal, setShowGenerateModal] = useState(false);
    const [generateDate, setGenerateDate] = useState(new Date().toISOString().split('T')[0]);
    const [showMonthlyModal, setShowMonthlyModal] = useState(false);
    const [monthlyReportMonth, setMonthlyReportMonth] = useState(new Date().toISOString().slice(0, 7));

    const handleFilter = () => {
        const params = new URLSearchParams();
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        if (status && status !== 'all') params.set('status', status);
        
        router.get(`/karyawan/laporan/daily?${params.toString()}`);
    };

    const handleGenerateReport = async (date: string) => {
        const formattedDate = new Date(date).toLocaleDateString('id-ID', {
            day: 'numeric',
            month: 'long',
            year: 'numeric'
        });

        const result = await Swal.fire({
            title: 'Generate Laporan Stok',
            text: `Apakah Anda yakin ingin generate laporan stok untuk tanggal ${formattedDate}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            router.post('/karyawan/laporan/generate', {
                date: date,
                type: 'stock'
            }, {
                onSuccess: () => {
                    Swal.fire({
                        title: 'Berhasil!',
                        text: `Laporan stok untuk tanggal ${formattedDate} berhasil di-generate.`,
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    });
                },
                onError: (errors) => {
                    Swal.fire({
                        title: 'Gagal!',
                        text: 'Terjadi kesalahan saat generate laporan. Silakan coba lagi.',
                        icon: 'error',
                        confirmButtonColor: '#ef4444'
                    });
                }
            });
        }
    };

    const handleGenerateMonthlyReport = async () => {
        if (!monthlyReportMonth) {
            Swal.fire({
                title: 'Error!',
                text: 'Silakan pilih bulan terlebih dahulu.',
                icon: 'error',
                confirmButtonColor: '#ef4444'
            });
            return;
        }

        const monthDate = new Date(monthlyReportMonth + '-01');
        const formattedMonth = monthDate.toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long'
        });

        const result = await Swal.fire({
            title: 'Generate Laporan Bulanan',
            text: `Apakah Anda yakin ingin generate laporan stok bulanan untuk ${formattedMonth}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#6b7280',
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal'
        });

        if (result.isConfirmed) {
            router.post('/karyawan/laporan/generate-monthly', {
                month: monthlyReportMonth,
                type: 'stock'
            }, {
                onSuccess: () => {
                    setShowMonthlyModal(false);
                    Swal.fire({
                        title: 'Berhasil!',
                        text: `Laporan stok bulanan untuk ${formattedMonth} berhasil di-generate dan dikirim untuk persetujuan owner.`,
                        icon: 'success',
                        confirmButtonColor: '#3b82f6'
                    });
                },
                onError: (errors) => {
                    console.error('Monthly report generation error:', errors);

                    let errorMessage = 'Terjadi kesalahan saat generate laporan bulanan. Silakan coba lagi.';

                    if (errors.message) {
                        errorMessage = errors.message;
                    } else if (typeof errors === 'string') {
                        errorMessage = errors;
                    } else if (errors.error) {
                        errorMessage = errors.error;
                    }

                    Swal.fire({
                        title: 'Gagal!',
                        text: errorMessage,
                        icon: 'error',
                        confirmButtonColor: '#ef4444',
                        width: '500px',
                        customClass: {
                            content: 'text-sm'
                        }
                    });
                }
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Laporan Stok Harian" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-2xl font-bold text-gray-900">Laporan Stok Harian</h1>
                        <p className="text-gray-600">Kelola dan lihat laporan pergerakan stok harian</p>
                    </div>
                    <div className="flex gap-2">
                        <Button
                            onClick={() => handleGenerateReport(new Date().toISOString().split('T')[0])}
                            className="bg-blue-600 hover:bg-blue-700"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Generate Hari Ini
                        </Button>
                        <Button
                            onClick={() => setShowGenerateModal(true)}
                            variant="outline"
                            className="border-blue-600 text-blue-600 hover:bg-blue-50"
                        >
                            <Calendar className="w-4 h-4 mr-2" />
                            Generate Tanggal Lain
                        </Button>
                        <Button
                            onClick={() => setShowMonthlyModal(true)}
                            variant="outline"
                            className="border-purple-600 text-purple-600 hover:bg-purple-50"
                        >
                            <BarChart3 className="w-4 h-4 mr-2" />
                            Laporan Bulanan
                        </Button>
                    </div>
                </div>

                {/* Today's Stats */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Pergerakan Stok Hari Ini</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">{todayStats.total_movements}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Nilai Stok Bergerak</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{formatCurrency(todayStats.total_stock_value)}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Item Terpengaruh</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-purple-600">{todayStats.items_affected}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-gray-600">Jenis Pergerakan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-1">
                                {Object.entries(todayStats.movement_types).map(([type, count]) => (
                                    <div key={type} className="flex justify-between text-sm">
                                        <span className="capitalize">{type}</span>
                                        <span className="font-medium">{count}</span>
                                    </div>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <Label htmlFor="date_from">Dari Tanggal</Label>
                                <Input
                                    id="date_from"
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="date_to">Sampai Tanggal</Label>
                                <Input
                                    id="date_to"
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                />
                            </div>
                            <div>
                                <Label htmlFor="status">Status</Label>
                                <Select value={status} onValueChange={setStatus}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="completed">Selesai</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div className="flex items-end">
                                <Button onClick={handleFilter} className="w-full">
                                    <Search className="w-4 h-4 mr-2" />
                                    Filter
                                </Button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Reports Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Laporan Harian</CardTitle>
                        <CardDescription>
                            Total: {reports.total} laporan
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {reports.data.length === 0 ? (
                            <div className="text-center py-8">
                                <Package className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                <p className="text-gray-500">Belum ada laporan stok harian</p>
                                <p className="text-sm text-gray-400">Generate laporan untuk hari ini atau hari sebelumnya</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {reports.data.map((report) => (
                                    <div key={report.id} className="border rounded-lg p-4 hover:bg-gray-50">
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <div className="flex items-center space-x-2 mb-2">
                                                    <h3 className="font-medium text-gray-900">
                                                        Laporan Stok - {new Date(report.report_date).toLocaleDateString('id-ID')}
                                                    </h3>
                                                    <Badge className={statusColors[report.status]}>
                                                        {statusLabels[report.status]}
                                                    </Badge>
                                                </div>
                                                <div className="grid grid-cols-3 gap-4 text-sm text-gray-600">
                                                    <div>
                                                        <span className="font-medium">Pergerakan Stok:</span> {report.total_stock_movements}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">Nilai Stok:</span> {formatCurrency(report.total_stock_value)}
                                                    </div>
                                                    <div>
                                                        <span className="font-medium">Status:</span> {statusLabels[report.status]}
                                                    </div>
                                                </div>
                                                <div className="mt-2 text-xs text-gray-500">
                                                    Dibuat: {formatDateTime(report.created_at)}
                                                </div>
                                            </div>
                                            <div className="flex space-x-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => router.get(`/daily-report/${report.id}`)}
                                                >
                                                    <Eye className="w-4 h-4 mr-1" />
                                                    Detail
                                                </Button>
                                                {report.status === 'draft' || report.status === 'rejected' ? (
                                                    <Button
                                                        size="sm"
                                                        onClick={() => handleGenerateReport(report.report_date)}
                                                    >
                                                        <RefreshCw className="w-4 h-4 mr-1" />
                                                        Generate Ulang
                                                    </Button>
                                                ) : null}
                                            </div>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>

            {/* Generate Report Modal */}
            <Dialog open={showGenerateModal} onOpenChange={setShowGenerateModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generate Laporan Stok</DialogTitle>
                        <DialogDescription>
                            Pilih tanggal untuk generate laporan stok harian
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="generate_date">Tanggal Laporan</Label>
                            <Input
                                id="generate_date"
                                type="date"
                                value={generateDate}
                                max={new Date().toISOString().split('T')[0]}
                                onChange={(e) => setGenerateDate(e.target.value)}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowGenerateModal(false)}>
                            Batal
                        </Button>
                        <Button
                            onClick={() => {
                                handleGenerateReport(generateDate);
                                setShowGenerateModal(false);
                            }}
                            className="bg-blue-600 hover:bg-blue-700"
                        >
                            <Plus className="w-4 h-4 mr-2" />
                            Generate Laporan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* Monthly Report Generation Modal */}
            <Dialog open={showMonthlyModal} onOpenChange={setShowMonthlyModal}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Generate Laporan Stok Bulanan</DialogTitle>
                        <DialogDescription>
                            Pilih bulan untuk generate laporan stok bulanan. Laporan akan dikirim ke owner untuk persetujuan.
                        </DialogDescription>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="monthly-month">Bulan</Label>
                            <Input
                                id="monthly-month"
                                type="month"
                                value={monthlyReportMonth}
                                onChange={(e) => setMonthlyReportMonth(e.target.value)}
                                max={new Date().toISOString().slice(0, 7)}
                            />
                        </div>
                    </div>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setShowMonthlyModal(false)}
                        >
                            Batal
                        </Button>
                        <Button
                            onClick={handleGenerateMonthlyReport}
                            className="bg-purple-600 hover:bg-purple-700"
                        >
                            <BarChart3 className="w-4 h-4 mr-2" />
                            Generate Laporan Bulanan
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
