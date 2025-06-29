import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { FileText, ArrowLeft } from 'lucide-react';

interface ReportType {
    [key: string]: string;
}

interface Props {
    reportTypes: ReportType;
}

const breadcrumbs = [
    { title: 'Laporan', href: '/laporan' },
    { title: 'Laporan Saya', href: '/report-submissions/my-reports' },
    { title: 'Buat Laporan Baru', href: '/report-submissions/create' },
];

export default function CreateReport({ reportTypes }: Props) {
    const [formData, setFormData] = useState({
        title: '',
        type: '',
        period_from: '',
        period_to: '',
        summary: '',
    });

    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleInputChange = (field: string, value: string) => {
        setFormData(prev => ({
            ...prev,
            [field]: value
        }));
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(route('report-submissions.store'), formData, {
            onSuccess: () => {
                // Success will be handled by redirect
            },
            onError: (errors) => {
                console.error('Validation errors:', errors);
                setIsSubmitting(false);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    };

    const isFormValid = () => {
        return formData.title.trim() !== '' && 
               formData.type !== '' && 
               formData.period_from !== '' && 
               formData.period_to !== '';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Buat Laporan Baru" />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Buat Laporan Baru</h1>
                                <p className="text-gray-600">Buat laporan baru untuk disubmit ke sistem approval</p>
                            </div>
                            <Link href={route('report-submissions.my-reports')}>
                                <Button variant="outline">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Kembali ke Laporan Saya
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Form */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="w-5 h-5" />
                                Form Laporan Baru
                            </CardTitle>
                            <CardDescription>
                                Isi form di bawah ini untuk membuat laporan baru. Laporan akan melalui proses crosscheck dan approval.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {/* Role Information */}
                            <div className="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                                <h4 className="font-medium text-blue-800 mb-2">Informasi Role:</h4>
                                <ul className="text-sm text-blue-700 space-y-1">
                                    <li>• <strong>Kasir:</strong> Hanya dapat membuat laporan penjualan dan transaksi</li>
                                    <li>• <strong>Karyawan:</strong> Hanya dapat membuat laporan stok barang</li>
                                    <li>• <strong>Admin/Owner:</strong> Dapat membuat semua jenis laporan</li>
                                    <li>• Data sensitif (harga beli, profit) hanya ditampilkan untuk Admin/Owner</li>
                                </ul>
                            </div>

                            <form onSubmit={handleSubmit} className="space-y-6">
                                {/* Title */}
                                <div className="space-y-2">
                                    <Label htmlFor="title">Judul Laporan <span className="text-red-500">*</span></Label>
                                    <Input
                                        id="title"
                                        type="text"
                                        placeholder="Masukkan judul laporan"
                                        value={formData.title}
                                        onChange={(e) => handleInputChange('title', e.target.value)}
                                        required
                                    />
                                </div>

                                {/* Type */}
                                <div className="space-y-2">
                                    <Label htmlFor="type">Jenis Laporan <span className="text-red-500">*</span></Label>
                                    <Select value={formData.type} onValueChange={(value) => handleInputChange('type', value)}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Pilih jenis laporan" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(reportTypes).map(([key, value]) => (
                                                <SelectItem key={key} value={key}>
                                                    {value}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>

                                {/* Period */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="period_from">Periode Dari <span className="text-red-500">*</span></Label>
                                        <Input
                                            id="period_from"
                                            type="date"
                                            value={formData.period_from}
                                            onChange={(e) => handleInputChange('period_from', e.target.value)}
                                            required
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="period_to">Periode Sampai <span className="text-red-500">*</span></Label>
                                        <Input
                                            id="period_to"
                                            type="date"
                                            value={formData.period_to}
                                            onChange={(e) => handleInputChange('period_to', e.target.value)}
                                            required
                                        />
                                    </div>
                                </div>

                                {/* Summary */}
                                <div className="space-y-2">
                                    <Label htmlFor="summary">Ringkasan (Opsional)</Label>
                                    <Textarea
                                        id="summary"
                                        placeholder="Tambahkan ringkasan atau catatan untuk laporan ini..."
                                        value={formData.summary}
                                        onChange={(e) => handleInputChange('summary', e.target.value)}
                                        rows={4}
                                    />
                                </div>

                                {/* Submit Button */}
                                <div className="flex justify-end gap-4 pt-6 border-t">
                                    <Link href={route('report-submissions.my-reports')}>
                                        <Button type="button" variant="outline">
                                            Batal
                                        </Button>
                                    </Link>
                                    <Button 
                                        type="submit" 
                                        disabled={!isFormValid() || isSubmitting}
                                    >
                                        {isSubmitting ? 'Membuat Laporan...' : 'Buat Laporan'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Info Card */}
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle className="text-lg">Informasi Proses Approval</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div className="text-center p-4 bg-blue-50 rounded-lg">
                                    <div className="text-2xl font-bold text-blue-600">1</div>
                                    <div className="text-sm font-medium text-blue-800 mt-2">Submit</div>
                                    <div className="text-xs text-blue-600 mt-1">Laporan disubmit untuk crosscheck</div>
                                </div>
                                <div className="text-center p-4 bg-yellow-50 rounded-lg">
                                    <div className="text-2xl font-bold text-yellow-600">2</div>
                                    <div className="text-sm font-medium text-yellow-800 mt-2">Crosscheck</div>
                                    <div className="text-xs text-yellow-600 mt-1">Kasir/karyawan lain melakukan crosscheck</div>
                                </div>
                                <div className="text-center p-4 bg-green-50 rounded-lg">
                                    <div className="text-2xl font-bold text-green-600">3</div>
                                    <div className="text-sm font-medium text-green-800 mt-2">Approval</div>
                                    <div className="text-xs text-green-600 mt-1">Owner/admin melakukan final approval</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
} 