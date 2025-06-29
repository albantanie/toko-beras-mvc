import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { FileText, ArrowLeft } from 'lucide-react';

interface ReportSubmission {
    id: number;
    title: string;
    type: string;
    status: string;
    period_from: string;
    period_to: string;
    summary?: string;
}

interface Props {
    report: ReportSubmission;
}

const breadcrumbs = [
    { title: 'Laporan', href: '/laporan' },
    { title: 'Laporan Saya', href: '/report-submissions/my-reports' },
    { title: 'Edit Laporan', href: '#' },
];

export default function EditReport({ report }: Props) {
    const [formData, setFormData] = useState({
        title: report.title,
        summary: report.summary || '',
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

        router.put(route('report-submissions.update', report.id), formData, {
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
        return formData.title.trim() !== '';
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit Laporan - ${report.title}`} />

            <div className="py-6">
                <div className="max-w-4xl mx-auto sm:px-6 lg:px-8">
                    {/* Header */}
                    <div className="mb-6">
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-2xl font-bold text-gray-900">Edit Laporan</h1>
                                <p className="text-gray-600">Edit laporan "{report.title}"</p>
                            </div>
                            <Link href={route('report-submissions.show', report.id)}>
                                <Button variant="outline">
                                    <ArrowLeft className="w-4 h-4 mr-2" />
                                    Kembali ke Detail
                                </Button>
                            </Link>
                        </div>
                    </div>

                    {/* Form */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="w-5 h-5" />
                                Edit Laporan
                            </CardTitle>
                            <CardDescription>
                                Edit informasi laporan. Hanya judul dan ringkasan yang dapat diubah.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
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

                                {/* Type (Read-only) */}
                                <div className="space-y-2">
                                    <Label htmlFor="type">Jenis Laporan</Label>
                                    <Input
                                        id="type"
                                        type="text"
                                        value={report.type}
                                        disabled
                                        className="bg-gray-50"
                                    />
                                    <p className="text-xs text-gray-500">Jenis laporan tidak dapat diubah</p>
                                </div>

                                {/* Period (Read-only) */}
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div className="space-y-2">
                                        <Label htmlFor="period_from">Periode Dari</Label>
                                        <Input
                                            id="period_from"
                                            type="date"
                                            value={report.period_from}
                                            disabled
                                            className="bg-gray-50"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="period_to">Periode Sampai</Label>
                                        <Input
                                            id="period_to"
                                            type="date"
                                            value={report.period_to}
                                            disabled
                                            className="bg-gray-50"
                                        />
                                    </div>
                                </div>
                                <p className="text-xs text-gray-500">Periode laporan tidak dapat diubah</p>

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
                                    <Link href={route('report-submissions.show', report.id)}>
                                        <Button type="button" variant="outline">
                                            Batal
                                        </Button>
                                    </Link>
                                    <Button 
                                        type="submit" 
                                        disabled={!isFormValid() || isSubmitting}
                                    >
                                        {isSubmitting ? 'Menyimpan...' : 'Simpan Perubahan'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    {/* Info Card */}
                    <Card className="mt-6">
                        <CardHeader>
                            <CardTitle className="text-lg">Informasi Status</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-sm text-gray-600">
                                <p><strong>Status saat ini:</strong> {report.status}</p>
                                <p className="mt-2">
                                    Laporan hanya dapat diedit jika statusnya adalah draft, crosscheck rejected, atau rejected.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
} 