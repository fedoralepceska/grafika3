<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order PDF</title>
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
            background-color: white;
        }
        .order {
            font-size: 30px;
            font-weight: bolder;
            padding-left: 5px;
        }

        .order1 {
            font-size: 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .left, .right {
            width: 45%;
            padding:0;
            box-sizing: border-box; /* Ensure box sizing includes borders */
            font-size: 16px;
        }
        .right{
            padding-left: 5px;
        }

        .left {
            border-right: 3px solid #cccccc; /* Add a vertical line to the right of .left */
        }

        .bolder {
            font-weight: bold;
        }

        .divider {
            height: 1px;
            background-color: #cccccc;
        }
        .job-table tr td:nth-child(2){
            border-bottom: 1px solid #f7f4f4;
            padding-left: 3px;
        }
        .job-table tr td:nth-child(1){
            width: 180px;
        }
        .page-break {
            page-break-after: always;
        }
        .copies-cell {
            border-bottom: 1px solid #f7f4f4;
        }
    </style>
</head>
<body>
@foreach ($invoice->jobs as $job)
    <div class="invoice-info">
        <table class="flex-container">
            <tr>
                <td style="width: 360px">
                    <div>
                        <span style="color: #333333; font-size: 18px">Работен налог:</span> <span class="order">бр.{{ $invoice->id }}/{{ date('Y', strtotime($invoice->start_date)) }}</span>
                    </div>
                </td>
                <td>
                    <img src="{{ public_path('logo_blue.png') }}" alt="LOGO" style="padding: 0 153px; height: 75px;">
                </td>

        </table>
        <div class="divider"></div>
        <div class="info">
            <table style="width: 100%; color: #333333">
                <tr>
                    <td class="left">
                        <div >Датум на отварање: <span class="bolder">{{ date('m/d/Y', strtotime($invoice->start_date)) }}</span></div>
                        <div >Краен рок: <span class="bolder">{{ date('m/d/Y', strtotime($invoice->end_date)) }}</span></div>
                        <div >Одговорно лице: <span class="bolder">{{$invoice->user->name}}</span></div>
                    </td>
                    <td class="right">
                        <div >Клиент: <span class="bolder">{{ $invoice->client->name }}</span></div>
                        <div >Контакт: <span class="bolder">{{$invoice->contact->name }}</span></div>
                        <div >Контакт број: <span class="bolder">{{$invoice->contact->phone }}</span> </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="divider"></div>
    </div>
    <div  class="bolder" style="margin-left: 25px; margin-top: 8px; font-size: 14px; color: #333333">
        РАБОТЕН НАЛОГ БР. 01
    </div>
    <div class="job-info" style="margin-left: 15px; margin-top: 20px">
        <table class="job-table" style="width: 100%; border-collapse: collapse; font-size: 15px" >
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc; margin: 0">Производ</td>
                <td colspan="3"> {{ $invoice->invoice_title }}</td>
            </tr>
            <tr >
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Машина</td>
                <td colspan="3"> {{ $job->machinePrint }}</td>
            </tr>
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Ширина:</td>
                <td colspan="3">{{ $job->width }} mm</td>
            </tr>
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Висина:</td>
                <td colspan="3">{{ $job->height }} mm</td>
            </tr>
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Површина: </td>
                <td colspan="3">{{ number_format(($job->height/1000) * ($job->width/1000),5)}} m²</td>
            </tr>
            @if($job->small_material)
                <tr>
                    <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Материјал:</td>
                    <td colspan="3">{{ $job->small_material->name }}</td>
                </tr>
            @endif
            @if($job->large_material)
                <tr>
                    <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Материјал:</td>
                    <td colspan="3">{{ $job->large_material->name }}</td>
                </tr>
            @endif
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Количина:</td>
                <td>{{ $job->quantity }}</td>

                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc !important; width: 160px">Копии:</td>
                <td class="copies-cell" style=" width: 80px">{{ $job->copies }}</td>
            </tr>
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Работни фајлови</td>
                <td colspan="3" >{{ $job->file }}</td>

            </tr>
            <tr>
                <td style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Коментар</td>
                <td colspan="3" >{{ $invoice->comment }}</td>

            </tr>
        </table>
    </div>
    <div  class="bolder" style="margin-left: 15px; margin-top: 8px; font-size: 14px; color: #333333">
         ДОРАБОТКА БР. 01
        <div class="divider"></div>
    </div>
        <div style="margin-left: 15px; margin-top: 5px">
        <table style="border-collapse: collapse; font-size: 15px">
            <tr style="padding-bottom: 20px">
                <td style="background-color: #F0EFEF; padding: 0 5px 0 160px;  border-bottom: 1px solid #cccccc;">Доработка</td>
                <td colspan="3" style="border-bottom: 1px solid #f7f4f4"></td>
            </tr>
            <tr>
                <td style="background-color: #F0EFEF; padding: 0 5px 0 160px; text-align: right; border-bottom: 1px solid #cccccc;">Достава</td>
                <td colspan="3" style="border-bottom: 1px solid #f7f4f4">{{$job->shippingInfo}}</td>
            </tr>

        </table>
        </div>

    <div  class="bolder" style="margin-top: 10px; color: #3f3f3f">
        ART BOARD:
    </div>
    @if ($job->file)
        <div style="text-align: center;" >
            <img src="{{ storage_path('app/public/uploads/' . $job->file) }}" alt="Job Image" style="max-height: 375px; min-height: 375px">
        </div>
    @endif

    <table style="width: 100%; text-align: center;">
        <tr>
            <td style="padding: 15px; width: 30%">Печатење и контрола</td>
            <td style="padding: 15px; width: 30%">Доработка и контрола</td>
            <td style="padding: 15px; width: 30%">Монтажа и контрола</td>
        </tr>
        <tr>
            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #cccccc;"></td>
            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #cccccc;"></td>
            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #cccccc;"></td>
        </tr>
    </table>
    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
