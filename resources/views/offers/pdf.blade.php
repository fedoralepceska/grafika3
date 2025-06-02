<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Понуда #{{ $offer->id }}</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tahoma:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: white;
            margin: -10px;
            font-size: 14px;
        }
        @font-face {
            font-family: 'Tahoma';

            font-weight: normal;
            font-style: normal;
        }
        @font-face {
            font-family: 'Tahoma';
            font-weight: bold;
            font-style: normal;
        }
        .tahoma {
            font-family: 'Tahoma', sans-serif;
            font-weight: 400; /* Regular */
        }

        /* Tahoma Bold */
        .tahoma-bold {
            font-family: 'Tahoma', sans-serif;
            font-weight: 700; /* Bold */
        }

        /* Open Sans Regular */
        .opensans {
            font-family: 'Open Sans', sans-serif;
            font-weight: 400; /* Regular */
        }

        /* Open Sans Bold */
        .opensans-bold {
            font-family: 'Open Sans', sans-serif;
            font-weight: 700; /* Bold */
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        td {
            padding: 5px;
            vertical-align: top;
        }
        .header-table td {
            padding-bottom: 15px;
        }
        .items-table {
            margin-top: 15px;
        }
        .items-table th, .items-table td {

            padding: 8px;
            text-align: right;
        }
        .items-table th {
            border-top: 2px solid #e4e4e4;
            border-bottom: 2px solid black;
            color: #333333;
        }

        .note {
            font-style: italic;
            color: #666;
            font-size: 11px;
        }
        .icon {
            width: 12px;
            margin-right: 5px;
        }
        .bolder {
            font-weight: bold;
        }
        .footer {
            position: running(footer);
        }
        @page :last {
            @bottom-center {
                content: element(footer);
            }
        }
    </style>
</head>
<body>
    <!-- Header with Client Info and Offer Details -->
    <div style="padding-bottom: 20px">
        <img src="{{ public_path('offer-header.png') }}" alt="header" style="width: 100%;">
    </div>

    <table class="header-table">
        <tr>
            <!-- Left column -->
            <td style="width: auto; padding-left: 0">
                <table style="width: auto; margin-right: 10px">
                    <tr style="border-bottom: 1px solid #073e87;height: 150px; border-left: 1px solid #073e87; margin-right: 10px;">
                        <td class="tahoma bolder" style="font-size: 9.5pt; line-height: 90%; padding-left: 0; padding-top: 15px">
                            <i class="fa-solid fa-play" style="color:#073e87"></i> ДО:
                        </td>
                        <td style="padding-top: 15px;">
                            <div class="tahoma bolder" style="font-size: 9.5pt;line-height: 90%;  margin: 0; border-spacing: 0;"> {{ $offer->client->name }}</div>
                            <div class="tahoma" style="color: #2b2b2b; font-size: 9.5pt; line-height: 90%; margin-top: 10px">Контакт: {{ $offer->contact->name }}</div>
                            <div class="tahoma" style="color: #2b2b2b; font-size: 9.5pt; line-height: 90%; margin-right: 10px">Тел. {{ $offer->contact->phone }}</div>
                        </td>
                    </tr>
                </table>
            </td>

            <!-- Right column with float to align the table to the right -->
            <td style="width: auto; padding-top: 10px; padding-right: 0; text-align: right;">
                <table style="background-color: #ececec; width: auto;padding: 5px 70px 0 3px;  border-radius: 7px; float: right;">
                    <tr style="padding: 0; margin: 0">
                        <td class="tahoma bolder" style="font-size: 9.5pt; line-height: 90%;">ПОНУДА: <span class="opensans">бр.</span></td>
                        <td >
                            <div class="opensans" style="font-size: 9.5pt; line-height: 90%;"> {{ $offer->id }}/{{ date('Y', strtotime($offer->created_at)) }}</div>
                            <div class="opensans" style="font-size: 9.5pt; line-height: 90%; margin-bottom: 5px"><span class="tahoma bolder">Дата:</span> {{ date('d/m/Y', strtotime($offer->created_at)) }}</div>
                            <div class="tahoma " style="font-size: 8.5pt; line-height: 90%; ">Понудата важи: <span class="opensans bolder">{{ $offer->validity_days }}</span> дена</div>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>


    <!-- Items Table -->
    <table class="items-table" style="letter-spacing: 0.5px">
        <tr>
            <td colspan="2"></td>
            <td colspan="4" style="background-color:#ececec; text-align: left; font-size: 8.5pt; line-height: 90%;" class="tahoma">*цените се изразени во денари</td>
        </tr>
        <tr class="tahoma" style="font-size: 8pt;line-height: 90%;">
            <th class="tahoma" style="width:5%;  text-align: left">Р.бр</th>
            <th style="width: 50%; text-align: left; font-weight: 400" class="tahoma" >Име на производ / Опис</th>
            <th class="tahoma" style="width: auto;  background-color:#ececec">Кол.</th>
            <th class="tahoma" style="width: auto;  background-color:#ececec">Ед.м.</th>
            <th class="tahoma" style="width: auto;  background-color:#ececec">Цена</th>
            <th class="tahoma" style="width: auto;  background-color:#ececec">Износ</th>
        </tr>
        @foreach($offer->catalogItems as $index => $item)
        <tr style="border-bottom: 1px solid #cccccc" >
            <td class="opensans bolder" style="color: #2b2b2b; font-size: 8pt;line-height: 90%; text-align: left">{{ $index + 1 }}.</td>
            <td style="text-align: left">
                <div class="tahoma bolder" style="font-size: 9pt; line-height: 90%;">{{ $item->name }}</div>
                <div class="tahoma" style="font-size: 7pt; line-height: 90%;">{{ $item->pivot->description ?: $item->description }}</div>
            </td>
            <td class="tahoma" style="font-size: 9pt;line-height: 90% ;vertical-align: middle;background-color:#ececec; color: #2b2b2b">{{ $item->pivot->quantity }}</td>
            <td class="tahoma" style="font-size: 9pt;line-height: 90%;vertical-align: middle; background-color:#ececec; color: #2b2b2b">Ком</td>
            <td class="tahoma" style="font-size: 9pt;line-height: 90%;vertical-align: middle; background-color:#ececec; color: #2b2b2b">{{ number_format($item->pivot->custom_price, 2) }}</td>
            <td class="tahoma" style="font-size: 9pt;line-height: 90%;vertical-align: middle; background-color:#ececec; color: #2b2b2b">{{ number_format($item->pivot->custom_price * $item->pivot->quantity, 2) }}</td>
        </tr>
        @endforeach

        <!-- Totals -->
        @php
            $subtotal = $offer->catalogItems->sum(function($item) {
                return $item->pivot->custom_price * $item->pivot->quantity;
            });
            $vat = $subtotal * 0.18;
            $total = $subtotal + $vat;
        @endphp
        <tr style=" border-bottom: 1px solid white">
            <td colspan="2"></td>
            <td colspan="4" class="tahoma" style="font-size: 8.5pt; line-height: 90%; background-color:#ececec;">Вкупно без ДДВ:
                <span class="bolder opensans" style="font-size: 9.5pt; line-height: 90%;">{{ number_format($subtotal, 2) }}</span></td>
        </tr>
        <tr style=" border-bottom: 1px solid white">
            <td colspan="2"></td>
            <td colspan="4" class="tahoma" style="font-size: 8.5pt;line-height: 90%; background-color:#ececec">ДДВ (18%):
                <span class="bolder opensans" style="font-size: 9.5pt;line-height: 90%;">{{ number_format($vat, 2) }}</span></td>
        </tr>
        <tr class="total-row">
            <td colspan="2"></td>
            <td colspan="4" class="tahoma" style="font-size: 8.5pt; line-height: 90%; background-color:#ececec">Вкупно со ДДВ:
                <span class="bolder opensans" style="font-size: 9.5pt; line-height: 90%;">{{ number_format($total, 2) }}</span></td>
        </tr>
    </table>

    <!-- Footer Notes -->
    <table >
        <tr>
            <td >
                <i class="fa-solid fa-clock-rotate-left"></i>
            </td>
            <td style="vertical-align: middle;">
                <div class="tahoma" style="font-size: 7.5pt; line-height: 90%;">Време на изработка {{$offer->production_time}} дена </div>
            </td>
        </tr>
        <tr>
            <td>
                <i class="fa-solid fa-print"></i>
            </td>
            <td style="vertical-align: middle;">
                <div class="tahoma" style="font-size: 7.5pt; line-height: 90%">Доколку сакате да одобрите пробен принт тоа може да го сторите во нашите простории, во спротивно се согласувате со нашите стандарди.</div>
            </td>
        </tr>
        <tr>
            <td>
                <i class="fa-regular fa-lightbulb"></i>
            </td>
            <td style="vertical-align: middle;">
                <div class="tahoma" style="font-size: 7.5pt; line-height: 90%">Напомена: Оваа понуда е припремена исклучиво за вашите потреби а врз основа на негусвена деловна лојалност понатамошно споделување не е дозволено.</div>
            </td>
        </tr>
    </table>
    <div class="footer" style="position: fixed; bottom: 0; left: 0; right: 0; padding-bottom: 15px; padding-right: 35px; padding-left: 35px">
        <img src="{{ public_path('offer-footer.png') }}" alt="header" style="width: 100%;">
    </div>
</body>
</html>
