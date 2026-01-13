<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Year-End Summary - Bank Statements {{ $year }}</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
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
            font-size: 24px;
            font-weight: bold;
            color: #1a1a1a;
        }
        .stat-label {
            font-size: 11px;
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
            font-size: 11px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background: #f0f0f0;
            font-weight: bold;
        }
        .text-center {
            text-align: center;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .green { color: #22c55e; }
        .gray { color: #9ca3af; }
        .closure-info {
            background: #e8f5e9;
            padding: 10px;
            margin-bottom: 20px;
            border-left: 4px solid #22c55e;
        }
        .closure-info strong {
            color: #22c55e;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Year-End Census Summary</h1>
        <h2>Bank Statements - Fiscal Year {{ $year }}</h2>
    </div>

    @if($closure)
    <div class="closure-info">
        <strong>✓ Year Closed</strong><br>
        Closed on {{ \Carbon\Carbon::parse($closure->closed_at)->format('d.m.Y H:i') }}
        @if($closure->closedByUser)
            by {{ $closure->closedByUser->name }}
        @endif
    </div>
    @endif

    <div class="summary-box">
        <h3>Summary Statistics</h3>
        <div class="stats-grid">
            <table>
                <tr>
                    <td>
                        <div class="stat-value">{{ $stats['total'] }}</div>
                        <div class="stat-label">Total Statements</div>
                    </td>
                    <td>
                        <div class="stat-value gray">{{ $stats['archived'] }}</div>
                        <div class="stat-label">Archived</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h3>Bank Statements List ({{ count($statements) }})</h3>
        @if(count($statements) > 0)
        <table>
            <thead>
                <tr>
                    <th>Statement #</th>
                    <th>Bank</th>
                    <th>Bank Account</th>
                    <th>Date</th>
                    <th>Created By</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($statements as $statement)
                <tr>
                    <td>#{{ $statement['id_per_bank'] }}/{{ $statement['fiscal_year'] }}</td>
                    <td>{{ $statement['bank'] }}</td>
                    <td>{{ $statement['bankAccount'] }}</td>
                    <td>{{ $statement['date'] ? \Carbon\Carbon::parse($statement['date'])->format('d.m.Y') : 'N/A' }}</td>
                    <td>{{ $statement['created_by'] ?? 'N/A' }}</td>
                    <td class="text-center">{{ $statement['archived'] ? 'Archived' : 'Active' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No bank statements for this fiscal year.</p>
        @endif
    </div>

    <div class="footer">
        <strong>Report Generated:</strong> {{ $exportedAt->format('d.m.Y H:i') }}<br>
        <strong>Generated by:</strong> {{ $exportedBy }}
    </div>
</body>
</html>
