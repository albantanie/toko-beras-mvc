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
        .summary {
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .summary-card {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            background-color: #f9f9f9;
        }
        .section {
            margin-bottom: 20px;
        }
        .section h4 {
            background: #f0f0f0;
            padding: 8px;
            margin: 0 0 10px 0;
            font-size: 14px;
            font-weight: bold;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table td {
            border: 1px solid #ddd;
            padding: 8px;
            font-size: 12px;
        }
        .table .total-row {
            background: #f9f9f9;
            font-weight: bold;
        }
        .table .profit-row {
            background: #e8f5e8;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .report-content h3 {
            color: #333;
            font-size: 16px;
            margin-bottom: 15px;
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 5px;
        }
        }
        .summary-card .value {
            font-size: 18px;
            font-weight: bold;
            color: #2563eb;
        }
        .profit-positive {
            color: #16a34a !important;
        }
        .profit-negative {
            color: #dc2626 !important;
        }
        .transactions-section {
            margin-top: 30px;
        }
        .transactions-section h3 {
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .transactions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .transactions-table th,
        .transactions-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        .transactions-table th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        .transactions-table tr:nth-child(even) {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 40px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #ddd;
            padding-top: 20px;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>TOKO BERAS</h1>
        <h2>Financial Report</h2>
    </div>

    <div class="period-info">
        <strong>Report Period: {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</strong>
    </div>

    <!-- Report Content -->
    <div class="report-content">
        @if($type === 'profit_loss')
            <!-- Profit & Loss Report -->
            <h3>LAPORAN LABA RUGI</h3>

            @if(isset($reportData['revenue']))
                <div class="section">
                    <h4>PENDAPATAN</h4>
                    <table class="table">
                        @foreach($reportData['revenue'] as $key => $value)
                            @if($key !== 'total' && is_numeric($value))
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                <td class="text-right">Rp {{ number_format($value, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                        @endforeach
                        <tr class="total-row">
                            <td><strong>Total Pendapatan</strong></td>
                            <td class="text-right"><strong>Rp {{ number_format($reportData['revenue']['total'] ?? 0, 0, ',', '.') }}</strong></td>
                        </tr>
                    </table>
                </div>
            @endif

            @if(isset($reportData['expenses']))
                <div class="section">
                    <h4>BEBAN</h4>
                    <table class="table">
                        @foreach($reportData['expenses'] as $key => $value)
                            @if($key !== 'total' && is_numeric($value))
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                <td class="text-right">Rp {{ number_format($value, 0, ',', '.') }}</td>
                            </tr>
                            @endif
                        @endforeach
                        <tr class="total-row">
                            <td><strong>Total Beban</strong></td>
                            <td class="text-right"><strong>Rp {{ number_format($reportData['expenses']['total'] ?? 0, 0, ',', '.') }}</strong></td>
                        </tr>
                    </table>
                </div>
            @endif

            @if(isset($reportData['profit']))
                <div class="section">
                    <h4>LABA RUGI</h4>
                    <table class="table">
                        <tr class="profit-row">
                            <td><strong>LABA BERSIH</strong></td>
                            <td class="text-right"><strong>Rp {{ number_format($reportData['profit']['net_profit'] ?? 0, 0, ',', '.') }}</strong></td>
                        </tr>
                    </table>
                </div>
            @endif

        @elseif($type === 'balance_sheet')
            <!-- Balance Sheet -->
            <h3>NERACA</h3>
            <p>Laporan Neraca untuk periode {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>

        @elseif($type === 'cash_flow')
            <!-- Cash Flow -->
            <h3>LAPORAN ARUS KAS</h3>
            <p>Laporan Arus Kas untuk periode {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>

        @elseif($type === 'budget_variance')
            <!-- Budget Variance -->
            <h3>ANALISIS VARIANS ANGGARAN</h3>
            <p>Analisis Varians Anggaran untuk periode {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>

        @else
            <!-- Default Report -->
            <h3>LAPORAN KEUANGAN</h3>
            <p>Laporan keuangan untuk periode {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}</p>

            @if(isset($reportData) && is_array($reportData))
                <div class="section">
                    <h4>DATA LAPORAN</h4>
                    <table class="table">
                        @foreach($reportData as $key => $value)
                            @if(is_numeric($value))
                            <tr>
                                <td>{{ ucfirst(str_replace('_', ' ', $key)) }}</td>
                                <td class="text-right">
                                    @if(strpos($key, 'total') !== false || strpos($key, 'amount') !== false)
                                        Rp {{ number_format($value, 0, ',', '.') }}
                                    @else
                                        {{ number_format($value) }}
                                    @endif
                                </td>
                            </tr>
                            @endif
                        @endforeach
                    </table>
                </div>
            @endif
        @endif
    </div>

    @if(isset($transactions) && $transactions->count() > 0)
    <div class="transactions-section">
        <h3>Detail Transaksi</h3>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No Transaksi</th>
                    <th>Pelanggan</th>
                    <th>Kasir</th>
                    <th class="text-right">Jumlah</th>
                    <th>Metode Pembayaran</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transactions as $transaction)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($transaction->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $transaction->nomor_transaksi }}</td>
                    <td>{{ $transaction->nama_pelanggan ?? $transaction->pelanggan->name ?? '-' }}</td>
                    <td>{{ $transaction->user->name ?? '-' }}</td>
                    <td class="text-right">Rp {{ number_format($transaction->total, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($transaction->metode_pembayaran) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <p>Dibuat pada {{ isset($generatedAt) ? $generatedAt->format('d M Y H:i:s') : now()->format('d M Y H:i:s') }}</p>
        <p>Laporan ini dibuat secara otomatis oleh sistem. Tidak memerlukan tanda tangan.</p>
    </div>
</body>
</html>
