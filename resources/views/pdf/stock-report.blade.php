<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .header h2 {
            margin: 5px 0;
            color: #666;
            font-size: 16px;
            font-weight: normal;
        }
        .summary {
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
            text-align: center;
        }
        .summary-card h3 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
        }
        .summary-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }
        .warning {
            color: #f59e0b !important;
        }
        .danger {
            color: #dc2626 !important;
        }
        .success {
            color: #16a34a !important;
        }
        .stock-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        .stock-table th,
        .stock-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .stock-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .stock-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .stock-status {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .stock-status.low {
            background-color: #fef3c7;
            color: #92400e;
        }
        .stock-status.out {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .stock-status.normal {
            background-color: #d1fae5;
            color: #065f46;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .report-date {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .report-date strong {
            color: #1976d2;
        }
        .section-title {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin: 30px 0 20px 0;
            font-size: 16px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TOKO BERAS</h1>
        <h2>Laporan Stok</h2>
    </div>

    <div class="report-date">
        <strong>Tanggal Laporan: {{ \Carbon\Carbon::parse($reportData['report_date'])->format('d M Y') }}</strong>
    </div>

    <!-- Penjelasan Jenis Pergerakan Stock -->
    <div class="explanation-section" style="margin: 30px 0; padding: 20px; background-color: #f8f9fa; border-left: 4px solid #007bff;">
        <h3 style="margin-top: 0; color: #333; font-size: 16px;">📋 Penjelasan Jenis Pergerakan Stock</h3>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 15px;">
            <div>
                <h4 style="color: #28a745; margin: 10px 0 5px 0; font-size: 14px;">✅ PERGERAKAN POSITIF (+)</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 11px; line-height: 1.6;">
                    <li><strong>Stock Masuk (in):</strong> Pembelian barang baru dari supplier</li>
                    <li><strong>Retur Barang (return):</strong> Barang dikembalikan oleh pelanggan</li>
                    <li><strong>Penyesuaian Stok (adjustment):</strong> Koreksi stok naik berdasarkan stock opname</li>
                    <li><strong>Koreksi Sistem (correction):</strong> Perbaikan data akibat kesalahan input</li>
                    <li><strong>Stock Awal (initial):</strong> Penetapan stok pembukaan</li>
                </ul>
            </div>

            <div>
                <h4 style="color: #dc3545; margin: 10px 0 5px 0; font-size: 14px;">❌ PERGERAKAN NEGATIF (-)</h4>
                <ul style="margin: 0; padding-left: 20px; font-size: 11px; line-height: 1.6;">
                    <li><strong>Stock Keluar (out):</strong> Penjualan atau penggunaan barang</li>
                    <li><strong>Kerusakan (damage):</strong> Barang rusak, hilang, atau kadaluarsa</li>
                    <li><strong>Penyesuaian Stok (adjustment):</strong> Koreksi stok turun berdasarkan stock opname</li>
                </ul>
            </div>
        </div>

        <div style="margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffeaa7; border-radius: 4px;">
            <h4 style="margin: 0 0 10px 0; color: #856404; font-size: 13px;">💡 Catatan Penting:</h4>
            <ul style="margin: 0; padding-left: 20px; font-size: 11px; line-height: 1.6; color: #856404;">
                <li><strong>Satuan Stock:</strong> Semua stock menggunakan satuan karung (1 karung = 25kg)</li>
                <li><strong>Nilai PLUS (+):</strong> Menambah stok dan nilai inventory (dihitung berdasarkan harga beli)</li>
                <li><strong>Nilai MINUS (-):</strong> Mengurangi stok dan nilai inventory (penjualan: harga jual, kerusakan: harga beli)</li>
                <li><strong>Total Pergerakan:</strong> Selisih antara stock masuk dan keluar dalam periode tertentu</li>
                <li><strong>Nilai Stock:</strong> Nilai moneter dari pergerakan stock berdasarkan harga yang berlaku</li>
            </ul>
        </div>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Barang</h3>
                <div class="value">{{ number_format($reportData['total_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Stok Tersedia</h3>
                <div class="value success">{{ number_format($reportData['in_stock_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Stok Menipis</h3>
                <div class="value warning">{{ number_format($reportData['low_stock_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Stok Habis</h3>
                <div class="value danger">{{ number_format($reportData['out_of_stock_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Nilai Stok (Jual)</h3>
                <div class="value">Rp {{ number_format($reportData['total_stock_value_sell'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <h3>Potensi Keuntungan</h3>
                <div class="value success">Rp {{ number_format($reportData['potential_profit'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <h3 class="section-title">Status Stok Saat Ini</h3>
    <table class="stock-table">
        <thead>
            <tr>
                <th>Kode Barang</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th class="text-right">Stok Saat Ini</th>
                <th class="text-right">Stok Minimum</th>
                <th class="text-center">Status</th>
                <th class="text-right">Harga Jual</th>
                <th class="text-right">Nilai Stok</th>
            </tr>
        </thead>
        <tbody>
            @foreach($barangs as $barang)
            <tr>
                <td>{{ $barang->kode_barang }}</td>
                <td>{{ $barang->nama }}</td>
                <td>{{ $barang->kategori }}</td>
                <td class="text-right">{{ number_format($barang->stok) }}
                    @if(strtolower($barang->kategori) === 'beras' || strpos(strtolower($barang->kategori), 'beras') !== false)
                        kg
                    @elseif(strpos(strtolower($barang->kategori), 'plastik') !== false || strpos(strtolower($barang->kategori), 'kemasan') !== false || strpos(strtolower($barang->kategori), 'karung') !== false)
                        pcs
                    @else
                        kg
                    @endif
                </td>
                <td class="text-right">{{ number_format($barang->stok_minimum) }}</td>
                <td class="text-center">
                    @if($barang->stok <= 0)
                        <span class="stock-status out">Habis</span>
                    @elseif($barang->stok <= $barang->stok_minimum)
                        <span class="stock-status low">Menipis</span>
                    @else
                        <span class="stock-status normal">Normal</span>
                    @endif
                </td>
                <td class="text-right">Rp {{ number_format($barang->harga_jual, 0, ',', '.') }}</td>
                <td class="text-right">Rp {{ number_format($barang->stok * $barang->harga_jual, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @if($stockMovements->count() > 0)
    <h3 class="section-title">Pergerakan Stok Terbaru (30 Hari Terakhir)</h3>
    <table class="stock-table">
        <thead>
            <tr>
                <th>Tanggal</th>
                <th>Barang</th>
                <th>Jenis</th>
                <th class="text-right">Jumlah</th>
                <th>Pengguna</th>
                <th>Catatan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($stockMovements->take(20) as $movement)
            <tr>
                <td>{{ $movement->created_at->format('d/m/Y H:i') }}</td>
                <td>{{ $movement->barang->nama ?? 'N/A' }}</td>
                <td>{{ ucfirst($movement->type) }}</td>
                <td class="text-right {{ $movement->quantity > 0 ? 'success' : 'danger' }}">
                    {{ $movement->quantity > 0 ? '+' : '' }}{{ number_format($movement->quantity) }}
                </td>
                <td>{{ $movement->user->name ?? 'System' }}</td>
                <td>{{ $movement->notes ?? '-' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        <p>Dibuat pada {{ $generatedAt->format('d M Y H:i:s') }}</p>
        <p>Ini adalah laporan yang dibuat oleh sistem. Tidak memerlukan tanda tangan.</p>
    </div>
</body>
</html>
