<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Year-End Summary - Orders {{ $year }}</title>
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
        .green { color: #22c55e; }
        .blue { color: #3b82f6; }
        .orange { color: #f59e0b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Year-End Census Summary</h1>
        <h2>Orders - Fiscal Year {{ $year }}</h2>
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
                        <div class="stat-value">{{ $stats['total'] }}</div>
                        <div class="stat-label">Total Orders</div>
                    </td>
                    <td>
                        <div class="stat-value green">{{ $stats['completed'] }}</div>
                        <div class="stat-label">Completed</div>
                    </td>
                    <td>
                        <div class="stat-value blue">{{ $stats['in_progress'] }}</div>
                        <div class="stat-label">In Progress</div>
                    </td>
                    <td>
                        <div class="stat-value orange">{{ $stats['not_started'] }}</div>
                        <div class="stat-label">Not Started</div>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="section">
        <h3>Completed Orders ({{ $completedOrders->count() }})</h3>
        @if($completedOrders->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Completion Date</th>
                </tr>
            </thead>
            <tbody>
                @foreach($completedOrders as $order)
                <tr>
                    <td>#{{ $order->order_number }}/{{ $order->fiscal_year }}</td>
                    <td>{{ $order->invoice_title }}</td>
                    <td>{{ $order->client->name ?? 'N/A' }}</td>
                    <td>{{ $order->end_date ? \Carbon\Carbon::parse($order->end_date)->format('d.m.Y') : 'N/A' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No completed orders for this fiscal year.</p>
        @endif
    </div>

    <div class="section">
        <h3>Incomplete Orders ({{ $transferOrders->count() }})</h3>
        @if($transferOrders->count() > 0)
        <table>
            <thead>
                <tr>
                    <th>Order #</th>
                    <th>Title</th>
                    <th>Client</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach($transferOrders as $order)
                <tr>
                    <td>#{{ $order->order_number }}/{{ $order->fiscal_year }}</td>
                    <td>{{ $order->invoice_title }}</td>
                    <td>{{ $order->client->name ?? 'N/A' }}</td>
                    <td>{{ $order->status }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @else
        <p>No incomplete orders for this fiscal year.</p>
        @endif
    </div>

    <div class="footer">
        <strong>Report Generated:</strong> {{ $exportedAt->format('d.m.Y H:i') }}<br>
        <strong>Generated by:</strong> {{ $exportedBy }}
    </div>
</body>
</html>
