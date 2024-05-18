<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order PDF</title>
    <style>
        @font-face {
            font-family: 'DejaVu Sans';
            src: url('{{ storage_path('fonts/DejaVuSans.ttf') }}') format('truetype');
            font-weight: normal;
            font-style: normal;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
        }
        .order {
            font-size: 28px;
            font-weight: bolder;
            padding-left: 5px;
        }

        .order1 {
            font-size: 18px;
        }

        .info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .left, .right {
            width: 45%;
            padding: 10px;
            box-sizing: border-box; /* Ensure box sizing includes borders */
        }

        .left {
            border-right: 1px solid gray; /* Add a vertical line to the right of .left */
        }

        .bolder {
            font-weight: bolder;
        }

        .divider {
            height: 1px;
            background-color: gray;
        }
        .job-table tr td:nth-child(2){
         border-bottom: 1px solid gainsboro;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@foreach ($invoice->jobs as $job)
    <div class="invoice-info">
        <table class="flex-container">
            <tr>
                <td>
                    <div class="order1">Налог: <span class="order">бр.{{ $invoice->id }}/{{ date('Y') }}</span></div>
                </td>
                <td>
                    <img src="{{ storage_path('app/public/images/logo_blue.png') }}" alt="Job Image" style="padding: 0 340px; height: 75px;">
                </td>
            </tr>
        </table>
        <div class="divider"></div>
        <div class="info">
            <table style="width: 100%;">
                <tr>
                    <td class="left">
                        <div>Почетен датум: <span class="bolder">{{ $invoice->start_date }}</span></div>
                        <div>Краен датум: <span class="bolder">{{ $invoice->end_date }}</span></div>
                        <div>Креиран од: <span class="bolder">{{$invoice->created_by}}</span></div>
                    </td>
                    <td class="right">
                        <div>Клиент: <span class="bolder">{{ $invoice->client->name }}</span></div>
                        <div>Контакт: <span class="bolder">{{$invoice->contact_id}}</span></div>
                        <div>Контакт број: </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="divider"></div>
    </div>
    <div  class="bolder" style="margin-left: 25px; margin-top: 8px">
        Работен налог бр.01
    </div>
    <div class="job-info" style="margin-left: 15px; margin-top: 20px">
        <table class="job-table" style="width: 70%;">
            <tr >
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Име на ставка </td>
                <td> {{ $job->file }}</td>
            </tr>
            <tr >
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Машина</td>
                <td> {{ $job->machinePrint }}</td>
            </tr>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Ширина:</td>
                <td>{{ $job->width }}</td>
            </tr>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Висина:</td>
                <td>{{ $job->height }}</td>
            </tr>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Вкупно во мм:</td>
                <td>{{ $job->height * $job->width}}</td>
            </tr>
            @if($job->small_material)
                <tr>
                    <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Материјал:</td>
                    <td>{{ $job->small_material->name }}</td>
                </tr>
            @endif
            @if($job->large_material)
                <tr>
                    <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Материјал:</td>
                    <td>{{ $job->large_material->name }}</td>
                </tr>
            @endif
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Количина:</td>
                <td>{{ $job->quantity }}</td>
            </tr>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Копии:</td>
                <td>{{ $job->copies }}</td>
            </tr>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 5px;">Коментар:</td>
                <td>{{ $invoice->comment }}</td>
            </tr>
        </table>
    </div>
    <div  class="bolder" style="margin-left: 15px; margin-top: 8px">
         Доработка бр.02
        <div class="divider"></div>
    </div>
        <div style="margin-left: 15px; margin-top: 5px">
        <table>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 160px;">Доработка</td>
                <td style="border-bottom: 1px solid gainsboro">{{$job->actions}}</td>
            </tr>
            <tr>
                <td style="background-color: gainsboro; padding: 5px 5px 5px 160px; text-align: right">Достава</td>
                <td style="border-bottom: 1px solid gainsboro">{{$invoice->shippinginfo}}</td>
            </tr>

        </table>
        </div>

    <div  class="bolder" style="margin-top: 10px">
        Слики:
    </div>
    @if ($job->file)
        <div style="text-align: center;">
            <img src="{{ storage_path('app/public/uploads/' . $job->file) }}" alt="Job Image" style="width: 310px; height: 310px;">
        </div>
    @endif

    <table style="width: 100%; text-align: center;">
        <tr>
            <td style="padding: 15px; width: 30%">Печатење и контрола</td>
            <td style="padding: 15px; width: 30%">Доработка и контрола</td>
            <td style="padding: 15px; width: 30%">Монтажа и контрола</td>
        </tr>
        <tr>
            <td style="padding: 15px; border-bottom: 1px solid dimgray;"></td>
            <td style="padding: 15px; border-bottom: 1px solid dimgray;"></td>
            <td style="padding: 15px; border-bottom: 1px solid dimgray;"></td>
        </tr>
    </table>
    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
