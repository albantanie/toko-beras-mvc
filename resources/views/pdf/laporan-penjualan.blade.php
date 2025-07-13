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
        h1 { 
            font-size: 18px; 
            margin-bottom: 10px; 
            text-align: center; 
        }
        h2 { 
            font-size: 16px; 
            margin-bottom: 8px; 
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
            border: 1px solid #ccc; 
            padding: 6px 8px; 
            text-align: left; 
        }
        th { 
            background: #f5f5f5; 
        }
        .text-right { 
            text-align: right; 
        }
        .text-center { 
            text-align: center; 
        }
        .footer { 
            margin-top: 30px; 
            font-size: 10px; 
            color: #666; 
            text-align: center; 
        }
    </style>
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
        <p>Periode: {{ \Carbon\Carbon::parse($period_from)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($period_to)->format('d M Y') }}</p>
        <p>Dibuat pada: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</p>
    </div>

    <div class="summary">
        <h3>Ringkasan Penjualan</h3>
        <table>
            <tr>
                <th>Total Penjualan</th>
                <td class="text-right">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Jumlah Transaksi</th>
                <td class="text-right">{{ number_format($summary['total_transaksi'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Rata-rata Nilai Transaksi</th>
                <td class="text-right">Rp {{ number_format($summary['rata_rata_transaksi'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>

    <h2>Detail Transaksi</h2>
    @if(isset($penjualans) && count($penjualans) > 0)
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Transaksi</th>
                <th>Customer</th>
                <th>Pickup</th>
                <th>User</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualans as $index => $t)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $t->nomor_transaksi }}</td>
                    <td>{{ $t->nama_pelanggan ?? 'Umum' }}</td>
                    <td>
                        @php
                            $pickupLabel = match($t->pickup_method ?? 'self') {
                                'self' => 'Ambil Sendiri',
                                'grab' => 'Grab',
                                'gojek' => 'Gojek',
                                'other' => 'Lainnya',
                                default => 'Ambil Sendiri'
                            };
                        @endphp
                        {{ $pickupLabel }}
                        @if($t->pickup_person_name)
                            <br><small>{{ $t->pickup_person_name }}</small>
                        @endif
                    </td>
                    <td>{{ $t->user->name ?? 'System' }}</td>
                    <td class="text-right">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="6" class="text-right">TOTAL</th>
                <th class="text-right">Rp {{ number_format($summary['total_penjualan'], 0, ',', '.') }}</th>
            </tr>
        </tfoot>
    </table>

    <h2>Detail Item Terjual</h2>
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>No. Transaksi</th>
                <th>Nama Barang</th>
                <th>Jumlah</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @php $counter = 1; @endphp
            @foreach($penjualans as $penjualan)
                @foreach($penjualan->detailPenjualans as $detail)
                    <tr>
                        <td class="text-center">{{ $counter++ }}</td>
                        <td>{{ $penjualan->nomor_transaksi }}</td>
                        <td>{{ $detail->barang->nama ?? 'Barang tidak ditemukan' }}</td>
                        <td class="text-center">{{ number_format($detail->jumlah, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->harga, 0, ',', '.') }}</td>
                        <td class="text-right">Rp {{ number_format($detail->subtotal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endforeach
        </tbody>
    </table>
    @else
        <p>Tidak ada transaksi dalam periode ini.</p>
    @endif
    
    <div class="footer">
        <p>Laporan ini dihasilkan secara otomatis oleh Sistem Toko Beras.</p>
        <p>© {{ date('Y') }} Toko Beras System</p>
    </div>
</body>
</html> 