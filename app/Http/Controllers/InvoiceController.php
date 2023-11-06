<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Events\InvoiceCreated;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\In;
use Inertia\Inertia;
use ZipArchive;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with(['jobs', 'user', 'client'])->get(); // Eager load jobs related to invoices

        if (request()->wantsJson()) {
            return response()->json($invoices);
        }

        return Inertia::render('Invoice/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        return Inertia::render('Invoice/InvoiceForm');
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
        $invoice = Invoice::with(['jobs', 'historyLogs', 'user', 'client'])->findOrFail($id);

        return Inertia::render('Invoice/InvoiceDetails', [
            'invoice' => $invoice,
        ]);
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
}
