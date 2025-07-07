import React, { useState } from 'react';
import { Head, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { ArrowLeft, CheckCircle, XCircle, Clock, Eye, FileText, User } from 'lucide-react';

interface Report {
    id: number;
    title: string;
    type: string;
    description: string;
    status: string;
    date_from: string;
    date_to: string;
    created_at: string;
    generator: {
        name: string;
        roles: Array<{
            name: string;
        }>;
    };
}

interface Props {
    pendingReports: {
        data: Report[];
        links: any;
        meta: any;
    };
}

export default function ApprovalPage({ pendingReports }: Props) {
    const [selectedReport, setSelectedReport] = useState<Report | null>(null);
    const [showApprovalDialog, setShowApprovalDialog] = useState(false);
    const [approvalAction, setApprovalAction] = useState<'approve' | 'reject'>('approve');

    const { data, setData, post, processing, reset } = useForm({
        action: '',
        notes: '',
    });

    const getTypeLabel = (type: string) => {
        switch (type) {
            case 'sales':
                return 'Laporan Penjualan';
            case 'stock':
                return 'Laporan Stok';
            case 'financial':
                return 'Laporan Keuangan';
            default:
                return type;
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'sales':
                return 'bg-blue-100 text-blue-800';
            case 'stock':
                return 'bg-green-100 text-green-800';
            case 'financial':
                return 'bg-purple-100 text-purple-800';
            default:
                return 'bg-gray-100 text-gray-800';
        }
    };

    const handleApproval = (report: Report, action: 'approve' | 'reject') => {
        setSelectedReport(report);
        setApprovalAction(action);
        setData('action', action);
        setShowApprovalDialog(true);
    };

    const handleSubmitApproval = (e: React.FormEvent) => {
        e.preventDefault();
        if (!selectedReport) return;

        post(`/reports/${selectedReport.id}/approval`, {
            onSuccess: () => {
                setShowApprovalDialog(false);
                setSelectedReport(null);
                reset();
            },
        });
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('id-ID', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    return (
        <AppLayout>
            <Head title="Approval Laporan" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" asChild>
                            <a href="/reports">
                                <ArrowLeft className="h-4 w-4 mr-2" />
                                Kembali ke Laporan
                            </a>
                        </Button>
                        <div>
                            <h1 className="text-3xl font-bold text-gray-900">Approval Laporan</h1>
                            <p className="text-gray-600">Setujui atau tolak laporan yang menunggu approval</p>
                        </div>
                    </div>
                </div>

                {/* Summary Card */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center space-x-2">
                            <Clock className="h-5 w-5 text-yellow-600" />
                            <span>Laporan Menunggu Approval</span>
                        </CardTitle>
                        <CardDescription>
                            {pendingReports.data.length} laporan menunggu persetujuan Anda
                        </CardDescription>
                    </CardHeader>
                </Card>

                {/* Pending Reports Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Laporan Pending</CardTitle>
                        <CardDescription>
                            Laporan yang memerlukan approval dari Owner
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul Laporan</TableHead>
                                    <TableHead>Tipe</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Dibuat Oleh</TableHead>
                                    <TableHead>Tanggal Dibuat</TableHead>
                                    <TableHead>Deskripsi</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pendingReports.data.map((report) => (
                                    <TableRow key={report.id}>
                                        <TableCell className="font-medium">
                                            {report.title}
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={getTypeColor(report.type)}>
                                                {getTypeLabel(report.type)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="text-sm">
                                                <div>{new Date(report.date_from).toLocaleDateString('id-ID')}</div>
                                                <div className="text-gray-500">s/d</div>
                                                <div>{new Date(report.date_to).toLocaleDateString('id-ID')}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <User className="h-4 w-4 text-gray-400" />
                                                <div>
                                                    <div className="font-medium">{report.generator.name}</div>
                                                    <div className="text-sm text-gray-500 capitalize">
                                                        {report.generator.roles[0]?.name}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            {formatDate(report.created_at)}
                                        </TableCell>
                                        <TableCell className="max-w-xs">
                                            <div className="truncate" title={report.description}>
                                                {report.description}
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    asChild
                                                >
                                                    <a href={`/reports/${report.id}`}>
                                                        <Eye className="h-4 w-4" />
                                                    </a>
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleApproval(report, 'approve')}
                                                    className="text-green-600 hover:text-green-700"
                                                >
                                                    <CheckCircle className="h-4 w-4" />
                                                </Button>
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => handleApproval(report, 'reject')}
                                                    className="text-red-600 hover:text-red-700"
                                                >
                                                    <XCircle className="h-4 w-4" />
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>

                        {pendingReports.data.length === 0 && (
                            <div className="text-center py-8">
                                <CheckCircle className="h-12 w-12 text-green-400 mx-auto mb-4" />
                                <h3 className="text-lg font-medium text-gray-900 mb-2">Semua laporan sudah diproses</h3>
                                <p className="text-gray-600">
                                    Tidak ada laporan yang menunggu approval saat ini
                                </p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Approval Dialog */}
                <Dialog open={showApprovalDialog} onOpenChange={setShowApprovalDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle className="flex items-center space-x-2">
                                {approvalAction === 'approve' ? (
                                    <CheckCircle className="h-5 w-5 text-green-600" />
                                ) : (
                                    <XCircle className="h-5 w-5 text-red-600" />
                                )}
                                <span>
                                    {approvalAction === 'approve' ? 'Setujui' : 'Tolak'} Laporan
                                </span>
                            </DialogTitle>
                            <DialogDescription>
                                {selectedReport && (
                                    <div className="space-y-2">
                                        <p>Anda akan {approvalAction === 'approve' ? 'menyetujui' : 'menolak'} laporan:</p>
                                        <div className="bg-gray-50 p-3 rounded-lg">
                                            <div className="font-medium">{selectedReport.title}</div>
                                            <div className="text-sm text-gray-600">{selectedReport.description}</div>
                                            <div className="text-sm text-gray-500 mt-1">
                                                Dibuat oleh: {selectedReport.generator.name}
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleSubmitApproval} className="space-y-4">
                            <div>
                                <Label htmlFor="notes">
                                    Catatan {approvalAction === 'approve' ? '(Opsional)' : '(Wajib)'}
                                </Label>
                                <Textarea
                                    id="notes"
                                    value={data.notes}
                                    onChange={(e) => setData('notes', e.target.value)}
                                    placeholder={
                                        approvalAction === 'approve' 
                                            ? 'Tambahkan catatan approval (opsional)' 
                                            : 'Berikan alasan penolakan'
                                    }
                                    rows={3}
                                    required={approvalAction === 'reject'}
                                />
                            </div>
                            <div className="flex justify-end space-x-2">
                                <Button 
                                    type="button" 
                                    variant="outline" 
                                    onClick={() => {
                                        setShowApprovalDialog(false);
                                        setSelectedReport(null);
                                        reset();
                                    }}
                                >
                                    Batal
                                </Button>
                                <Button 
                                    type="submit" 
                                    disabled={processing}
                                    className={approvalAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}
                                >
                                    {processing ? 'Memproses...' : (
                                        approvalAction === 'approve' ? 'Setujui Laporan' : 'Tolak Laporan'
                                    )}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>
        </AppLayout>
    );
}
