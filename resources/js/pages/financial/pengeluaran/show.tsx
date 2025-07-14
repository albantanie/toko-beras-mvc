import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, Edit, CheckCircle, XCircle, Clock, Download, Eye } from 'lucide-react';
import { formatCurrency, formatDate } from '@/lib/utils';
import AppLayout from '@/layouts/app-layout';

interface Pengeluaran {
  id: number;
  tanggal: string;
  keterangan: string;
  jumlah: number;
  kategori: string;
  metode_pembayaran: string;
  status: string;
  catatan?: string;
  bukti_pembayaran?: string;
  created_by: number;
  approved_by?: number;
  approved_at?: string;
  financial_account_id?: number;
  creator: {
    id: number;
    name: string;
  };
  approver?: {
    id: number;
    name: string;
  };
  financial_account?: {
    id: number;
    account_name: string;
    account_type: string;
  };
  financial_transaction?: {
    id: number;
    transaction_date: string;
    amount: number;
    transaction_type: string;
    description: string;
    reference_type: string;
    reference_id: number;
  };
  kategori_label: string;
  status_label: string;
  metode_pembayaran_label: string;
  status_color: string;
  canBeApproved: boolean;
  canBePaid: boolean;
}

interface Props {
  pengeluaran: Pengeluaran;
}

