<?php

namespace App\Http\Controllers;

use App\Models\JobAction;
use App\Events\InvoiceCreated;
use App\Models\Article;
use App\Models\Faktura;
use App\Models\FakturaTradeItem;
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
use Spatie\PdfToImage\Pdf as PdfToImage;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Invoice::with([
                'jobs' => function ($query) {
                    $query->select([
                        'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                        'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
                    ]);
                },
                'user:id,name',
                'client:id,name'
            ]);

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

        // Check if jobs are provided
        $jobs = $request->jobs;
        if (!$jobs || empty($jobs)) {
            return response()->json([
                'error' => 'Cannot create an order without jobs. Please add at least one job before creating the order.',
                'message' => 'No jobs provided'
            ], 422);
        }

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

            // STOCK DEDUCTION DISABLED: Stock consumption is now handled by the Stock Realization system
            // When an invoice is completed, a StockRealization record is created which can be edited
            // and then realized to actually consume the materials. This allows for adjustments
            // based on actual material usage rather than estimated usage at order creation time.
            $shouldSkipConsumption = true; // Always skip - stock deduction moved to realization system
            
            \Log::info('Stock deduction skipped - using Stock Realization system', [
                'invoice_id' => $invoiceId,
                'client_name' => $clientName
            ]);

            foreach ($jobs as $job) {
                // Check if job has a catalog item before proceeding
                if (!$job->catalogItem) {
                    \Log::warning('Job without catalog item found during invoice creation', [
                        'job_id' => $job->id,
                        'invoice_id' => $invoiceId
                    ]);
                    continue; // Skip this job if no catalog item
                }

                $catalogItemArticles = DB::table('catalog_item_articles')
                    ->select()
                    ->where('catalog_item_id', $job->catalogItem->id)
                    ->get();
                
                // Process articles for stock consumption
                // Prefer recomputing from catalog item (reflects latest job.quantity/copies)
                $articlesToConsume = [];

                if ($job->catalogItem && $job->catalogItem->articles()->exists()) {
                    $materialRequirements = $job->catalogItem->calculateMaterialRequirements($job);

                    foreach ($materialRequirements as $requirement) {
                        $article = $requirement['article'];
                        $neededQuantity = $requirement['actual_required'];
                        $unitType = $requirement['unit_type'];

                        // Resolve category to an actual article at consumption time
                        $actualArticle = $article;
                        if ($article->pivot->category_id) {
                            $actualArticle = $job->catalogItem->getFirstArticleFromCategory(
                                $article->pivot->category_id,
                                null,
                                $neededQuantity
                            );
                            if (!$actualArticle) {
                                $errorMessages[] = "No available articles with sufficient stock in the selected category (ID: {$article->pivot->category_id}) for catalog item '{$job->catalogItem->name}'.";
                                continue;
                            }
                        }

                        $articlesToConsume[] = [
                            'article' => $actualArticle,
                            'quantity' => $neededQuantity,
                            'source' => 'catalog_item_recomputed'
                        ];
                    }
                } elseif ($job->articles && $job->articles->count() > 0) {
                    // Manual jobs: use direct job article assignments
                    foreach ($job->articles as $jobArticle) {
                        $articlesToConsume[] = [
                            'article' => $jobArticle,
                            'quantity' => $jobArticle->pivot->quantity,
                            'source' => 'job_direct'
                        ];
                    }
                } else {
                    \Log::warning('No articles found for job', [
                        'job_id' => $job->id,
                        'catalog_item_id' => $job->catalogItem->id ?? null
                    ]);
                }
                
                // Process all articles for stock consumption
                if (!$shouldSkipConsumption) {
                    foreach ($articlesToConsume as $articleData) {
                        $article = $articleData['article'];
                        $neededQuantity = $articleData['quantity'];
                        $source = $articleData['source'];
                        
                        // Check stock availability
                        if (!$article->hasStock($neededQuantity)) {
                            $errorMessages[] = "For the job '{$job->name}', article '{$article->name}' needs {$neededQuantity} units, but only {$article->getCurrentStock()} are available in stock.";
                            continue;
                        }
                        
                        // Consume article stock
                        $this->consumeArticleStock($article, $neededQuantity);
                        
                        \Log::info('Consumed article stock for job', [
                            'job_id' => $job->id,
                            'article_id' => $article->id,
                            'article_name' => $article->name,
                            'quantity_consumed' => $neededQuantity,
                            'source' => $source
                        ]);
                    }
                }

                // Only process legacy large/small material reduction if we DID NOT consume any component articles
                // Component articles (either attached to the job or resolved from catalog) take precedence over legacy material assignments
                if (empty($articlesToConsume)) {
                    // Update Large Material (legacy fallback)
                    if ($job->large_material_id !== null && !$shouldSkipConsumption) {
                        $large_material = LargeFormatMaterial::with('article')->find($job->large_material_id);
                        if ($large_material) {
                            $units = ($job->catalogItem && $job->catalogItem->by_copies) ? (int)$job->copies : (int)$job->quantity;
                            // Check stock availability
                            if ($units > (int) $large_material->quantity) {
                                $errorMessages[] = "For the catalog item {$job->catalogItem->name} with material {$large_material->name}, you need {$units} units, but you only have {$large_material->quantity} in storage.";
                            }
                            
                            // Consume material stock
                            if ($large_material?->article?->in_square_meters === 1) {
                                $large_material->quantity -= ($units * ($job->total_area_m2 ?? 0));
                            } else {
                                $large_material->quantity -= $units;
                            }
                            $large_material->save();
                            
                            \Log::info('Used legacy large material stock reduction', [
                                'job_id' => $job->id,
                                'material_id' => $large_material->id,
                                'material_name' => $large_material->name
                            ]);
                        } else {
                            \Log::warning('Large material not found for job', [
                                'job_id' => $job->id,
                                'large_material_id' => $job->large_material_id
                            ]);
                        }
                    }
                    
                    // Update Small Material (legacy fallback)
                    if ($job->small_material_id !== null && !$shouldSkipConsumption) {
                        $small_material = SmallMaterial::with('article')->find($job->small_material_id);
                        if ($small_material) {
                            $units = ($job->catalogItem && $job->catalogItem->by_copies) ? (int)$job->copies : (int)$job->quantity;
                            // Check stock availability
                            if ($units > (int) $small_material->quantity) {
                                $errorMessages[] = "For the catalog item '{$job->catalogItem->name}', which uses the material '{$small_material->name}', you need {$units} units, but only {$small_material->quantity} are available in storage.";
                            }
                            
                            // Consume material stock
                            if ($small_material?->article?->in_square_meters === 1) {
                                $small_material->quantity -= ($units * ($job->total_area_m2 ?? 0));
                            } else {
                                $small_material->quantity -= $units;
                            }
                            $small_material->save();
                            
                            \Log::info('Used legacy small material stock reduction', [
                                'job_id' => $job->id,
                                'material_id' => $small_material->id,
                                'material_name' => $small_material->name
                            ]);
                        } else {
                            \Log::warning('Small material not found for job', [
                                'job_id' => $job->id,
                                'small_material_id' => $job->small_material_id
                            ]);
                        }
                    }
                } else {
                    \Log::info('Skipped legacy material stock reduction - using component articles instead', [
                        'job_id' => $job->id,
                        'catalog_item_id' => $job->catalogItem->id,
                        'component_articles_count' => $job->catalogItem->articles()->count()
                    ]);
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
        $invoice = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                    'jobs.small_material_id', 'jobs.large_material_id'
                ]);
            },
            'jobs.small_material.smallFormatMaterial', 
            'jobs.articles.categories',
            'jobs.articles.largeFormatMaterial',
            'jobs.articles.smallMaterial',
            'historyLogs', 
            'user:id,name', 
            'client:id,name', 
            'jobs.actions', 
            'jobs.large_material'
        ])->findOrFail($id);

        // Always append totalPrice for each job
        $invoice->jobs->each(function ($job) {
            $job->append('totalPrice');
        });

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
        $invoices = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
                ]);
            },
            'historyLogs',
            'user:id,name',
            'client:id,name'
        ])
            ->latest()
            ->take(3)
            ->get();

        // Assuming each job has an 'image' attribute
        return response()->json($invoices);
    }

    /**
     * Return latest orders that are NOT completed, paginated independently for dashboard.
     */
    public function latestOpenOrders(Request $request)
    {
        $query = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
                ]);
            },
            'jobs.articles.categories',
            'jobs.articles.largeFormatMaterial',
            'jobs.articles.smallMaterial',
            'user:id,name',
            'client:id,name'
        ]);

        // Reuse generic search filters but do not apply a status from request here
        $this->applySearch($query, $request, null);

        // Exclude completed orders explicitly
        $query->where('status', '!=', 'Completed');

        $query->orderBy('created_at', 'desc');

        $perPage = (int)($request->input('per_page', 10));
        $invoices = $query->paginate($perPage);

        // Ensure originalFile is properly cast for each job
        foreach ($invoices->items() as $invoice) {
            if ($invoice->jobs) {
                foreach ($invoice->jobs as $job) {
                    // Force cast originalFile to array if it's a string
                    if (is_string($job->originalFile)) {
                        $job->originalFile = json_decode($job->originalFile, true) ?: [];
                    }
                    
                    // Ensure file field is properly set (for legacy system)
                    if (empty($job->file) && !empty($job->originalFile) && is_array($job->originalFile) && count($job->originalFile) > 0) {
                        // If no legacy file but we have originalFile, use the first file as legacy
                        $firstOriginalFile = $job->originalFile[0];
                        $job->file = pathinfo(basename($firstOriginalFile), PATHINFO_FILENAME) . '.jpg';
                    }
                }
            }
        }

        return response()->json($invoices);
    }

    /**
     * Return completed orders only, paginated (separate from latest list).
     */
    public function completedOrders(Request $request)
    {
        $query = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
                ]);
            },
            'jobs.articles.categories',
            'jobs.articles.largeFormatMaterial',
            'jobs.articles.smallMaterial',
            'user:id,name',
            'client:id,name'
        ]);

        // Reuse generic search filters but pin status to Completed regardless of request
        $this->applySearch($query, $request, null);

        $query->where('status', 'Completed')->orderBy('created_at', 'desc');

        $perPage = (int)($request->input('per_page', 5));
        $invoices = $query->paginate($perPage);

        return response()->json($invoices);
    }

    public function getOrderDetails($id)
    {
        $invoice = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                    'jobs.small_material_id', 'jobs.large_material_id'
                ]);
            },
            'jobs.small_material.smallFormatMaterial', 
            'jobs.large_material',
            'jobs.actions',
            'jobs.articles',
            'user:id,name', 
            'client:id,name'
        ])->findOrFail($id);

        return response()->json($invoice);
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

        $selectedPairs = $request->selectedPairs ?? [];

        // Get all job IDs associated with the invoice
        $jobIds = $invoice->jobs->pluck('id')->toArray();

        // Get all job actions for those jobs
        $jobActionIds = DB::table('job_job_action')
            ->whereIn('job_id', $jobIds)
            ->pluck('job_action_id')
            ->toArray();

        $jobActions = JobAction::whereIn('id', $jobActionIds)->get();

        // Reset all actions involved in this invoice's jobs to 0 first
        foreach ($jobActions as $jobAction) {
            $jobAction->hasNote = 0;
            $jobAction->save();
        }

        // Set hasNote only for selected action names (derived from selectedPairs)
        if (!empty($selectedPairs)) {
            $selectedActionNames = collect($selectedPairs)->pluck('action_name')->unique()->values();
            JobAction::whereIn('id', $jobActionIds)
                ->whereIn('name', $selectedActionNames)
                ->update(['hasNote' => 1]);
        }

        // Return a response, could be the updated invoice, a success message, etc.
        return response()->json([
            'message' => 'Invoice jobs updated successfully.'
        ]);
    }

    public function generateInvoicePdf($invoiceId)
    {
        $invoice = Invoice::with(['jobs.actions','contact','user'])->findOrFail($invoiceId);

        $tempFiles = [];
        foreach ($invoice->jobs as $job) {
            $originalFiles = is_array($job->originalFile) ? $job->originalFile : [];
            if (count($originalFiles) > 0) {
                $localThumbnails = [];
                foreach ($originalFiles as $index => $filePath) {
                    try {
                        $disk = app(TemplateStorageService::class)->getDisk();
                        $allThumbnails = $disk->files('job-thumbnails/');
                        $jobThumbnails = array_filter($allThumbnails, function($file) use ($job) {
                            return strpos($file, 'job_' . $job->id . '_') !== false;
                        });
                        usort($jobThumbnails, function($a, $b) {
                            preg_match('/job_\\d+_(\\d+)_/', $a, $matchesA);
                            preg_match('/job_\\d+_(\\d+)_/', $b, $matchesB);
                            $timestampA = isset($matchesA[1]) ? (int)$matchesA[1] : 0;
                            $timestampB = isset($matchesB[1]) ? (int)$matchesB[1] : 0;
                            return $timestampA <=> $timestampB;
                        });
                        if (isset($jobThumbnails[$index])) {
                            $thumbnailPath = $jobThumbnails[$index];
                            $tempFileName = 'temp_thumbnail_' . $job->id . '_' . $index . '_' . time() . '.jpg';
                            $tempPath = storage_path('app/temp/' . $tempFileName);
                            if (!file_exists(dirname($tempPath))) {
                                mkdir(dirname($tempPath), 0755, true);
                            }
                            $thumbnailContent = $disk->get($thumbnailPath);
                            file_put_contents($tempPath, $thumbnailContent);
                            $localThumbnails[] = $tempPath;
                            $tempFiles[] = $tempPath;
                        }
                    } catch (\Exception $e) {
                        // Silently continue if thumbnail download fails
                    }
                }
                $job->local_thumbnails = $localThumbnails;
            }

            // Cutting file image generation
            if (!empty($job->cuttingFiles) && is_array($job->cuttingFiles)) {
                foreach ($job->cuttingFiles as $cuttingFilePath) {
                    $ext = strtolower(pathinfo($cuttingFilePath, PATHINFO_EXTENSION));
                    if ($ext === 'pdf') {
                        $disk = app(TemplateStorageService::class)->getDisk();
                        if ($disk->exists($cuttingFilePath)) {
                            $cuttingPdfContent = $disk->get($cuttingFilePath);
                            $cuttingPdfTempPath = storage_path('app/temp/cutting_' . $job->id . '_' . uniqid() . '.pdf');
                            file_put_contents($cuttingPdfTempPath, $cuttingPdfContent);
                            $tempFiles[] = $cuttingPdfTempPath;

                            // Convert first page to image
                            try {
                                $pdfToImage = new PdfToImage($cuttingPdfTempPath);
                                $imagePath = storage_path('app/temp/cutting_' . $job->id . '_' . uniqid() . '.jpg');
                                $pdfToImage->setPage(1)->saveImage($imagePath);
                                $job->cutting_file_image = $imagePath;
                                $tempFiles[] = $imagePath;
                            } catch (\Exception $e) {
                                // If conversion fails, do not set image
                                $job->cutting_file_image = null;
                            }
                        }
                    }
                }
            }
        }

        // Generate the main invoice PDF with DomPDF (Blade handles header, title, and image)
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'), [
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
        ]);
        $output = $pdf->output();

        // Clean up temp files
        foreach ($tempFiles as $tempFile) {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }

        return response($output, 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'inline; filename="Order_' . $invoice->id . '_' . date('Y', strtotime($invoice->start_date)) . '.pdf"');
    }

    public function invoiceReady(Request $request){
        try {
            $query = Invoice::with(['jobs', 'user', 'client'])
                ->where('status', 'Completed') // Filter by 'Completed' status
                ->whereNull('faktura_id'); // Only show invoices that haven't been invoiced yet

            $this->applySearch($query, $request, $request->input('status'));

            // Exclude completed orders for the individual client 'Физичко лице'
            $query->whereDoesntHave('client', function($q){
                $q->where('name', 'Физичко лице');
            });

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

    public function getFilteredUninvoicedOrders(Request $request)
    {
        try {
            $query = Invoice::with(['jobs', 'user', 'client'])
                ->where('status', 'Completed') // Filter by 'Completed' status
                ->whereNull('faktura_id'); // Only show invoices that haven't been invoiced yet

            // Apply search, client filter, and status filter
            $this->applySearch($query, $request, $request->input('status'));

            // Exclude completed orders for the individual client 'Физичко лице'
            $query->whereDoesntHave('client', function($q){
                $q->where('name', 'Физичко лице');
            });

            // Apply sort order
            $query->orderBy('created_at', $request->input('sortOrder', 'desc'));

            // Get paginated results
            $invoices = $query->latest()->paginate(10);

            // Return JSON response for AJAX calls
            return response()->json($invoices);
            
        } catch (\Exception $e) {
            Log::error('Filtered Uninvoiced Orders Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function getFilteredAllInvoices(Request $request)
    {
        try {
            $query = Faktura::with([
                'createdBy:id,name',
                'invoices.client:id,name',
                'invoices.user:id,name',
                'tradeItems.article:id,name,code'
            ])->where('isInvoiced', 1); // Filter for generated Fakturas (isInvoiced = 1)

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

            // Return JSON response for AJAX calls
            return response()->json($fakturas);
            
        } catch (\Exception $e) {
            Log::error('Filtered All Invoices Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    public function outgoingInvoicePdf(Request $request)
    {
        $invoiceIds = $request->input('invoiceIds', []);
        $isAlreadyGenerated = $request->input('generated', false);
        $debug = filter_var($request->input('debug', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        // If already generated, fetch ALL invoices that belong to the same faktura
        if ($isAlreadyGenerated) {
            $firstInvoice = Invoice::with(['faktura'])->findOrFail($invoiceIds[0]);
            if ($firstInvoice->faktura) {
                // Get all invoices that belong to this faktura
                $invoices = Invoice::with(['article','client','client.clientCardStatement','faktura.tradeItems.article','jobs'])
                    ->where('faktura_id', $firstInvoice->faktura->id)
                    ->get();
            } else {
                // Fallback to original behavior if no faktura
                $invoices = Invoice::with(['article','client','client.clientCardStatement','faktura.tradeItems.article'])->findOrFail($invoiceIds);
            }
        } else {
            // For preview/generation, use the provided invoice IDs
            $invoices = Invoice::with(['article','client','client.clientCardStatement','faktura.tradeItems.article'])->findOrFail($invoiceIds);
        }

        $dns1d = new DNS1D();
        // Create a transformed array for PDF generation without modifying the original invoices
        $nextFakturaId = (int) (\App\Models\Faktura::max('id') ?? 0) + 1;
        $transformedInvoices = $invoices->map(function ($invoice) use ($dns1d, $isAlreadyGenerated, $nextFakturaId, $invoices) {
            $barcodeString = $invoice->id . '-' . date('m-Y', strtotime($invoice->end_date));
            $barcodeImage = base64_encode($dns1d->getBarcodePNG($barcodeString, 'C128'));

            $totalSalePrice = $invoice->jobs->sum('salePrice');
            $totalCopies = $invoice->jobs->sum('copies');

            $taxRate = 0.18;
            $priceWithTax = $totalSalePrice * (1 + $taxRate);
            $taxAmount = $totalSalePrice * $taxRate;

            // Include trade items for already generated invoices (load from Faktura)
            // Only add trade items to the first invoice to avoid duplicates
            $tradeItemsArray = [];
            if ($isAlreadyGenerated && $invoice->faktura && $invoice->id === $invoices->first()->id) {
                $tradeItems = $invoice->faktura->tradeItems; // allow lazy/eager
                $tradeItemsArray = $tradeItems->map(function ($item) {
                    return [
                        'article_id' => $item->article_id,
                        'article_name' => optional($item->article)->name,
                        'quantity' => (float) $item->quantity,
                        'unit_price' => (float) $item->unit_price,
                        'total_price' => (float) $item->total_price,
                        'vat_rate' => (float) $item->vat_rate,
                        'vat_amount' => (float) $item->vat_amount,
                    ];
                })->toArray();
            }

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
                    'trade_items' => $tradeItemsArray,
                    // For this flow, use current timestamp as generated_at (pre or post generation)
                    'generated_at' => now()->toDateTimeString(),
                    // If already generated, include faktura_id; else propose next
                    'faktura_id' => optional($invoice->faktura)->id,
                    'preview_faktura_id' => $isAlreadyGenerated ? null : $nextFakturaId,
                ]
            );
        })->toArray(); // Convert the collection to an array for the PDF

        // Attempt to generate the PDF before creating Faktura
        try {
            if ($debug) {
                \Log::info('OutgoingInvoice PDF data', ['invoices' => $transformedInvoices]);
                return response()->json(['invoices' => $transformedInvoices], 200);
            }

            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
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
            // Handle both old format (invoices=1,2,3) and new format (orders[]=1&orders[]=2)
            if ($request->has('invoices')) {
                $invoiceIds = explode(',', $request->query('invoices'));
            } elseif ($request->has('orders')) {
                $invoiceIds = $request->query('orders');
            } else {
                throw new \Exception('No invoice IDs provided');
            }

            $invoices = Invoice::with([
                'jobs' => function ($query) {
                    $query->select([
                        'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                        'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                        'jobs.small_material_id', 'jobs.large_material_id'
                    ]);
                },
                'jobs.small_material.smallFormatMaterial',
                'user:id,name', 
                'client:id,name',
                'jobs.actions', 
                'jobs.large_material'
            ])->whereIn('id', $invoiceIds)->get();

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
        $tradeItems = $request->input('trade_items', []);
        
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Create a new Faktura instance
            $faktura = Faktura::create([
                'isInvoiced' => 1,
                'comment' => $comment,
                'created_by' => auth()->id()
            ]);

            // Retrieve the Invoice instances based on the provided IDs
            $invoices = Invoice::with(['jobs.actions', 'contact', 'user', 'client', 'article', 'client.clientCardStatement'])
                ->find($invoiceIds);

            // Associate the retrieved Invoice instances with the new Faktura
            foreach ($invoices as $invoice) {
                $invoice->faktura_id = $faktura->id;
                $invoice->save();
            }

            // Handle trade items if provided
            \Log::info('GenerateInvoice received trade items', [
                'count' => is_array($tradeItems) ? count($tradeItems) : 0,
                'items' => $tradeItems,
            ]);
            foreach ($tradeItems as $tradeItem) {
                FakturaTradeItem::create([
                    'faktura_id' => $faktura->id,
                    'article_id' => $tradeItem['article_id'] ?? null,
                    'quantity' => $tradeItem['quantity'] ?? 0,
                    'unit_price' => $tradeItem['unit_price'] ?? 0,
                    'total_price' => $tradeItem['total_price'] ?? 0,
                    'vat_rate' => $tradeItem['vat_rate'] ?? 0,
                    'vat_amount' => $tradeItem['vat_amount'] ?? 0,
                ]);
            }
            \Log::info('Faktura trade items stored', [
                'faktura_id' => $faktura->id,
                'stored_count' => $faktura->tradeItems()->count()
            ]);

            // Commit the transaction
            DB::commit();

            // Generate PDF for the created invoice
            $dns1d = new DNS1D();
            $transformedInvoices = $invoices->map(function ($invoice) use ($dns1d, $tradeItems, $comment, $faktura) {
                $barcodeString = $invoice->id . '-' . date('m-Y', strtotime($invoice->end_date));
                $barcodeImage = base64_encode($dns1d->getBarcodePNG($barcodeString, 'C128'));

                $totalSalePrice = $invoice->jobs->sum('salePrice');
                $totalCopies = $invoice->jobs->sum('copies');

                $taxRate = 0.18;
                $priceWithTax = $totalSalePrice * (1 + $taxRate);
                $taxAmount = $totalSalePrice * $taxRate;

                // Add trade items to this invoice's data
                $invoiceTradeItems = collect($tradeItems)->map(function ($item) {
                    return [
                        'article_id' => $item['article_id'],
                        'article_name' => $item['article_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'vat_rate' => $item['vat_rate'],
                        'vat_amount' => $item['vat_amount']
                    ];
                });

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
                        'trade_items' => $invoiceTradeItems,
                        'comment' => $comment,
                        // Use Faktura creation time as the official generated time
                        'generated_at' => optional($faktura->created_at)->toDateTimeString(),
                        'faktura_id' => $faktura->id,
                    ]
                );
            })->toArray();

            // Generate PDF using v2 template for preview
            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => $transformedInvoices,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'chroot' => storage_path('fonts'),
                'dpi' => 150,
            ]);

            // Return success response with PDF
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice_' . $faktura->id . '.pdf"'
            ]);

        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();
            Log::error('Generate Invoice Error: ' . $e->getMessage());

            // Return error response
            return response()->json(['error' => 'Failed to create Faktura'], 500);
        }
    }

    public function getGeneratedInvoice($id)
    {
        try {
            // Find the Faktura by its ID
            $faktura = Faktura::with([
                'invoices.jobs.small_material.smallFormatMaterial', 
                'invoices.user', 
                'invoices.client', 
                'invoices.client.clientCardStatement',
                'invoices.jobs.actions', 
                'invoices.jobs.large_material',
                'tradeItems.article'
            ])->findOrFail($id);

            $faktura->invoices->each(function ($invoice)  {
                $invoice->jobs->each(function ($job) {
                    $job->append('totalPrice');
                });
            });

            // Prepare data as an object - trade items belong to faktura, not individual invoices
            $invoiceData = $faktura->invoices->map(function ($invoice) use ($faktura) {
                return [
                    'id' => $invoice->id,
                    'invoice_title' => $invoice->invoice_title,
                    'client' => $invoice->client->name,
                    'client_data' => $invoice->client,
                    'jobs' => $invoice->jobs,
                    'user' => $invoice->user->name,
                    'start_date' => $invoice->start_date,
                    'end_date' => $invoice->end_date,
                    'status' => $invoice->status,
                    'faktura_comment' => $faktura->comment,
                    'fakturaId' => $faktura->id,
                    'createdBy' => $faktura->createdBy->name,
                    'created' => $faktura->created_at
                ];
            });

            // Trade items are separate from invoices - they belong to the faktura
            $tradeItems = $faktura->tradeItems->map(function ($tradeItem) {
                return [
                    'id' => $tradeItem->id,
                    'article_id' => $tradeItem->article_id,
                    'article_name' => $tradeItem->article->name ?? 'Unknown Article',
                    'article_code' => $tradeItem->article->code ?? '',
                    'quantity' => $tradeItem->quantity,
                    'unit_price' => $tradeItem->unit_price,
                    'total_price' => $tradeItem->total_price,
                    'vat_rate' => $tradeItem->vat_rate,
                    'vat_amount' => $tradeItem->vat_amount
                ];
            });

            return Inertia::render('Finance/Invoice', [
                'invoice' => $invoiceData,
                'faktura' => $faktura,
                'tradeItems' => $tradeItems
            ]);
        } catch (Exception $e) {
            // If Faktura with the given ID is not found, return error response
            return response()->json(['error' => 'Invoice not found'], 404);
        }
    }

    public function generateAllInvoicesPdf(Request $request)
    {
        $invoiceIds = explode(',', $request->query->all()['invoices']);

        $invoices = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                    'jobs.small_material_id', 'jobs.large_material_id'
                ]);
            },
            'jobs.actions',
            'jobs.small_material.article',
            'jobs.large_material.article'
        ])->whereIn('id', $invoiceIds)->get();

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

            
            $query = Faktura::with([
                'createdBy:id,name',
                'invoices.client:id,name',
                'invoices.user:id,name',
                'tradeItems.article:id,name,code'
            ])->where('isInvoiced', 1); // Filter for generated Fakturas (isInvoiced = 1)

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

            // Handle AJAX requests for JSON responses
            if ($request->wantsJson()) {
                return response()->json($fakturas);
            }


            return Inertia::render('Finance/AllInvoices', [
                'fakturas' => $fakturas,
            ]);
        } catch (\Exception $e) {
            Log::error('AllFaktura Error: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            // For AJAX requests, return JSON
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Internal Server Error'], 500);
            }
            
            // For Inertia requests, return proper Inertia response
            return Inertia::render('Finance/AllInvoices', [
                'fakturas' => collect([]), // Empty collection
                'error' => 'Unable to load invoices. Please try again.'
            ]);
        }
    }

    public function previewInvoice(Request $request)
    {
        try {
            $invoiceIds = $request->input('orders');
            $comment = $request->input('comment');
            $tradeItems = $request->input('trade_items', []);

            // Basic validation
            if (empty($invoiceIds) || !is_array($invoiceIds)) {
                return response()->json(['error' => 'No invoices selected'], 400);
            }

            // Get the invoices for preview with necessary relationships (same as generateInvoice)
            $invoices = Invoice::with(['jobs.actions', 'contact', 'user', 'client', 'article', 'client.clientCardStatement'])
                ->find($invoiceIds);

            // Transform invoices for PDF generation (same as outgoingInvoicePdf)
            $dns1d = new DNS1D();
            $nextFakturaId = (int) (\App\Models\Faktura::max('id') ?? 0) + 1;
            $transformedInvoices = $invoices->map(function ($invoice) use ($dns1d, $tradeItems, $comment, $nextFakturaId) {
                // Compose barcode safely (GD may be missing in some environments)
                $period = $invoice->end_date ? date('m-Y', strtotime($invoice->end_date)) : date('m-Y');
                $barcodeString = $invoice->id . '-' . $period;
                try {
                    $barcodePng = $dns1d->getBarcodePNG($barcodeString, 'C128');
                    $barcodeImage = base64_encode($barcodePng);
                } catch (\Exception $e) {
                    $barcodeImage = null;
                }

                $totalSalePrice = (float) $invoice->jobs->sum('salePrice');
                $totalCopies = (int) $invoice->jobs->sum('copies');

                $taxRate = 0.18;
                $priceWithTax = $totalSalePrice * (1 + $taxRate);
                $taxAmount = $totalSalePrice * $taxRate;

                // Add trade items to this invoice's data
                $invoiceTradeItems = collect($tradeItems)->map(function ($item) {
                    return [
                        'article_id' => $item['article_id'],
                        'article_name' => $item['article_name'],
                        'quantity' => $item['quantity'],
                        'unit_price' => $item['unit_price'],
                        'total_price' => $item['total_price'],
                        'vat_rate' => $item['vat_rate'],
                        'vat_amount' => $item['vat_amount']
                    ];
                });

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
                        'trade_items' => $invoiceTradeItems,
                        'comment' => $comment,
                        // Freeze time at preview click
                        'generated_at' => now()->toDateTimeString(),
                        'preview_faktura_id' => $nextFakturaId,
                    ]
                );
            })->toArray();

            // Generate PDF using v2 template for preview
            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => $transformedInvoices,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'chroot' => storage_path('fonts'),
                'dpi' => 150,
            ]);
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="invoice_preview.pdf"'
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Invoice Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate preview'], 500);
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

    /**
     * Consume article stock when creating an invoice
     */
    private function consumeArticleStock($article, $quantity)
    {
        // For material-type articles, reduce the corresponding material quantity
        if ($article->format_type == 1 && $article->smallMaterial) {
            $material = $article->smallMaterial;
            $material->quantity = max(0, $material->quantity - $quantity);
            $material->save();
        } elseif ($article->format_type == 2 && $article->largeFormatMaterial) {
            $material = $article->largeFormatMaterial;
            $material->quantity = max(0, $material->quantity - $quantity);
            $material->save();
        } elseif ($article->format_type == 3) {
            $material = \App\Models\OtherMaterial::where('article_id', $article->id)->first();
            if ($material) {
                $material->quantity = max(0, $material->quantity - $quantity);
                $material->save();
            }
        } else {
            // For regular product/service articles, create a consumption record
            // Create a consumption record (negative quantity in priemnica system)
            $consumptionPriemnica = \App\Models\Priemnica::create([
                'warehouse' => 1, // Default warehouse
                'client_id' => null,
                'comment' => "Invoice consumption - Article ID: {$article->id}"
            ]);
            
            // Attach the article with negative quantity to track consumption
            $consumptionPriemnica->articles()->attach($article->id, ['quantity' => -$quantity]);
        }
    }

    /**
     * Update a job within an invoice
     */
    public function updateInvoiceJob(Request $request, $fakturaId, $jobId)
    {
        try {
            DB::beginTransaction();

            // Validate the faktura exists and user has access
            $faktura = Faktura::findOrFail($fakturaId);
            
            // First check if the job exists
            $job = Job::find($jobId);
            if (!$job) {
                return response()->json([
                    'error' => 'Job not found',
                    'message' => "Job with ID {$jobId} does not exist"
                ], 404);
            }

            // Check if the job belongs to this faktura
            $jobBelongsToFaktura = Job::whereHas('invoice', function ($query) use ($faktura) {
                $query->where('faktura_id', $faktura->id);
            })->where('id', $jobId)->exists();

            if (!$jobBelongsToFaktura) {
                return response()->json([
                    'error' => 'Job access denied',
                    'message' => "Job with ID {$jobId} does not belong to the specified invoice"
                ], 403);
            }

            // Get the job with the relationship
            $job = Job::whereHas('invoice', function ($query) use ($faktura) {
                $query->where('faktura_id', $faktura->id);
            })->find($jobId);

            // Validate request data
            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'quantity' => 'sometimes|required|numeric|min:1',
                'copies' => 'sometimes|required|numeric|min:1',
                'salePrice' => 'sometimes|required|numeric|min:0',
                'width' => 'sometimes|numeric|min:0',
                'height' => 'sometimes|numeric|min:0',
                'status' => 'sometimes|string'
            ]);

            // Update the job with validated data
            $job->fill($validatedData);
            $job->save();

            DB::commit();

            return response()->json([
                'message' => 'Job updated successfully',
                'job' => $job->fresh()->append('totalPrice')
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating invoice job:', [
                'faktura_id' => $fakturaId,
                'job_id' => $jobId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update job',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Add a new trade item to an existing faktura
     */
    public function addTradeItem(Request $request, $fakturaId)
    {
        try {
            DB::beginTransaction();

            $faktura = Faktura::findOrFail($fakturaId);

            $validatedData = $request->validate([
                'article_id' => 'required|exists:article,id',
                'quantity' => 'required|numeric|min:1',
                'unit_price' => 'required|numeric|min:0',
                'vat_rate' => 'sometimes|numeric|min:0|max:100'
            ]);

            // Calculate totals
            $totalPrice = $validatedData['quantity'] * $validatedData['unit_price'];
            $vatRate = $validatedData['vat_rate'] ?? 18.00;
            $vatAmount = $totalPrice * ($vatRate / 100);

            $tradeItem = FakturaTradeItem::create([
                'faktura_id' => $faktura->id,
                'article_id' => $validatedData['article_id'],
                'quantity' => $validatedData['quantity'],
                'unit_price' => $validatedData['unit_price'],
                'total_price' => $totalPrice,
                'vat_rate' => $vatRate,
                'vat_amount' => $vatAmount
            ]);

            $tradeItem->load('article');

            DB::commit();

            return response()->json([
                'message' => 'Trade item added successfully',
                'trade_item' => [
                    'id' => $tradeItem->id,
                    'article_id' => $tradeItem->article_id,
                    'article_name' => $tradeItem->article->name,
                    'article_code' => $tradeItem->article->code ?? '',
                    'quantity' => $tradeItem->quantity,
                    'unit_price' => $tradeItem->unit_price,
                    'total_price' => $tradeItem->total_price,
                    'vat_rate' => $tradeItem->vat_rate,
                    'vat_amount' => $tradeItem->vat_amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error adding trade item:', [
                'faktura_id' => $fakturaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to add trade item',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an existing trade item
     */
    public function updateTradeItem(Request $request, $fakturaId, $tradeItemId)
    {
        try {
            DB::beginTransaction();

            $faktura = Faktura::findOrFail($fakturaId);
            $tradeItem = FakturaTradeItem::where('faktura_id', $faktura->id)
                ->findOrFail($tradeItemId);

            $validatedData = $request->validate([
                'quantity' => 'sometimes|required|numeric|min:1',
                'unit_price' => 'sometimes|required|numeric|min:0',
                'vat_rate' => 'sometimes|numeric|min:0|max:100'
            ]);

            // Update fields
            if (isset($validatedData['quantity'])) {
                $tradeItem->quantity = $validatedData['quantity'];
            }
            if (isset($validatedData['unit_price'])) {
                $tradeItem->unit_price = $validatedData['unit_price'];
            }
            if (isset($validatedData['vat_rate'])) {
                $tradeItem->vat_rate = $validatedData['vat_rate'];
            }

            // Recalculate totals
            $tradeItem->total_price = $tradeItem->quantity * $tradeItem->unit_price;
            $tradeItem->vat_amount = $tradeItem->total_price * ($tradeItem->vat_rate / 100);
            $tradeItem->save();

            $tradeItem->load('article');

            DB::commit();

            return response()->json([
                'message' => 'Trade item updated successfully',
                'trade_item' => [
                    'id' => $tradeItem->id,
                    'article_id' => $tradeItem->article_id,
                    'article_name' => $tradeItem->article->name,
                    'article_code' => $tradeItem->article->code ?? '',
                    'quantity' => $tradeItem->quantity,
                    'unit_price' => $tradeItem->unit_price,
                    'total_price' => $tradeItem->total_price,
                    'vat_rate' => $tradeItem->vat_rate,
                    'vat_amount' => $tradeItem->vat_amount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating trade item:', [
                'faktura_id' => $fakturaId,
                'trade_item_id' => $tradeItemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update trade item',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete a trade item
     */
    public function deleteTradeItem($fakturaId, $tradeItemId)
    {
        try {
            DB::beginTransaction();

            $faktura = Faktura::findOrFail($fakturaId);
            $tradeItem = FakturaTradeItem::where('faktura_id', $faktura->id)
                ->findOrFail($tradeItemId);

            $tradeItem->delete();

            DB::commit();

            return response()->json([
                'message' => 'Trade item deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error deleting trade item:', [
                'faktura_id' => $fakturaId,
                'trade_item_id' => $tradeItemId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to delete trade item',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get available articles for trade items
     */
    public function getAvailableArticles()
    {
        try {
            $articles = \App\Models\Article::select('id', 'name', 'code', 'price_1', 'tax_type')
                ->orderBy('name')
                ->get()
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'name' => $article->name,
                        'code' => $article->code,
                        'price' => $article->price_1,
                        'tax_type' => $article->tax_type
                    ];
                });

            return response()->json($articles);

        } catch (\Exception $e) {
            \Log::error('Error fetching articles:', ['error' => $e->getMessage()]);
            
            return response()->json([
                'error' => 'Failed to fetch articles',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice title
     */
    public function updateInvoiceTitle(Request $request, $fakturaId, $invoiceId)
    {
        try {
            DB::beginTransaction();

            $faktura = Faktura::findOrFail($fakturaId);
            $invoice = Invoice::where('faktura_id', $faktura->id)
                ->findOrFail($invoiceId);

            $validatedData = $request->validate([
                'invoice_title' => 'required|string|max:255'
            ]);

            $invoice->invoice_title = $validatedData['invoice_title'];
            $invoice->save();

            DB::commit();

            return response()->json([
                'message' => 'Invoice title updated successfully',
                'invoice' => $invoice
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating invoice title:', [
                'faktura_id' => $fakturaId,
                'invoice_id' => $invoiceId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update invoice title',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update invoice date
     */
    public function updateInvoiceDate(Request $request, $fakturaId)
    {
        try {
            DB::beginTransaction();

            $faktura = Faktura::findOrFail($fakturaId);

            $validatedData = $request->validate([
                'created' => 'required|date'
            ]);

            $faktura->created_at = $validatedData['created'];
            $faktura->save();

            DB::commit();

            return response()->json([
                'message' => 'Invoice date updated successfully',
                'success' => true,
                'faktura' => $faktura
            ]);

        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('Error updating invoice date:', [
                'faktura_id' => $fakturaId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to update invoice date',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
