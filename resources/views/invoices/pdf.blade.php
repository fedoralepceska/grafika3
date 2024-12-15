<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order PDF</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Tahoma:wght@400;700&display=swap" rel="stylesheet">

    <style>
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
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: white;
            margin: -10px;
            font-size: 14px;
        }
        .order {
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
            margin: 0;
            line-height: 8pt;
            box-sizing: border-box; /* Ensure box sizing includes borders */
            font-size: 11pt;
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
            font-size: 9.5pt;
        }
        .job-table tr td:nth-child(1){
            width: 160px;
            font-size: 9.5pt;
        }
        @page {
            size: A4; /* Set A4 size */
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
        <table  style="table-layout: fixed;  width: 100%;">
            <tr style=" line-height: 10px; margin-top: -25px" >
                <td style="width: 360px; text-align: left; margin-top: 5px">
                    <div>
                        <span class="tahoma" style="margin-left: -3px; color: #333333; font-size: 11.5pt;">Работен налог</span><span class="opensans" style="color: #333333;">:
                        </span> <span class="order tahoma bolder" style="font-size: 19pt">бр<span class="opensans" style="font-size: 27pt">.</span><span class="opensans bolder">{{ $invoice->id }}/{{ date('Y', strtotime($invoice->start_date)) }}</span></span>
                    </div>
                </td>
                <td style="text-align: right" >
                    <img src="{{ public_path('logo_blue.png') }}" alt="LOGO" style="height: 30px;">
                </td>

        </table>
        <div class="divider"></div>
        <div class="info">
            <table style="width: 100%; color: #333333; gap: 0">
                <tr>
                    <td class="left" style="padding-top:5px; padding-bottom: 10px">
                        <div style="font-size:9.5pt" class="tahoma">Датум на отварање: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->start_date)) }}</span></div>
                        <div style="font-size:9.5pt" class="tahoma">Краен рок: <span class="opensans bolder">{{ date('m/d/Y', strtotime($invoice->end_date)) }}</span></div>
                        <div style="font-size:9.5pt" class="tahoma">Одговорно лице: <span class="opensans bolder">{{$invoice->user->name}}</span></div>
                    </td>
                    <td class="right"  style="padding-top:5px; padding-bottom: 10px">
                        <div class="bolder tahoma" style="text-transform: uppercase; font-size: 12px;">Нарачател: <span class="bolder">{{ $invoice->client->name }}</span></div>
                        <div style="font-size: 13px;">Контакт: <span>{{$invoice->contact->name }}</span></div>
                        <div style="font-size: 13px">Контакт тел: <span>{{$invoice->contact->phone }}</span> </div>
                    </td>
                </tr>
            </table>
        </div>
        <div class="divider"></div>
    </div>
    <div  class="bolder opensans" style="margin-left: 25px; margin-top: 8px; font-size: 10pt; color: #333333">
        РАБОТНА СТАВКА БР. <span class="opensans bolder" style="color: #333333; font-size: 10pt" >01</span>
    </div>
    <div class="job-info" style="margin-left: 15px; margin-top: 20px">
        <table class="job-table" style="width: 100%; border-collapse: collapse; font-size: 10px" >
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc; margin: 0; font-size: 9.5pt">Производ</td>
                <td colspan="3"> {{ $job->name }}</td>
            </tr>
            @if($job->small_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Оддел:</td>
                    <td colspan="3">Small Format</td>
                </tr>
            @endif
            @if($job->large_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Оддел:</td>
                    <td colspan="3">Large Format</td>
                </tr>
            @endif
            <tr >
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Машина</td>
                <td colspan="3"> {{ $job->machinePrint }}</td>
            </tr>
            @if($job->small_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Тип на материјал:</td>
                    <td colspan="3">{{ $job->small_material->name }}</td>
                </tr>
            @endif
            @if($job->large_material)
                <tr>
                    <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Тип на материјал:</td>
                    <td colspan="3">{{ $job->large_material->name }}</td>
                </tr>
            @endif
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;;">Димензија во mm:</td>
                <td colspan="3">{{ number_format($job->width) }}x{{ number_format( $job->height) }}</td>
            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Количина:</td>
                <td>{{ $job->quantity }}</td>

                <td class="tahoma" style="font-size: 9.5pt; background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc !important; width: 160px;">Копии:</td>
                <td class="copies-cell" style="padding-left: 3px; font-size: 9.5pt">{{ $job->copies }}</td>
            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Површина: </td>
                <td colspan="3">{{ number_format(($job->height/1000) * ($job->width/1000),5) * $job->copies}} m²</td>
            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Работни фајлови</td>
                <td colspan="3" >{{ $job->file }}</td>

            </tr>
            <tr>
                <td class="tahoma" style="background-color: #F0EFEF; padding-left: 5px; border-bottom: 1px solid #cccccc;">Коментар</td>
                <td colspan="3" >{{ $invoice->comment }}</td>

            </tr>
        </table>
    </div>
    <div  class="bolder opensans" style="margin-left: 15px; margin-top: 8px; font-size: 10pt; color: black">
         ДОРАБОТКА БР. <span class="opensans bolder" style="color: black; font-size: 10pt" >01</span>
        <div class="divider"></div>
    </div>
        <div style="margin-left: 15px; margin-top: 10px">
        <table style="border-collapse: collapse" >
            <tr>
                <td class="tahoma bolder" style="font-size: 7pt; background-color: #F0EFEF;letter-spacing: 1.5px; padding: 3px 3px 3px 5px;; color: black; text-align: right; !important width: 90px">ДОРАБОТКА</td>
                <td style="width: 160px; border-bottom: 1px solid #f7f4f4"> </td>

                <td class="tahoma bolder" style="font-size: 7pt; background-color: #F0EFEF;letter-spacing: 1.5px; padding: 3px 3px 3px 5px; !important; width: 110px; color: black; text-align: right; align-content: center; display: flex">ДОСТАВА</td>
                <td style="width: 160px; border-bottom: 1px solid #f7f4f4"> </td>
            </tr>
            <tr>
                <td style="height: 10px; !important width: 90px" ></td>
            </tr>
            <tr>
                <td class="tahoma bolder" style="background-color: #F0EFEF; font-size: 7pt; padding: 3px 5px 3px 110px; text-align: right; color: black;!important width: 90px">Достава</td>
                <td colspan="3">{{$job->shippingInfo}}</td>
            </tr>
        </table>
        </div>

    <div  class="bolder tahoma" style="margin-top: 13px; font-size: 9.5pt; color: #3f3f3f">
        ART BOARD<span class="opensans bolder" style="color: #333333; font-size: 10pt" >:</span>
    </div>
    @if ($job->file)
        <div style="text-align: center; height: 440px;">
            <img src="{{ storage_path('app/public/uploads/' . $job->file) }}" alt="Job Image" style="max-height: 375px; min-height: 375px; vertical-align: middle;">
        </div>

    @endif

    <table style="width: 100%; text-align: center; letter-spacing: 0.5px">
        <tr style="font-size: 11.5px; text-transform: uppercase">
            <td class="tahoma" style="padding: 15px;">Печатење и контрола</td>
            <td class="tahoma" style="padding: 15px;">Доработка и контрола</td>
            <td class="tahoma" style="padding: 15px;">Монтажа и контрола</td>
        </tr>
        <tr style="">
            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
            <td style="padding: 15px 15px 0 15px; border-bottom: 1px solid #d7d7d7;"></td>
        </tr>
    </table>
    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
