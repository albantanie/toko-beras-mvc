<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Cash Flow</title>
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
            border-bottom: 2px solid #2563eb;
            padding-bottom: 15px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
            font-size: 24px;
            font-weight: bold;
        }
        .header .subtitle {
            color: #666;
            margin: 5px 0;
            font-size: 14px;
        }
        .period {
            background: #f8fafc;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
            border: 1px solid #e2e8f0;
        }
        .section {
            margin-bottom: 25px;
        }
        .section-title {
            background: #2563eb;
            color: white;
            padding: 8px 12px;
            margin: 0 0 10px 0;
            font-weight: bold;
            font-size: 14px;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        .table th,
        .table td {
            border: 1px solid #d1d5db;
            padding: 8px;
            text-align: left;
        }
        .table th {
            background: #f3f4f6;
            font-weight: bold;
            font-size: 11px;
        }
        .table td {
            font-size: 11px;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .amount {
            font-family: 'Courier New', monospace;
            font-weight: bold;
        }
        .positive {
            color: #059669;
        }
        .negative {
            color: #dc2626;
        }
        .total-row {
            background: #f9fafb;
            font-weight: bold;
        }
        .summary-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            padding: 5px 0;
            border-bottom: 1px dotted #d1d5db;
        }
        .summary-item:last-child {
            border-bottom: none;
            font-weight: bold;
            font-size: 13px;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN CASH FLOW</h1>
        <div class="subtitle">Toko Beras MVC</div>
        <div class="subtitle">{{ $generatedAt->format('d F Y H:i:s') }}</div>
    </div>

    <div class="period">
        <strong>Periode: {{ \Carbon\Carbon::parse($startDate)->format('d F Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d F Y') }}</strong>
    </div>

    <!-- Cash Flow Statement -->
    <div class="section">
        <h2 class="section-title">LAPORAN ARUS KAS</h2>

        <table class="table">
            <thead>
                <tr>
                    <th style="width: 60%">Keterangan</th>
                    <th style="width: 40%" class="text-right">Jumlah (Rp)</th>
                </tr>
            </thead>
            <tbody>
                <!-- Operating Activities -->
                <tr>
                    <td colspan="2" style="background: #e5e7eb; font-weight: bold;">AKTIVITAS OPERASIONAL</td>
                </tr>
                @if(isset($cashFlowStatement['operating_activities']))
                    @if(isset($cashFlowStatement['operating_activities']['inflows']) && is_array($cashFlowStatement['operating_activities']['inflows']))
                        @foreach($cashFlowStatement['operating_activities']['inflows'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? $item['category'] ?? 'Pemasukan Operasional' }}</td>
                            <td class="text-right amount positive">
                                {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    @endif
                    @if(isset($cashFlowStatement['operating_activities']['outflows']) && is_array($cashFlowStatement['operating_activities']['outflows']))
                        @foreach($cashFlowStatement['operating_activities']['outflows'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? $item['category'] ?? 'Pengeluaran Operasional' }}</td>
                            <td class="text-right amount negative">
                                ({{ number_format($item['amount'] ?? 0, 0, ',', '.') }})
                            </td>
                        </tr>
                        @endforeach
                    @endif
                @endif
                <tr class="total-row">
                    <td><strong>Total Aktivitas Operasional</strong></td>
                    <td class="text-right amount">
                        <strong>{{ number_format($cashFlowStatement['operating_activities']['net_operating'] ?? 0, 0, ',', '.') }}</strong>
                    </td>
                </tr>

                <!-- Investing Activities -->
                <tr>
                    <td colspan="2" style="background: #e5e7eb; font-weight: bold;">AKTIVITAS INVESTASI</td>
                </tr>
                @if(isset($cashFlowStatement['investing_activities']))
                    @if(isset($cashFlowStatement['investing_activities']['inflows']) && is_array($cashFlowStatement['investing_activities']['inflows']))
                        @foreach($cashFlowStatement['investing_activities']['inflows'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? $item['category'] ?? 'Pemasukan Investasi' }}</td>
                            <td class="text-right amount positive">
                                {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    @endif
                    @if(isset($cashFlowStatement['investing_activities']['outflows']) && is_array($cashFlowStatement['investing_activities']['outflows']))
                        @foreach($cashFlowStatement['investing_activities']['outflows'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? $item['category'] ?? 'Pengeluaran Investasi' }}</td>
                            <td class="text-right amount negative">
                                ({{ number_format($item['amount'] ?? 0, 0, ',', '.') }})
                            </td>
                        </tr>
                        @endforeach
                    @endif
                @endif
                <tr class="total-row">
                    <td><strong>Total Aktivitas Investasi</strong></td>
                    <td class="text-right amount">
                        <strong>{{ number_format($cashFlowStatement['investing_activities']['net_investing'] ?? 0, 0, ',', '.') }}</strong>
                    </td>
                </tr>

                <!-- Financing Activities -->
                <tr>
                    <td colspan="2" style="background: #e5e7eb; font-weight: bold;">AKTIVITAS PENDANAAN</td>
                </tr>
                @if(isset($cashFlowStatement['financing_activities']))
                    @if(isset($cashFlowStatement['financing_activities']['inflows']) && is_array($cashFlowStatement['financing_activities']['inflows']))
                        @foreach($cashFlowStatement['financing_activities']['inflows'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? $item['category'] ?? 'Pemasukan Pendanaan' }}</td>
                            <td class="text-right amount positive">
                                {{ number_format($item['amount'] ?? 0, 0, ',', '.') }}
                            </td>
                        </tr>
                        @endforeach
                    @endif
                    @if(isset($cashFlowStatement['financing_activities']['outflows']) && is_array($cashFlowStatement['financing_activities']['outflows']))
                        @foreach($cashFlowStatement['financing_activities']['outflows'] as $item)
                        <tr>
                            <td>{{ $item['description'] ?? $item['category'] ?? 'Pengeluaran Pendanaan' }}</td>
                            <td class="text-right amount negative">
                                ({{ number_format($item['amount'] ?? 0, 0, ',', '.') }})
                            </td>
                        </tr>
                        @endforeach
                    @endif
                @endif
                <tr class="total-row">
                    <td><strong>Total Aktivitas Pendanaan</strong></td>
                    <td class="text-right amount">
                        <strong>{{ number_format($cashFlowStatement['financing_activities']['net_financing'] ?? 0, 0, ',', '.') }}</strong>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Summary -->
    <div class="summary-box">
        <h3 style="margin-top: 0; color: #2563eb;">RINGKASAN CASH FLOW</h3>
        <div class="summary-item">
            <span>Kas Awal Periode:</span>
            <span class="amount">Rp {{ number_format($cashFlowStatement['opening_balance'] ?? 0, 0, ',', '.') }}</span>
        </div>
        <div class="summary-item">
            <span>Arus Kas Operasional:</span>
            <span class="amount {{ ($cashFlowStatement['operating_activities']['net_operating'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                Rp {{ number_format($cashFlowStatement['operating_activities']['net_operating'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        <div class="summary-item">
            <span>Arus Kas Investasi:</span>
            <span class="amount {{ ($cashFlowStatement['investing_activities']['net_investing'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                Rp {{ number_format($cashFlowStatement['investing_activities']['net_investing'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        <div class="summary-item">
            <span>Arus Kas Pendanaan:</span>
            <span class="amount {{ ($cashFlowStatement['financing_activities']['net_financing'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                Rp {{ number_format($cashFlowStatement['financing_activities']['net_financing'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        <div class="summary-item">
            <span>Perubahan Kas Bersih:</span>
            <span class="amount {{ ($cashFlowStatement['net_cash_flow'] ?? 0) >= 0 ? 'positive' : 'negative' }}">
                Rp {{ number_format($cashFlowStatement['net_cash_flow'] ?? 0, 0, ',', '.') }}
            </span>
        </div>
        <div class="summary-item">
            <span>Kas Akhir Periode:</span>
            <span class="amount">Rp {{ number_format($cashFlowStatement['closing_balance'] ?? 0, 0, ',', '.') }}</span>
        </div>
    </div>

    <!-- Analytics -->
    @if(isset($analytics) && !empty($analytics))
    <div class="section">
        <h2 class="section-title">ANALISIS CASH FLOW</h2>

        <table class="table">
            <thead>
                <tr>
                    <th>Indikator</th>
                    <th class="text-right">Nilai</th>
                </tr>
            </thead>
            <tbody>
                @if(isset($analytics['total_inflows']))
                <tr>
                    <td>Total Arus Masuk</td>
                    <td class="text-right amount">Rp {{ number_format($analytics['total_inflows'], 0, ',', '.') }}</td>
                </tr>
                @endif
                @if(isset($analytics['total_outflows']))
                <tr>
                    <td>Total Arus Keluar</td>
                    <td class="text-right amount">Rp {{ number_format($analytics['total_outflows'], 0, ',', '.') }}</td>
                </tr>
                @endif
                @if(isset($analytics['net_flow']))
                <tr>
                    <td><strong>Arus Kas Bersih</strong></td>
                    <td class="text-right amount">
                        <strong style="color: {{ $analytics['net_flow'] >= 0 ? '#059669' : '#dc2626' }}">
                            Rp {{ number_format($analytics['net_flow'], 0, ',', '.') }}
                        </strong>
                    </td>
                </tr>
                @endif
            </tbody>
        </table>

        @if(isset($analytics['flow_type_breakdown']) && is_array($analytics['flow_type_breakdown']))
        <h4 style="margin-top: 20px;">Breakdown per Jenis Aktivitas</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Jenis Aktivitas</th>
                    <th class="text-right">Arus Masuk</th>
                    <th class="text-right">Arus Keluar</th>
                    <th class="text-right">Arus Bersih</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['flow_type_breakdown'] as $breakdown)
                <tr>
                    <td>{{ $breakdown['type_display'] ?? $breakdown['type'] }}</td>
                    <td class="text-right amount">Rp {{ number_format($breakdown['inflows'], 0, ',', '.') }}</td>
                    <td class="text-right amount">Rp {{ number_format($breakdown['outflows'], 0, ',', '.') }}</td>
                    <td class="text-right amount" style="color: {{ $breakdown['net'] >= 0 ? '#059669' : '#dc2626' }}">
                        Rp {{ number_format($breakdown['net'], 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if(isset($analytics['category_breakdown']) && is_array($analytics['category_breakdown']) && count($analytics['category_breakdown']) > 0)
        <h4 style="margin-top: 20px;">Breakdown per Kategori</h4>
        <table class="table">
            <thead>
                <tr>
                    <th>Kategori</th>
                    <th class="text-right">Arus Masuk</th>
                    <th class="text-right">Arus Keluar</th>
                    <th class="text-right">Arus Bersih</th>
                </tr>
            </thead>
            <tbody>
                @foreach($analytics['category_breakdown'] as $breakdown)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $breakdown['category'])) }}</td>
                    <td class="text-right amount">Rp {{ number_format($breakdown['inflows'], 0, ',', '.') }}</td>
                    <td class="text-right amount">Rp {{ number_format($breakdown['outflows'], 0, ',', '.') }}</td>
                    <td class="text-right amount" style="color: {{ $breakdown['net'] >= 0 ? '#059669' : '#dc2626' }}">
                        Rp {{ number_format($breakdown['net'], 0, ',', '.') }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @endif

    <div class="footer">
        <p>Laporan ini dibuat secara otomatis oleh Sistem Toko Beras MVC</p>
        <p>Dicetak pada: {{ $generatedAt->format('d F Y H:i:s') }}</p>
    </div>
</body>
</html>
