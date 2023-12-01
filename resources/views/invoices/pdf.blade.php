<!DOCTYPE html>
<html>
<head>
    <title>Invoice PDF</title>
    <style>
        .invoice-info, .job-info {
            margin-bottom: 20px;
        }
        .divider {
            height: 2px;
            background-color: gray;
            margin: 20px 0;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
@foreach ($invoice->jobs as $job)
    <div class="invoice-info">
        <h2>Invoice Number: {{ $invoice->id }}</h2>
        <p>Start Date: {{ $invoice->start_date }}</p>
        <p>End Date: {{ $invoice->end_date }}</p>
        <p>Client: {{ $invoice->client->name }}</p>
        <p>Contacts:</p>
        @foreach ($invoice->client->contacts as $contact)
            <span>{{ $contact->name }}</span>
        @endforeach
        <p>Comment: {{ $invoice->comment }}</p>
        <p>Created By: {{ $invoice->user->name }}</p>
    </div>
    <div class="divider"></div>
    <div class="job-info">
        <h3>Job Name: {{ $job->file }}</h3>
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
