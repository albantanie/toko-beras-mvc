<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Mutasi Stok Barang</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .header p {
            margin: 5px 0;
            font-size: 14px;
        }
        .summary {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .summary h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        .summary-grid {
            width: 100%;
            margin-bottom: 15px;
        }
        .summary-item {
            display: inline-block;
            width: 23%;
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
            border: 1px solid #ddd;
            margin-right: 1%;
            vertical-align: top;
        }
        .summary-item h4 {
            margin: 0 0 5px 0;
            color: #666;
            font-size: 12px;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #333;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f8f9fa;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .low-stock {
            background-color: #fff3cd;
        }
        .out-of-stock {
            background-color: #f8d7da;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN MUTASI STOK BARANG</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($period_from)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($period_to)->format('d M Y') }}</p>
        <p>Dibuat pada: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</p>
    </div>

    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>Total Barang</h4>
                <div class="value">{{ number_format($summary['total_items']) }}</div>
            </div>
            <div class="summary-item">
                <h4>Total Mutasi</h4>
                <div class="value">{{ number_format($summary['total_movements']) }}</div>
            </div>
            <div class="summary-item">
                <h4>Mutasi Masuk</h4>
                <div class="value">{{ number_format($summary['in_movements']) }}</div>
            </div>
            <div class="summary-item">
                <h4>Mutasi Keluar</h4>
                <div class="value">{{ number_format($summary['out_movements']) }}</div>
            </div>
        </div>
    </div>

    <!-- Penjelasan Jenis Pergerakan Stock -->
    <div style="margin: 20px 0; padding: 15px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
        <h3 style="margin-top: 0; color: #333; font-size: 14px;">📋 Penjelasan Jenis Pergerakan Stock</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-top: 10px;">
            <div>
                <h4 style="color: #28a745; margin: 8px 0 5px 0; font-size: 12px;">✅ POSITIF (+)</h4>
                <ul style="margin: 0; padding-left: 15px; font-size: 10px; line-height: 1.5;">
                    <li><strong>in:</strong> Stock masuk dari supplier</li>
                    <li><strong>return:</strong> Retur dari pelanggan</li>
                    <li><strong>adjustment:</strong> Penyesuaian stok naik</li>
                    <li><strong>initial:</strong> Stock awal</li>
                </ul>
            </div>

            <div>
                <h4 style="color: #dc3545; margin: 8px 0 5px 0; font-size: 12px;">❌ NEGATIF (-)</h4>
                <ul style="margin: 0; padding-left: 15px; font-size: 10px; line-height: 1.5;">
                    <li><strong>out:</strong> Stock keluar (penjualan)</li>
                    <li><strong>damage:</strong> Kerusakan/hilang</li>
                    <li><strong>adjustment:</strong> Penyesuaian stok turun</li>
                </ul>
            </div>
        </div>

        <p style="margin: 10px 0 0 0; font-size: 10px; color: #856404; background-color: #fff3cd; padding: 8px; border-radius: 3px;">
            <strong>💡 Catatan:</strong> Satuan = karung (1 karung = 25kg). Qty positif = menambah stok, Qty negatif = mengurangi stok.
            Stok Awal = stok sebelum pergerakan, Stok Akhir = stok setelah pergerakan.
        </p>
    </div>

    <!-- Tabel Mutasi Stok -->
    @if($movements->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Tipe</th>
                    <th>Qty</th>
                    <th>Stok Awal</th>
                    <th>Stok Akhir</th>
                    <th>Keterangan</th>
                    <th>User</th>
                </tr>
            </thead>
            <tbody>
                @foreach($movements as $index => $movement)
                    <tr class="{{ $movement->quantity < 0 ? 'low-stock' : '' }}">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($movement->created_at)->format('d/m/Y H:i') }}</td>
                        <td>{{ $movement->barang->kode_barang ?? '-' }}</td>
                        <td>{{ $movement->barang->nama ?? 'Barang tidak ditemukan' }}</td>
                        <td>{{ $movement->type }}</td>
                        <td class="text-center">{{ number_format($movement->quantity) }}</td>
                        <td class="text-center">{{ number_format($movement->stock_before) }}</td>
                        <td class="text-center">{{ number_format($movement->stock_after) }}</td>
                        <td>{{ $movement->description }}</td>
                        <td>{{ $movement->user->name ?? 'System' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @else
        <p>Tidak ada data mutasi stok dalam periode ini.</p>
    @endif
    
    <!-- Tabel Stok Terkini -->
    <h3>Status Stok Terkini</h3>
    @if(isset($barangs) && $barangs->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($barangs as $index => $b)
                    <tr class="@if($b->stok == 0) out-of-stock @elseif($b->stok <= $b->stok_minimum) low-stock @endif">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $b->kode_barang }}</td>
                        <td>{{ $b->nama }}</td>
                        <td>{{ $b->kategori }}</td>
                        <td class="text-center">{{ number_format($b->stok) }}</td>
                        <td class="text-center">
                            @if($b->stok == 0)
                                <span style="color: #dc3545; font-weight: bold;">HABIS</span>
                            @elseif($b->stok <= ($b->stok_minimum ?? 10))
                                <span style="color: #ffc107; font-weight: bold;">MENIPIS</span>
                            @else
                                <span style="color: #28a745;">NORMAL</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if(isset($lowStockItems) && $lowStockItems > 0)
            <div style="margin-top: 30px;">
                <h4>Barang dengan Stok Menipis (≤10):</h4>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Stok</th>
                            @if(isset($isAdminOrOwner) && $isAdminOrOwner)
                                <th>Harga Beli</th>
                            @endif
                            <th>Harga Jual</th>
                            <th>Nilai Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lowStockIndex = 1; @endphp
                        @foreach($barangs->where('stok', '<=', 10) as $b)
                            <tr class="low-stock">
                                <td class="text-center">{{ $lowStockIndex++ }}</td>
                                <td>{{ $b->kode_barang }}</td>
                                <td>{{ $b->nama }}</td>
                                <td class="text-center">{{ number_format($b->stok) }}</td>
                                @if(isset($isAdminOrOwner) && $isAdminOrOwner)
                                    <td class="text-right">Rp {{ number_format($b->harga_beli ?? 0) }}</td>
                                @endif
                                <td class="text-right">Rp {{ number_format($b->harga_jual ?? 0) }}</td>
                                <td class="text-right">Rp {{ number_format($b->stok * ($b->harga_jual ?? 0)) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if(isset($outOfStockItems) && $outOfStockItems > 0)
            <div style="margin-top: 30px;">
                <h4>Barang Habis Stok:</h4>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            @if(isset($isAdminOrOwner) && $isAdminOrOwner)
                                <th>Harga Beli</th>
                            @endif
                            <th>Harga Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $outOfStockIndex = 1; @endphp
                        @foreach($barangs->where('stok', 0) as $b)
                            <tr class="out-of-stock">
                                <td class="text-center">{{ $outOfStockIndex++ }}</td>
                                <td>{{ $b->kode_barang }}</td>
                                <td>{{ $b->nama }}</td>
                                <td>{{ $b->kategori }}</td>
                                @if(isset($isAdminOrOwner) && $isAdminOrOwner)
                                    <td class="text-right">Rp {{ number_format($b->harga_beli ?? 0) }}</td>
                                @endif
                                <td class="text-right">Rp {{ number_format($b->harga_jual ?? 0) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <div style="text-align: center; padding: 40px; color: #666;">
            <h3>Tidak ada data barang</h3>
        </div>
    @endif

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem</p>
        <p>Halaman 1</p>
    </div>
</body>
</html> 