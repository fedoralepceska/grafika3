<!DOCTYPE html>
<html lang="mk">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Фактури</title>
    <style>
        @font-face {
            font-family: 'Tahoma';
            src: url('{{ storage_path('fonts/tahoma.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Tahoma';
            src: url('{{ storage_path('fonts/tahomabd.ttf') }}') format('truetype');
            font-weight: bold;
            font-style: normal;
        }
        html, body {
            font-family: 'Tahoma', sans-serif;
            height: 100%;
            margin: 0;
        }

        .flex-container {
            position: relative;
            min-height: 100vh; /* Full height for each page */
            padding: 20px 20px 100px 20px ;
            box-sizing: border-box;
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
        }
        .header {
            text-align: left;
        }
        .title{
            border-bottom: 1px solid black;
            font-size: 17px;
            padding-left: 5px;
            padding-bottom: 5px;
            font-weight: bold;
        }

        .header-content{
            text-align: center;
            font-size: 10px;
            margin: 0;
        }
        .order{
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-top: 20px;
        }

        .o-inf{
            display: flex;
            justify-content: space-between;
            font-size: 6.5px;
            padding-top: 20px;
        }
        .ispratnica{
            font-size: 8px;
            font-weight: bold;
        }
        .invoice-number{
            font-size: 15px;
            font-weight: bold;
            padding-top: 50px;
        }
        .client-border{
            border: 1px solid black;
            padding: 5px;
        }
        .client-name{
            font-size: 12px;
            padding-left: 5px;
            padding-bottom: 25px;
            font-weight: bold;
        }
        .client-info{
            font-size: 9px;
            padding-left: 5px;
            width: 300vh;
            display: flex;
        }
        .edb{
            display: flex;
            justify-content: right;
            align-items: center;
            text-align: right;
        }
        .left{
            width: 300px;
        }
        .right{
            width: 355px;
            padding-left: 40px;
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
            width: 100%;
            font-size: 9px;
            text-align: center;
            margin-top: 20px;
            table-layout: fixed;
        }
        .footer-table td {
            vertical-align: top;
            padding: 5px;
            width: 33.33%;
        }
        .footer-table .line {
            margin-top: 20px;
            border-top: 1px solid black;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
<div class="flex-container">
    <div class="content">
    <div class="header">
        <div class="title">Графика Плус доо</div>
        <div class="header-content">
            <div>Ул. Качанички Пат бр.54 • 1000 Скопје • тел: +389 2 26 11 024 • факс: +389 2 26 36 400 • e-mail: info@grafikaplus.com.mk</div>
            <div>Bank: • Swift: • IBAN EUR: • IBAN USD:</div>
            <div>сметка: 380176885603156 • ЕДБ: МК 4030008024260 • Про Кредит Банка</div>
        </div>
    </div>
    @foreach($invoices as $invoice)
        @if($loop->last)
            <div class="order">
                <table style="border: none">
                    <tr style="border: none">
                        <td class="order-info left border-0">
            {{--                <div class="barcode">--}}
            {{--                    <img src="data:image/png;base64,{{ $invoice->barcodeImage }}" alt="Invoice Barcode">--}}
            {{--                </div>--}}
                            <div class="o-inf">
                                    No.     {{ $invoice['id'] . '-' . date('m-Y', strtotime($invoice['end_date'])) }}
                            </div>
                            <div class="ispratnica">Испратница Бр:</div>
                            <div class="invoice-number">
                                Фактура Бр.  {{ $invoice['id'] . '-' . date('m-Y', strtotime($invoice['end_date'])) }}
                            </div>
                        </td>
                        <td class="client-info right">
                            <div class="client-border">
                                <div class="client-name">{{$invoice['client']['name']}}</div>
                                <div class="contact-info">
                                    <div>{{$invoice['client']['client_card_statement']['fax'] || ''}}</div>
                                    <div>{{$invoice['client']['address']}}</div>
                                </div>
                                <div class="edb">
                                   ЕДБ: {{$invoice['client']['client_card_statement']['edb'] || ''}}
                                </div>
                            </div>
                            <div style=" text-align: right "><span style="font-weight: bold">Датум :</span> {{$invoice['end_date']}}</div>
                            <div style=" text-align: right"><span style="font-weight: bold">Валута :</span> {{$invoice['end_date']}}</div>
                        </td>
                    </tr>
                </table>
            </div>
       @endif
    <div class="main-content" style="background-color: black">
        <table style="font-size: 9px; width: 100%; color: white; margin: 0; padding: 0; border-bottom: 3px solid black" >

            <thead style="background-color: black">
                <tr>
                    <td>Бр.</td>
                    <td>Шифра</td>
                    <td>Име</td>
                    <td>Е.М.</td>
                    <td>Кол</td>
                    <td>Цена без П</td>
                    <td>П %</td>
                    <td>Цена</td>
                    <td>Д %</td>
                    <td>Цена ДДВ</td>
                    <td>Износ</td>
                    <td>Данок</td>
                    <td>Вкупно</td>
                </tr>
            </thead>
            <tbody style="background-color: white !important; color: black">
            @foreach($invoice['jobs'] as $job)
                <tr >
                    <td>{{ $loop->iteration }}.</td>
                    <td>000</td>
                    <td>{{$invoice['invoice_title']}}</td>
                    <td>{{getUnit($job)}}</td>
                    <td>{{ $invoice['copies'] }}</td>
                    <td>{{ $invoice['priceWithTax'] }} </td>
                    <td>0%</td>
                    <td>{{ $invoice['totalSalePrice'] }}</td>
                    <td>{{ $invoice['taxRate'] }}%</td>
                    <td>{{ $invoice['priceWithTax'] }}</td>
                    <td>{{ $invoice['totalSalePrice'] }}</td>
                    <td>{{ $invoice['taxAmount'] }}</td>
                    <td>{{ $invoice['priceWithTax'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <!-- First Table Wrapper in the First Column -->
                <td style="vertical-align: top; padding-top: 2px">
                    <table style="border-collapse: collapse; width: fit-content; text-align: center; font-size: 9px;">
                        <thead>
                        <tr>
                            <th style="border: 1px solid black; padding: 3px;">Данок</th>
                            <th style="border: 1px solid black; padding: 3px;">ДДВ %</th>
                            <th style="border: 1px solid black; padding: 3px;">ДДВ Основа</th>
                            <th style="border: 1px solid black; padding: 3px;">Данок</th>
                            <th style="border: 1px solid black; padding: 3px;">Вкупно</th>
                        </tr>
                        </thead>
                        <tbody>
                        @php

                            $ddvData = [
                                ['danok' => 'ДДВ А', 'ddv_percent' => '18.00'],
                                ['danok' => 'ДДВ Б', 'ddv_percent' => '5.00'],
                                ['danok' => 'ДДВ В', 'ddv_percent' => '10.00'],
                                ['danok' => 'ДДВ Г', 'ddv_percent' => '0.00'],
                            ];
                        @endphp
                        @foreach ($ddvData as $data)
                            @php
                                $totals = calculateTotalsByTaxRate($invoices, (float) $data['ddv_percent']);
                                $verticalSums = calculateVerticalSums($invoices);
                            @endphp
                            <tr>
                                <td style="border: 1px solid black; padding: 1px;">{{ $data['danok'] }}</td>
                                <td style="border: 1px solid black; padding: 1px;">{{ $data['ddv_percent'] }}</td>
                                <td style="border: 1px solid black; padding: 1px;" class="totaldyanmicprice">{{ number_format($totals['totalPriceWithTax'], 2) }}</td>
                                <td style="border: 1px solid black; padding: 1px;" class="totaldyanmicdanok">{{ number_format($totals['totalTaxAmount'], 2) }}</td>
                                <td style="border: 1px solid black; padding: 1px;" class="totaldynamicoverall">{{ number_format($totals['totalOverall'], 2) }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="2" style="border:none"></td>
                            <td style="border: 1px solid black; padding: 3px;"><strong>{{ number_format($verticalSums['totalPriceWithTaxSum'], 2) }}</strong></td>
                            <td style="border: 1px solid black; padding: 3px;"><strong>{{ number_format($verticalSums['totalTaxSum'], 2) }}</strong></td>
                            <td style="border: 1px solid black; padding: 3px;"><strong>{{ number_format($verticalSums['totalOverallSum'], 2) }}</strong></td>
                        </tr>
                        </tfoot>
                    </table>
                </td>

                <!-- Second Table Wrapper in the Second Column -->
                <td style="vertical-align: top; padding-top: 2px;">
                    <table style="font-size: 9px; text-align: center; border: 1px solid black; width: 100%;">
                        <tbody>
                        <tr>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;"><strong>Основа :</strong></td>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;">{{ number_format($verticalSums['totalPriceWithTaxSum'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;"><strong>Попуст :</strong></td>
                            <td style="border: 1px solid black; padding: 1px;">
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="text-align: left; white-space: nowrap;">
                                            00 <span style="border: 1px solid black; padding: 2px;">%</span>
                                        </td>
                                        <td style="text-align: right; white-space: nowrap;">
                                            00
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;"><strong>Износ :</strong></td>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;">{{ number_format($verticalSums['totalPriceWithTaxSum'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;"><strong>Данок :</strong></td>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;">{{ number_format($verticalSums['totalTaxSum'], 2) }}</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;"><strong>Вкупно :</strong></td>
                            <td style="border: 1px solid black; padding: 1px; text-align: right;"><strong>{{ number_format($verticalSums['totalOverallSum'], 2) }}</strong></td>
                        </tr>
                        </tbody>
                    </table>
                </td>
            </tr>
        </table>
        </div>
        <footer class="footer">
            <div class="invoice-note">
                <div class="line"></div>
                <p>За ненавремено плаќање пресметуваме еднократен надомест, согласно Законот за финансиска дисциплина, од 3000.00 Ден.</p>
                <div class="payment-due" style="padding-bottom: 30px">
                    <span>Рок на плаќање:</span>
                    <input type="text" class="days-input" readonly value=""> Дена од прием на фактурата
                </div>
                <div class="line"></div>
            </div>
            <table class="footer-table">
                <tr>
                    <td>
                        <strong>Примил</strong><br>
                        {{$invoice['client']['name']}}
                        <div class="line"></div>
                    </td>
                    <td>
                        <div><strong>Составил :</strong> Петранка Димоска  &nbsp;  {{$invoice['end_date']}}</div>
                        <div><strong>Печатил :</strong> Петранка Димоска &nbsp; {{$invoice['end_date']}}</div>
                        <div style="font-size: 8px">Copyright © 2024, ERP </div>
                    </td>
                    <td>
                        <strong>Овластено Лице за потпишување на фактури</strong><br>
                        Петранка Димоска
                        <div class="line"></div>
                    </td>
                </tr>
            </table>
        </footer>
    </div>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
