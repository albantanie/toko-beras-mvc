<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan - {{ $period }}</title>
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
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #7f8c8d;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN PENJUALAN</h1>
        <p>Toko Beras - Sistem Manajemen Toko</p>
        <p>Periode: {{ $dateFrom }} s/d {{ $dateTo }}</p>
        <p>Dibuat pada: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <div class="summary">
        <h2>Ringkasan Penjualan</h2>
        <div class="summary-grid">
            <div class="summary-item">
                <h3>Total Transaksi</h3>
                <div class="value">{{ number_format($summary['total_transactions']) }}</div>
            </div>
            <div class="summary-item">
                <h3>Total Penjualan</h3>
                <div class="value">Rp {{ number_format($summary['total_sales']) }}</div>
            </div>
            <div class="summary-item">
                <h3>Rata-rata Transaksi</h3>
                <div class="value">Rp {{ number_format($summary['average_transaction']) }}</div>
            </div>
            <div class="summary-item">
                <h3>Total Profit</h3>
                <div class="value">Rp {{ number_format($summary['total_profit']) }}</div>
            </div>
        </div>
    </div>

    <div class="table-container">
        <h2>Detail Transaksi</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th>Total</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $index => $transaction)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $transaction->nomor_transaksi }}</td>
                    <td>{{ \Carbon\Carbon::parse($transaction->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $transaction->nama_pelanggan }}</td>
                    <td>{{ $transaction->user->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->total) }}</td>
                    <td class="text-center">{{ ucfirst($transaction->status) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @if($topProducts->count() > 0)
    <div class="table-container">
        <h2>Produk Terlaris</h2>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Jumlah Terjual</th>
                    <th>Total Revenue</th>
                </tr>
            </thead>
            <tbody>
                @foreach($topProducts as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->nama }}</td>
                    <td>{{ $product->kategori }}</td>
                    <td class="text-center">{{ number_format($product->total_terjual) }}</td>
                    <td class="text-right">Rp {{ number_format($product->total_revenue) }}</td>
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