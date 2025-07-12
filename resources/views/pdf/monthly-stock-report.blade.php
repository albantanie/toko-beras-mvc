<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Stok Bulanan</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 20px;
        }
        
        .header h1 {
            color: #7c3aed;
            font-size: 24px;
            margin: 0 0 10px 0;
            font-weight: bold;
        }
        
        .header h2 {
            color: #64748b;
            font-size: 18px;
            margin: 0 0 5px 0;
            font-weight: normal;
        }
        
        .header .period {
            color: #475569;
            font-size: 14px;
            margin: 5px 0;
        }
        
        .info-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 25px;
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }
        
        .info-left, .info-right {
            width: 48%;
        }
        
        .info-item {
            margin-bottom: 8px;
        }
        
        .info-label {
            font-weight: bold;
            color: #374151;
            display: inline-block;
            width: 120px;
        }
        
        .summary-cards {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            background: linear-gradient(135deg, #7c3aed 0%, #5b21b6 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary-card.orange {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }
        
        .summary-card h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            opacity: 0.9;
        }
        
        .summary-card .value {
            font-size: 24px;
            font-weight: bold;
            margin: 0;
        }
        
        .table-container {
            margin-bottom: 30px;
        }
        
        .table-title {
            font-size: 16px;
            font-weight: bold;
            color: #1f2937;
            margin-bottom: 15px;
            padding-bottom: 5px;
            border-bottom: 1px solid #e5e7eb;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
        }
        
        th, td {
            padding: 12px 8px;
            text-align: left;
            border-bottom: 1px solid #e5e7eb;
        }
        
        th {
            background-color: #f9fafb;
            font-weight: bold;
            color: #374151;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            font-size: 11px;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .currency {
            font-weight: bold;
            color: #059669;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            font-size: 10px;
            color: #6b7280;
            text-align: center;
        }
        
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        
        .signature-box {
            width: 200px;
            text-align: center;
        }
        
        .signature-line {
            border-top: 1px solid #374151;
            margin-top: 60px;
            padding-top: 5px;
            font-size: 11px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>TOKO BERAS</h1>
        <h2>Laporan Stok Bulanan</h2>
        <div class="period">{{ $data['period']['month_name'] }}</div>
        <div class="period">Periode: {{ \Carbon\Carbon::parse($data['period']['from'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($data['period']['to'])->format('d/m/Y') }}</div>
    </div>

    <div class="info-section">
        <div class="info-left">
            <div class="info-item">
                <span class="info-label">Dibuat oleh:</span>
                {{ $user->name }}
            </div>
            <div class="info-item">
                <span class="info-label">Role:</span>
                {{ ucfirst($user->roles->first()->name ?? 'Unknown') }}
            </div>
            <div class="info-item">
                <span class="info-label">Tanggal Generate:</span>
                {{ $generated_at->format('d/m/Y H:i:s') }}
            </div>
        </div>
        <div class="info-right">
            <div class="info-item">
                <span class="info-label">Total Laporan:</span>
                {{ $data['reports_included'] }} hari
            </div>
            <div class="info-item">
                <span class="info-label">Hari Pergerakan:</span>
                {{ $data['summary']['days_with_movements'] }} hari
            </div>
            <div class="info-item">
                <span class="info-label">Status:</span>
                Menunggu Persetujuan
            </div>
        </div>
    </div>

    <div class="summary-cards">
        <div class="summary-card">
            <h3>Total Pergerakan</h3>
            <div class="value">{{ number_format($data['summary']['total_movements']) }}</div>
        </div>
        <div class="summary-card orange">
            <h3>Nilai Stok</h3>
            <div class="value">Rp {{ number_format($data['summary']['total_stock_value'], 0, ',', '.') }}</div>
        </div>
        <div class="summary-card">
            <h3>Rata-rata Harian</h3>
            <div class="value">{{ number_format($data['summary']['average_daily_movements'], 1) }}</div>
        </div>
        <div class="summary-card orange">
            <h3>Hari Aktif</h3>
            <div class="value">{{ $data['summary']['days_with_movements'] }} hari</div>
        </div>
    </div>

    <div class="table-container">
        <div class="table-title">Ringkasan Pergerakan Stok Harian</div>
        <table>
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th class="text-center">Total Pergerakan</th>
                    <th class="text-right">Nilai Stok</th>
                    <th class="text-center">Jenis Pergerakan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($data['daily_breakdown'] as $daily)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($daily['date'])->format('d/m/Y') }}</td>
                    <td class="text-center">{{ $daily['total_movements'] }}</td>
                    <td class="text-right currency">Rp {{ number_format($daily['total_stock_value'], 0, ',', '.') }}</td>
                    <td class="text-center">
                        @if(isset($daily['movement_types']))
                            @foreach($daily['movement_types'] as $type => $count)
                                {{ ucfirst($type) }}: {{ $count }}
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

    <div class="signature-section">
        <div class="signature-box">
            <div>Dibuat oleh:</div>
            <div class="signature-line">{{ $user->name }}</div>
        </div>
        <div class="signature-box">
            <div>Disetujui oleh:</div>
            <div class="signature-line">Owner</div>
        </div>
    </div>

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh sistem Toko Beras</p>
        <p>Dicetak pada: {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>
</body>
</html>
