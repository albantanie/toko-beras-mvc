<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Penjualan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #2c5530;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            color: #666;
        }
        .period {
            text-align: center;
            margin: 20px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .summary {
            display: table;
            width: 100%;
            margin: 20px 0;
        }
        .summary-item {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            padding: 15px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
        }
        .summary-item h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #666;
        }
        .summary-item .value {
            font-size: 18px;
            font-weight: bold;
            color: #2c5530;
        }
        .section {
            margin: 30px 0;
        }
        .section h3 {
            font-size: 16px;
            margin-bottom: 15px;
            color: #2c5530;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .currency {
            font-family: monospace;
        }
        .footer {
            margin-top: 50px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
        }
        .payment-methods {
            display: table;
            width: 100%;
        }
        .payment-method {
            display: table-cell;
            width: 50%;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🌾 TOKO BERAS</h1>
        <h2>Laporan Penjualan</h2>
    </div>

    <div class="period">
        Periode: {{ \Carbon\Carbon::parse($period_from)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($period_to)->format('d/m/Y') }}
    </div>

    <!-- Summary Section -->
    <div class="summary">
        <div class="summary-item">
            <h3>Total Penjualan</h3>
            <div class="value currency">Rp {{ number_format($total_revenue, 0, ',', '.') }}</div>
        </div>
        <div class="summary-item">
            <h3>Total Transaksi</h3>
            <div class="value">{{ number_format($total_transactions) }}</div>
        </div>

    </div>

    <!-- Payment Methods Section -->
    <div class="section">
        <h3>📊 Metode Pembayaran</h3>
        <table>
            <thead>
                <tr>
                    <th>Metode Pembayaran</th>
                    <th class="text-center">Jumlah Transaksi</th>
                    <th class="text-right">Total Nilai</th>
                    <th class="text-right">Persentase</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment_methods as $method => $data)
                <tr>
                    <td>{{ ucfirst($method) }}</td>
                    <td class="text-center">{{ $data['count'] }}</td>
                    <td class="text-right currency">Rp {{ number_format($data['total'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format(($data['total'] / $total_revenue) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Top Products Section -->
    <div class="section">
        <h3>🏆 Produk Terlaris</h3>
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th class="text-center">Jumlah Terjual</th>
                    <th class="text-right">Total Pendapatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($top_products as $index => $product)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ $product->barang->nama }}</td>
                    <td>{{ $product->barang->kategori }}</td>
                    <td class="text-center">{{ number_format($product->total_sold) }} {{ $product->barang->satuan }}</td>
                    <td class="text-right currency">Rp {{ number_format($product->total_revenue, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Daily Sales Section -->
    <div class="section">
        <h3>📈 Penjualan Harian</h3>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-center">Jumlah Transaksi</th>
                    <th class="text-right">Total Penjualan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($daily_sales as $daily)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($daily['date'])->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $daily['transactions'] }}</td>
                    <td class="text-right currency">Rp {{ number_format($daily['total'], 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Recent Transactions Section -->
    <div class="section">
        <h3>📋 Transaksi Terbaru (10 Terakhir)</h3>
        <table>
            <thead>
                <tr>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th class="text-center">Items</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions->take(10) as $transaction)
                <tr>
                    <td>{{ $transaction->nomor_transaksi }}</td>
                    <td>{{ \Carbon\Carbon::parse($transaction->tanggal_transaksi)->format('d/m/Y H:i') }}</td>
                    <td>{{ $transaction->nama_pelanggan ?? ($transaction->pelanggan->name ?? 'Walk-in') }}</td>
                    <td>{{ $transaction->user->name }}</td>
                    <td class="text-center">{{ $transaction->detailPenjualans->count() }}</td>
                    <td class="text-right currency">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis pada {{ \Carbon\Carbon::now()->format('d/m/Y H:i:s') }}</p>
        <p>© {{ date('Y') }} Toko Beras - Sistem Manajemen Toko</p>
    </div>
</body>
</html>
