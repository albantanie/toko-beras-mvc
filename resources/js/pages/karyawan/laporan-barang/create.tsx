import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
// import { Textarea } from '@/components/ui/textarea';
import { ArrowLeft, FileText, Calendar, BarChart3 } from 'lucide-react';
import { format } from 'date-fns';

/**
 * Halaman untuk membuat laporan barang baru (karyawan)
 * Karyawan dapat memilih jenis laporan barang dan periode
 */
export default function CreateLaporanBarang() {
    const { data, setData, post, processing, errors } = useForm({
        type: '',
        title: '',
        period_from: '',
        period_to: '',
    });

    // Handle form submission
    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        post(route('karyawan.laporan.barang.generate'));
    };

    // Handle report type change
    const handleTypeChange = (type: string) => {
        setData('type', type);
        
        // Auto-generate title based on type
        let title = '';
        const today = format(new Date(), 'dd-MM-yyyy');
        
        switch (type) {
            case 'barang_stok':
                title = `Laporan Stok Barang - ${today}`;
                break;
            case 'barang_movement':
                title = `Laporan Pergerakan Barang - ${today}`;
                break;
            case 'barang_performance':
                title = `Laporan Performa Barang - ${today}`;
                break;
        }
        
        setData('title', title);
    };

    // Get report type description
    const getReportDescription = (type: string) => {
        switch (type) {
            case 'barang_stok':
                return 'Laporan kondisi stok saat ini, termasuk barang dengan stok rendah dan nilai inventori.';
            case 'barang_movement':
                return 'Laporan pergerakan barang dalam periode tertentu, termasuk barang masuk dan keluar.';
            case 'barang_performance':
                return 'Laporan performa penjualan barang, termasuk barang terlaris dan yang kurang laku.';
            default:
                return '';
        }
    };

    // Check if period is required
    const isPeriodRequired = data.type !== 'barang_stok';

    // Halaman ini tidak lagi digunakan, hanya owner yang bisa generate laporan
    return <div className="text-center text-red-500 mt-10">Akses pembuatan laporan hanya untuk owner. Fitur ini sudah tidak tersedia untuk karyawan.</div>;

    return (
        <AppLayout>
            <Head title="Buat Laporan Barang - Karyawan" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center gap-4 mb-4">
                            <Link href={route('karyawan.laporan.barang.index')}>
                                <Button variant="outline" size="sm">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Kembali
                                </Button>
                            </Link>
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Buat Laporan Barang Baru</h1>
                                <p className="text-gray-600">Generate laporan barang untuk review owner</p>
                            </div>
                        </div>
                    </div>

                    <form onSubmit={handleSubmit} className="space-y-6">
                        {/* Report Type Selection */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2">
                                    <FileText className="w-5 h-5" />
                                    Jenis Laporan
                                </CardTitle>
                                <CardDescription>
                                    Pilih jenis laporan barang yang ingin dibuat
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="type">Jenis Laporan *</Label>
                                    <Select value={data.type} onValueChange={handleTypeChange}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih jenis laporan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="barang_stok">
                                                <div className="flex items-center gap-2">
                                                    <BarChart3 className="w-4 h-4" />
                                                    Laporan Stok Barang
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="barang_movement">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="w-4 h-4" />
                                                    Laporan Pergerakan Barang
                                                </div>
                                            </SelectItem>
                                            <SelectItem value="barang_performance">
                                                <div className="flex items-center gap-2">
                                                    <BarChart3 className="w-4 h-4" />
                                                    Laporan Performa Barang
                                                </div>
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    {errors.type && (
                                        <p className="text-sm text-red-600 mt-1">{errors.type}</p>
                                    )}
                                </div>

                                {data.type && (
                                    <div className="p-4 bg-blue-50 rounded-md">
                                        <p className="text-sm text-blue-800">
                                            {getReportDescription(data.type)}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Report Details */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Detail Laporan</CardTitle>
                                <CardDescription>
                                    Atur judul dan periode laporan
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div>
                                    <Label htmlFor="title">Judul Laporan</Label>
                                    <Input
                                        id="title"
                                        type="text"
                                        value={data.title}
                                        onChange={(e) => setData('title', e.target.value)}
                                        placeholder="Masukkan judul laporan (opsional)"
                                    />
                                    {errors.title && (
                                        <p className="text-sm text-red-600 mt-1">{errors.title}</p>
                                    )}
                                    <p className="text-sm text-gray-500 mt-1">
                                        Jika kosong, judul akan dibuat otomatis berdasarkan jenis laporan
                                    </p>
                                </div>

                                {isPeriodRequired && (
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <Label htmlFor="period_from">Tanggal Mulai *</Label>
                                            <Input
                                                id="period_from"
                                                type="date"
                                                value={data.period_from}
                                                onChange={(e) => setData('period_from', e.target.value)}
                                            />
                                            {errors.period_from && (
                                                <p className="text-sm text-red-600 mt-1">{errors.period_from}</p>
                                            )}
                                        </div>
                                        <div>
                                            <Label htmlFor="period_to">Tanggal Selesai *</Label>
                                            <Input
                                                id="period_to"
                                                type="date"
                                                value={data.period_to}
                                                onChange={(e) => setData('period_to', e.target.value)}
                                            />
                                            {errors.period_to && (
                                                <p className="text-sm text-red-600 mt-1">{errors.period_to}</p>
                                            )}
                                        </div>
                                    </div>
                                )}

                                {data.type === 'barang_stok' && (
                                    <div className="p-4 bg-yellow-50 rounded-md">
                                        <p className="text-sm text-yellow-800">
                                            <strong>Info:</strong> Laporan stok barang akan menampilkan kondisi stok saat ini, 
                                            tidak memerlukan periode tanggal.
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Submit Button */}
                        <div className="flex justify-end gap-4">
                            <Link href={route('karyawan.laporan.barang.index')}>
                                <Button type="button" variant="outline">
                                    Batal
                                </Button>
                            </Link>
                            <Button 
                                type="submit" 
                                disabled={processing || !data.type}
                            >
                                {processing ? 'Membuat Laporan...' : 'Buat Laporan'}
                            </Button>
                        </div>
                    </form>

                    {/* Info Card */}
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle className="text-lg">Informasi Penting</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-2 text-sm text-gray-600">
                                <p>• Laporan yang dibuat akan berstatus <strong>Draft</strong> terlebih dahulu</p>
                                <p>• Anda perlu submit laporan untuk mendapatkan approval dari Owner</p>
                                <p>• Laporan yang sudah disetujui dapat digunakan untuk analisis bisnis</p>
                                <p>• Jika laporan ditolak, Anda dapat mengedit dan submit ulang</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
