import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { PageProps, BreadcrumbItem } from '@/types';
import { formatCurrency, formatDateTime, StatusBadge, Icons } from '@/utils/formatters';
import { useState } from 'react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import Swal from 'sweetalert2';

// Use global route function
declare global {
    function route(name: string, params?: any): string;
}

interface PdfReport {
    id: number;
    title: string;
    type: string;
    report_date: string;
    period_from?: string;
    period_to?: string;
    file_name: string;
    status: 'pending' | 'approved' | 'rejected';
    generated_by: number;
    approved_by?: number;
    approved_at?: string;
    approval_notes?: string;
    created_at: string;
    generator: {
        id: number;
        name: string;
    };
    approver?: {
        id: number;
        name: string;
    };
}

interface PaginatedReports {
    data: PdfReport[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface MyReportsProps extends PageProps {
    reports: PaginatedReports;
    filters?: {
        type?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
    allowedTypes: string[];
    userRole: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Laporan',
        href: '/laporan',
    },
    {
        title: 'Laporan Saya',
        href: '/laporan/my-reports',
    },
];

const typeLabels: Record<string, string> = {
    'penjualan': 'Laporan Penjualan',
    'stock': 'Laporan Stok',
    'financial': 'Laporan Keuangan',
};

const statusColors: Record<string, string> = {
    'pending': 'bg-yellow-100 text-yellow-800',
    'approved': 'bg-green-100 text-green-800',
    'rejected': 'bg-red-100 text-red-800',
};

const statusLabels: Record<string, string> = {
    'pending': 'Menunggu Persetujuan',
    'approved': 'Disetujui',
    'rejected': 'Ditolak',
};

export default function MyReports({ auth, reports, filters = {}, allowedTypes = [], userRole = '' }: MyReportsProps) {
    const [typeFilter, setTypeFilter] = useState(filters.type || '');
    const [statusFilter, setStatusFilter] = useState(filters.status || '');
    const [dateFrom, setDateFrom] = useState(filters.date_from || '');
    const [dateTo, setDateTo] = useState(filters.date_to || '');

    // Safety checks
    const safeReports = reports || { data: [], current_page: 1, last_page: 1, per_page: 10, total: 0, links: [] };
    const safeAllowedTypes = allowedTypes || [];
    const safeAuth = auth || { user: null };

    const handleFilter = () => {
        const params = new URLSearchParams();
        if (typeFilter) params.set('type', typeFilter);
        if (statusFilter) params.set('status', statusFilter);
        if (dateFrom) params.set('date_from', dateFrom);
        if (dateTo) params.set('date_to', dateTo);
        
        window.location.href = `${window.location.pathname}?${params.toString()}`;
    };

    const canGenerateReport = (type: string) => {
        return safeAllowedTypes.includes(type);
    };

    const getGenerateUrl = (type: string) => {
        if (type === 'penjualan') {
            return '/laporan/generate-sales';
        } else if (type === 'stock') {
            return '/laporan/generate-stock';
        }
        return '#';
    };

    const handleGenerateReport = async (type: string) => {
        const typeLabel = typeLabels[type];

        // For sales report, get dates from form
        if (type === 'penjualan') {
            const dateFrom = (document.getElementById('date_from') as HTMLInputElement)?.value;
            const dateTo = (document.getElementById('date_to') as HTMLInputElement)?.value;

            if (!dateFrom || !dateTo) {
                Swal.fire({
                    title: 'Error!',
                    text: 'Mohon isi tanggal dari dan sampai',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Show confirmation dialog
            const result = await Swal.fire({
                title: `Generate ${typeLabel}?`,
                text: `Apakah Anda yakin ingin generate ${typeLabel.toLowerCase()} dari ${dateFrom} sampai ${dateTo}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Generate!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            });

            if (result.isConfirmed) {
                // Create and submit form directly
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/laporan/generate-sales';

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                form.appendChild(csrfInput);

                // Add date inputs
                const dateFromInput = document.createElement('input');
                dateFromInput.type = 'hidden';
                dateFromInput.name = 'date_from';
                dateFromInput.value = dateFrom;
                form.appendChild(dateFromInput);

                const dateToInput = document.createElement('input');
                dateToInput.type = 'hidden';
                dateToInput.name = 'date_to';
                dateToInput.value = dateTo;
                form.appendChild(dateToInput);

                // Submit form
                document.body.appendChild(form);
                form.submit();
            }
        } else {
            // For stock report
            const result = await Swal.fire({
                title: `Generate ${typeLabel}?`,
                text: `Apakah Anda yakin ingin generate ${typeLabel.toLowerCase()}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Ya, Generate!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            });

            if (result.isConfirmed) {
                // Create and submit form directly
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '/laporan/generate-stock';

                // Add CSRF token
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = '_token';
                csrfInput.value = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                form.appendChild(csrfInput);

                // Submit form
                document.body.appendChild(form);
                form.submit();
            }
        }
    };

    return (
        <AppLayout
            breadcrumbs={breadcrumbs}
        >
            <Head title="Laporan Saya" />

            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">Laporan Saya</h1>
                        <p className="text-gray-600">Kelola dan pantau status laporan yang Anda ajukan</p>
                        <div className="mt-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p className="text-sm text-blue-800">
                                💡 <strong>Info:</strong> Sebagai {userRole.toUpperCase()}, Anda dapat mengajukan{' '}
                                {allowedTypes.map(type => typeLabels[type]).join(' dan ')}.
                                Laporan akan ditinjau oleh Owner sebelum disetujui.
                            </p>
                        </div>
                    </div>
                </div>

                {/* Generate Report Buttons */}
                <div className="flex flex-wrap gap-4">
                    {safeAllowedTypes.map(type => (
                        <Card key={type} className="flex-1 min-w-[300px]">
                            <CardHeader>
                                <CardTitle className="text-lg">{typeLabels[type]}</CardTitle>
                                <CardDescription>
                                    {type === 'penjualan' && 'Generate laporan penjualan untuk periode tertentu'}
                                    {type === 'stock' && 'Generate laporan stok dan inventory saat ini'}
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                {type === 'penjualan' ? (
                                    <div className="space-y-4">
                                        <div className="grid grid-cols-2 gap-4">
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                                                <input
                                                    type="date"
                                                    id="date_from"
                                                    required
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                                                <input
                                                    type="date"
                                                    id="date_to"
                                                    required
                                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                                />
                                            </div>
                                        </div>
                                        <Button
                                            type="button"
                                            className="w-full"
                                            onClick={() => handleGenerateReport(type)}
                                        >
                                            <Icons.fileText className="w-4 h-4 mr-2" />
                                            Generate Laporan Penjualan
                                        </Button>
                                    </div>
                                ) : (
                                    <div>
                                        <Button
                                            onClick={() => handleGenerateReport(type)}
                                            className="w-full"
                                        >
                                            <Icons.package className="w-4 h-4 mr-2" />
                                            Generate Laporan Stok
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    ))}
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter Laporan</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Jenis Laporan</label>
                                <select
                                    value={typeFilter}
                                    onChange={(e) => setTypeFilter(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option value="">Semua Jenis</option>
                                    {allowedTypes.map(type => (
                                        <option key={type} value={type}>{typeLabels[type]}</option>
                                    ))}
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Status</label>
                                <select
                                    value={statusFilter}
                                    onChange={(e) => setStatusFilter(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                >
                                    <option value="">Semua Status</option>
                                    <option value="pending">Menunggu Persetujuan</option>
                                    <option value="approved">Disetujui</option>
                                    <option value="rejected">Ditolak</option>
                                </select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Dari Tanggal</label>
                                <input
                                    type="date"
                                    value={dateFrom}
                                    onChange={(e) => setDateFrom(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium text-gray-700">Sampai Tanggal</label>
                                <input
                                    type="date"
                                    value={dateTo}
                                    onChange={(e) => setDateTo(e.target.value)}
                                    className="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                                />
                            </div>
                        </div>
                        <div className="mt-4">
                            <Button onClick={handleFilter}>
                                <Icons.filter className="w-4 h-4 mr-2" />
                                Terapkan Filter
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                {/* Reports List */}
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Laporan</CardTitle>
                        <CardDescription>
                            Total: {safeReports.total} laporan
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {safeReports.data.length === 0 ? (
                            <div className="text-center py-8">
                                <Icons.fileText className="w-12 h-12 mx-auto text-gray-400 mb-4" />
                                <p className="text-gray-500">Belum ada laporan yang diajukan</p>
                                <p className="text-sm text-gray-400">Gunakan tombol di atas untuk mengajukan laporan baru</p>
                            </div>
                        ) : (
                            <div className="space-y-4">
                                {safeReports.data.map((report) => (
                                    <div key={report.id} className="border rounded-lg p-4 hover:bg-gray-50">
                                        <div className="flex justify-between items-start">
                                            <div className="flex-1">
                                                <div className="flex items-center space-x-2 mb-2">
                                                    <h3 className="font-medium text-gray-900">{report.title}</h3>
                                                    <Badge variant="outline">{typeLabels[report.type]}</Badge>
                                                    <Badge className={statusColors[report.status]}>
                                                        {statusLabels[report.status]}
                                                    </Badge>
                                                </div>
                                                <div className="text-sm text-gray-600 space-y-1">
                                                    <p>Dibuat: {formatDateTime(report.created_at)}</p>
                                                    {report.period_from && report.period_to && (
                                                        <p>Periode: {formatDateTime(report.period_from)} - {formatDateTime(report.period_to)}</p>
                                                    )}
                                                    {report.approved_at && (
                                                        <p>Disetujui: {formatDateTime(report.approved_at)} oleh {report.approver?.name}</p>
                                                    )}
                                                    {report.approval_notes && (
                                                        <p className="text-blue-600">Catatan: {report.approval_notes}</p>
                                                    )}
                                                </div>
                                            </div>
                                            <div className="flex space-x-2">
                                                {report.status === 'approved' && (
                                                    <a
                                                        href={`/laporan/download/${report.id}`}
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500"
                                                    >
                                                        <Icons.download className="w-4 h-4 mr-1" />
                                                        Download
                                                    </a>
                                                )}
                                            </div>
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
