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
        body {
            font-family: 'Tahoma', sans-serif;
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

        .page-break { page-break-after: always; }
    </style>
</head>
<body>

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
                                    No.     {{ $invoice->id . '-' . date('m-Y', strtotime($invoice->end_date)) }}
                            </div>
                            <div class="ispratnica">Испратница Бр:</div>
                            <div class="invoice-number">
                                Фактура Бр.  {{ $invoice->id . '-' . date('m-Y', strtotime($invoice->end_date)) }}
                            </div>
                        </td>
                        <td class="client-info right">
                            <div class="client-border">
                                <div class="client-name">{{$invoice->client->name}}</div>
                                <div class="contact-info">
                                    <div>{{$invoice->client->clientCardStatement ?-> fax}}</div>
                                    <div>{{$invoice->client->address}}</div>
                                </div>
                                <div class="edb">
                                   ЕДБ: {{$invoice -> client -> clientCardStatement ?-> edb}}
                                </div>
                            </div>
                            <div style=" text-align: right "><span style="font-weight: bold">Датум :</span> {{$invoice->end_date}}</div>
                            <div style=" text-align: right"><span style="font-weight: bold">Валута :</span> {{$invoice->end_date}}</div>
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
                    <td>Име на артикал</td>
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
            @foreach($invoice->jobs as $job)
                <tr >
                    <td>{{ $loop->iteration }}.</td>
                    <td>{{$invoice->article->code}}</td>
                    <td>{{$invoice->article->name}}</td>
                    <td>{{getUnit($job)}}</td>
                    <td>{{$job->copies}}</td>
                    <td>{{priceWithVAT($invoice->article)}} </td>
                    <td>0%</td>
                    <td>{{$invoice->article->price_1}}</td>
                    <td>{{getVat($invoice->article)}}%</td>
                    <td>{{priceWithVAT($invoice->article)}}</td>
                    <td>{{$job->copies * $invoice->article->price_1}}</td>
                    <td>{{($job->copies * $invoice->article->price_1) * getVat($invoice->article) / 100 }}</td>
                    <td>{{($job->copies * $invoice->article->price_1)+($job->copies * $invoice->article->price_1) * getVat($invoice->article) / 100 }}</td>
                </tr>
            @endforeach
            </tbody>

        </table>

    </div>
    {{--    <div class="invoice-details">--}}
    {{--        <div>--}}
    {{--            <p><strong>Фактура Бр. {{ $invoice->invoice_number }}</strong></p>--}}
    {{--            <p>Датум: {{ $invoice->invoice_date->format('d.m.Y') }}</p>--}}
    {{--            <p>Валута: {{ $invoice->due_date->format('d.m.Y') }}</p>--}}
    {{--        </div>--}}
    {{--        <div>--}}
    {{--            <p><strong>{{ $invoice->client->name }}</strong></p>--}}
    {{--            <p>{{ $invoice->client->address }}</p>--}}
    {{--            <p>{{ $invoice->client->phone }}</p>--}}
    {{--            <p>ЕДБ : {{ $invoice->client->tax_number }}</p>--}}
    {{--        </div>--}}
    {{--    </div>--}}

    {{--    <table>--}}
    {{--        <thead>--}}
    {{--        <tr>--}}
    {{--            <th>Бр.</th>--}}
    {{--            <th>Шифра</th>--}}
    {{--            <th>Име на артикал</th>--}}
    {{--            <th>Кол</th>--}}
    {{--            <th>Цена</th>--}}
    {{--            <th>ДДВ %</th>--}}
    {{--            <th>Данок</th>--}}
    {{--            <th>Вкупно</th>--}}
    {{--        </tr>--}}
    {{--        </thead>--}}
    {{--        <tbody>--}}
    {{--        @foreach($invoice->items as $item)--}}
    {{--            <tr>--}}
    {{--                <td>{{ $loop->iteration }}</td>--}}
    {{--                <td>{{ $item->code }}</td>--}}
    {{--                <td>{{ $item->name }}</td>--}}
    {{--                <td>{{ number_format($item->quantity, 2) }}</td>--}}
    {{--                <td>{{ number_format($item->price, 3) }}</td>--}}
    {{--                <td>{{ number_format($item->vat_rate, 2) }}</td>--}}
    {{--                <td>{{ number_format($item->vat_amount, 2) }}</td>--}}
    {{--                <td>{{ number_format($item->total, 2) }}</td>--}}
    {{--            </tr>--}}
    {{--        @endforeach--}}
    {{--        </tbody>--}}
    {{--    </table>--}}

    {{--    <div class="totals">--}}
    {{--        <p>Вкупно без ДДВ: {{ number_format($invoice->subtotal, 2) }} ден.</p>--}}
    {{--        <p>ДДВ: {{ number_format($invoice->vat_amount, 2) }} ден.</p>--}}
    {{--        <p><strong>Вкупно за плаќање: {{ number_format($invoice->total, 2) }} ден.</strong></p>--}}
    {{--        <p>Со зборови: {{ $invoice->total_in_words }}</p>--}}
    {{--    </div>--}}

    {{--    <p>За ненавремено плаќање пресметуваме еднократен надомест, согласно Законот за финансиска дисциплина, од 3000.00 Ден.</p>--}}
    {{--    <p>Рок на плаќање: {{ $invoice->payment_terms }} Дена од прием на фактурата</p>--}}

    {{--    <div style="margin-top: 40px;">--}}
    {{--        <p>Овластено Лице за потпишување на фактури: {{ $invoice->authorized_person }}</p>--}}
    {{--        <p>Составил: {{ $invoice->prepared_by }}</p>--}}
    {{--        <p>Печател: {{ $invoice->printed_by }}   {{ $invoice->printed_at->format('d.m.Y H:i') }}</p>--}}
    {{--    </div>--}}

    <footer style="text-align: center; margin-top: 40px;">
        <p>Copyright © 2002-{{ date('Y') }} , ONYX Software</p>
    </footer>

    @if(!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
