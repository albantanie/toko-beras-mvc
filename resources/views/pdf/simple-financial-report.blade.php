<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Keuangan</title>
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
        .period-info {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        .period-info strong {
            color: #1976d2;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h4 {
            margin: 0 0 10px 0;
            color: #333;
            font-size: 14px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th,
        .table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .table th {
            background-color: #f5f5f5;
            font-weight: bold;
            border-bottom: 2px solid #333;
        }
        .table thead th {
            background-color: #e3f2fd;
            color: #1976d2;
        }
        .text-right {
            text-align: right;
        }
        .total-row {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .profit-row {
            background-color: #e8f5e8;
            font-weight: bold;
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
        <h1>TOKO BERAS</h1>
        <h2>Laporan Keuangan</h2>
    </div>

    <div class="period-info">
        <strong>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
    </div>

    <!-- Revenue Section -->
    <div class="section">
        <h4>PENDAPATAN</h4>
        <table class="table">
            <tr>
                <td>Total Penjualan</td>
                <td class="text-right">Rp {{ number_format($reportData['revenue']['total'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Jumlah Transaksi</td>
                <td class="text-right">{{ number_format($reportData['revenue']['total_transactions'] ?? 0) }}</td>
            </tr>
            <tr>
                <td>Total Item Terjual</td>
                <td class="text-right">{{ number_format($reportData['revenue']['total_items_sold'] ?? 0) }} karung</td>
            </tr>
            <tr>
                <td>Rata-rata per Transaksi</td>
                <td class="text-right">Rp {{ number_format($reportData['revenue']['average_transaction'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Pendapatan</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($reportData['revenue']['total'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Expenses Section -->
    <div class="section">
        <h4>BEBAN</h4>
        <table class="table">
            <tr>
                <td>Beban Operasional</td>
                <td class="text-right">Rp {{ number_format($reportData['expenses']['total'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td><strong>Total Beban</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($reportData['expenses']['total'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <!-- Profit Section -->
    <div class="section">
        <h4>LABA RUGI</h4>
        <table class="table">
            <tr>
                <td>Pendapatan Kotor</td>
                <td class="text-right">Rp {{ number_format($reportData['profit']['gross_revenue'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td>Beban Operasional</td>
                <td class="text-right">Rp {{ number_format($reportData['profit']['operating_expenses'] ?? 0, 0, ',', '.') }}</td>
            </tr>
            <tr class="profit-row">
                <td><strong>LABA BERSIH</strong></td>
                <td class="text-right"><strong>Rp {{ number_format($reportData['profit']['net_profit'] ?? 0, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Margin Laba (%)</td>
                <td class="text-right">{{ number_format($reportData['profit']['net_margin'] ?? 0, 1) }}%</td>
            </tr>
        </table>
    </div>

    <!-- Daily Sales Breakdown -->
    @if(isset($reportData['daily_breakdown']) && count($reportData['daily_breakdown']) > 0)
    <div class="section">
        <h4>RINGKASAN PENJUALAN HARIAN</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-right">Total Penjualan</th>
                    <th class="text-right">Transaksi</th>
                    <th class="text-right">Item Terjual</th>
                    <th>Metode Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($reportData['daily_breakdown'] as $daily)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($daily['date'])->format('d/m/Y') }}</td>
                    <td class="text-right">Rp {{ number_format($daily['total_amount'], 0, ',', '.') }}</td>
                    <td class="text-right">{{ $daily['total_transactions'] }}</td>
                    <td class="text-right">{{ $daily['total_items_sold'] }} karung</td>
                    <td>
                        @if(count($daily['payment_methods']) > 0)
                            @foreach($daily['payment_methods'] as $method)
                                {{ ucfirst($method['method']) }} ({{ $method['count'] }}x)
                                @if(!$loop->last), @endif
                            @endforeach
                        @else
                            -
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Laporan dibuat pada: {{ $generatedAt->format('d M Y H:i:s') }}</p>
        <p>Toko Beras - Sistem Manajemen Keuangan</p>
    </div>
</body>
</html>
