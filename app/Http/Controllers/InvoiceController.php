<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Events\InvoiceCreated;
use App\Models\Invoice;
use App\Models\Job;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use ZipArchive;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Invoice::with(['jobs', 'user', 'client']);

            $this->applySearch($query, $request,$request->input('status'));

            $query->orderBy('created_at', $request->input('sortOrder', 'desc'));

            $invoices = $query->latest()->paginate(10);

            if ($request->wantsJson()) {
                return response()->json($invoices);
            }

            return Inertia::render('Invoice/Index', [
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    protected function applySearch($query, Request $request, $status)
    {
        if ($request->has('searchQuery')) {
            $searchQuery = $request->input('searchQuery');

            $searchQuery = '%' . preg_replace('/[^A-Za-z0-9\-]/', '', $searchQuery) . '%';

            $query->where(function ($query) use ($searchQuery) {
                $query->where('invoice_title', 'LIKE', $searchQuery)
                    ->orWhere('id', 'LIKE', $searchQuery);
            });
        }
        if ($status && $status !== 'All') {
            $query->where('status', $status);
        }
        if ($request->has('client') && $request->input('client') !== 'All') {
            $client = $request->input('client');

            $query->whereHas('client', function ($subquery) use ($client) {
                $subquery->where('name', $client);
            });
        }
    }
    public function getUniqueClients()
    {
        $uniqueClients = Invoice::join('clients', 'invoices.client_id', '=', 'clients.id')
            ->distinct()
            ->pluck('clients.name');

        return response()->json($uniqueClients);
    }
    public function create()
    {
        return Inertia::render('Invoice/InvoiceForm', [
            'invoiceData' => request('invoiceData') ?? null,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'contact_id' => 'required',
            'invoice_title' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'comment' => 'string|nullable',
            'jobs' => 'array',
        ]);

        $invoiceData = $request->all();

        // Create the invoice
        $invoice = new Invoice($invoiceData);
        $invoice->status = 'Not started yet';
        $invoice->created_by = Auth::id();

        $invoice->save();
        event(new InvoiceCreated($invoice));
        $jobs = $request->jobs;
        foreach ($jobs as $job) {
            $job_id = $job['id']; // Assuming 'id' holds the job ID

            $invoice->jobs()->attach($job_id);
        }

        $client = $invoice->client;
        if ($client) {
            $clientName = $client->name; // Replace 'name' with the actual field that has the client's name
            $clientFolderPath = 'public/' . $clientName;

            // Check if client directory exists before creating it
            if (!Storage::exists($clientFolderPath)) {
                Storage::makeDirectory($clientFolderPath); // This will create a directory at 'storage/app/public/clientName'
            }

            // Now create a directory for the invoice within the client's folder
            $invoiceFolderPath = $clientFolderPath . '/' . $invoice->id;
            if (!Storage::exists($invoiceFolderPath)) {
                Storage::makeDirectory($invoiceFolderPath); // This will create a directory at 'storage/app/public/clientName/invoiceId'
            }

            $invoiceId = $invoice->id;

            $jobs = Job::whereHas('invoices', function ($query) use ($invoiceId) {
                $query->where('invoices.id', $invoiceId); // Specify the table name before the column name
            })->get();

            foreach ($jobs as $job) {
                if ($job->originalFile) {
                    // Define the new path for the original file
                    $newPath = $clientName . '/' . $invoice->id . '/' . basename($job->originalFile);
                    // Move the original file to the new directory
                    Storage::move($job->originalFile, $newPath);
                    // Update the job with the new file path
                    $job->originalFile = $newPath;
                    $job->save();
                }
            }
        }

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ]);
    }
    public function show($id)
    {
        $invoice = Invoice::with(['jobs.small_material.smallFormatMaterial', 'historyLogs', 'user', 'client', 'jobs.actions', 'jobs.large_material'])->findOrFail($id);
        // Append the totalPrice attribute to each job
        $invoice->jobs->each(function ($job) {
            $job->append('totalPrice');
        });
        return Inertia::render('Invoice/InvoiceDetails', [
            'invoice' => $invoice,
        ]);
    }
    public function update(Request $request, $id)
    {
        // Retrieve the job by its ID
        $invoice = Invoice::find($id);

        if (!$invoice) {
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $validatedData = $request->validate([
            'perfect' => 'sometimes|required',
            'onHold' => 'sometimes|required',
            'ripFirst' => 'sometimes|required',
            'revisedArt' => 'sometimes|required',
            'revisedArtComplete' => 'sometimes|required',
            'rush' => 'sometimes|required',
            'additionalArt' => 'sometimes|required',
            'status' => 'sometimes|required',
            'comment' => 'sometimes'
        ]);

        // Update the job with only the validated data that's present in the request
        $invoice->update($request->only([
            'perfect',
            'onHold',
            'ripFirst',
            'revisedArt',
            'revisedArtComplete',
            'rush',
            'additionalArt',
            'status',
            'comment'
        ]));
        return response()->json(['message' => 'Invoice updated successfully']);
    }
    public function downloadInvoiceFiles(Request $request)
    {
        $clientName = $request->query('clientName');
        $invoiceId = $request->query('invoiceId');
        // Define the folder paths
        $clientFolderPath = storage_path('app/' . $clientName);
        $invoiceFolderPath = $clientFolderPath . '/' . $invoiceId;

        // Create a new ZipArchive instance
        $zip = new ZipArchive();

        $zipFilePath = tempnam(sys_get_temp_dir(), 'invoice_files') . '.zip';

        if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
            // Define the base directory inside the zip
            $baseDir = $clientName . '/' . $invoiceId . '/';

            // Add files to the zip file, retaining the directory structure
            $files = Storage::disk('local')->allFiles($clientName . '/' . $invoiceId);

            foreach ($files as $file) {
                // Get real path for file
                $realPath = storage_path('app/' . $file);
                // Add the file to the zip with the correct directory structure
                $zip->addFile($realPath, $baseDir . basename($file));
            }
            $zip->close(); // You just closed the zip file.

            $tempDir = sys_get_temp_dir();
            if (!is_writable($tempDir)) {
                return response()->json(['message' => "The temporary directory is not writable: " . $tempDir], 500);
            }

            // The $zipFilePath already points to the newly created zip file.
            if (!file_exists($zipFilePath)) {
                return response()->json(['message' => "Failed to create the zip file."], 500);
            }

            // Set the headers for file download
            $headers = [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . "invoice_files_{$clientName}_{$invoiceId}.zip" . '"',
                'Content-Length' => filesize($zipFilePath)
            ];

            // Prepare the streamed response with headers
            $response = response()->stream(function() use ($zipFilePath) {
                readfile($zipFilePath);
            }, 200, $headers);

            // Use Laravel's `Response` facade to delete the file after the response is sent to the client
            register_shutdown_function('unlink', $zipFilePath);

            return $response;
        } else {
            // Handle the case where the ZIP could not be created
            return response()->json(['message' => 'Could not create zip file'], 500);
        }
    }

    public function latest()
    {
        $invoices = Invoice::with(['jobs', 'historyLogs', 'user', 'client'])
            ->latest()
            ->take(3)
            ->get();

        // Assuming each job has an 'image' attribute
        return response()->json($invoices);
    }

    public function countToday() {
        $count = Invoice::whereDate('created_at', Carbon::today())->count();
        return response()->json(['count' => $count]);
    }

    public function countShippingToday() {
        $count = Invoice::whereDate('end_date', Carbon::today())->count();
        return response()->json(['count' => $count]);
    }

    public function countInvoicesSevenOrMoreDaysAgo() {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $count = Invoice::whereDate('end_date', '<', $sevenDaysAgo)->where('status', '!=', 'Completed')->count();
        return response()->json(['count' => $count]);
    }

    public function updateNoteProperty(Request $request)
    {
        $invoiceId = $request->input('id');
        $comment = $request->input('comment');
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            // Handle the case where the invoice is not found
            return response()->json(['message' => $invoiceId], 404);
        }

        $invoice->comment = $comment;
        $invoice->save();

        $selectedActions = $request->selectedActions;

        // Get all job IDs associated with the invoice
        $jobIds = $invoice->jobs->pluck('id')->toArray();

        // Find all jobs based on the IDs
        $jobActionIds = DB::table('job_job_action')
            ->whereIn('job_id', $jobIds)
            ->pluck('job_action_id')
            ->toArray();

        $jobActions = JobAction::whereIn('id', $jobActionIds)->get();

        // Loop through each job action
        foreach ($jobActions as $jobAction) {
            // Check if the action is in the selected actions
            if (in_array($jobAction->name, $selectedActions)) {
                // Update the hasNote property on the action
                $jobAction->hasNote = true;
                $jobAction->save();
            }
        }

        // Return a response, could be the updated invoice, a success message, etc.
        return response()->json([
            'message' => 'Invoice jobs updated successfully.'
        ]);
    }

    public function generateInvoicePdf($invoiceId)
    {
        $invoice = Invoice::with('jobs')->findOrFail($invoiceId);

        $pdf = PDF::loadView('invoices.pdf', compact('invoice'));
        return $pdf->stream('invoice-' . $invoice->invoice_number . '.pdf');
    }
}
