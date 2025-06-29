<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Penjualan</title>
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
            grid-template-columns: repeat(3, 1fr);
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
        <h1>LAPORAN PENJUALAN</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($periodFrom)->format('d M Y') }} - {{ \Carbon\Carbon::parse($periodTo)->format('d M Y') }}</p>
        <p>Dibuat pada: {{ now()->format('d M Y H:i:s') }}</p>
    </div>

    <div class="summary">
        <h3>Ringkasan</h3>
        <div class="summary-grid">
            <div class="summary-item">
                <h4>Total Transaksi</h4>
                <div class="value">{{ number_format($totalTransaksi) }}</div>
            </div>
            <div class="summary-item">
                <h4>Total Pendapatan</h4>
                <div class="value">Rp {{ number_format($totalRevenue) }}</div>
            </div>
            <div class="summary-item">
                <h4>Total Item Terjual</h4>
                <div class="value">{{ number_format($totalItems) }}</div>
            </div>
        </div>
    </div>

    @if($penjualan->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>No Transaksi</th>
                    <th>Kasir</th>
                    <th>Total Item</th>
                    <th>Total Harga</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($penjualan as $index => $p)
                    <tr>
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td>{{ \Carbon\Carbon::parse($p->tanggal_transaksi)->format('d/m/Y') }}</td>
                        <td>{{ $p->nomor_transaksi }}</td>
                        <td>{{ $p->user->name ?? 'N/A' }}</td>
                        <td class="text-center">{{ $p->detailPenjualans->sum('jumlah') }}</td>
                        <td class="text-right">Rp {{ number_format($p->total) }}</td>
                        <td class="text-center">{{ ucfirst($p->status) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        @if($penjualan->count() > 10)
            <div style="margin-top: 20px;">
                <h4>Detail Item Terjual:</h4>
                <table>
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Tanggal</th>
                            <th>Barang</th>
                            <th>Jumlah</th>
                            <th>Harga Satuan</th>
                            <th>Total</th>
                            @if($isAdminOrOwner)
                                <th>Profit</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @php $itemIndex = 1; @endphp
                        @foreach($penjualan as $p)
                            @foreach($p->detailPenjualans as $detail)
                                <tr>
                                    <td class="text-center">{{ $itemIndex++ }}</td>
                                    <td>{{ \Carbon\Carbon::parse($p->tanggal_transaksi)->format('d/m/Y') }}</td>
                                    <td>{{ $detail->barang->nama ?? 'N/A' }}</td>
                                    <td class="text-center">{{ $detail->jumlah }}</td>
                                    <td class="text-right">Rp {{ number_format($detail->harga_satuan) }}</td>
                                    <td class="text-right">Rp {{ number_format($detail->subtotal) }}</td>
                                    @if($isAdminOrOwner && isset($detail->barang->harga_beli))
                                        <td class="text-right">Rp {{ number_format(($detail->harga_satuan - $detail->barang->harga_beli) * $detail->jumlah) }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    @else
        <div style="text-align: center; padding: 40px; color: #666;">
            <h3>Tidak ada data penjualan untuk periode ini</h3>
        </div>
    @endif

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem</p>
        <p>Halaman 1</p>
    </div>
</body>
</html> 