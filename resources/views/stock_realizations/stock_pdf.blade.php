<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Реализација на залихи</title>
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


        .stock-details { display: flex; justify-content: space-between; margin-bottom: 20px; }

        .stock-note {
            font-size: 8px;
            margin-top: 20px;
        }
        .stock-note p {
            margin: 0;
        }
        .stock-note .line {
            border-top: 1px solid black;
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
    </style>
</head>
<body>
<div class="flex-container with-sidebar">
    <div class="side-banner">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/stockbanner.png'))) }}" alt="Side Banner">
    </div>
    <div class="side-banner-right">
        <img src="data:image/png;base64,{{ base64_encode(file_get_contents(public_path('images/rightstockbanner.png'))) }}" alt="Right Banner">
    </div>
    <div class="content">
    <div class="header">
        <!-- header kept minimal; actual two-column header is rendered per-stock realization below -->
    </div>
    @php
        // Use the first stock realization for header info (all stock realizations should have same client)
        $firstStockRealization = is_array($stockRealizations) ? $stockRealizations[0] : $stockRealizations->first();
    @endphp
    
    <table style="width: 100%; border: none; border-collapse: collapse; margin-top: 5px;">
        <tr style="border: none;">
            <td style="border: none; vertical-align: bottom; width: 60%; padding-left: 12px;">
                <div class="client-summary" style="margin-top: 90px;">
                    <div class="client-summary-top"></div>
                    <div style="border-left: 1px solid black; padding: 15px 10px;">
                    <div class="client-name-large">{{$firstStockRealization['client']['name'] ?? ''}}</div>
                    <div class="client-name-large">{{$firstStockRealization['client']['address'] ?? ''}}</div>
                    <div class="client-address">{{$firstStockRealization['client']['address'] ?? ''}}</div>
                    <div class="client-account">ЕДБ: {{$firstStockRealization['client']['client_card_statement']['edb'] ?? ''}}</div>
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
    <!-- Stock realization details section -->
    <table style="width: 100%; border: none; border-collapse: collapse; margin-top: 20px;">
        <tr style="border: none;">
            <td style="border: none; vertical-align: bottom; width: 70%; padding-left: 12px;">
                @php
                    $period = isset($firstStockRealization['start_date']) ? date('m-Y', strtotime($firstStockRealization['start_date'])) : date('m-Y');
                    $displayId = $firstStockRealization['id'] ?? '';
                @endphp
                <div style="font-size: 14pt; font-family: 'Calibri'; font-weight: 700;;">
                    Реализација на залихи Бр. {{ $displayId . '-' . $period }}
                </div>
                <div style="font-size: 10pt; font-family: 'Calibri'; font-weight: 300; margin-bottom: 5px;">
                    <span>Налог Бр: {{ $firstStockRealization['invoice_id'] ?? '' }} - {{ $firstStockRealization['invoice_title'] ?? '' }}</span>
                </div>
            </td>
            <td style="border: none; vertical-align: bottom; text-align: right; width: 30%; padding-right: 12px;">
                @php
                    // Determine generation date/time
                    // If stock realization is realized, use realized_at date, otherwise use current date
                    if ($firstStockRealization['is_realized'] && $firstStockRealization['realized_at']) {
                        $generatedDate = date('Y-m-d', strtotime($firstStockRealization['realized_at']));
                    } else {
                        $generatedDate = date('Y-m-d'); // Current date
                    }
                @endphp
                <div style="font-size: 8pt; font-family: 'Calibri'; font-weight: 300;">
                    <strong>Датум:</strong> {{$generatedDate}}
                </div>
                <div style="font-size: 8pt; font-family: 'Calibri'; font-weight: 300; margin-bottom: 5px;">
                    <strong>Период:</strong> {{date('d.m.Y', strtotime($firstStockRealization['start_date'] ?? ''))}} - {{date('d.m.Y', strtotime($firstStockRealization['end_date'] ?? ''))}}
                </div>
            </td>
        </tr>
    </table>
    @php
        // Collect all materials used from all stock realizations
        $allMaterials = [];
        $materialCounter = 1;
        $totalMaterialCost = 0;
        
        // Debug: Log what we received
        \Log::info('Blade Template Debug - Stock Realizations Data', [
            'stock_realizations_type' => gettype($stockRealizations),
            'stock_realizations_count' => is_array($stockRealizations) ? count($stockRealizations) : (is_object($stockRealizations) ? $stockRealizations->count() : 'unknown'),
            'first_stock_realization' => is_array($stockRealizations) ? $stockRealizations[0] ?? 'empty' : (is_object($stockRealizations) ? $stockRealizations->first() ?? 'empty' : 'not array/object')
        ]);
        
        // Sort stock realizations by ID to ensure consistent ordering
        $sortedStockRealizations = is_array($stockRealizations) ? $stockRealizations : (is_object($stockRealizations) ? $stockRealizations->toArray() : []);
        
        // If we have a single stock realization, wrap it in an array
        if (!empty($sortedStockRealizations) && !isset($sortedStockRealizations[0])) {
            $sortedStockRealizations = [$sortedStockRealizations];
        }
        
        foreach ($sortedStockRealizations as $stockRealization) {
            \Log::info('Processing Stock Realization', [
                'id' => $stockRealization['id'] ?? 'no id',
                'has_jobs' => isset($stockRealization['jobs']),
                'jobs_count' => isset($stockRealization['jobs']) ? count($stockRealization['jobs']) : 0
            ]);
            
            // Add materials from jobs in this stock realization
            if (isset($stockRealization['jobs']) && is_array($stockRealization['jobs'])) {
                foreach ($stockRealization['jobs'] as $job) {
                    \Log::info('Processing Job', [
                        'job_id' => $job['id'] ?? 'no id',
                        'job_name' => $job['name'] ?? 'no name',
                        'has_articles' => isset($job['articles']),
                        'articles_count' => isset($job['articles']) ? count($job['articles']) : 0
                    ]);
                    
                    if (isset($job['articles']) && is_array($job['articles'])) {
                        foreach ($job['articles'] as $articleData) {
                            $article = $articleData['article'] ?? [];
                            $unitPrice = $article['purchase_price'] ?? 0;
                            $quantity = $articleData['quantity'] ?? 0;
                            $totalPrice = $unitPrice * $quantity;
                            $totalMaterialCost += $totalPrice;
                            
                            \Log::info('Processing Article', [
                                'article_name' => $article['name'] ?? 'no name',
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice
                            ]);
                            
                            // Translate unit types to Macedonian
                            $unitType = $articleData['unit_type'] ?? 'ед.';
                            $translatedUnitType = match($unitType) {
                                'square_meters', 'm2' => 'м²',
                                'meters', 'm' => 'метри',
                                'pieces', 'pcs' => 'ком.',
                                'kilograms', 'kg' => 'кг',
                                'liters', 'l' => 'литри',
                                default => $unitType
                            };
                            
                            $allMaterials[] = [
                                'row_number' => $materialCounter++,
                                'job_name' => $job['name'] ?? '',
                                'article_name' => $article['name'] ?? '',
                                'unit_type' => $translatedUnitType,
                                'quantity' => $quantity,
                                'unit_price' => $unitPrice,
                                'total_price' => $totalPrice,
                                'stock_realization_id' => $stockRealization['id']
                            ];
                        }
                    }
                }
            }
        }
        
        \Log::info('Final Materials Array', [
            'materials_count' => count($allMaterials),
            'total_cost' => $totalMaterialCost
        ]);
    @endphp
    <div class="main-content">
        <table style="width: 100%; border-collapse: collapse; margin: 0; padding: 0;">
            <colgroup>
                <col style="width: 5%;">
                <col style="width: 35%;">
                <col style="width: 20%;">
                <col style="width: 8%;">
                <col style="width: 10%;">
                <col style="width: 11%;">
                <col style="width: 11%;">
            </colgroup>
            <thead>
                <tr style="background-color: black; color: white;">
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 5%; white-space: nowrap;">Рб.</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 35%;">Име на материјал</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 20%;">За нарачка</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 8%;">е.м.</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 10%;">Користено</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; border-right: 1px solid white; width: 11%;">ед. Цена</td>
                    <td style="font-size: 8pt; text-align: center; padding: 6px 4px; width: 11%;">Вкупно</td>
                </tr>
            </thead>
            <tbody>
            @if(count($allMaterials) > 0)
                @foreach($allMaterials as $material)
                    <tr style="border-bottom: 2px solid #cccccc; font-weight: 700 ; font-family: 'Calibri'">
                        <td style="font-size: 10pt; padding: 6px; text-align: center; white-space: nowrap;">{{ $material['row_number'] }}.</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: left;">{{ $material['article_name'] }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: left; background-color: #EAF2E6;">{{ $material['job_name'] }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #EAF2E6;">{{ $material['unit_type'] }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: center; background-color: #EAF2E6;">{{ number_format($material['quantity'], 4) }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: right; background-color: #EAF2E6;">{{ number_format($material['unit_price'], 2) }}</td>
                        <td style="font-size: 10pt; padding: 6px; text-align: right; background-color: #EAF2E6;">{{ number_format($material['total_price'], 2) }}</td>
                    </tr>
                @endforeach
            @else
                <tr style="border-bottom: 2px solid #cccccc; font-weight: 700 ; font-family: 'Calibri'">
                    <td colspan="7" style="font-size: 10pt; padding: 6px; text-align: center; background-color: #EAF2E6;">
                        Нема пронајдени материјали за оваа реализација на залихи
                    </td>
                </tr>
            @endif
            
            <!-- Summary section -->
            <tr>
                <td colspan="2"></td>
                <td colspan="4" style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #EAF2E6; border-bottom: 2px solid white">Вкупно трошоци за материјали:</td>
                <td style="font-size: 10pt; padding: 6px; text-align: right; font-weight: bold; background-color: #EAF2E6; border-bottom: 2px solid white">{{ number_format($totalMaterialCost, 2) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
        </div>
        </div>
        <footer class="footer" style="padding-left: 80px; padding-right: 80px;">
            <div class="stock-note" style="padding-left: 20px;">
                <p style="font-family: 'Calibri'; font-weight: 300; font-size: 9pt; margin: 5px 0;"><span>• Оваа реализација на залихи ги прикажува сите материјали користени за нарачката</span></p>
                <p style="font-family: 'Calibri'; font-weight: 300; font-size: 9pt; margin: 5px 0;"><span>• Количините се точни според реалната употреба на материјалите</span></p>
                <p style="font-family: 'Calibri'; font-weight: 300; font-size: 9pt; margin: 5px 0;"><span>• Цените се актуелни цени на материјалите во моментот на реализација</span></p>
            </div>
            <table class="footer-table" style="margin-top: 40px;">
                <tr>
                    <td style="font-size: 9pt; text-align: center; vertical-align: top; width: 30%;">
                        <span style="font-family: 'Calibri'; font-weight: 300;">Подготвил</span><br>
                        <br>
                        <div class="line" style="margin-top: 25px;"></div>
                    </td>
                    <td style="font-size: 9pt; font-family: 'Calibri'; font-weight: 300; text-align: center; vertical-align: top; width: 40%;">
                        <span style="font-family: 'Calibri'; font-weight: 300;">Овластено лице за реализација на залихи</span><br>
                        {{ $firstStockRealization['realized_by']['name'] ?? 'Зорица Гаштаровска' }}<br>
                        <div class="line" style="margin-top: 25px;"></div>
                    </td>
                    <td style="font-size: 9pt; text-align: center; vertical-align: top; width: 30%;">
                        <span style="font-family: 'Calibri'; font-weight: 300;">Потврдил</span><br>
                        <br>
                        <div class="line" style="margin-top: 25px;"></div>
                    </td>
                </tr>
            </table>
        </footer>
    </div>
</body>
</html>
