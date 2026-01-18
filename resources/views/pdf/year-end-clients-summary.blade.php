<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Year-End Summary - Clients {{ $year }}</title>
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
            font-size: 18px;
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
        .text-center {
            text-align: center;
        }
        .positive {
            color: #22c55e;
        }
        .negative {
            color: #ef4444;
        }
        .we-owe {
            background: #e8f5e9;
        }
        .they-owe {
            background: #ffebee;
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
        .status-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
        }
        .status-pending {
            background: #fff3e0;
            color: #f57c00;
        }
        .status-ready_to_close {
            background: #e3f2fd;
            color: #1976d2;
        }
        .status-closed {
            background: #ffebee;
            color: #d32f2f;
        }
        .adjusted {
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Year-End Census Summary</h1>
        <h2>Clients - Fiscal Year {{ $year }}</h2>
    </div>

    @if($closure)
    <div class="closure-info">
        <strong>Year Status:</strong> {{ $closure->summary['is_fully_closed'] ?? false ? 'Fully Closed' : 'Partially Closed' }}<br>
        <strong>Closed on:</strong> {{ $closure->closed_at->format('d.m.Y H:i') }}<br>
        <strong>Closed by:</strong> {{ $closure->closedByUser->name ?? 'Unknown' }}<br>
        @if(isset($closure->summary['closed_count']))
        <strong>Clients closed:</strong> {{ $closure->summary['closed_count'] }}
        @endif
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
                        <div class="stat-value">{{ $stats['total_clients'] }}</div>
                        <div class="stat-label">Total Clients</div>
                    </td>
                    <td>
                        <div class="stat-value positive">{{ $stats['clients_we_owe'] }}</div>
                        <div class="stat-label">Clients We Owe</div>
                    </td>
                    <td>
                        <div class="stat-value negative">{{ $stats['clients_owe_us'] }}</div>
                        <div class="stat-label">Clients Owe Us</div>
                    </td>
                    <td>
                        <div class="stat-value positive">{{ number_format($stats['total_we_owe'], 2) }}</div>
                        <div class="stat-label">Total We Owe</div>
                    </td>
                    <td>
                        <div class="stat-value negative">{{ number_format($stats['total_they_owe'], 2) }}</div>
                        <div class="stat-label">Total They Owe</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h3>Client Balances ({{ count($entries) }} clients)</h3>
        @if(count($entries) > 0)
        <table>
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th class="text-right">Initial Balance</th>
                    <th class="text-right">Output Invoices</th>
                    <th class="text-right">Trade Invoices</th>
                    <th class="text-right">Expenses</th>
                    <th class="text-right">Incoming Inv.</th>
                    <th class="text-right">Income</th>
                    <th class="text-right">Final Balance</th>
                    <th class="text-center">Direction</th>
                    <th class="text-center">Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($entries as $entry)
                <tr class="{{ $entry['balance_direction'] === 'we_owe' ? 'we-owe' : 'they-owe' }} {{ $entry['is_adjusted'] ? 'adjusted' : '' }}">
                    <td>
                        {{ $entry['client_name'] }}
                        @if($entry['is_adjusted'])
                        <span style="font-size: 8px;">(adjusted)</span>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($entry['initial_balance'], 2) }}</td>
                    <td class="text-right">{{ number_format($entry['total_output_invoices'], 2) }}</td>
                    <td class="text-right">{{ number_format($entry['total_trade_invoices'], 2) }}</td>
                    <td class="text-right">{{ number_format($entry['total_statement_expenses'], 2) }}</td>
                    <td class="text-right">{{ number_format($entry['total_incoming_invoices'], 2) }}</td>
                    <td class="text-right">{{ number_format($entry['total_statement_income'], 2) }}</td>
                    <td class="text-right {{ $entry['final_balance'] >= 0 ? 'positive' : 'negative' }}">
                        {{ number_format($entry['final_balance'], 2) }}
                    </td>
                    <td class="text-center">
                        {{ $entry['balance_direction'] === 'we_owe' ? 'We Owe' : 'They Owe' }}
                    </td>
                    <td class="text-center">
                        <span class="status-badge status-{{ $entry['status'] }}">
                            {{ ucfirst(str_replace('_', ' ', $entry['status'])) }}
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No client data for this fiscal year.</p>
        @endif
    </div>

    <div class="footer">
        <strong>Report Generated:</strong> {{ $exportedAt->format('d.m.Y H:i') }}<br>
        <strong>Generated by:</strong> {{ $exportedBy }}
    </div>
</body>
</html>
