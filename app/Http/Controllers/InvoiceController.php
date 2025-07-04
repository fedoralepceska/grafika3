<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Events\InvoiceCreated;
use App\Models\Article;
use App\Models\Faktura;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use Barryvdh\DomPDF\Facade\Pdf;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Milon\Barcode\DNS1D;
use ZipArchive;
use function PHPUnit\Framework\isEmpty;
use App\Services\TemplateStorageService;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Invoice::with(['jobs', 'user', 'client']);

            // Apply search filters
            $this->applySearch($query, $request, $request->input('status'));

            // Add start_date and end_date search if available
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');

                $query->whereDate('end_date', '>=', $startDate) // Filter by end_date >= start_date
                ->whereDate('end_date', '<=', $endDate);   // Filter by end_date <= end_date
            } else if ($request->has('start_date')) {
                $startDate = $request->input('start_date');
                $query->whereDate('end_date', '>=', $startDate); // Filter by end_date >= start_date
            } else if ($request->has('end_date')) {
                $endDate = $request->input('end_date');
                $query->whereDate('end_date', '<=', $endDate); // Filter by end_date <= end_date
            }

            // Maintain search by status (if applicable)
            $status = $request->input('status');
            if ($status && $status !== 'All') {
                $query->where('status', $status);
            }

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
    public function create()
    {
        return Inertia::render('Invoice/InvoiceForm', [
            'invoiceData' => request('invoiceData') ?? null,
        ]);
    }
    public function store(Request $request)
    {
        $request->validate([
            'invoice_title' => 'required',
            'client_id' => 'required',
            'contact_id' => 'required',
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

            $jobIdsForInvoice = DB::table('invoice_job')
                ->where('invoice_id', $invoiceId)
                ->pluck('job_id');

            $jobs = Job::whereIn('id', $jobIdsForInvoice)->with('catalogItem')->get();

            $errorMessages = [];

            foreach ($jobs as $job) {
                // Check if job has a catalog item before proceeding
                if (!$job->catalogItem) {
                    \Log::warning('Job without catalog item found during invoice creation', [
                        'job_id' => $job->id,
                        'invoice_id' => $invoiceId
                    ]);
                    continue; // Skip this job if no catalog item
                }

                $catalogItemArticle = DB::table('catalog_item_articles')
                    ->select()
                    ->where('catalog_item_id', $job->catalogItem->id)
                    ->get();
                
                // Skip if no catalog item articles found
                if ($catalogItemArticle->isEmpty()) {
                    \Log::warning('No catalog item articles found for job', [
                        'job_id' => $job->id,
                        'catalog_item_id' => $job->catalogItem->id
                    ]);
                    continue;
                }

                // Update Large Material
                if ($job->large_material_id !== null) {
                    $large_material = LargeFormatMaterial::with('article')->find($job->large_material_id);
                    if ($large_material) {
                    $neededQuantity = $catalogItemArticle[0]->quantity * $job->copies;
                    if ($neededQuantity > (int) $large_material->quantity) {
                        $errorMessages[] = "For the catalog item {$job->catalogItem->name} with material {$large_material->name}, you need {$neededQuantity} (quantity), but you only have {$large_material->quantity} in storage.";
                    }
                    if ($large_material?->article?->in_square_meters === 1) {
                        $large_material->quantity -= ($job->copies * ($job->width * $job->height / 1000000));
                    } else {
                        $large_material->quantity -= $job->copies;
                    }
                    $large_material->save();
                    } else {
                        \Log::warning('Large material not found for job', [
                            'job_id' => $job->id,
                            'large_material_id' => $job->large_material_id
                        ]);
                }
                }
                
                // Update Small Material
                if ($job->small_material_id !== null) {
                    $small_material = SmallMaterial::with('article')->find($job->small_material_id);
                    if ($small_material) {
                    $neededQuantity = (int) $catalogItemArticle[0]->quantity * $job->copies;
                    if ($neededQuantity > (int) $small_material->quantity) {
                        $errorMessages[] = "For the catalog item '{$job->catalogItem->name}', which uses the material '{$small_material->name}', you need {$neededQuantity} units, but only {$small_material->quantity} are available in storage.";
                    }
                    if ($small_material?->article?->in_square_meters === 1) {
                        $small_material->quantity -= ($job->copies * ($job->width * $job->height / 1000000));
                    } else {
                        $small_material->quantity -= $job->copies;
                    }
                    $small_material->save();
                    } else {
                        \Log::warning('Small material not found for job', [
                            'job_id' => $job->id,
                            'small_material_id' => $job->small_material_id
                        ]);
                    }
                }

                if ($job->hasOriginalFiles()) {
                    $originalFiles = $job->getOriginalFiles();
                    $updatedFiles = [];
                    
                    foreach ($originalFiles as $originalFile) {
                        // Skip file organization for R2-stored files (they are already properly organized)
                        // R2 files start with 'job-originals/' and don't need to be moved
                        if (!str_starts_with($originalFile, 'job-originals/')) {
                            // This is a legacy local file, attempt to move it
                            try {
                                $newPath = $clientName . '/' . $invoice->id . '/' . basename($originalFile);
                                Storage::move($originalFile, $newPath);
                                $updatedFiles[] = $newPath;
                            } catch (\Exception $e) {
                                \Log::warning('Failed to move legacy original file: ' . $e->getMessage(), [
                                    'old_file' => $originalFile,
                                    'new_path' => $newPath ?? 'unknown',
                                    'job_id' => $job->id
                                ]);
                                // Keep the original path if move failed
                                $updatedFiles[] = $originalFile;
                            }
                        } else {
                            // R2 files don't need to be moved, they stay in their original location
                            $updatedFiles[] = $originalFile;
                        }
                    }
                    
                    // Update the job with the new file paths if any changes were made
                    if ($updatedFiles !== $originalFiles) {
                        $job->originalFile = $updatedFiles;
                    $job->save();
                    }
                }
            }
            // Check if there are any errors and throw them as a single exception
            if (!empty($errorMessages)) {
                throw new \Exception(implode("\n", $errorMessages));
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

        if (!auth()->user()->hasRole('Rabotnik')) {
            $invoice->jobs->each(function ($job) {
                $job->append('totalPrice');
            });
        }

        return Inertia::render('Invoice/InvoiceDetails', [
            'invoice' => $invoice,
            'canViewPrice' => !auth()->user()->hasRole('Rabotnik')
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

    public function countShippingTomorrow() {
        $count = Invoice::whereDate('end_date', Carbon::tomorrow())->count();
        return response()->json(['count' => $count]);
    }

    public function countInvoicesSevenOrMoreDaysAgo() {
        $sevenDaysAgo = Carbon::now()->subDays(7);
        $count = Invoice::whereDate('end_date', '<', $sevenDaysAgo)->where('status', '!=', 'Completed')->count();
        return response()->json(['count' => $count]);
    }

    public function updateLockedNote(Request $request)
    {
        $request->validate([
            'id' => 'required|exists:invoices,id', // Change table name to 'invoices'
            'comment' => 'nullable|string',
        ]);

        $invoiceId = $request->input('id');
        $invoice = Invoice::find($invoiceId);

        if (!$invoice) {
            // Handle the case where the invoice is not found
            return response()->json(['message' => 'Invoice not found'], 404);
        }

        $invoice->LockedNote = $request->comment; // Use the correct property name
        $invoice->save();

        return response()->json(['message' => 'Locked note successfully updated'], 200);
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
        $invoice = Invoice::with(['jobs.actions','contact','user'])->findOrFail($invoiceId);

        // Prepare thumbnails for PDF generation
        $tempFiles = [];
        
        foreach ($invoice->jobs as $job) {
            $originalFiles = is_array($job->originalFile) ? $job->originalFile : [];
            
            if (count($originalFiles) > 0) {
                // This job has multiple files, download thumbnails from R2
                $localThumbnails = [];
                
                foreach ($originalFiles as $index => $filePath) {
                    try {
                        // Find thumbnail in R2
                        $disk = app(TemplateStorageService::class)->getDisk();
                        
                        // List all thumbnails for this job
                        $allThumbnails = $disk->files('job-thumbnails/');
                        
                        // Find any thumbnail that contains this job ID
                        $jobThumbnails = array_filter($allThumbnails, function($file) use ($job) {
                            return strpos($file, 'job_' . $job->id . '_') !== false;
                        });
                        
                        // Sort by timestamp (chronological order)
                        usort($jobThumbnails, function($a, $b) {
                            // Extract timestamp from filename: job_123_1234567890_0_filename.jpg
                            preg_match('/job_\d+_(\d+)_/', $a, $matchesA);
                            preg_match('/job_\d+_(\d+)_/', $b, $matchesB);
                            $timestampA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                            $timestampB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
                            return $timestampA <=> $timestampB;
                        });
                        
                        // Get the thumbnail for this specific index
                        if (isset($jobThumbnails[$index])) {
                            $thumbnailPath = $jobThumbnails[$index];
                            
                            // Download to temporary local storage
                            $tempFileName = 'temp_thumbnail_' . $job->id . '_' . $index . '_' . time() . '.jpg';
                            $tempPath = storage_path('app/temp/' . $tempFileName);
                            
                            // Ensure temp directory exists
                            if (!file_exists(dirname($tempPath))) {
                                mkdir(dirname($tempPath), 0755, true);
                            }
                            
                            // Download thumbnail content
                            $thumbnailContent = $disk->get($thumbnailPath);
                            file_put_contents($tempPath, $thumbnailContent);
                            
                            $localThumbnails[] = $tempPath;
                            $tempFiles[] = $tempPath; // Track for cleanup
                        }
                    } catch (\Exception $e) {
                        // Silently continue if thumbnail download fails
                    }
                }
                
                // Add local thumbnails to job data
                $job->local_thumbnails = $localThumbnails;
            }
        }

        try {
        // Load the view and pass data
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'), [
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
        ]);

            $result = $pdf->stream('Order_' . $invoice->id .'/'. date('Y', strtotime($invoice->start_date)) . '.pdf');
            
            return $result;
        } finally {
            // Clean up temporary files
            foreach ($tempFiles as $tempFile) {
                if (file_exists($tempFile)) {
                    unlink($tempFile);
                }
            }
        }
    }

    public function invoiceReady(Request $request){
        try {
            $query = Invoice::with(['jobs', 'user', 'client'])
                ->where('status', 'Completed'); // Filter by 'Completed' status

            $this->applySearch($query, $request, $request->input('status'));

            $query->orderBy('created_at', $request->input('sortOrder', 'desc'));

            $invoices = $query->latest()->paginate(10);

            if ($request->wantsJson()) {
                return response()->json($invoices);
            }

            return Inertia::render('Finance/Index', [
                'invoices' => $invoices,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function outgoingInvoicePdf(Request $request)
    {
        $invoiceIds = $request->input('invoiceIds', []);
        $isAlreadyGenerated = $request->input('generated', false);

        if (empty($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        $invoices = Invoice::with(['article','client','client.clientCardStatement'])->findOrFail($invoiceIds);

        $dns1d = new DNS1D();
        // Create a transformed array for PDF generation without modifying the original invoices
        $transformedInvoices = $invoices->map(function ($invoice) use ($dns1d) {
            $barcodeString = $invoice->id . '-' . date('m-Y', strtotime($invoice->end_date));
            $barcodeImage = base64_encode($dns1d->getBarcodePNG($barcodeString, 'C128'));

            $totalSalePrice = $invoice->jobs->sum('salePrice');
            $totalCopies = $invoice->jobs->sum('copies');

            $taxRate = 0.18;
            $priceWithTax = $totalSalePrice * (1 + $taxRate);
            $taxAmount = $totalSalePrice * $taxRate;

            // Return a new array for this invoice with the required fields
            return array_merge(
                $invoice->toArray(),
                [
                    'barcodeImage' => $barcodeImage,
                    'totalSalePrice' => $totalSalePrice,
                    'taxRate' => $taxRate * 100,
                    'priceWithTax' => $priceWithTax,
                    'taxAmount' => $taxAmount,
                    'copies' => $totalCopies,
                ]
            );
        })->toArray(); // Convert the collection to an array for the PDF

        // Attempt to generate the PDF before creating Faktura
        try {
            $pdf = PDF::loadView('invoices.outgoing_invoice', [
                'invoices' => $transformedInvoices,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'chroot' => storage_path('fonts'),
                'dpi' => 150,
            ]);

            if (!$isAlreadyGenerated) {
                // Create and store the Faktura
                $faktura = Faktura::create([
                    'isInvoiced' => true,
                    'comment' => '',
                    'created_by' => auth()->id()
                ]);

                // Associate the retrieved Invoice instances with the new Faktura
                $faktura->invoices()->saveMany($invoices->all());
            }

            return $pdf->stream('OutgoingInvoice.pdf');

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function showGenerateInvoice(Request $request)
    {
        try {
            $invoiceIds = explode(',', $request->query('invoices'));

            $invoices = Invoice::with('jobs.small_material.smallFormatMaterial','jobs', 'user', 'client','jobs.actions', 'jobs.large_material')->whereIn('id', $invoiceIds)->get();

            $invoices->each(function ($invoice) {
                $invoice->jobs->each(function ($job) {
                    $job->append('totalPrice');
                });
            });

            // Prepare data as an object
            $invoiceData = $invoices->reduce(function ($acc, $invoice) {
                $acc[$invoice->id] = [
                    'id' => $invoice->id,
                    'invoice_title' => $invoice->invoice_title,
                    'client' => $invoice->client->name,
                    'jobs' => $invoice->jobs,
                    'user'=>$invoice->user->name,
                    'start_date' => $invoice->start_date,
                    'end_date' => $invoice->end_date,
                    'status' => $invoice->status,
                    // ... other invoice data ...
                ];
                return $acc;
            }, []);

            return Inertia::render('Finance/GenerateInvoice', [
                'invoiceData' => $invoiceData,
            ]);
        } catch (\Exception $e) {
            // ... handle error ...
        }
    }

    public function generateInvoice(Request $request)
    {
        $invoiceIds = $request->input('orders');
        $comment = $request->input('comment');
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create a new Faktura instance
            $faktura = Faktura::create([
                'isInvoiced' => true,
                'comment' => $comment,
                'created_by' => auth()->id()
            ]);

            // Retrieve the Invoice instances based on the provided IDs
            $invoices = Invoice::find($invoiceIds);

            // Associate the retrieved Invoice instances with the new Faktura
            $faktura->invoices()->saveMany($invoices);

            // Commit the transaction
            DB::commit();

            // Return success response
            return response()->json([
                'message' => 'Faktura created successfully',
                'invoice_id' => $faktura->id
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();

            // Return error response
            return response()->json(['error' => 'Failed to create Faktura'], 500);
        }
    }

    public function getGeneratedInvoice($id)
    {
        try {
            // Find the Faktura by its ID
            $faktura = Faktura::with('invoices.jobs.small_material.smallFormatMaterial', 'invoices.user', 'invoices.client', 'invoices.jobs.actions', 'invoices.jobs.large_material')->findOrFail($id);

            $faktura->invoices->each(function ($invoice)  {
                $invoice->jobs->each(function ($job) {
                    $job->append('totalPrice');
                });
            });

            // Prepare data as an object
            $invoiceData = $faktura->invoices->map(function ($invoice) use ($faktura) {
                return [
                    'id' => $invoice->id,
                    'invoice_title' => $invoice->invoice_title,
                    'client' => $invoice->client->name,
                    'jobs' => $invoice->jobs,
                    'user' => $invoice->user->name,
                    'start_date' => $invoice->start_date,
                    'end_date' => $invoice->end_date,
                    'status' => $invoice->status,
                    'faktura_comment' => $faktura->comment,
                    'fakturaId' => $faktura->id,
                    'createdBy' => $faktura->createdBy->name,
                    'created' => $faktura->created_at
                    // ... other invoice data ...
                ];
            });

            return Inertia::render('Finance/Invoice', [
                'invoice' => $invoiceData,
            ]);
        } catch (Exception $e) {
            // If Faktura with the given ID is not found, return error response
            return response()->json(['error' => 'Invoice not found'], 404);
        }
    }

    public function generateAllInvoicesPdf(Request $request)
    {
        $invoiceIds = explode(',', $request->query->all()['invoices']);

        $invoices = Invoice::with(
            'jobs',
            'jobs.actions',
            'jobs.small_material.article',
            'jobs.large_material.article'
        )->whereIn('id', $invoiceIds)->get();

        // Load the view and pass data
        $pdf = Pdf::loadView('invoices.generated_invoice', compact('invoices'), [
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
        ]);

        return $pdf->stream('Invoice_' . Date::now()->format('Y') . '.pdf');
    }

    public function allFaktura(Request $request)
    {
        try {
            $query = Faktura::with('createdBy')->where('isInvoiced', true); // Filter for generated Fakturas

            // Apply search query if provided
            if ($request->has('searchQuery')) {
                $searchQuery = $request->input('searchQuery');
                $query->where('id', 'like', "%{$searchQuery}%");
            }

            // Apply filter client if provided
            if ($request->has('client') && $request->input('client') !== 'All') {
                $client = $request->input('client');
                $query->whereHas('invoices.client', function ($q) use ($client) {
                    $q->where('name', $client);
                });
            }

            // Apply sort order
            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);


            // Apply pagination with 10 results per page
            $fakturas = $query->paginate(10);

            // Eager load invoices with related models
            $fakturas->load('invoices.jobs', 'invoices.user', 'invoices.client');

            // Handle AJAX requests for JSON responses
            if ($request->wantsJson()) {
                return response()->json($fakturas);
            }

            return Inertia::render('Finance/AllInvoices', [
                'fakturas' => $fakturas,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function updateInvoiceComment(Request $request, $id)
    {
        try {
            // Find the Faktura by its ID
            $faktura = Faktura::findOrFail($id);

            // Update the comment
            $faktura->update([
                'comment' => $request->input('comment')
            ]);

            // Return success response
            return response()->json([
                'message' => 'Faktura comment updated successfully',
                'invoice_id' => $faktura->id
            ], 200);
        } catch (\Exception $e) {
            // Return error response
            return response()->json(['error' => 'Failed to update Faktura comment'], 500);
        }
    }

    /**
     * Download all files from an invoice as a ZIP
     */
    public function downloadAllFiles(Request $request)
    {
        try {
            $request->validate([
                'invoiceId' => 'required|integer',
                'clientName' => 'required|string',
                'files' => 'required|array'
            ]);

            $invoiceId = $request->input('invoiceId');
            $clientName = $request->input('clientName');
            $files = $request->input('files');

            // Find the invoice
            $invoice = Invoice::with('jobs')->findOrFail($invoiceId);

            // Create a temporary file for the ZIP
            $zipFileName = 'Invoice_' . $clientName . '_' . $invoiceId . '_AllFiles.zip';
            $tempZipPath = storage_path('app/temp/' . $zipFileName);
            
            // Ensure temp directory exists
            if (!file_exists(dirname($tempZipPath))) {
                mkdir(dirname($tempZipPath), 0755, true);
            }

            $zip = new \ZipArchive();
            if ($zip->open($tempZipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== TRUE) {
                return response()->json(['error' => 'Cannot create ZIP file'], 500);
            }

            $addedFiles = 0;
            $templateStorageService = app(\App\Services\TemplateStorageService::class);

            foreach ($files as $file) {
                try {
                    if ($file['isMultiple']) {
                        // New system: Get file from R2 storage
                        $job = $invoice->jobs->firstWhere('id', $file['jobId']);
                        if (!$job) continue;

                        $originalFiles = $job->getOriginalFiles();
                        if (!isset($originalFiles[$file['fileIndex']])) continue;

                        $filePath = $originalFiles[$file['fileIndex']];
                        
                        // Check if file exists in R2
                        if (!$templateStorageService->getDisk()->exists($filePath)) {
                            \Log::warning('File not found in R2', ['file_path' => $filePath]);
                            continue;
                        }

                        // Get file content from R2
                        $fileContent = $templateStorageService->getDisk()->get($filePath);
                        $fileName = $file['jobName'] . '_' . ($file['fileIndex'] + 1) . '.pdf';
                        
                        // Add to ZIP
                        $zip->addFromString($fileName, $fileContent);
                        $addedFiles++;

                    } else {
                        // Legacy system: Get file from local storage
                        $localFilePath = storage_path('app/public/uploads/' . $file['filePath']);
                        if (file_exists($localFilePath)) {
                            $fileName = $file['jobName'] . '_' . $file['filePath'];
                            $zip->addFile($localFilePath, $fileName);
                            $addedFiles++;
                        } else {
                            \Log::warning('Legacy file not found', ['file_path' => $localFilePath]);
                        }
                    }

                } catch (\Exception $e) {
                    \Log::warning('Failed to add file to ZIP: ' . $e->getMessage(), [
                        'file' => $file,
                        'error' => $e->getMessage()
                    ]);
                    continue;
                }
            }

            $zip->close();

            if ($addedFiles === 0) {
                // Clean up empty ZIP file
                if (file_exists($tempZipPath)) {
                    unlink($tempZipPath);
                }
                return response()->json(['error' => 'No files could be added to download'], 404);
            }

            \Log::info('ZIP file created successfully', [
                'invoice_id' => $invoiceId,
                'files_added' => $addedFiles,
                'zip_path' => $tempZipPath
            ]);

            // Return the ZIP file for download
            return response()->download($tempZipPath, $zipFileName)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error creating ZIP download:', [
                'invoice_id' => $request->input('invoiceId'),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to create download',
                'details' => $e->getMessage()
            ], 500);
        }
    }
}
