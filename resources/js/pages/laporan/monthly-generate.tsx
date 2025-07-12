import React, { useState } from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Calendar, FileText, TrendingUp, Package, AlertCircle } from 'lucide-react';
import Swal from 'sweetalert2';

interface AvailableMonth {
    value: string;
    label: string;
    report_count: number;
}

interface Props {
    user_role: string;
    available_months: AvailableMonth[];
}

export default function MonthlyGenerate({ user_role, available_months }: Props) {
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
            const response = await fetch('/laporan/monthly/preview', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    month: selectedMonth,
                    type: reportType
                })
            });

            const data = await response.json();

            if (data.success) {
                setPreview(data.preview);
            } else {
                Swal.fire({
                    title: 'Error',
                    text: data.error || 'Gagal memuat preview',
                    icon: 'error'
                });
            }
        } catch (error) {
            Swal.fire({
                title: 'Error',
                text: 'Terjadi kesalahan saat memuat preview',
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
            title: 'Generate Laporan Bulanan',
            text: `Apakah Anda yakin ingin generate ${reportTitle} untuk ${available_months.find(m => m.value === selectedMonth)?.label}?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Ya, Generate!',
            cancelButtonText: 'Batal'
        });

        if (!result.isConfirmed) return;

        setIsGenerating(true);

        try {
            const response = await fetch('/laporan/monthly/generate', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                },
                body: JSON.stringify({
                    month: selectedMonth,
                    type: reportType
                })
            });

            const data = await response.json();

            if (data.success) {
                Swal.fire({
                    title: 'Berhasil!',
                    text: 'Laporan bulanan berhasil di-generate dan dikirim ke owner untuk approval',
                    icon: 'success'
                }).then(() => {
                    // Reset form
                    setSelectedMonth('');
                    setPreview(null);
                });
            } else {
                Swal.fire({
                    title: 'Gagal!',
                    text: data.error || 'Terjadi kesalahan saat generate laporan',
                    icon: 'error'
                });
            }
        } catch (error) {
            Swal.fire({
                title: 'Error!',
                text: 'Terjadi kesalahan saat generate laporan',
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
                        Generate laporan bulanan dari data laporan harian untuk dikirim ke owner untuk approval
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
                                {isGenerating ? 'Generating...' : 'Generate Laporan'}
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
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
