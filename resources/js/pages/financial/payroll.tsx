import React, { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Users, DollarSign, Calendar, Plus, Eye, CreditCard, Settings } from 'lucide-react';

interface Payroll {
    id: number;
    payroll_code: string;
    user: {
        id: number;
        name: string;
        email: string;
    };
    period_month: string;
    period_start: string;
    period_end: string;
    basic_salary: number;
    overtime_amount: number;
    bonus_amount: number;
    allowance_amount: number;
    gross_salary: number;
    tax_amount: number;
    insurance_amount: number;
    deduction_amount: number;
    net_salary: number;
    status: string;
    payment_date: string | null;
    created_at: string;
    breakdown: {
        basic_salary_details: any;
        overtime_details: any;
        bonus_details: any;
        allowance_details: any;
        deduction_details: any;
        tax_details: any;
        insurance_details: any;
    };
}

interface PayrollSummary {
    total_gross_salary: number;
    total_net_salary: number;
    total_deductions: number;
    employees_count: number;
    paid_count: number;
    pending_count: number;
}

interface Props {
    payrolls: {
        data: Payroll[];
        links: any;
        meta: any;
    };
    summary: PayrollSummary;
    filters: {
        period?: string;
        status?: string;
    };
}

export default function PayrollPage({ payrolls, summary, filters }: Props) {
    const [selectedPayroll, setSelectedPayroll] = useState<Payroll | null>(null);
    const [showGenerateDialog, setShowGenerateDialog] = useState(false);
    const [showPaymentDialog, setShowPaymentDialog] = useState(false);
    const [showCancelDialog, setShowCancelDialog] = useState(false);

    const { data: generateData, setData: setGenerateData, post: postGenerate, processing: generatingPayroll } = useForm({
        period: new Date().toISOString().slice(0, 7), // YYYY-MM format
        user_ids: [] as number[],
    });

    const { data: paymentData, setData: setPaymentData, post: postPayment, processing: processingPayment } = useForm({
        account_id: '',
        payment_notes: '',
    });

    const { data: cancelData, setData: setCancelData, post: postCancel, processing: processingCancel } = useForm({
        cancellation_reason: '',
    });

    const formatCurrency = (amount: number) => {
        return new Intl.NumberFormat('id-ID', {
            style: 'currency',
            currency: 'IDR',
            minimumFractionDigits: 0,
        }).format(amount);
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'paid': return 'bg-green-100 text-green-800';
            case 'approved': return 'bg-blue-100 text-blue-800';
            case 'draft': return 'bg-yellow-100 text-yellow-800';
            case 'cancelled': return 'bg-red-100 text-red-800';
            default: return 'bg-gray-100 text-gray-800';
        }
    };

    const getStatusText = (status: string) => {
        switch (status) {
            case 'paid': return 'Sudah Dibayar';
            case 'approved': return 'Disetujui - Siap Dibayar';
            case 'draft': return 'Menunggu Persetujuan';
            case 'cancelled': return 'Dibatalkan';
            default: return status;
        }
    };

    const handleGeneratePayroll = (e: React.FormEvent) => {
        e.preventDefault();
        postGenerate('/owner/keuangan/payroll/generate', {
            onSuccess: () => {
                setShowGenerateDialog(false);
                setGenerateData('period', new Date().toISOString().slice(0, 7));
                setGenerateData('user_ids', []);
            },
        });
    };

    const handleApprovePayroll = (payrollId: number) => {
        if (confirm('Apakah Anda yakin ingin menyetujui gaji ini?')) {
            router.post(`/owner/keuangan/payroll/${payrollId}/approve`, {}, {
                onSuccess: () => {
                    // Page will reload automatically
                },
                onError: (errors) => {
                    console.error('Approve failed:', errors);
                }
            });
        }
    };

    const handleProcessPayment = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedPayroll) {
            postPayment(`/owner/keuangan/payroll/${selectedPayroll.id}/payment`, {
                onSuccess: () => {
                    setShowPaymentDialog(false);
                    setSelectedPayroll(null);
                    setPaymentData('account_id', '');
                    setPaymentData('payment_notes', '');
                },
            });
        }
    };

    const handleCancelPayroll = (e: React.FormEvent) => {
        e.preventDefault();
        if (selectedPayroll) {
            postCancel(`/owner/keuangan/payroll/${selectedPayroll.id}/cancel`, {
                onSuccess: () => {
                    setShowCancelDialog(false);
                    setSelectedPayroll(null);
                    setCancelData('cancellation_reason', '');
                },
            });
        }
    };

    return (
        <AppLayout>
            <Head title="Manajemen Gaji" />
            
            <div className="space-y-6">
                {/* Header */}
                <div className="flex justify-between items-center">
                    <div>
                        <h1 className="text-3xl font-bold text-gray-900">💰 Manajemen Gaji Karyawan</h1>
                        <p className="text-gray-600">Kelola pembayaran gaji dan tunjangan karyawan dengan mudah</p>
                        <div className="mt-3 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <p className="text-sm text-yellow-800">
                                ⚠️ <strong>Penting:</strong> Gaji hanya bisa di-generate sekali per bulan untuk setiap karyawan.
                                Pastikan data sudah benar sebelum melakukan pembayaran.
                            </p>
                        </div>
                        <div className="mt-3 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                            <p className="text-sm text-blue-800">
                                📋 <strong>Alur Pembayaran Gaji:</strong><br/>
                                1️⃣ Generate gaji → Status: <span className="font-semibold text-yellow-600">Menunggu Persetujuan</span><br/>
                                2️⃣ Setujui gaji → Status: <span className="font-semibold text-blue-600">Disetujui - Siap Dibayar</span><br/>
                                3️⃣ Bayar gaji → Status: <span className="font-semibold text-green-600">Sudah Dibayar</span> (masuk ke pengeluaran)
                            </p>
                        </div>
                    </div>
                    <div className="flex items-center space-x-4">
                        <Button variant="outline" asChild>
                            <a href="/owner/keuangan/payroll-configuration">
                                <Settings className="h-4 w-4 mr-2" />
                                Konfigurasi
                            </a>
                        </Button>
                        <Button onClick={() => setShowGenerateDialog(true)}>
                            <Plus className="h-4 w-4 mr-2" />
                            Generate Gaji
                        </Button>
                    </div>
                </div>

                {/* Summary Cards */}
                <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Karyawan</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{summary.employees_count}</div>
                            <p className="text-xs text-muted-foreground">
                                Dibayar: {summary.paid_count} | Pending: {summary.pending_count}
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Gaji Kotor</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-blue-600">
                                {formatCurrency(summary.total_gross_salary)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Potongan</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-red-600">
                                {formatCurrency(summary.total_deductions)}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Gaji Bersih</CardTitle>
                            <DollarSign className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">
                                {formatCurrency(summary.total_net_salary)}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardHeader>
                        <CardTitle>Filter</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex items-center space-x-4">
                            <div className="flex-1">
                                <Label htmlFor="period">Periode</Label>
                                <Input
                                    id="period"
                                    type="month"
                                    defaultValue={filters.period}
                                    onChange={(e) => {
                                        const url = new URL(window.location.href);
                                        url.searchParams.set('period', e.target.value);
                                        window.location.href = url.toString();
                                    }}
                                />
                            </div>
                            <div className="flex-1">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    defaultValue={filters.status || 'all'}
                                    onValueChange={(value) => {
                                        const url = new URL(window.location.href);
                                        if (value && value !== 'all') {
                                            url.searchParams.set('status', value);
                                        } else {
                                            url.searchParams.delete('status');
                                        }
                                        window.location.href = url.toString();
                                    }}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Semua Status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="all">Semua Status</SelectItem>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="approved">Disetujui</SelectItem>
                                        <SelectItem value="paid">Dibayar</SelectItem>
                                        <SelectItem value="cancelled">Dibatalkan</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Payroll Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Daftar Gaji</CardTitle>
                        <CardDescription>Daftar gaji karyawan berdasarkan periode dan filter yang dipilih</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Kode</TableHead>
                                    <TableHead>Karyawan</TableHead>
                                    <TableHead>Periode</TableHead>
                                    <TableHead>Gaji Kotor</TableHead>
                                    <TableHead>Potongan</TableHead>
                                    <TableHead>Gaji Bersih</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {payrolls.data.map((payroll) => (
                                    <TableRow key={payroll.id}>
                                        <TableCell className="font-medium">{payroll.payroll_code}</TableCell>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{payroll.user.name}</div>
                                                <div className="text-sm text-gray-500">{payroll.user.email}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>{payroll.period_month}</TableCell>
                                        <TableCell>{formatCurrency(payroll.gross_salary)}</TableCell>
                                        <TableCell className="text-red-600">
                                            {formatCurrency(payroll.tax_amount + payroll.insurance_amount + payroll.deduction_amount)}
                                        </TableCell>
                                        <TableCell className="font-medium text-green-600">
                                            {formatCurrency(payroll.net_salary)}
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={getStatusColor(payroll.status)}>
                                                {getStatusText(payroll.status)}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex items-center space-x-2">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => setSelectedPayroll(payroll)}
                                                    title="Lihat Detail"
                                                >
                                                    <Eye className="h-4 w-4" />
                                                </Button>

                                                {/* Tombol Setujui - hanya untuk status draft */}
                                                {payroll.status === 'draft' && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="text-blue-600 border-blue-600 hover:bg-blue-50"
                                                        onClick={() => handleApprovePayroll(payroll.id)}
                                                        title="Setujui Gaji"
                                                    >
                                                        ✓
                                                    </Button>
                                                )}

                                                {/* Tombol Bayar - hanya untuk status approved */}
                                                {payroll.status === 'approved' && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="text-green-600 border-green-600 hover:bg-green-50"
                                                        onClick={() => {
                                                            setSelectedPayroll(payroll);
                                                            setShowPaymentDialog(true);
                                                        }}
                                                        title="Bayar Gaji"
                                                    >
                                                        <CreditCard className="h-4 w-4" />
                                                    </Button>
                                                )}

                                                {/* Tombol Batal - untuk status draft dan approved */}
                                                {(payroll.status === 'draft' || payroll.status === 'approved') && (
                                                    <Button
                                                        variant="outline"
                                                        size="sm"
                                                        className="text-red-600 border-red-600 hover:bg-red-50"
                                                        onClick={() => {
                                                            setSelectedPayroll(payroll);
                                                            setShowCancelDialog(true);
                                                        }}
                                                        title="Batalkan Gaji"
                                                    >
                                                        ✕
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {/* Generate Payroll Dialog */}
                <Dialog open={showGenerateDialog} onOpenChange={setShowGenerateDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Generate Gaji</DialogTitle>
                            <DialogDescription>
                                Buat gaji untuk periode tertentu. Sistem akan menghitung gaji berdasarkan data karyawan.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleGeneratePayroll} className="space-y-4">
                            <div>
                                <Label htmlFor="generate_period">Periode</Label>
                                <Input
                                    id="generate_period"
                                    type="month"
                                    value={generateData.period}
                                    onChange={(e) => setGenerateData('period', e.target.value)}
                                    required
                                />
                            </div>
                            <div className="flex justify-end space-x-2">
                                <Button type="button" variant="outline" onClick={() => setShowGenerateDialog(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" disabled={generatingPayroll}>
                                    {generatingPayroll ? 'Generating...' : 'Generate'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Payment Dialog */}
                <Dialog open={showPaymentDialog} onOpenChange={setShowPaymentDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Proses Pembayaran Gaji</DialogTitle>
                            <DialogDescription>
                                Proses pembayaran gaji untuk {selectedPayroll?.user.name}
                            </DialogDescription>
                        </DialogHeader>
                        {selectedPayroll && (
                            <div className="space-y-4">
                                <div className="bg-gray-50 p-4 rounded-lg">
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span className="text-gray-600">Karyawan:</span>
                                            <div className="font-medium">{selectedPayroll.user.name}</div>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Periode:</span>
                                            <div className="font-medium">{selectedPayroll.period_month}</div>
                                        </div>
                                        <div>
                                            <span className="text-gray-600">Gaji Bersih:</span>
                                            <div className="font-medium text-green-600">
                                                {formatCurrency(selectedPayroll.net_salary)}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <form onSubmit={handleProcessPayment} className="space-y-4">
                                    <div>
                                        <Label htmlFor="account_id">Akun Pembayaran</Label>
                                        <Select
                                            value={paymentData.account_id}
                                            onValueChange={(value) => setPaymentData('account_id', value)}
                                            required
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Pilih akun pembayaran" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="1">Kas Utama</SelectItem>
                                                <SelectItem value="2">Bank BCA</SelectItem>
                                                <SelectItem value="3">Bank Mandiri</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div>
                                        <Label htmlFor="payment_notes">Catatan Pembayaran (Opsional)</Label>
                                        <textarea
                                            id="payment_notes"
                                            className="w-full p-2 border border-gray-300 rounded-md"
                                            rows={2}
                                            value={paymentData.payment_notes}
                                            onChange={(e) => setPaymentData('payment_notes', e.target.value)}
                                            placeholder="Catatan tambahan untuk pembayaran..."
                                        />
                                    </div>
                                    <div className="flex justify-end space-x-2">
                                        <Button type="button" variant="outline" onClick={() => setShowPaymentDialog(false)}>
                                            Batal
                                        </Button>
                                        <Button type="submit" disabled={processingPayment}>
                                            {processingPayment ? 'Processing...' : 'Proses Pembayaran'}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                        )}
                    </DialogContent>
                </Dialog>

                {/* Cancel Payroll Dialog */}
                <Dialog open={showCancelDialog} onOpenChange={setShowCancelDialog}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Batalkan Gaji</DialogTitle>
                            <DialogDescription>
                                Batalkan gaji untuk {selectedPayroll?.user.name} periode {selectedPayroll?.period_month}
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleCancelPayroll} className="space-y-4">
                            <div>
                                <Label htmlFor="cancellation_reason">Alasan Pembatalan</Label>
                                <textarea
                                    id="cancellation_reason"
                                    className="w-full p-2 border border-gray-300 rounded-md"
                                    rows={3}
                                    value={cancelData.cancellation_reason}
                                    onChange={(e) => setCancelData('cancellation_reason', e.target.value)}
                                    placeholder="Masukkan alasan pembatalan gaji..."
                                    required
                                />
                            </div>
                            <div className="flex justify-end space-x-2">
                                <Button type="button" variant="outline" onClick={() => setShowCancelDialog(false)}>
                                    Batal
                                </Button>
                                <Button type="submit" variant="destructive" disabled={processingCancel}>
                                    {processingCancel ? 'Membatalkan...' : 'Batalkan Gaji'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Payroll Detail Dialog */}
                {selectedPayroll && !showPaymentDialog && (
                    <Dialog open={!!selectedPayroll} onOpenChange={() => setSelectedPayroll(null)}>
                        <DialogContent className="max-w-2xl">
                            <DialogHeader>
                                <DialogTitle>Detail Gaji - {selectedPayroll.user.name}</DialogTitle>
                                <DialogDescription>
                                    Periode: {selectedPayroll.period_month}
                                </DialogDescription>
                            </DialogHeader>
                            <div className="space-y-4">
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <h4 className="font-medium mb-2">Pendapatan</h4>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span>Gaji Pokok:</span>
                                                <span>{formatCurrency(selectedPayroll.basic_salary)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Lembur:</span>
                                                <span>{formatCurrency(selectedPayroll.overtime_amount)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Bonus:</span>
                                                <span>{formatCurrency(selectedPayroll.bonus_amount)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Tunjangan:</span>
                                                <span>{formatCurrency(selectedPayroll.allowance_amount)}</span>
                                            </div>
                                            <div className="flex justify-between font-medium border-t pt-2">
                                                <span>Total Kotor:</span>
                                                <span>{formatCurrency(selectedPayroll.gross_salary)}</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <h4 className="font-medium mb-2">Potongan</h4>
                                        <div className="space-y-2 text-sm">
                                            <div className="flex justify-between">
                                                <span>Pajak (PPh 21):</span>
                                                <span className="text-red-600">{formatCurrency(selectedPayroll.tax_amount)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>BPJS:</span>
                                                <span className="text-red-600">{formatCurrency(selectedPayroll.insurance_amount)}</span>
                                            </div>
                                            <div className="flex justify-between">
                                                <span>Potongan Lain:</span>
                                                <span className="text-red-600">{formatCurrency(selectedPayroll.deduction_amount)}</span>
                                            </div>
                                            <div className="flex justify-between font-medium border-t pt-2">
                                                <span>Total Potongan:</span>
                                                <span className="text-red-600">
                                                    {formatCurrency(selectedPayroll.tax_amount + selectedPayroll.insurance_amount + selectedPayroll.deduction_amount)}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-green-50 p-4 rounded-lg">
                                    <div className="flex justify-between items-center">
                                        <span className="text-lg font-medium">Gaji Bersih:</span>
                                        <span className="text-2xl font-bold text-green-600">
                                            {formatCurrency(selectedPayroll.net_salary)}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </AppLayout>
    );
}