export default function PengeluaranShow({ pengeluaran }: Props) {
  const [catatan, setCatatan] = useState('');
  const [isSubmitting, setIsSubmitting] = useState(false);

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'paid':
        return <CheckCircle className="h-5 w-5 text-green-600" />;
      case 'approved':
        return <CheckCircle className="h-5 w-5 text-blue-600" />;
      case 'pending':
        return <Clock className="h-5 w-5 text-yellow-600" />;
      case 'cancelled':
        return <XCircle className="h-5 w-5 text-red-600" />;
      default:
        return <Clock className="h-5 w-5 text-gray-600" />;
    }
  };

  const handleApprove = () => {
    if (confirm('Apakah Anda yakin ingin menyetujui pengeluaran ini?')) {
      setIsSubmitting(true);
      router.post(route('owner.keuangan.pengeluaran.approve', pengeluaran.id), {
        catatan,
      }, {
        onFinish: () => setIsSubmitting(false),
      });
    }
  };

  const handlePay = () => {
    if (confirm('Apakah Anda yakin ingin menandai pengeluaran ini sebagai sudah dibayar?')) {
      setIsSubmitting(true);
      router.post(route('owner.keuangan.pengeluaran.pay', pengeluaran.id), {
        catatan,
      }, {
        onFinish: () => setIsSubmitting(false),
      });
    }
  };

  const handleCancel = () => {
    if (confirm('Apakah Anda yakin ingin membatalkan pengeluaran ini?')) {
      setIsSubmitting(true);
      router.post(route('owner.keuangan.pengeluaran.cancel', pengeluaran.id), {
        catatan,
      }, {
        onFinish: () => setIsSubmitting(false),
      });
    }
  };

  return (
    <AppLayout>
      <Head title={`Detail Pengeluaran - ${pengeluaran.keterangan}`} />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-4">
            <Link href={route('owner.keuangan.pengeluaran.index')}>
              <Button variant="ghost" size="sm">
                <ArrowLeft className="h-4 w-4 mr-2" />
                Kembali
              </Button>
            </Link>
            <div>
              <h1 className="text-3xl font-bold tracking-tight">Detail Pengeluaran</h1>
              <p className="text-muted-foreground">
                Informasi lengkap pengeluaran
              </p>
            </div>
          </div>
          <div className="flex items-center gap-2">
            <Link href={route('owner.keuangan.pengeluaran.edit', pengeluaran.id)}>
              <Button variant="outline">
                <Edit className="h-4 w-4 mr-2" />
                Edit
              </Button>
            </Link>
          </div>
        </div>

        <div className="grid gap-6 md:grid-cols-3">
          {/* Main Information */}
          <div className="md:col-span-2 space-y-6">
            {/* Basic Information */}
            <Card>
              <CardHeader>
                <CardTitle className="flex items-center gap-2">
                  {getStatusIcon(pengeluaran.status)}
                  Informasi Pengeluaran
                </CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid gap-4 md:grid-cols-2">
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Tanggal</Label>
                    <p className="text-lg font-medium">{formatDate(pengeluaran.tanggal)}</p>
                  </div>
                  
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Jumlah</Label>
                    <p className="text-2xl font-bold text-red-600">{formatCurrency(pengeluaran.jumlah)}</p>
                  </div>
                  
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Keterangan</Label>
                    <p className="text-lg">{pengeluaran.keterangan}</p>
                  </div>
                  
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Kategori</Label>
                    <Badge variant="secondary" className="text-sm">
                      {pengeluaran.kategori_label}
                    </Badge>
                  </div>
                  
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Metode Pembayaran</Label>
                    <p className="text-lg">{pengeluaran.metode_pembayaran_label}</p>
                  </div>
                  
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Status</Label>
                    <div className="flex items-center gap-2">
                      {getStatusIcon(pengeluaran.status)}
                      <Badge variant={pengeluaran.status_color as any}>
                        {pengeluaran.status_label}
                      </Badge>
                    </div>
                  </div>
                </div>
                
                {pengeluaran.catatan && (
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Catatan</Label>
                    <p className="text-lg bg-gray-50 p-3 rounded-lg">{pengeluaran.catatan}</p>
                  </div>
                )}
              </CardContent>
            </Card>

            {/* Bukti Pembayaran */}
            {pengeluaran.bukti_pembayaran && (
              <Card>
                <CardHeader>
                  <CardTitle>Bukti Pembayaran</CardTitle>
                </CardHeader>
                <CardContent>
                  <div className="flex items-center gap-4">
                    <Button variant="outline" asChild>
                      <a href={`/storage/${pengeluaran.bukti_pembayaran}`} target="_blank">
                        <Eye className="h-4 w-4 mr-2" />
                        Lihat Bukti
                      </a>
                    </Button>
                    <Button variant="outline" asChild>
                      <a href={`/storage/${pengeluaran.bukti_pembayaran}`} download>
                        <Download className="h-4 w-4 mr-2" />
                        Download
                      </a>
                    </Button>
                  </div>
                </CardContent>
              </Card>
            )}

            {/* Approval Information */}
            {(pengeluaran.approved_by || pengeluaran.approved_at) && (
              <Card>
                <CardHeader>
                  <CardTitle>Informasi Persetujuan</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {pengeluaran.approver && (
                    <div>
                      <Label className="text-sm font-medium text-muted-foreground">Disetujui Oleh</Label>
                      <p className="text-lg">{pengeluaran.approver.name}</p>
                    </div>
                  )}
                  
                  {pengeluaran.approved_at && (
                    <div>
                      <Label className="text-sm font-medium text-muted-foreground">Tanggal Persetujuan</Label>
                      <p className="text-lg">{formatDate(pengeluaran.approved_at)}</p>
                    </div>
                  )}
                </CardContent>
              </Card>
            )}

            {/* Financial Transaction Information */}
            {(pengeluaran.financial_account || pengeluaran.financial_transaction) && (
              <Card>
                <CardHeader>
                  <CardTitle>Informasi Transaksi Keuangan</CardTitle>
                </CardHeader>
                <CardContent className="space-y-4">
                  {pengeluaran.financial_account && (
                    <div>
                      <Label className="text-sm font-medium text-muted-foreground">Akun Keuangan</Label>
                      <div className="flex items-center gap-2">
                        <Badge variant="outline" className="text-sm">
                          {pengeluaran.financial_account.account_type}
                        </Badge>
                        <p className="text-lg font-medium">{pengeluaran.financial_account.account_name}</p>
                      </div>
                    </div>
                  )}
                  
                  {pengeluaran.financial_transaction && (
                    <>
                      <div>
                        <Label className="text-sm font-medium text-muted-foreground">ID Transaksi</Label>
                        <p className="text-lg font-mono">#{pengeluaran.financial_transaction.id}</p>
                      </div>
                      
                      <div>
                        <Label className="text-sm font-medium text-muted-foreground">Tanggal Transaksi</Label>
                        <p className="text-lg">{formatDate(pengeluaran.financial_transaction.transaction_date)}</p>
                      </div>
                      
                      <div>
                        <Label className="text-sm font-medium text-muted-foreground">Jumlah Transaksi</Label>
                        <p className="text-lg font-bold text-red-600">
                          {formatCurrency(pengeluaran.financial_transaction.amount)}
                        </p>
                      </div>
                      
                      <div>
                        <Label className="text-sm font-medium text-muted-foreground">Tipe Transaksi</Label>
                        <Badge 
                          variant={pengeluaran.financial_transaction.transaction_type === 'debit' ? 'destructive' : 'default'}
                          className="text-sm"
                        >
                          {pengeluaran.financial_transaction.transaction_type === 'debit' ? 'Debit (Keluar)' : 'Kredit (Masuk)'}
                        </Badge>
                      </div>
                      
                      <div>
                        <Label className="text-sm font-medium text-muted-foreground">Deskripsi Transaksi</Label>
                        <p className="text-lg">{pengeluaran.financial_transaction.description}</p>
                      </div>
                    </>
                  )}
                  
                  {!pengeluaran.financial_transaction && pengeluaran.status === 'paid' && (
                    <Alert>
                      <AlertDescription>
                        Transaksi keuangan belum dibuat. Hubungi administrator untuk memeriksa integrasi sistem.
                      </AlertDescription>
                    </Alert>
                  )}
                </CardContent>
              </Card>
            )}
          </div>

          {/* Sidebar */}
          <div className="space-y-6">
            {/* Creator Information */}
            <Card>
              <CardHeader>
                <CardTitle>Informasi Pembuat</CardTitle>
              </CardHeader>
              <CardContent>
                <div className="space-y-2">
                  <div>
                    <Label className="text-sm font-medium text-muted-foreground">Dibuat Oleh</Label>
                    <p className="text-lg">{pengeluaran.creator.name}</p>
                  </div>
                </div>
              </CardContent>
            </Card>

            {/* Actions */}
            <Card>
              <CardHeader>
                <CardTitle>Aksi</CardTitle>
              </CardHeader>
              <CardContent className="space-y-4">
                {pengeluaran.canBeApproved && (
                  <div className="space-y-2">
                    <Label htmlFor="catatan">Catatan (Opsional)</Label>
                    <Textarea
                      id="catatan"
                      placeholder="Catatan untuk persetujuan..."
                      value={catatan}
                      onChange={(e) => setCatatan(e.target.value)}
                      rows={3}
                    />
                    <Button 
                      onClick={handleApprove}
                      disabled={isSubmitting}
                      className="w-full"
                    >
                      <CheckCircle className="h-4 w-4 mr-2" />
                      {isSubmitting ? 'Menyetujui...' : 'Setujui Pengeluaran'}
                    </Button>
                  </div>
                )}

                {pengeluaran.canBePaid && (
                  <div className="space-y-2">
                    <Label htmlFor="catatan-pay">Catatan Pembayaran (Opsional)</Label>
                    <Textarea
                      id="catatan-pay"
                      placeholder="Catatan untuk pembayaran..."
                      value={catatan}
                      onChange={(e) => setCatatan(e.target.value)}
                      rows={3}
                    />
                    <Button 
                      onClick={handlePay}
                      disabled={isSubmitting}
                      variant="default"
                      className="w-full bg-green-600 hover:bg-green-700"
                    >
                      <CheckCircle className="h-4 w-4 mr-2" />
                      {isSubmitting ? 'Memproses...' : 'Tandai Sudah Dibayar'}
                    </Button>
                  </div>
                )}

                {pengeluaran.status !== 'cancelled' && (
                  <div className="space-y-2">
                    <Label htmlFor="catatan-cancel">Catatan Pembatalan (Opsional)</Label>
                    <Textarea
                      id="catatan-cancel"
                      placeholder="Alasan pembatalan..."
                      value={catatan}
                      onChange={(e) => setCatatan(e.target.value)}
                      rows={3}
                    />
                    <Button 
                      onClick={handleCancel}
                      disabled={isSubmitting}
                      variant="destructive"
                      className="w-full"
                    >
                      <XCircle className="h-4 w-4 mr-2" />
                      {isSubmitting ? 'Membatalkan...' : 'Batalkan Pengeluaran'}
                    </Button>
                  </div>
                )}
              </CardContent>
            </Card>
          </div>
        </div>
      </div>
    </AppLayout>
  );
} 