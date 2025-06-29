<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Stok Barang</title>
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
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        .summary-item {
            text-align: center;
            padding: 10px;
            background-color: white;
            border-radius: 5px;
            border: 1px solid #ddd;
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
        <h1>LAPORAN STOK BARANG</h1>
        <p>Dibuat pada: {{ $generatedAt }}</p>
    </div>

    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>Total Barang</h4>
                <div class="value">{{ number_format($totalItems) }}</div>
            </div>
            <div class="summary-item">
                <h4>Stok Menipis (≤10)</h4>
                <div class="value">{{ number_format($lowStockItems) }}</div>
            </div>
            <div class="summary-item">
                <h4>Habis Stok</h4>
                <div class="value">{{ number_format($outOfStockItems) }}</div>
            </div>
            <div class="summary-item">
                <h4>Total Nilai Stok</h4>
                <div class="value">Rp {{ number_format($totalValue) }}</div>
            </div>
        </div>
    </div>

    @if($barang->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Nama Barang</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    @if($isAdminOrOwner)
                        <th>Harga Beli</th>
                    @endif
                    <th>Harga Jual</th>
                    <th>Nilai Stok</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($barang as $index => $b)
                    <tr class="@if($b->stok == 0) out-of-stock @elseif($b->stok <= 10) low-stock @endif">
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ $b->kode }}</td>
                        <td>{{ $b->nama }}</td>
                        <td>{{ $b->kategori }}</td>
                        <td class="text-center">{{ number_format($b->stok) }}</td>
                        @if($isAdminOrOwner)
                            <td class="text-right">Rp {{ number_format($b->harga_beli) }}</td>
                        @endif
                        <td class="text-right">Rp {{ number_format($b->harga_jual) }}</td>
                        <td class="text-right">Rp {{ number_format($b->stok * $b->harga_jual) }}</td>
                        <td class="text-center">
                            @if($b->stok == 0)
                                <span style="color: #dc3545; font-weight: bold;">HABIS</span>
                            @elseif($b->stok <= 10)
                                <span style="color: #ffc107; font-weight: bold;">MENIPIS</span>
                            @else
                                <span style="color: #28a745;">NORMAL</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($lowStockItems > 0)
            <div style="margin-top: 30px;">
                <h4>Barang dengan Stok Menipis (≤10):</h4>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Stok</th>
                            @if($isAdminOrOwner)
                                <th>Harga Beli</th>
                            @endif
                            <th>Harga Jual</th>
                            <th>Nilai Stok</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $lowStockIndex = 1; @endphp
                        @foreach($barang->where('stok', '<=', 10) as $b)
                            <tr class="low-stock">
                                <td class="text-center">{{ $lowStockIndex++ }}</td>
                                <td>{{ $b->kode }}</td>
                                <td>{{ $b->nama }}</td>
                                <td class="text-center">{{ number_format($b->stok) }}</td>
                                @if($isAdminOrOwner)
                                    <td class="text-right">Rp {{ number_format($b->harga_beli) }}</td>
                                @endif
                                <td class="text-right">Rp {{ number_format($b->harga_jual) }}</td>
                                <td class="text-right">Rp {{ number_format($b->stok * $b->harga_jual) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        @if($outOfStockItems > 0)
            <div style="margin-top: 30px;">
                <h4>Barang Habis Stok:</h4>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            @if($isAdminOrOwner)
                                <th>Harga Beli</th>
                            @endif
                            <th>Harga Jual</th>
                        </tr>
                    </thead>
                    <tbody>
                        @php $outOfStockIndex = 1; @endphp
                        @foreach($barang->where('stok', 0) as $b)
                            <tr class="out-of-stock">
                                <td class="text-center">{{ $outOfStockIndex++ }}</td>
                                <td>{{ $b->kode }}</td>
                                <td>{{ $b->nama }}</td>
                                <td>{{ $b->kategori }}</td>
                                @if($isAdminOrOwner)
                                    <td class="text-right">Rp {{ number_format($b->harga_beli) }}</td>
                                @endif
                                <td class="text-right">Rp {{ number_format($b->harga_jual) }}</td>
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