import React, { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { ArrowLeft, Save, Upload, Eye, Download } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { formatCurrency } from '@/utils/formatters';

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
  financial_account_id?: number;
}

interface Props {
  pengeluaran: Pengeluaran;
  kategoriOptions: Record<string, string>;
  statusOptions: Record<string, string>;
  metodePembayaranOptions: Record<string, string>;
  financialAccounts: { id: number; account_name: string; account_type: string; current_balance: number }[];
}

export default function PengeluaranEdit({ pengeluaran, kategoriOptions, statusOptions, metodePembayaranOptions, financialAccounts }: Props) {
  const { data, setData, put, processing, errors } = useForm({
    tanggal: pengeluaran.tanggal,
    keterangan: pengeluaran.keterangan,
    jumlah: pengeluaran.jumlah.toString(),
    kategori: pengeluaran.kategori,
    metode_pembayaran: pengeluaran.metode_pembayaran,
    status: pengeluaran.status,
    catatan: pengeluaran.catatan || '',
    bukti_pembayaran: null as File | null,
    financial_account_id: pengeluaran.financial_account_id?.toString() || '',
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(route('owner.keuangan.pengeluaran.update', pengeluaran.id));
  };

  const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0];
    if (file) {
      setData('bukti_pembayaran', file);
    }
  };

  return (
    <AppLayout>
      <Head title="Edit Pengeluaran" />
      
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
              <h1 className="text-3xl font-bold tracking-tight">Edit Pengeluaran</h1>
              <p className="text-muted-foreground">
                Edit informasi pengeluaran
              </p>
            </div>
          </div>
        </div>

        {/* Form */}
        <Card>
          <CardHeader>
            <CardTitle>Form Edit Pengeluaran</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="grid gap-6 md:grid-cols-2">
                {/* Tanggal */}
                <div className="space-y-2">
                  <Label htmlFor="tanggal">Tanggal Pengeluaran *</Label>
                  <Input
                    id="tanggal"
                    type="date"
                    value={data.tanggal}
                    onChange={(e) => setData('tanggal', e.target.value)}
                    className={errors.tanggal ? 'border-red-500' : ''}
                  />
                  {errors.tanggal && (
                    <p className="text-sm text-red-500">{errors.tanggal}</p>
                  )}
                </div>

                {/* Jumlah */}
                <div className="space-y-2">
                  <Label htmlFor="jumlah">Jumlah (Rp) *</Label>
                  <Input
                    id="jumlah"
                    type="number"
                    placeholder="0"
                    value={data.jumlah}
                    onChange={(e) => setData('jumlah', e.target.value)}
                    className={errors.jumlah ? 'border-red-500' : ''}
                  />
                  {errors.jumlah && (
                    <p className="text-sm text-red-500">{errors.jumlah}</p>
                  )}
                </div>

                {/* Keterangan */}
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="keterangan">Keterangan *</Label>
                  <Input
                    id="keterangan"
                    placeholder="Contoh: Pembayaran listrik bulan Januari 2025"
                    value={data.keterangan}
                    onChange={(e) => setData('keterangan', e.target.value)}
                    className={errors.keterangan ? 'border-red-500' : ''}
                  />
                  {errors.keterangan && (
                    <p className="text-sm text-red-500">{errors.keterangan}</p>
                  )}
                </div>

                {/* Kategori */}
                <div className="space-y-2">
                  <Label htmlFor="kategori">Kategori *</Label>
                  <Select value={data.kategori} onValueChange={(value) => setData('kategori', value)}>
                    <SelectTrigger className={errors.kategori ? 'border-red-500' : ''}>
                      <SelectValue placeholder="Pilih kategori" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(kategoriOptions).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                          {label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.kategori && (
                    <p className="text-sm text-red-500">{errors.kategori}</p>
                  )}
                </div>

                {/* Metode Pembayaran */}
                <div className="space-y-2">
                  <Label htmlFor="metode_pembayaran">Metode Pembayaran *</Label>
                  <Select value={data.metode_pembayaran} onValueChange={(value) => setData('metode_pembayaran', value)}>
                    <SelectTrigger className={errors.metode_pembayaran ? 'border-red-500' : ''}>
                      <SelectValue placeholder="Pilih metode pembayaran" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(metodePembayaranOptions).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                          {label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.metode_pembayaran && (
                    <p className="text-sm text-red-500">{errors.metode_pembayaran}</p>
                  )}
                </div>

                {/* Status */}
                <div className="space-y-2">
                  <Label htmlFor="status">Status *</Label>
                  <Select value={data.status} onValueChange={(value) => setData('status', value)}>
                    <SelectTrigger className={errors.status ? 'border-red-500' : ''}>
                      <SelectValue placeholder="Pilih status" />
                    </SelectTrigger>
                    <SelectContent>
                      {Object.entries(statusOptions).map(([value, label]) => (
                        <SelectItem key={value} value={value}>
                          {label}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {errors.status && (
                    <p className="text-sm text-red-500">{errors.status}</p>
                  )}
                </div>

                {/* Akun Kas/Bank */}
                <div className="space-y-2">
                  <Label htmlFor="financial_account_id">Akun Kas/Bank *</Label>
                  <Select
                    value={data.financial_account_id}
                    onValueChange={(value) => setData('financial_account_id', value)}
                  >
                    <SelectTrigger className={errors.financial_account_id ? 'border-red-500' : ''}>
                      <SelectValue placeholder="Pilih akun kas/bank" />
                    </SelectTrigger>
                    <SelectContent>
                      {(financialAccounts || []).map((acc) => (
                        <SelectItem key={acc.id} value={acc.id.toString()}>
                          {acc.account_name} ({acc.account_type})
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                  {data.financial_account_id && (
                    <div className="text-sm text-muted-foreground mt-1">
                      Saldo: {formatCurrency(
                        financialAccounts.find(acc => acc.id.toString() === data.financial_account_id)?.current_balance ?? 0
                      )}
                    </div>
                  )}
                  {errors.financial_account_id && (
                    <p className="text-sm text-red-500">{errors.financial_account_id}</p>
                  )}
                </div>

                {/* Bukti Pembayaran */}
                <div className="space-y-2">
                  <Label htmlFor="bukti_pembayaran">Bukti Pembayaran</Label>
                  <Input
                    id="bukti_pembayaran"
                    type="file"
                    accept="image/*,.pdf"
                    onChange={handleFileChange}
                    className={errors.bukti_pembayaran ? 'border-red-500' : ''}
                  />
                  <p className="text-sm text-muted-foreground">
                    Format: JPG, PNG, PDF. Maksimal 2MB.
                  </p>
                  {pengeluaran.bukti_pembayaran && (
                    <div className="flex items-center gap-2 mt-2">
                      <span className="text-sm text-muted-foreground">Bukti saat ini:</span>
                      <Button variant="outline" size="sm" asChild>
                        <a href={`/storage/${pengeluaran.bukti_pembayaran}`} target="_blank">
                          <Eye className="h-4 w-4 mr-2" />
                          Lihat
                        </a>
                      </Button>
                      <Button variant="outline" size="sm" asChild>
                        <a href={`/storage/${pengeluaran.bukti_pembayaran}`} download>
                          <Download className="h-4 w-4 mr-2" />
                          Download
                        </a>
                      </Button>
                    </div>
                  )}
                  {errors.bukti_pembayaran && (
                    <p className="text-sm text-red-500">{errors.bukti_pembayaran}</p>
                  )}
                </div>

                {/* Catatan */}
                <div className="space-y-2 md:col-span-2">
                  <Label htmlFor="catatan">Catatan</Label>
                  <Textarea
                    id="catatan"
                    placeholder="Catatan tambahan (opsional)"
                    value={data.catatan}
                    onChange={(e) => setData('catatan', e.target.value)}
                    rows={3}
                  />
                  {errors.catatan && (
                    <p className="text-sm text-red-500">{errors.catatan}</p>
                  )}
                </div>
              </div>

              {/* Submit Button */}
              <div className="flex justify-end gap-4">
                <Link href={route('owner.keuangan.pengeluaran.index')}>
                  <Button type="button" variant="outline">
                    Batal
                  </Button>
                </Link>
                <Button type="submit" disabled={processing}>
                  <Save className="h-4 w-4 mr-2" />
                  {processing ? 'Menyimpan...' : 'Update Pengeluaran'}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
} 