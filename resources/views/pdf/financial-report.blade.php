<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Financial Report</title>
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
        <strong>Report Period: {{ \Carbon\Carbon::parse($periodFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($periodTo)->format('d M Y') }}</strong>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Revenue</h3>
                <div class="value">Rp {{ number_format($reportData['total_revenue'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <h3>Total Cost</h3>
                <div class="value">Rp {{ number_format($reportData['total_cost'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <h3>Net Profit</h3>
                <div class="value {{ $reportData['profit'] >= 0 ? 'profit-positive' : 'profit-negative' }}">
                    Rp {{ number_format($reportData['profit'], 0, ',', '.') }}
                </div>
            </div>
            <div class="summary-card">
                <h3>Total Transactions</h3>
                <div class="value">{{ number_format($reportData['total_transactions']) }}</div>
            </div>
        </div>
        
        <div class="summary-card">
            <h3>Average Transaction Value</h3>
            <div class="value">Rp {{ number_format($reportData['average_transaction'], 0, ',', '.') }}</div>
        </div>
    </div>

    @if($transactions->count() > 0)
    <div class="transactions-section">
        <h3>Transaction Details</h3>
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Transaction No</th>
                    <th>Customer</th>
                    <th>Cashier</th>
                    <th class="text-right">Amount</th>
                    <th>Payment Method</th>
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
        <p>Generated on {{ $generatedAt->format('d M Y H:i:s') }}</p>
        <p>This is a computer-generated report. No signature required.</p>
    </div>
</body>
</html>
