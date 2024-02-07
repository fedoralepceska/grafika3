<!DOCTYPE html>
<html>
<head>
    <title>Invoice PDF</title>
    <style>
        .order{
            font-size: 28px;
            font-weight: bolder;
            padding-left: 5px;
        }
        .order1{
            font-size: 18px;
        }
        .bolder{
            font-weight: bolder;
        }
        .info {
            /* no changes needed */
        }

        .left,
        .right {
            display: inline-block;
            vertical-align: middle; /* align content vertically if needed */
            width: 50%; /* optional: set equal widths */
            padding: 5px;
        }
        .invoice-info, .job-info {
            margin-bottom: 20px;
        }
        .divider {
            height: 1px;
            background-color: gray;
        }
        img{
            display: flex;
            justify-items: center;
            justify-content: center;
            align-items: center;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@foreach ($invoice->jobs as $job)
    <div class="invoice-info">
        <div>
        <div class="order1">Order: <span class="order">num.{{ $invoice->id }}/{{ date('Y') }}</span></div>
        </div>
        <div class="divider"></div>
        <div class="info">
            <table>
                <tr>
            <td class="left">
                <div>Start Date: <span class="bolder">{{ $invoice->start_date }}</span></div>
                <div>End Date: <span class="bolder">{{ $invoice->end_date }}</span></div>
                <div>Created by: <span class="bolder">{{$invoice->created_by}}</span></div>
            </td>
            <td class="right">
                <div>Client: <span class="bolder">{{ $invoice->client->name }}</span></div>
                <div>Contact: <span class="bolder">{{$invoice->contact_id}}</span></div>
                <div>Contact number: </div>
            </td>
                </tr>
            </table>
        </div>
        <div class="divider"></div>
    </div>
    <div class="job-info">
        <div>Job Name: {{ $job->file }}</div>
        <p>Width: {{ $job->width }}</p>
        <p>Height: {{ $job->height }}</p>
        <p>Material: {{ $job->small_material }}</p>
        <p>Quantity: {{ $job->quantity }}</p>
        <p>Copies: {{ $job->copies }}</p>
        <!-- Display the image if it exists -->
        @if ($job->file)
            <img src="{{ storage_path('app/public/uploads/' . $job->file) }}" alt="Job Image" style="width: 200px; height: 200px;">
        @endif
    </div>
    @if (!$loop->last)
        <div class="page-break"></div>
    @endif
@endforeach
</body>
</html>
