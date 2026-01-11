<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Year-End Summary - Materials {{ $year }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 15px;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            color: #1a1a1a;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 16px;
            color: #666;
            font-weight: normal;
        }
        .summary-box {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 25px;
        }
        .summary-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #333;
        }
        .stats-grid {
            width: 100%;
        }
        .stats-grid table {
            width: 100%;
            border: none;
        }
        .stats-grid td {
            text-align: center;
            padding: 10px;
            border: none;
        }
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .stat-label {
            font-size: 10px;
            color: #666;
            margin-top: 5px;
        }
        .section {
            margin-bottom: 25px;
        }
        .section h3 {
            font-size: 14px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 6px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .positive {
            color: #22c55e;
        }
        .negative {
            color: #ef4444;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .closure-info {
            background: #e8f5e9;
            padding: 10px;
            margin-bottom: 20px;
        }
        .closure-info.open {
            background: #fff3e0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Year-End Census Summary</h1>
        <h2>Materials - Fiscal Year {{ $year }}</h2>
    </div>

    @if($closure)
    <div class="closure-info">
        <strong>Year Status:</strong> Closed<br>
        <strong>Closed on:</strong> {{ $closure->closed_at->format('d.m.Y H:i') }}<br>
        <strong>Closed by:</strong> {{ $closure->closedByUser->name ?? 'Unknown' }}
    </div>
    @else
    <div class="closure-info open">
        <strong>Year Status:</strong> Open
    </div>
    @endif

    <div class="summary-box">
        <h3>Summary Statistics</h3>
        <div class="stats-grid">
            <table>
                <tr>
                    <td>
                        <div class="stat-value">{{ $stats['total_articles'] }}</div>
                        <div class="stat-label">Total Articles</div>
                    </td>
                    <td>
                        <div class="stat-value">{{ number_format($stats['total_imported'], 2) }}</div>
                        <div class="stat-label">Total Imported</div>
                    </td>
                    <td>
                        <div class="stat-value">{{ number_format($stats['total_used'], 2) }}</div>
                        <div class="stat-label">Total Used</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h3>Materials Balance ({{ count($materials) }} articles)</h3>
        @if(count($materials) > 0)
        <table>
            <thead>
                <tr>
                    <th>Article Code</th>
                    <th>Article Name</th>
                    <th class="text-right">Imported</th>
                    <th class="text-right">Used</th>
                    <th class="text-right">Balance</th>
                </tr>
            </thead>
            <tbody>
                @foreach($materials as $material)
                <tr>
                    <td>{{ $material['article_code'] ?: '-' }}</td>
                    <td>{{ $material['article_name'] }}</td>
                    <td class="text-right">{{ number_format($material['imported'], 2) }}</td>
                    <td class="text-right">{{ number_format($material['used'], 2) }}</td>
                    <td class="text-right {{ $material['balance'] >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($material['balance'], 2) }}
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No materials data for this fiscal year.</p>
        @endif
    </div>

    <div class="footer">
        <strong>Report Generated:</strong> {{ $exportedAt->format('d.m.Y H:i') }}<br>
        <strong>Generated by:</strong> {{ $exportedBy }}
    </div>
</body>
</html>
