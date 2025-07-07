import React from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import { format } from 'date-fns';

interface Report {
    id: number;
    title: string;
    type: string;
    status: string;
    period_from: string;
    period_to: string;
    created_at: string;
    file_path: string;
    summary?: string;
    approval_notes?: string;
}

interface Props {
    reports: {
        data: Report[];
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
}

const statusBadge = (status: string) => {
    switch (status) {
        case 'pending_approval':
            return <Badge className="bg-orange-100 text-orange-800">Menunggu Approval</Badge>;
        case 'approved':
            return <Badge className="bg-green-100 text-green-800">Disetujui</Badge>;
        case 'rejected':
            return <Badge className="bg-red-100 text-red-800">Ditolak</Badge>;
        default:
            return <Badge>{status}</Badge>;
    }
};

export default function Reports({ reports }: Props) {
    const { auth } = usePage().props as any;
    if (!auth?.user?.roles?.some((r: any) => r.name === 'owner')) {
        return <div className="text-center text-red-500 mt-10">Laporan hanya dapat diakses oleh owner.</div>;
    }

    return (
        <AppLayout title="Laporan Owner">
            <Head title="Laporan Owner" />
            <div className="max-w-4xl mx-auto py-8">
                <h1 className="text-2xl font-bold mb-6">Daftar Laporan (PDF)</h1>
                <div className="space-y-4">
                    {reports.data.length === 0 && <div className="text-gray-500">Belum ada laporan.</div>}
                    {reports.data.map((report) => (
                        <Card key={report.id}>
                            <CardContent className="flex flex-col md:flex-row md:items-center md:justify-between gap-2 py-4">
                                <div>
                                    <div className="font-semibold">{report.title}</div>
                                    <div className="text-xs text-gray-500">Periode: {report.period_from} s/d {report.period_to}</div>
                                    <div className="text-xs text-gray-500">{report.summary}</div>
                                    <div className="mt-1">{statusBadge(report.status)}</div>
                                    {report.approval_notes && <div className="text-xs text-red-500 mt-1">Catatan: {report.approval_notes}</div>}
                                </div>
                                <div className="flex flex-col gap-2 items-end">
                                    <a
                                        href={`/owner/download-report/${report.id}`}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="text-blue-600 underline text-sm"
                                    >
                                        Download PDF
                                    </a>
                                    {report.status === 'pending_approval' && (
                                        <div className="flex gap-2">
                                            <Button size="sm" onClick={() => router.post(`/owner/reports/${report.id}/approve`, { action: 'approve' })}>
                                                Approve
                                            </Button>
                                            <Button size="sm" variant="destructive" onClick={() => router.post(`/owner/reports/${report.id}/approve`, { action: 'reject', approval_notes: 'Laporan ditolak oleh owner' })}>
                                                Reject
                                            </Button>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}
