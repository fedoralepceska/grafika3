<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Фактури</title>
    <style>
        /* Register Calibri (supports multiple locations; DomPDF will use the first accessible) */
        @font-face {
            font-family: 'Calibri';
            src: url('{{ storage_path('fonts/CALIBRI.TTF') }}') format('truetype'),
                 url('{{ public_path('fonts/CALIBRI.TTF') }}') format('truetype'),
                 url('{{ asset('fonts/CALIBRI.TTF') }}') format('truetype');
            font-weight: 400;
            font-style: normal;
        }
        @font-face {
            font-family: 'Calibri';
            src: url('{{ storage_path('fonts/CALIBRIB.TTF') }}') format('truetype'),
                 url('{{ public_path('fonts/CALIBRIB.TTF') }}') format('truetype'),
                 url('{{ asset('fonts/CALIBRIB.TTF') }}') format('truetype');
            font-weight: 700;
            font-style: normal;
        }
        @font-face {
            font-family: 'Calibri';
            src: url('{{ storage_path('fonts/CALIBRIL.TTF') }}') format('truetype'),
                 url('{{ public_path('fonts/CALIBRIL.TTF') }}') format('truetype'),
                 url('{{ asset('fonts/CALIBRIL.TTF') }}') format('truetype');
            font-weight: 300;
            font-style: normal;
        }

        html, body {
            font-family: 'Calibri', 'DejaVu Sans', sans-serif;
            height: 100%;
            margin: 0;
        }

        .flex-container {
            position: relative;
            min-height: 100vh; /* Full height for each page */
            padding: 20px 20px 100px 20px ;
            box-sizing: border-box;
        }

        /* With left side banner */
        .flex-container.with-sidebar {
            /* add extra left space to not overlap the banner */
            padding-left: 105px;
            padding-right: 20px; /* space for right banner */
        }

        .side-banner {
            position: absolute;
            top: 0;
            bottom: 0;
            left: 0;
            width: 100px; /* adjust as needed */
            z-index: 0;
        }

        .side-banner img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .side-banner-right {
            position: absolute;
            top: 0;
            bottom: 0;
            right: 0;
            max-width: 100%; /* adjust as needed */
            z-index: 0;
        }

        .side-banner-right img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .footer {
            position: absolute;
            bottom: 0;
            width: 95%;
            margin-bottom: 40px;
            justify-content: center;
        }
        .content {
            flex-grow: 1; /* Expands to fill the remaining space, pushing footer to the bottom */
            position: relative;
            z-index: 1; /* ensure content sits above the banner */
            /* debug background only for content */
        }
        .header {
            text-align: left;
            margin-top: 30px;
        }
        .header-logo{
            /* Flex has limited support in DomPDF; use block + text-align instead */
            display: block;
            width: 100%;
            text-align: right; /* position image on the right side of red container */
            padding-top: 12px;
        }
        .header-logo img{
            max-width: 100%;
            max-height: 100%;
            width: auto;
            height: auto;
            display: block;
        }
        .title {
            border-bottom: 1px solid black;
            font-size: 16px;
            padding-left: 5px;
            padding-bottom: 5px;
            font-weight: bold;
        }

        .header-content {
            text-align: center;
            font-size: 7px;
            margin: 0;
        }
        .order {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
        }

        /* Left client info block under header */
        .client-summary {
            margin-top: 20px;
            padding-top: 12px;
            width: 85%;
        }
        /* Short top border (10% width) for client summary - Dompdf safe */
        .client-summary-top {
            width: 10%;
            height: 0;
            border-top: 1px solid black;
          
        }
        .client-summary .client-name-large {
            font-size: 11pt;
            font-family: 'Calibri';
            font-weight: 700; /* use CALIBRIB.TTF */
        }
        .client-summary .client-address {
            font-family: 'Calibri';
            font-weight: 700;
            font-size: 6.5pt;
        }
        .client-summary .client-account {
            font-family: 'Calibri';
            font-weight: 700;
            font-size: 6.5pt;
        }

        table{
            border-collapse: collapse;
        }


        .invoice-details { display: flex; justify-content: space-between; margin-bottom: 20px; }

        .invoice-note {
            font-size: 8px;
            margin-top: 20px;
        }
        .invoice-note p {
            margin: 0;
        }
        .invoice-note .line {
            border-top: 1px solid black;
        }
        .payment-due {
            display: flex;
            align-items: center;
            margin-top: 10px;
        }
        .payment-due .days-input {
            width: 50px;
            border: none;
            border-bottom: 1px solid black;
            margin: 0 5px;
            text-align: center;
        }

        .footer-table {
            width:90%;
            font-size: 9px;
            text-align: center;
            margin-top: 20px;
            table-layout: fixed;
        }
        .footer-table td {
            vertical-align: top;
            padding: 5px;
        }
        .footer-table .line {
            margin-top: 20px;
            border-top: 1px solid #cccccc;
            width: 60%;
            margin-left: auto;
            margin-right: auto;
        }
        td {
            font-size: 6px;
        }
        .table-columns {
            font-size: 6px;
            font-weight: bold;
            
        }

        .page-break { page-break-after: always; }
        /* Allow long article/job names to wrap within cell without expanding column */
        .truncate-cell {
            display: block;
            width: 100%;
            max-width: 100%;
            overflow: hidden;
            white-space: normal; /* enable wrapping */
            word-wrap: break-word; /* legacy support */
            overflow-wrap: anywhere; /* modern wrapping for long words */
        }
    </style>
