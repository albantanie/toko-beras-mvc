<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok - {{ $period }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            color: #2c3e50;
            font-size: 24px;
        }
        .header p {
            margin: 5px 0;
            color: #7f8c8d;
        }
        .summary {
            margin-bottom: 30px;
        }
        .summary-grid {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .summary-item {
            display: table-cell;
            width: 25%;
            padding: 15px;
            text-align: center;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
        }
        .summary-item h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #2c3e50;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #27ae60;
        }
        .table-container {
            margin-bottom: 30px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
            font-size: 11px;
        }
        td {
            font-size: 10px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .status-critical {
            color: #e74c3c;
            font-weight: bold;
        }
        .status-low {
            color: #f39c12;
            font-weight: bold;
        }
        .status-normal {
            color: #27ae60;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN STOK</h1>
        <p>Toko Beras - Sistem Manajemen Toko</p>
        <p>Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
        <p>Dibuat pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="summary">
        <h2>Ringkasan Stok</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total Produk</h3>
                <div class="value">{{ number_format($summary['total_products']) }}</div>
            </div>
            <div class="summary-item">
                <h3>Stok Normal</h3>
                <div class="value">{{ number_format($summary['normal_stock']) }}</div>
            </div>
            <div class="summary-item">
                <h3>Stok Rendah</h3>
                <div class="value">{{ number_format($summary['low_stock']) }}</div>
            </div>
            <div class="summary-item">
                <h3>Habis Stok</h3>
                <div class="value">{{ number_format($summary['out_of_stock']) }}</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2>Detail Stok Produk</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok</th>
                    <th>Minimal Stok</th>
                    <th>Satuan</th>
                    <th>Harga Jual</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->nama }}</td>
                    <td>{{ $product->kategori }}</td>
                    <td class="text-center">{{ number_format($product->stok) }}</td>
                    <td class="text-center">{{ number_format($product->min_stok) }}</td>
                    <td class="text-center">{{ $product->satuan }}</td>
                    <td class="text-right">Rp {{ number_format($product->harga_jual) }}</td>
                    <td class="text-center">
                        @if($product->stok == 0)
                            <span class="status-critical">HABIS</span>
                        @elseif($product->stok <= $product->min_stok)
                            <span class="status-low">RENDAH</span>
                        @else
                            <span class="status-normal">NORMAL</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($lowStockItems->count() > 0)
    <div class="table-container">
        <h2>Alert Stok Rendah</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Stok Saat Ini</th>
                    <th>Minimal Stok</th>
                    <th>Satuan</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lowStockItems as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $item->nama }}</td>
                    <td>{{ $item->kategori }}</td>
                    <td class="text-center">{{ number_format($item->stok) }}</td>
                    <td class="text-center">{{ number_format($item->min_stok) }}</td>
                    <td class="text-center">{{ $item->satuan }}</td>
                    <td class="text-center">
                        @if($item->stok == 0)
                            <span class="status-critical">KRITIS</span>
                        @else
                            <span class="status-low">RENDAH</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem Toko Beras</p>
        <p>© {{ date('Y') }} Toko Beras. All rights reserved.</p>
    </div>
</body>
</html> 