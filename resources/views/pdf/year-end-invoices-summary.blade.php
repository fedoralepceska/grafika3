<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Year-End Summary - Invoices {{ $year }}</title>
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
        .text-right {
            text-align: right;
        }
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #ddd;
            font-size: 10px;
            color: #666;
        }
        .green { color: #22c55e; }
        .totals-row {
            font-weight: bold;
            background: #f5f5f5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Year-End Census Summary</h1>
        <h2>Invoices - Fiscal Year {{ $year }}</h2>
    </div>

    <div class="summary-box">
        <h3>Summary Statistics</h3>
        <div class="stats-grid">
            <table>
                <tr>
                    <td>
                        <div class="stat-value">{{ $stats['total'] }}</div>
                        <div class="stat-label">Total Invoices</div>
                    </td>
                    <td>
                        <div class="stat-value green">{{ number_format($stats['total_revenue'], 2) }}</div>
                        <div class="stat-label">Total Revenue (ден.)</div>
                    </td>
                    <td>
                        <div class="stat-value">{{ number_format($stats['total_vat'], 2) }}</div>
                        <div class="stat-label">Total VAT (ден.)</div>
                    </td>
                    <td>
                        <div class="stat-value">{{ $stats['orders_count'] }}</div>
                        <div class="stat-label">Orders Invoiced</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h3>Invoices List ({{ count($invoices) }})</h3>
        @if(count($invoices) > 0)
        <table>
            <thead>
                <tr>
                    <th>Invoice #</th>
                    <th>Date</th>
                    <th>Client</th>
                    <th>Orders</th>
                    <th class="text-right">Amount (ден.)</th>
                    <th class="text-right">VAT (ден.)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoices as $invoice)
                <tr>
                    <td>#{{ $invoice['faktura_number'] }}/{{ $invoice['fiscal_year'] }}</td>
                    <td>{{ $invoice['created_at'] ? \Carbon\Carbon::parse($invoice['created_at'])->format('d.m.Y') : 'N/A' }}</td>
                    <td>{{ $invoice['client_name'] ?? 'N/A' }}</td>
                    <td>{{ $invoice['orders_count'] }}</td>
                    <td class="text-right">{{ number_format($invoice['total_amount'], 2) }}</td>
                    <td class="text-right">{{ number_format($invoice['total_vat'], 2) }}</td>
                </tr>
                @endforeach
                <tr class="totals-row">
                    <td colspan="4"><strong>TOTAL</strong></td>
                    <td class="text-right"><strong>{{ number_format($stats['total_revenue'], 2) }}</strong></td>
                    <td class="text-right"><strong>{{ number_format($stats['total_vat'], 2) }}</strong></td>
                </tr>
            </tbody>
        </table>
        @else
        <p>No invoices for this fiscal year.</p>
        @endif
    </div>

    <div class="footer">
        <strong>Report Generated:</strong> {{ $exportedAt->format('d.m.Y H:i') }}<br>
        <strong>Generated by:</strong> {{ $exportedBy }}
    </div>
</body>
</html>
