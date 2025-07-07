<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Keuangan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; }
        h1 { font-size: 18px; margin-bottom: 10px; text-align: center; }
        h2 { font-size: 16px; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f5f5f5; }
        .summary-box { 
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            margin-bottom: 20px;
        }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .header { border-bottom: 2px solid #333; padding-bottom: 10px; margin-bottom: 20px; text-align: center; }
        .footer { margin-top: 30px; font-size: 10px; color: #666; text-align: center; }
        .profit { color: green; }
        .loss { color: red; }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN KEUANGAN</h1>
        <p>Periode: {{ \Carbon\Carbon::parse($period_from)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($period_to)->format('d M Y') }}</p>
        <p>Dibuat pada: {{ \Carbon\Carbon::now()->format('d M Y H:i') }}</p>
    </div>
    
    <div class="summary-box">
        <h2>Ringkasan Keuangan</h2>
        <table>
            <tr>
                <th>Total Pemasukan</th>
                <td class="text-right">Rp {{ number_format($data['total_pemasukan'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Modal (HPP)</th>
                <td class="text-right">Rp {{ number_format($data['total_pengeluaran'], 0, ',', '.') }}</td>
            </tr>
            <tr>
                <th>Total Profit</th>
                <td class="text-right {{ $data['saldo_akhir'] >= 0 ? 'profit' : 'loss' }}">
                    Rp {{ number_format($data['saldo_akhir'], 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <th>Jumlah Transaksi</th>
                <td class="text-right">{{ number_format($data['total_transaksi'], 0, ',', '.') }}</td>
            </tr>
        </table>
    </div>
    
    <h2>Detail Transaksi</h2>
    @if(isset($transactions) && count($transactions) > 0)
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>No. Transaksi</th>
                <th>Customer</th>
                <th>Total</th>
                <th>HPP</th>
                <th>Profit</th>
            </tr>
        </thead>
        <tbody>
            @foreach($transactions as $index => $t)
                @php 
                    $hpp = 0;
                    foreach ($t->detailPenjualans as $detail) {
                        $hpp += $detail->jumlah * ($detail->barang->harga_beli ?? 0);
                    }
                    $profit = $t->total - $hpp;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($t->tanggal_transaksi)->format('d/m/Y') }}</td>
                    <td>{{ $t->nomor_transaksi }}</td>
                    <td>{{ $t->nama_pelanggan ?? 'Umum' }}</td>
                    <td class="text-right">Rp {{ number_format($t->total, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($hpp, 0, ',', '.') }}</td>
                    <td class="text-right {{ $profit >= 0 ? 'profit' : 'loss' }}">
                        Rp {{ number_format($profit, 0, ',', '.') }}
                    </td>
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <th colspan="4">TOTAL</th>
                <th class="text-right">Rp {{ number_format($data['total_pemasukan'], 0, ',', '.') }}</th>
                <th class="text-right">Rp {{ number_format($data['total_pengeluaran'], 0, ',', '.') }}</th>
                <th class="text-right {{ $data['saldo_akhir'] >= 0 ? 'profit' : 'loss' }}">
                    Rp {{ number_format($data['saldo_akhir'], 0, ',', '.') }}
                </th>
            </tr>
        </tfoot>
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
