import React, { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { format } from 'date-fns';
import { Download, FileText, Calendar, Filter, Plus } from 'lucide-react';
import Swal from 'sweetalert2';

interface PdfReport {
    id: number;
    title: string;
    type: 'financial' | 'stock';
    report_date: string;
    period_from?: string;
    period_to?: string;
    file_name: string;
    file_path: string;
    status: 'pending' | 'approved' | 'rejected';
    generated_by: number;
    approved_by?: number;
    approved_at?: string;
    approval_notes?: string;
    report_data?: any;
    created_at: string;
    generator?: {
        id: number;
        name: string;
    };
    approver?: {
        id: number;
        name: string;
    };
}

interface Props {
    reports: {
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
    };
    filters: {
        type?: string;
        status?: string;
        date_from?: string;
        date_to?: string;
    };
}

const statusBadge = (status: string) => {
    switch (status) {
        case 'pending':
            return <Badge className="bg-orange-100 text-orange-800">Pending Approval</Badge>;
        case 'approved':
            return <Badge className="bg-green-100 text-green-800">Approved</Badge>;
        case 'rejected':
            return <Badge className="bg-red-100 text-red-800">Rejected</Badge>;
        default:
            return <Badge>{status}</Badge>;
    }
};

const typeBadge = (type: string) => {
    switch (type) {
        case 'financial':
            return <Badge variant="outline" className="bg-blue-50 text-blue-700">Financial Report</Badge>;
        case 'stock':
            return <Badge variant="outline" className="bg-purple-50 text-purple-700">Stock Report</Badge>;
        default:
            return <Badge variant="outline">{type}</Badge>;
    }
};

export default function PdfReports({ reports, filters }: Props) {
    const { auth } = usePage().props as any;
    const [localFilters, setLocalFilters] = useState(filters);

    // Check if user is OWNER
    if (!auth?.user?.roles?.some((r: any) => r.name === 'owner')) {
        return (
            <AppLayout title="Access Denied">
                <div className="text-center text-red-500 mt-10">
                    <h2 className="text-xl font-semibold mb-2">Access Denied</h2>
                    <p>Only OWNER role can access PDF reports.</p>
                </div>
            </AppLayout>
        );
    }

    const handleFilterChange = (key: string, value: string) => {
        const newFilters = { ...localFilters, [key]: value };
        setLocalFilters(newFilters);
        router.get(route('owner.laporan.reports'), newFilters, { preserveState: true });
    };

    const handleApproval = async (reportId: number, action: 'approve' | 'reject') => {
        let notes = '';
        
        if (action === 'reject') {
            const result = await Swal.fire({
                title: 'Reject Report',
                text: 'Please provide a reason for rejection:',
                input: 'textarea',
                inputPlaceholder: 'Enter rejection reason...',
                showCancelButton: true,
                confirmButtonText: 'Reject',
                confirmButtonColor: '#dc2626',
                cancelButtonText: 'Cancel',
                inputValidator: (value) => {
                    if (!value) {
                        return 'Please provide a reason for rejection';
                    }
                }
            });

            if (!result.isConfirmed) return;
            notes = result.value;
        } else {
            const result = await Swal.fire({
                title: 'Approve Report',
                text: 'Are you sure you want to approve this report?',
                showCancelButton: true,
                confirmButtonText: 'Approve',
                confirmButtonColor: '#16a34a',
                cancelButtonText: 'Cancel'
            });

            if (!result.isConfirmed) return;
        }

        router.post(`/owner/reports/${reportId}/approve`, {
            action,
            approval_notes: notes
        }, {
            onSuccess: () => {
                Swal.fire({
                    title: 'Success!',
                    text: `Report ${action}d successfully`,
                    icon: 'success',
                    timer: 2000,
                    showConfirmButton: false
                });
            },
            onError: () => {
                Swal.fire({
                    title: 'Error!',
                    text: `Failed to ${action} report`,
                    icon: 'error'
                });
            }
        });
    };

    const generateFinancialReport = async () => {
        const result = await Swal.fire({
            title: 'Generate Financial Report',
            html: `
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">From Date:</label>
                        <input type="date" id="date_from" class="w-full p-2 border rounded" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">To Date:</label>
                        <input type="date" id="date_to" class="w-full p-2 border rounded" required>
                    </div>
                </div>
            `,
            showCancelButton: true,
            confirmButtonText: 'Generate',
            preConfirm: () => {
                const dateFrom = (document.getElementById('date_from') as HTMLInputElement).value;
                const dateTo = (document.getElementById('date_to') as HTMLInputElement).value;
                
                if (!dateFrom || !dateTo) {
                    Swal.showValidationMessage('Please select both dates');
                    return false;
                }
                
                if (new Date(dateFrom) > new Date(dateTo)) {
                    Swal.showValidationMessage('From date must be before or equal to To date');
                    return false;
                }
                
                return { date_from: dateFrom, date_to: dateTo };
            }
        });

        if (result.isConfirmed) {
            router.post('/owner/generate-financial-report', result.value, {
                onSuccess: () => {
                    Swal.fire('Success!', 'Financial report generated successfully', 'success');
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to generate financial report', 'error');
                }
            });
        }
    };

    const generateStockReport = async () => {
        const result = await Swal.fire({
            title: 'Generate Stock Report',
            text: 'This will generate a current stock status report. Continue?',
            showCancelButton: true,
            confirmButtonText: 'Generate',
            confirmButtonColor: '#16a34a'
        });

        if (result.isConfirmed) {
            router.post('/owner/generate-stock-report', {}, {
                onSuccess: () => {
                    Swal.fire('Success!', 'Stock report generated successfully', 'success');
                },
                onError: () => {
                    Swal.fire('Error!', 'Failed to generate stock report', 'error');
                }
            });
        }
    };

    return (
        <AppLayout title="PDF Reports">
            <Head title="PDF Reports" />
            <div className="max-w-6xl mx-auto py-8">
                {/* Header */}
                <div className="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <div>
                        <h1 className="text-2xl font-bold mb-2">PDF Reports Management</h1>
                        <p className="text-gray-600">Generate, manage, and approve financial and stock reports</p>
                    </div>
                    <div className="flex gap-2 mt-4 md:mt-0">
                        <Button onClick={generateFinancialReport} className="flex items-center gap-2">
                            <Plus className="w-4 h-4" />
                            Generate Financial Report
                        </Button>
                        <Button onClick={generateStockReport} variant="outline" className="flex items-center gap-2">
                            <Plus className="w-4 h-4" />
                            Generate Stock Report
                        </Button>
                    </div>
                </div>

                {/* Filters */}
                <Card className="mb-6">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Filter className="w-4 h-4" />
                            Filters
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label className="block text-sm font-medium mb-1">Type</label>
                                <Select value={localFilters.type || 'all'} onValueChange={(value) => handleFilterChange('type', value === 'all' ? '' : value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Types" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Types</SelectItem>
                                        <SelectItem value="financial">Financial</SelectItem>
                                        <SelectItem value="stock">Stock</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">Status</label>
                                <Select value={localFilters.status || 'all'} onValueChange={(value) => handleFilterChange('status', value === 'all' ? '' : value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="All Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">All Status</SelectItem>
                                        <SelectItem value="pending">Pending</SelectItem>
                                        <SelectItem value="approved">Approved</SelectItem>
                                        <SelectItem value="rejected">Rejected</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">From Date</label>
                                <Input
                                    type="date"
                                    value={localFilters.date_from || ''}
                                    onChange={(e) => handleFilterChange('date_from', e.target.value)}
                                />
                            </div>
                            <div>
                                <label className="block text-sm font-medium mb-1">To Date</label>
                                <Input
                                    type="date"
                                    value={localFilters.date_to || ''}
                                    onChange={(e) => handleFilterChange('date_to', e.target.value)}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Reports List */}
                <div className="space-y-4">
                    {reports.data.length === 0 ? (
                        <Card>
                            <CardContent className="text-center py-8">
                                <FileText className="w-12 h-12 text-gray-400 mx-auto mb-4" />
                                <p className="text-gray-500">No reports found. Generate your first report above.</p>
                            </CardContent>
                        </Card>
                    ) : (
                        reports.data.map((report) => (
                            <Card key={report.id}>
                                <CardContent className="py-4">
                                    <div className="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                        <div className="flex-1">
                                            <div className="flex items-center gap-2 mb-2">
                                                <h3 className="font-semibold">{report.title}</h3>
                                                {typeBadge(report.type)}
                                                {statusBadge(report.status)}
                                            </div>
                                            <div className="text-sm text-gray-600 space-y-1">
                                                <div className="flex items-center gap-2">
                                                    <Calendar className="w-4 h-4" />
                                                    <span>Generated: {format(new Date(report.report_date), 'dd MMM yyyy')}</span>
                                                </div>
                                                {report.period_from && report.period_to && (
                                                    <div>Period: {format(new Date(report.period_from), 'dd MMM yyyy')} - {format(new Date(report.period_to), 'dd MMM yyyy')}</div>
                                                )}
                                                {report.approval_notes && (
                                                    <div className="text-red-600">Notes: {report.approval_notes}</div>
                                                )}
                                                {report.approved_at && (
                                                    <div>
                                                        {report.status === 'approved' ? 'Approved' : 'Rejected'} on {format(new Date(report.approved_at), 'dd MMM yyyy HH:mm')}
                                                    </div>
                                                )}
                                            </div>
                                        </div>
                                        <div className="flex flex-col sm:flex-row gap-2">
                                            <a
                                                href={`/owner/download-report/${report.id}`}
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                className="inline-flex items-center justify-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors text-sm"
                                            >
                                                <Download className="w-4 h-4" />
                                                Download PDF
                                            </a>
                                            {report.status === 'pending' && (
                                                <div className="flex gap-2">
                                                    <Button 
                                                        size="sm" 
                                                        onClick={() => handleApproval(report.id, 'approve')}
                                                        className="bg-green-600 hover:bg-green-700"
                                                    >
                                                        Approve
                                                    </Button>
                                                    <Button 
                                                        size="sm" 
                                                        variant="destructive" 
                                                        onClick={() => handleApproval(report.id, 'reject')}
                                                    >
                                                        Reject
                                                    </Button>
                                                </div>
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
                    <div className="flex justify-center mt-6">
                        <div className="flex gap-2">
                            {reports.links.map((link, index) => (
                                <button
                                    key={index}
                                    onClick={() => link.url && router.get(link.url)}
                                    disabled={!link.url}
                                    className={`px-3 py-2 text-sm rounded ${
                                        link.active
                                            ? 'bg-blue-600 text-white'
                                            : link.url
                                            ? 'bg-gray-200 text-gray-700 hover:bg-gray-300'
                                            : 'bg-gray-100 text-gray-400 cursor-not-allowed'
                                    }`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ))}
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