</head>
<body>
<div class="flex-container with-sidebar">
    <div class="side-banner">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/sidebanner.png'))) }}" alt="Side Banner">
    </div>
    <div class="side-banner-right">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/rightbanner.png'))) }}" alt="Right Banner">
    </div>
    <div class="content">
    <div class="header">
        <!-- header kept minimal; actual two-column header is rendered per-invoice below -->
    </div>
    @php
        // Use the first invoice for header info (all invoices in a faktura should have same client)
        $firstInvoice = is_array($invoices) ? $invoices[0] : $invoices->first();
    @endphp
    
    <table style="width: 100%; border: none; border-collapse: collapse; margin-top: 5px;">
        <tr style="border: none;">
            <td style="border: none; vertical-align: bottom; width: 60%; padding-left: 12px;">
                <div class="client-summary" style="margin-top: 90px;">
                    <div class="client-summary-top"></div>
                    <div style="border-left: 1px solid black; padding: 15px 10px;">
                    <div class="client-name-large">{{$firstInvoice['client']['name'] ?? ''}}</div>
                    <div class="client-name-large">{{$firstInvoice['client']['address'] ?? ''}}</div>
                    <div class="client-address">{{$firstInvoice['client']['address'] ?? ''}}</div>
                    <div class="client-account">ЕДБ: {{$firstInvoice['client']['client_card_statement']['edb'] ?? ''}}</div>
                    </div>
                    <div class="client-summary-top"></div>
                </div>
            </td>
            <td style="border: none; vertical-align: top; text-align: right; width: 40%; padding-right: 12px;">
                <div class="header-logo">
                    <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/company-info.png'))) }}" alt="Company Info">
                </div>
            </td>
        </tr>
    </table>
    <!-- Invoice details section -->
    <table style="width: 100%; border: none; border-collapse: collapse; margin-top: 20px;">
        <tr style="border: none;">
            <td style="border: none; vertical-align: bottom; width: 70%; padding-left: 12px;">
                @php
                    $period = isset($firstInvoice['generated_at']) ? date('m-Y', strtotime($firstInvoice['generated_at'])) : (isset($firstInvoice['end_date']) ? date('m-Y', strtotime($firstInvoice['end_date'])) : date('m-Y'));
                    $isTrade = $firstInvoice['is_trade'] ?? false;
                    // Determine display id: prefer faktura_id, then preview_faktura_id, else invoice id
                    $displayId = $firstInvoice['faktura_id'] ?? ($firstInvoice['preview_faktura_id'] ?? $firstInvoice['id']);
                    $prefix = $isTrade ? 'T-' : '';
                @endphp
                <div style="font-size: 14pt; font-family: 'Calibri'; font-weight: 700;;">
                    Фактура Бр. {{ $prefix . $displayId . '-' . $period }}
                </div>
                <div style="font-size: 10pt; font-family: 'Calibri'; font-weight: 300; margin-bottom: 5px;">
                    <span>Испратница Бр:  {{ $prefix . $displayId . '-' . $period }}</span>
                </div>
            </td>
            <td style="border: none; vertical-align: bottom; text-align: right; width: 30%; padding-right: 12px;">
                @php
                    // Determine generation date/time per rules
                    $generatedAt = $firstInvoice['generated_at'] ?? null;
                    if (!$generatedAt) {
                        // Fallbacks: trade invoice date or faktura created_at or invoice end_date
                        $generatedAt = $firstInvoice['end_date'] ?? date('Y-m-d');
                    }
                    $generatedDate = date('Y-m-d', strtotime($generatedAt));
                    // Payment deadline days from client card statement; can be string/float
                    $deadlineDaysRaw = $firstInvoice['client']['client_card_statement']['payment_deadline'] ?? null;
                    $deadlineDays = is_null($deadlineDaysRaw) || $deadlineDaysRaw === '' ? 30 : (int) $deadlineDaysRaw;
                    $currencyDate = date('Y-m-d', strtotime($generatedDate . ' +' . $deadlineDays . ' days'));
                @endphp
                <div style="font-size: 8pt; font-family: 'Calibri'; font-weight: 300;">
                    <strong>Датум:</strong> {{$generatedDate}}
                </div>
                <div style="font-size: 8pt; font-family: 'Calibri'; font-weight: 300; margin-bottom: 5px;">
                    <strong>Валута:</strong> {{$currencyDate}}
                </div>
            </td>
        </tr>
    </table>
    @php
        $verticalSums = calculateVerticalSums($invoices);
        
        // Collect all jobs and trade items from all invoices in this faktura
        $allItems = [];
        $jobCounter = 1;
        
        // Sort invoices by ID to ensure consistent ordering
        $sortedInvoices = is_array($invoices) ? $invoices : $invoices->toArray();
        usort($sortedInvoices, function($a, $b) {
            return ($a['id'] ?? 0) <=> ($b['id'] ?? 0);
        });
        
        foreach ($sortedInvoices as $invoice) {
            // Add jobs from this invoice first
            if (isset($invoice['jobs']) && is_array($invoice['jobs'])) {
                foreach ($invoice['jobs'] as $job) {
                    $allItems[] = [
                        'type' => 'job',
                        'job' => $job,
                        'invoice_title' => $invoice['invoice_title'],
                        'taxRate' => $invoice['taxRate'],
                        'copies' => $invoice['copies'],
                        'totalSalePrice' => $invoice['totalSalePrice'],
                        'row_number' => $jobCounter++
                    ];
                }
            }
            
            // Add trade items from this invoice after jobs (accept array or object with toArray)
            if (isset($invoice['trade_items'])) {
                $tradeItemsSource = $invoice['trade_items'];
                if (is_object($tradeItemsSource) && method_exists($tradeItemsSource, 'toArray')) {
                    $tradeItemsSource = $tradeItemsSource->toArray();
                }
                if (is_array($tradeItemsSource)) {
                    foreach ($tradeItemsSource as $tradeItem) {
                    $allItems[] = [
                        'type' => 'trade_item',
                        'tradeItem' => $tradeItem,
                        'row_number' => $jobCounter++
                    ];
                    }
                }
            }
        }
        
        // Calculate totals from the collected items (no duplication)
        $tradeItemsTotal = 0;
        $tradeItemsVatTotal = 0;
        foreach ($allItems as $item) {
            if ($item['type'] === 'trade_item') {
                $tradeItemsTotal += $item['tradeItem']['total_price'];
                $tradeItemsVatTotal += $item['tradeItem']['vat_amount'];
            }
        }
        
        // Update totals to include trade items
        $verticalSums['totalPriceWithTaxSum'] += $tradeItemsTotal;
        $verticalSums['totalTaxSum'] += $tradeItemsVatTotal;
        $verticalSums['totalOverallSum'] += $tradeItemsTotal + $tradeItemsVatTotal;
    @endphp
    <div class="main-content">
        <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0; table-layout: fixed;">
            <colgroup>
                <col style="width: 3%;">
                <col style="width: 46%;">
                <col style="width: 7%;">
                <col style="width: 7%;">
                <col style="width: 10%;">
                <col style="width: 13%;">
                <col style="width: 13%;">
            </colgroup>
            <thead>
                <tr style="background-color: black; color: white;">
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 3%; white-space: nowrap;">Рб.</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 46%;">Име на артикал</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 7%;">данок%</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 7%;">е.м.</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 10%;">Количина</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 13%;">ед. Цена</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; width: 13%;">Износ</td>
                </tr>
            </thead>
            <tbody>
            @foreach($allItems as $item)
                @if($item['type'] === 'job')
                    <tr style="border-bottom: 2px solid #cccccc; font-weight: 700 ; font-family: 'Calibri'">
                        <td style="font-size: 10pt; padding: 6px; text-align: center; white-space: nowrap;">{{ $item['row_number'] }}.</td>
                        @php
                            $jobName = $item['job']['name'] ?? '';
                            $orderName = $item['invoice_title'] ?? '';
                        @endphp
                        <td style="font-size: 10pt; padding: 6px; text-align: left;">
                            @if($jobName)
                                <div class="truncate-cell">{{ $jobName }}</div>
                            @endif
                            @if($orderName)
                                <div class="truncate-cell" style="color:rgb(109, 128, 129); font-size: 8pt; line-height: 0.8;">{{ $orderName }}</div>
                            @endif
                        </td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #E7F1F2;">{{ $item['taxRate'] }}%</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #E7F1F2;">{{getUnit($item['job'])}}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #E7F1F2;">{{ $item['job']['quantity'] }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: right; background-color: #E7F1F2;">{{ number_format($item['totalSalePrice'] / $item['copies'], 2) }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: right; background-color: #E7F1F2;">{{ number_format($item['totalSalePrice'], 2) }}</td>
                    </tr>
                @elseif($item['type'] === 'trade_item')
                    <tr style="border-bottom: 2px solid #cccccc; font-weight: 700 ; font-family: 'Calibri'">
                        <td style="font-size: 10pt; padding: 6px; text-align: center; white-space: nowrap;">{{ $item['row_number'] }}.</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: left;"><span class="truncate-cell">{{ data_get($item, 'tradeItem.article_name') }}</span></td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #E7F1F2;">{{ number_format((float) data_get($item, 'tradeItem.vat_rate', 0), 0) }}%</td>
                        <td style="font-size: 10pt; padding: 8px; text-align: center; background-color: #E7F1F2;">ед.</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #E7F1F2;">{{ number_format((float) data_get($item, 'tradeItem.quantity', 0), 2) }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: right; background-color: #E7F1F2;">{{ number_format((float) data_get($item, 'tradeItem.unit_price', 0), 2) }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: right; background-color: #E7F1F2;">{{ number_format((float) data_get($item, 'tradeItem.total_price', 0), 2) }}</td>
                    </tr>
                @endif
            @endforeach
            
            <!-- Summary section: 2 cells per row (label spans 6 cols, amount in last col) -->
            <tr>
                <td colspan="2" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold;"></td>
                <td colspan="4" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #E7F1F2; border-bottom: 2px solid white">Вкупно без ДДВ:</td>
                <td style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #E7F1F2; border-bottom: 2px solid white">{{ number_format($verticalSums['totalPriceWithTaxSum'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold;"></td>
                <td colspan="4" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #E7F1F2; border-bottom: 2px solid white">ДДВ(18%):</td>
                <td style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #E7F1F2; border-bottom: 2px solid white">{{ number_format($verticalSums['totalTaxSum'], 2) }}</td>
            </tr>
            <tr>
                <td colspan="2" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold;"></td>
                <td colspan="4" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #E7F1F2;">Вкупно со ДДВ:</td>
                <td style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #E7F1F2;">{{ number_format($verticalSums['totalOverallSum'], 2) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
        </div>
        </div>
        <footer class="footer" style="padding-left: 80px; padding-right: 80px;">
            <div class="invoice-note" style="padding-left: 20px;">
                <p style="font-family: 'Calibri'; font-weight: 300; font-size: 9pt; margin: 5px 0;"><span>• Рекламации се примаат во рок од 8 дена по приемот на стоката</span></p>
                <p style="font-family: 'Calibri'; font-weight: 300; font-size: 9pt; margin: 5px 0;"><span>• Ве молиме вкупниот износ на плаќање да го платите во валутниот рок</span></p>
                <p style="font-family: 'Calibri'; font-weight: 300; font-size: 9pt; margin: 5px 0;"><span>• За секое задоцнување пресметуваме за констата камата</span></p>
            </div>
            <table class="footer-table" style="margin-top: 40px;">
                <tr>
                    <td style="font-size: 9pt; text-align: center; vertical-align: top; width: 30%;">
                        <span style="font-family: 'Calibri'; font-weight: 300;">Фактурирал</span><br>
                        <br>
                        <div class="line" style="margin-top: 25px;"></div>
                    </td>
                    <td style="font-size: 9pt; font-family: 'Calibri'; font-weight: 300; text-align: center; vertical-align: top; width: 40%;">
                        <span style="font-family: 'Calibri'; font-weight: 300;">Овластено лице за потпишување фактури</span><br>
                        Зорица Гаштаровска<br>
                        <div class="line" style="margin-top: 25px;"></div>
                    </td>
                    <td style="font-size: 9pt; text-align: center; vertical-align: top; width: 30%;">
                        <span style="font-family: 'Calibri'; font-weight: 300;">Примил</span><br>
                        <br>
                        <div class="line" style="margin-top: 25px;"></div>
                    </td>
                </tr>
            </table>
        </footer>
    </div>
</body>
</html>


