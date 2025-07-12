import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Calendar, FileText, TrendingUp, Package, AlertCircle } from 'lucide-react';
import Swal from 'sweetalert2';
import axios from '@/bootstrap';

interface AvailableMonth {
    value: string;
    label: string;
    report_count: number;
}

interface GeneratedReport {
    id: number;
    title: string;
    period_from: string;
    period_to: string;
    month_year: string;
    month_label: string;
    status: string;
    status_label: string;
    status_color: string;
    generated_at: string;
    approved_at: string | null;
    approval_notes: string | null;
}

interface Props {
    user_role: string;
    available_months: AvailableMonth[];
    generated_reports: GeneratedReport[];
}

export default function MonthlyGenerate({ user_role, available_months, generated_reports }: Props) {
    const [selectedMonth, setSelectedMonth] = useState<string>('');
    const [isGenerating, setIsGenerating] = useState(false);
    const [preview, setPreview] = useState<any>(null);

    const reportType = user_role === 'kasir' ? 'transaction' : 'stock';
    const reportTitle = user_role === 'kasir' ? 'Laporan Penjualan Bulanan' : 'Laporan Stok Bulanan';
    const reportIcon = user_role === 'kasir' ? TrendingUp : Package;

    const handlePreview = async () => {
        if (!selectedMonth) {
            Swal.fire({
                title: 'Pilih Bulan',
                text: 'Silakan pilih bulan terlebih dahulu',
                icon: 'warning'
            });
            return;
        }

        try {
            const response = await axios.post('/laporan/monthly/preview', {
                month: selectedMonth,
                type: reportType
            });

            if (response.data.success) {
                setPreview(response.data.preview);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: response.data.error || 'Gagal memuat preview',
                    icon: 'error'
                });
            }
        } catch (error: any) {
            console.error('Preview error:', error);
            let errorMessage = 'Terjadi kesalahan saat memuat preview';

            if (error.response?.data?.message) {
                errorMessage = error.response.data.message;
            } else if (error.response?.data?.error) {
                errorMessage = error.response.data.error;
            }

            Swal.fire({
                title: 'Error',
                text: errorMessage,
                icon: 'error'
            });
        }
    };

    const handleGenerate = async () => {
        if (!selectedMonth) {
            Swal.fire({
                title: 'Pilih Bulan',
                text: 'Silakan pilih bulan terlebih dahulu',
                icon: 'warning'
            });
            return;
        }

        const result = await Swal.fire({
            title: 'Buat Laporan Bulanan',
            text: `Apakah Anda yakin ingin membuat ${reportTitle} untuk ${available_months.find(m => m.value === selectedMonth)?.label}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Buat!',
            cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) return;

        setIsGenerating(true);

        try {
            const response = await axios.post('/laporan/monthly/generate', {
                month: selectedMonth,
                type: reportType
            });

            if (response.data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Laporan bulanan berhasil dibuat dan dikirim ke owner untuk persetujuan',
                    icon: 'success'
                }).then(() => {
                    // Reset form and reload page to get updated data
                    setSelectedMonth('');
                    setPreview(null);
                    router.reload();
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: response.data.error || 'Terjadi kesalahan saat membuat laporan',
                    icon: 'error'
                });
            }
        } catch (error: any) {
            console.error('Generate error:', error);

            let errorMessage = 'Terjadi kesalahan saat membuat laporan';
            if (error.response?.data?.error) {
                errorMessage = error.response.data.error;
            } else if (error.response?.data?.message) {
                errorMessage = error.response.data.message;
            }

            Swal.fire({
                title: 'Gagal!',
                text: errorMessage,
                icon: 'error'
            });
        } finally {
            setIsGenerating(false);
        }
    };

    const ReportIcon = reportIcon;

    return (
        <AppLayout title={reportTitle}>
            <Head title={reportTitle} />
            <div className="max-w-4xl mx-auto py-8">
                {/* Header */}
                <div className="mb-6">
                    <div className="flex items-center gap-3 mb-2">
                        <ReportIcon className="w-8 h-8 text-blue-600" />
                        <h1 className="text-2xl font-bold">{reportTitle}</h1>
                    </div>
                    <p className="text-gray-600">
                        Buat laporan bulanan dari data laporan harian untuk dikirim ke owner untuk persetujuan
                    </p>
                </div>

                {/* Main Form */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Calendar className="w-5 h-5" />
                            Pilih Bulan
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div>
                            <label className="block text-sm font-medium mb-2">
                                Bulan yang Tersedia
                            </label>
                            <Select value={selectedMonth} onValueChange={setSelectedMonth}>
                                <SelectTrigger>
                                    <SelectValue placeholder="Pilih bulan..." />
                                </SelectTrigger>
                                <SelectContent>
                                    {available_months.length === 0 ? (
                                        <SelectItem value="no-data" disabled>
                                            Tidak ada data laporan harian
                                        </SelectItem>
                                    ) : (
                                        available_months.map((month) => (
                                            <SelectItem key={month.value} value={month.value}>
                                                <div className="flex items-center justify-between w-full">
                                                    <span>{month.label}</span>
                                                    <Badge variant="secondary" className="ml-2">
                                                        {month.report_count} laporan
                                                    </Badge>
                                                </div>
                                            </SelectItem>
                                        ))
                                    )}
                                </SelectContent>
                            </Select>
                            {available_months.length === 0 && (
                                <div className="mt-2 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                                    <div className="flex items-center gap-2 text-yellow-800">
                                        <AlertCircle className="w-4 h-4" />
                                        <span className="text-sm">
                                            Belum ada laporan harian yang dapat dikompilasi menjadi laporan bulanan.
                                        </span>
                                    </div>
                                </div>
                            )}
                        </div>

                        <div className="flex gap-2">
                            <Button 
                                onClick={handlePreview} 
                                variant="outline"
                                disabled={!selectedMonth || available_months.length === 0}
                            >
                                <FileText className="w-4 h-4 mr-2" />
                                Preview
                            </Button>
                            <Button
                                onClick={handleGenerate}
                                disabled={!selectedMonth || isGenerating || available_months.length === 0}
                            >
                                {isGenerating ? 'Membuat...' : 'Buat Laporan'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Preview */}
                {preview && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Preview Laporan</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {preview.message ? (
                                <div className="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                                    <div className="flex items-center">
                                        <div className="flex-shrink-0">
                                            <svg className="h-5 w-5 text-yellow-400" viewBox="0 0 20 20" fill="currentColor">
                                                <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                            </svg>
                                        </div>
                                        <div className="ml-3">
                                            <p className="text-sm text-yellow-800">{preview.message}</p>
                                        </div>
                                    </div>
                                </div>
                            ) : (
                                <div className="space-y-4">
                                    <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div className="bg-blue-50 p-4 rounded-lg">
                                        <div className="text-sm text-blue-600 font-medium">Periode</div>
                                        <div className="text-lg font-bold text-blue-900">
                                            {preview.period?.month_name}
                                        </div>
                                    </div>
                                    <div className="bg-green-50 p-4 rounded-lg">
                                        <div className="text-sm text-green-600 font-medium">
                                            {reportType === 'transaction' ? 'Total Penjualan' : 'Total Pergerakan'}
                                        </div>
                                        <div className="text-lg font-bold text-green-900">
                                            {reportType === 'transaction' 
                                                ? `Rp ${preview.summary?.total_amount?.toLocaleString('id-ID') || 0}`
                                                : `${preview.summary?.total_movements || 0} item`
                                            }
                                        </div>
                                    </div>
                                    <div className="bg-purple-50 p-4 rounded-lg">
                                        <div className="text-sm text-purple-600 font-medium">Laporan Harian</div>
                                        <div className="text-lg font-bold text-purple-900">
                                            {preview.reports_included || 0} laporan
                                        </div>
                                    </div>
                                </div>

                                {reportType === 'transaction' && preview.summary && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <div className="text-sm text-gray-600 font-medium">Total Transaksi</div>
                                            <div className="text-lg font-bold">
                                                {preview.summary.total_transactions || 0}
                                            </div>
                                        </div>
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <div className="text-sm text-gray-600 font-medium">Item Terjual</div>
                                            <div className="text-lg font-bold">
                                                {preview.summary.total_items_sold || 0}
                                            </div>
                                        </div>
                                    </div>
                                )}

                                {reportType === 'stock' && preview.summary && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <div className="text-sm text-gray-600 font-medium">Nilai Stok</div>
                                            <div className="text-lg font-bold">
                                                Rp {preview.summary.total_stock_value?.toLocaleString('id-ID') || 0}
                                            </div>
                                        </div>
                                        <div className="bg-gray-50 p-4 rounded-lg">
                                            <div className="text-sm text-gray-600 font-medium">Rata-rata Harian</div>
                                            <div className="text-lg font-bold">
                                                {Math.round(preview.summary.average_daily_movements || 0)} item/hari
                                            </div>
                                        </div>
                                    </div>
                                )}
                                </div>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Status Laporan yang Sudah Dibuat */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Status Laporan Bulanan
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {generated_reports.length === 0 ? (
                            <div className="text-center py-8 text-gray-500">
                                <FileText className="h-12 w-12 mx-auto mb-4 opacity-50" />
                                <p>Belum ada laporan bulanan yang dibuat</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {generated_reports.map((report) => (
                                    <div key={report.id} className="border rounded-lg p-4 bg-gray-50">
                                        <div className="flex items-center justify-between mb-2">
                                            <h3 className="font-medium text-gray-900">{report.title}</h3>
                                            <Badge
                                                variant={report.status_color === 'green' ? 'default' :
                                                       report.status_color === 'yellow' ? 'secondary' : 'destructive'}
                                            >
                                                {report.status_label}
                                            </Badge>
                                        </div>
                                        <div className="grid grid-cols-2 gap-4 text-sm text-gray-600">
                                            <div>
                                                <span className="font-medium">Periode:</span> {report.month_label}
                                            </div>
                                            <div>
                                                <span className="font-medium">Dibuat:</span> {report.generated_at}
                                            </div>
                                            {report.approved_at && (
                                                <div>
                                                    <span className="font-medium">
                                                        {report.status === 'approved' ? 'Disetujui:' : 'Ditolak:'}
                                                    </span> {report.approved_at}
                                                </div>
                                            )}
                                            {report.approval_notes && (
                                                <div className="col-span-2">
                                                    <span className="font-medium">Catatan:</span> {report.approval_notes}
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
