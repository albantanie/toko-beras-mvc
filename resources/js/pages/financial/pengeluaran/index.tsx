import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Badge } from '@/components/ui/badge';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Pagination } from '@/components/ui/pagination';
import { Search, Plus, Filter, Download, Eye, Edit, Trash2, CheckCircle, XCircle, Clock } from 'lucide-react';
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
  creator: {
    id: number;
    name: string;
  };
  approver?: {
    id: number;
    name: string;
  };
  kategori_label: string;
  status_label: string;
  metode_pembayaran_label: string;
  status_color: string;
}

interface Statistics {
  total: number;
  bulan_ini: number;
  pending: number;
  approved: number;
}

interface Filters {
  search?: string;
  kategori?: string;
  status?: string;
  start_date?: string;
  end_date?: string;
  sort?: string;
  direction?: string;
}

interface Props {
  pengeluaran: {
    data: Pengeluaran[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
  statistics: Statistics;
  filters: Filters;
  kategoriOptions: Record<string, string>;
  statusOptions: Record<string, string>;
  metodePembayaranOptions: Record<string, string>;
}

export default function PengeluaranIndex({ 
  pengeluaran, 
  statistics, 
  filters, 
  kategoriOptions, 
  statusOptions, 
  metodePembayaranOptions 
}: Props) {
  const [search, setSearch] = useState(filters.search || '');
  const [kategori, setKategori] = useState(filters.kategori || 'all');
  const [status, setStatus] = useState(filters.status || 'all');
  const [startDate, setStartDate] = useState(filters.start_date || '');
  const [endDate, setEndDate] = useState(filters.end_date || '');

  const handleSearch = () => {
    router.get(route('owner.keuangan.pengeluaran.index'), {
      search,
      kategori: kategori === 'all' ? '' : kategori,
      status: status === 'all' ? '' : status,
      start_date: startDate,
      end_date: endDate,
    }, {
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleReset = () => {
    setSearch('');
    setKategori('all');
    setStatus('all');
    setStartDate('');
    setEndDate('');
    router.get(route('owner.keuangan.pengeluaran.index'), {}, {
      preserveState: false,
    });
  };

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'paid':
        return <CheckCircle className="h-4 w-4 text-green-600" />;
      case 'approved':
        return <CheckCircle className="h-4 w-4 text-blue-600" />;
      case 'pending':
        return <Clock className="h-4 w-4 text-yellow-600" />;
      case 'cancelled':
        return <XCircle className="h-4 w-4 text-red-600" />;
      default:
        return <Clock className="h-4 w-4 text-gray-600" />;
    }
  };

  return (
    <AppLayout>
      <Head title="Pengeluaran" />
      
      <div className="space-y-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-3xl font-bold tracking-tight">Pengeluaran</h1>
            <p className="text-muted-foreground">
              Kelola semua pengeluaran toko beras
            </p>
          </div>
          <Link href={route('owner.keuangan.pengeluaran.create')}>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Tambah Pengeluaran
            </Button>
          </Link>
        </div>

        {/* Statistics Cards */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Total Pengeluaran</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{formatCurrency(Number(statistics.total))}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Bulan Ini</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold">{formatCurrency(Number(statistics.bulan_ini))}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Pending</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-yellow-600">{formatCurrency(Number(statistics.pending))}</div>
            </CardContent>
          </Card>
          
          <Card>
            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
              <CardTitle className="text-sm font-medium">Disetujui</CardTitle>
            </CardHeader>
            <CardContent>
              <div className="text-2xl font-bold text-blue-600">{formatCurrency(Number(statistics.approved))}</div>
            </CardContent>
          </Card>
        </div>

        {/* Filters */}
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2">
              <Filter className="h-5 w-5" />
              Filter Pengeluaran
            </CardTitle>
          </CardHeader>
          <CardContent>
            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-5">
              <div className="space-y-2">
                <Label htmlFor="search">Cari</Label>
                <Input
                  id="search"
                  placeholder="Cari keterangan..."
                  value={search}
                  onChange={(e) => setSearch(e.target.value)}
                  onKeyPress={(e) => e.key === 'Enter' && handleSearch()}
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="kategori">Kategori</Label>
                <Select value={kategori} onValueChange={setKategori}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih kategori" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Semua Kategori</SelectItem>
                    {Object.entries(kategoriOptions).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="status">Status</Label>
                <Select value={status} onValueChange={setStatus}>
                  <SelectTrigger>
                    <SelectValue placeholder="Pilih status" />
                  </SelectTrigger>
                  <SelectContent>
                    <SelectItem value="all">Semua Status</SelectItem>
                    {Object.entries(statusOptions).map(([value, label]) => (
                      <SelectItem key={value} value={value}>
                        {label}
                      </SelectItem>
                    ))}
                  </SelectContent>
                </Select>
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="start_date">Tanggal Mulai</Label>
                <Input
                  id="start_date"
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                />
              </div>
              
              <div className="space-y-2">
                <Label htmlFor="end_date">Tanggal Akhir</Label>
                <Input
                  id="end_date"
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                />
              </div>
            </div>
            
            <div className="flex gap-2 mt-4">
              <Button onClick={handleSearch}>
                <Search className="h-4 w-4 mr-2" />
                Cari
              </Button>
              <Button variant="outline" onClick={handleReset}>
                Reset
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Pengeluaran Table */}
        <Card>
          <CardHeader>
            <CardTitle>Daftar Pengeluaran</CardTitle>
          </CardHeader>
          <CardContent>
            <div className="rounded-md border">
              <Table>
                <TableHeader>
                  <TableRow>
                    <TableHead>Tanggal</TableHead>
                    <TableHead>Keterangan</TableHead>
                    <TableHead>Jumlah</TableHead>
                    <TableHead>Kategori</TableHead>
                    <TableHead>Status</TableHead>
                    <TableHead>Metode Pembayaran</TableHead>
                    <TableHead>Dibuat Oleh</TableHead>
                    <TableHead className="text-right">Aksi</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {pengeluaran.data.map((item) => (
                    <TableRow key={item.id}>
                      <TableCell>{formatDate(item.tanggal)}</TableCell>
                      <TableCell className="max-w-xs truncate">
                        {item.keterangan}
                      </TableCell>
                      <TableCell className="font-medium">
                        {formatCurrency(Number(item.jumlah))}
                      </TableCell>
                      <TableCell>
                        <Badge variant="secondary">
                          {item.kategori_label}
                        </Badge>
                      </TableCell>
                      <TableCell>
                        <span>{item.status_label}</span>
                      </TableCell>
                      <TableCell>
                        {item.metode_pembayaran_label}
                      </TableCell>
                      <TableCell>
                        {item.creator.name}
                      </TableCell>
                      <TableCell className="text-right">
                        <div className="flex items-center justify-end gap-2">
                          <Link href={route('owner.keuangan.pengeluaran.show', item.id)}>
                            <Button variant="ghost" size="sm">
                              <Eye className="h-4 w-4" />
                            </Button>
                          </Link>
                          <Link href={route('owner.keuangan.pengeluaran.edit', item.id)}>
                            <Button variant="ghost" size="sm">
                              <Edit className="h-4 w-4" />
                            </Button>
                          </Link>
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            </div>

            {/* Pagination */}
            {pengeluaran.last_page > 1 && (
              <div className="mt-4">
                <Pagination
                  currentPage={pengeluaran.current_page}
                  totalPages={pengeluaran.last_page}
                  onPageChange={(page) => {
                    router.get(route('owner.keuangan.pengeluaran.index'), {
                      ...filters,
                      page,
                    }, {
                      preserveState: true,
                      preserveScroll: true,
                    });
                  }}
                />
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
} 