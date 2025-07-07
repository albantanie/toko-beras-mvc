<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock Report</title>
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
        <h2>Stock Report</h2>
    </div>

    <div class="report-date">
        <strong>Report Date: {{ \Carbon\Carbon::parse($reportData['report_date'])->format('d M Y') }}</strong>
    </div>

    <div class="summary">
        <div class="summary-grid">
            <div class="summary-card">
                <h3>Total Items</h3>
                <div class="value">{{ number_format($reportData['total_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>In Stock</h3>
                <div class="value success">{{ number_format($reportData['in_stock_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Low Stock</h3>
                <div class="value warning">{{ number_format($reportData['low_stock_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Out of Stock</h3>
                <div class="value danger">{{ number_format($reportData['out_of_stock_items']) }}</div>
            </div>
            <div class="summary-card">
                <h3>Stock Value (Sell)</h3>
                <div class="value">Rp {{ number_format($reportData['total_stock_value_sell'], 0, ',', '.') }}</div>
            </div>
            <div class="summary-card">
                <h3>Potential Profit</h3>
                <div class="value success">Rp {{ number_format($reportData['potential_profit'], 0, ',', '.') }}</div>
            </div>
        </div>
    </div>

    <h3 class="section-title">Current Stock Status</h3>
    <table class="stock-table">
        <thead>
            <tr>
                <th>Item Code</th>
                <th>Item Name</th>
                <th>Category</th>
                <th class="text-right">Current Stock</th>
                <th class="text-right">Min Stock</th>
                <th class="text-center">Status</th>
                <th class="text-right">Sell Price</th>
                <th class="text-right">Stock Value</th>
            </tr>
        </thead>
        <tbody>
            @foreach($barangs as $barang)
            <tr>
                <td>{{ $barang->kode_barang }}</td>
                <td>{{ $barang->nama }}</td>
                <td>{{ $barang->kategori }}</td>
                <td class="text-right">{{ number_format($barang->stok) }} {{ $barang->satuan }}</td>
                <td class="text-right">{{ number_format($barang->stok_minimum) }}</td>
                <td class="text-center">
                    @if($barang->stok <= 0)
                        <span class="stock-status out">Out</span>
                    @elseif($barang->stok <= $barang->stok_minimum)
                        <span class="stock-status low">Low</span>
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
    <h3 class="section-title">Recent Stock Movements (Last 30 Days)</h3>
    <table class="stock-table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Item</th>
                <th>Type</th>
                <th class="text-right">Quantity</th>
                <th>User</th>
                <th>Notes</th>
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
        <p>Generated on {{ $generatedAt->format('d M Y H:i:s') }}</p>
        <p>This is a computer-generated report. No signature required.</p>
    </div>
</body>
</html>
