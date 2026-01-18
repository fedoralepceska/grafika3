<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Client Balance Breakdown - {{ $clientName }} ({{ $year }})</title>
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
            font-size: 20px;
            color: #1a1a1a;
        }
        .header h2 {
            margin: 5px 0 0 0;
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        .client-info {
            background: #f5f5f5;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 5px;
        }
        .client-info h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
        }
        .balance-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .initial-balance {
            font-size: 14px;
            color: #666;
        }
        .final-balance {
            font-size: 18px;
            font-weight: bold;
        }
        .positive { color: #22c55e; }
        .negative { color: #ef4444; }
        
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
        .section-total {
            background: #f9f9f9;
            font-weight: bold;
        }
        .adjustment-info {
            background: #fff3e0;
            padding: 10px;
            margin: 15px 0;
            border-left: 4px solid #ff9800;
        }
        .status-info {
            background: #e8f5e9;
            padding: 10px;
            margin: 15px 0;
            border-left: 4px solid #4caf50;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Client Balance Breakdown</h1>
        <h2>{{ $clientName }} - Fiscal Year {{ $year }}</h2>
    </div>

    <div class="client-info">
        <h3>{{ $clientName }}</h3>
        <div class="balance-summary">
            <div class="initial-balance">
                Initial Balance: {{ number_format($breakdown['initial_balance'], 2) }}
            </div>
            <div class="final-balance {{ $breakdown['final_balance'] >= 0 ? 'positive' : 'negative' }}">
                Final Balance: {{ number_format($breakdown['final_balance'], 2) }}
                ({{ $breakdown['balance_direction'] === 'we_owe' ? 'We Owe Them' : 'They Owe Us' }})
            </div>
        </div>
    </div>

    @if($entry->is_adjusted)
    <div class="adjustment-info">
        <strong>Note:</strong> This balance has been manually adjusted from the calculated amount of {{ number_format($breakdown['calculated_balance'], 2) }}.
    </div>
    @endif

    <div class="status-info">
        <strong>Status:</strong> {{ ucfirst(str_replace('_', ' ', $entry->status)) }}
    </div>

    <!-- Output Invoices -->
    @if(count($breakdown['output_invoices']['items']) > 0)
    <div class="section">
        <h3>Output Invoices (Faktura) - {{ number_format($breakdown['output_invoices']['total'], 2) }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice Number</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown['output_invoices']['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['number'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="section-total">
                    <td colspan="2">Total Output Invoices</td>
                    <td class="text-right">{{ number_format($breakdown['output_invoices']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Trade Invoices -->
    @if(count($breakdown['trade_invoices']['items']) > 0)
    <div class="section">
        <h3>Trade Invoices - {{ number_format($breakdown['trade_invoices']['total'], 2) }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Invoice Number</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown['trade_invoices']['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['number'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="section-total">
                    <td colspan="2">Total Trade Invoices</td>
                    <td class="text-right">{{ number_format($breakdown['trade_invoices']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Statement Expenses -->
    @if(count($breakdown['statement_expenses']['items']) > 0)
    <div class="section">
        <h3>Statement Expenses - {{ number_format($breakdown['statement_expenses']['total'], 2) }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Comment</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown['statement_expenses']['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['comment'] ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="section-total">
                    <td colspan="2">Total Statement Expenses</td>
                    <td class="text-right">{{ number_format($breakdown['statement_expenses']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Incoming Invoices -->
    @if(count($breakdown['incoming_invoices']['items']) > 0)
    <div class="section">
        <h3>Incoming Invoices - {{ number_format($breakdown['incoming_invoices']['total'], 2) }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown['incoming_invoices']['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="section-total">
                    <td>Total Incoming Invoices</td>
                    <td class="text-right">{{ number_format($breakdown['incoming_invoices']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <!-- Statement Income -->
    @if(count($breakdown['statement_income']['items']) > 0)
    <div class="section">
        <h3>Statement Income - {{ number_format($breakdown['statement_income']['total'], 2) }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Comment</th>
                    <th class="text-right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach($breakdown['statement_income']['items'] as $item)
                <tr>
                    <td>{{ $item['date'] }}</td>
                    <td>{{ $item['comment'] ?: '-' }}</td>
                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="section-total">
                    <td colspan="2">Total Statement Income</td>
                    <td class="text-right">{{ number_format($breakdown['statement_income']['total'], 2) }}</td>
                </tr>
            </tbody>
        </table>
    </div>
    @endif

    <div class="footer">
        <strong>Report Generated:</strong> {{ $exportedAt->format('d.m.Y H:i') }}<br>
        <strong>Generated by:</strong> {{ $exportedBy }}
    </div>
</body>
</html>