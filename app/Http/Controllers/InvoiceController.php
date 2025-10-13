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
use Illuminate\Support\Facades\Cache;
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
                        'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
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

            // Load thumbnails for all jobs
            $this->loadThumbnailsForInvoices($invoices->items());

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

            // Support Cyrillic letters and other Unicode characters
            // Remove only truly problematic characters, keep letters, numbers, spaces, and common punctuation
            $searchQuery = '%' . preg_replace('/[^\p{L}\p{N}\s\-_.,]/u', '', $searchQuery) . '%';

            $query->where(function ($query) use ($searchQuery) {
                $query->where('invoice_title', 'LIKE', $searchQuery)
                    ->orWhere('id', 'LIKE', $searchQuery)
                    ->orWhereHas('client', function ($subquery) use ($searchQuery) {
                        $subquery->where('name', 'LIKE', $searchQuery);
                    })
                    ->orWhereHas('user', function ($subquery) use ($searchQuery) {
                        $subquery->where('name', 'LIKE', $searchQuery);
                    });
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
        if ($request->has('createdBy') && $request->input('createdBy') !== 'All') {
            $createdBy = $request->input('createdBy');

            $query->whereHas('user', function ($subquery) use ($createdBy) {
                $subquery->where('id', $createdBy);
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
        // Simple idempotency: prevent duplicate creations from the same user with same payload within 5 seconds
        try {
            $payloadHash = hash('sha256', json_encode([
                'user' => Auth::id(),
                'title' => $request->input('invoice_title'),
                'client' => $request->input('client_id'),
                'contact' => $request->input('contact_id'),
                'start' => $request->input('start_date'),
                'end' => $request->input('end_date'),
                'jobs' => array_map(function ($j) { return ['id' => $j['id'] ?? null]; }, (array) $request->input('jobs', [])),
            ]));
            $lockKey = 'orders:create:lock:' . $payloadHash;
            $lock = Cache::lock($lockKey, 5);
            if (!$lock->get()) {
                return response()->json([
                    'error' => 'Duplicate create detected. Please wait a moment.',
                    'message' => 'Order is already being created'
                ], 409);
            }
        } catch (\Throwable $t) {
            // If locking fails, continue without blocking (fail-open) but log it
            Log::warning('Idempotency lock failed', ['error' => $t->getMessage()]);
        }
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
            $unit = $job['unit'] ?? 'ком'; // Default unit if not provided

            // Attach job to invoice
            $invoice->jobs()->attach($job_id);
            
            // Update job's unit directly on the job model
            Job::where('id', $job_id)->update(['unit' => $unit]);
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
                                \Log::notice("Stock validation disabled: no available articles with sufficient stock in category {$article->pivot->category_id} for catalog item '{$job->catalogItem->name}'.");
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
                            \Log::notice("Stock validation disabled: '{$job->name}' requires {$neededQuantity} of '{$article->name}', available {$article->getCurrentStock()}.");
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
                                \Log::notice("Stock validation disabled: catalog item {$job->catalogItem->name} with material {$large_material->name} needs {$units}, available {$large_material->quantity}.");
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
                                \Log::notice("Stock validation disabled: catalog item '{$job->catalogItem->name}' material '{$small_material->name}' needs {$units}, available {$small_material->quantity}.");
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
            // Stock validation disabled: do not throw collected messages
            if (!empty($errorMessages)) {
                \Log::notice('Stock validation disabled; collected messages suppressed.', $errorMessages);
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
                    'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
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

    /**
     * Delete an invoice in Not started yet status with all related data.
     * Only admin users can perform this action and must provide a frontend passcode.
     */
    public function destroy(Request $request, $id)
    {
        try {
            // Authorization: only Admin role
            if (!auth()->user() || !auth()->user()->hasRole('Admin')) {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            // Simple in-house passcode verification (frontend-provided). This is intentionally not secure for internet use.
            $request->validate([
                'passcode' => 'required|string'
            ]);

            if ($request->input('passcode') !== '9632') {
                return response()->json(['error' => 'Invalid passcode'], 422);
            }

            $invoice = Invoice::with(['jobs', 'historyLogs'])->findOrFail($id);

            // Only allow delete for specific status
            if (strtolower((string)$invoice->status) !== strtolower('Not started yet')) {
                return response()->json(['error' => 'Only orders with status Not started yet can be deleted'], 409);
            }

            DB::beginTransaction();

            // Detach jobs from invoice and collect for deletion
            $jobs = $invoice->jobs()->get();
            $jobIds = $jobs->pluck('id')->all();
            $invoice->jobs()->detach();

            // Delete history logs for this invoice
            $invoice->historyLogs()->delete();

            // Delete the invoice itself
            $invoice->delete();

            DB::commit();

            // After the invoice is deleted, delete the jobs and their related artifacts using existing JobController logic contract
            // We inline a simplified cleanup here to avoid controller coupling
            try {
                foreach ($jobIds as $jobId) {
                    try {
                        // Use direct cleanup similar to JobController::destroy without importing controller
                        $job = Job::find($jobId);
                        if (!$job) continue;

                        // Store original files for cleanup, then remove DB records
                        $originalFiles = $job->getOriginalFiles();

                        // Remove pivot to actions and actions
                        $actionIds = DB::table('job_job_action')
                            ->where('job_id', $job->id)
                            ->pluck('job_action_id');

                        DB::table('job_job_action')->where('job_id', $job->id)->delete();
                        DB::table('job_actions')->whereIn('id', $actionIds)->delete();

                        // Finally delete job
                        $job->delete();

                        // Best-effort thumbnails/original files cleanup
                        try {
                            $templateStorage = app(\App\Services\TemplateStorageService::class);
                            if (!empty($originalFiles)) {
                                foreach ($originalFiles as $filePath) {
                                    if ($filePath && str_starts_with($filePath, 'job-originals/')) {
                                        try { $templateStorage->deleteTemplate($filePath); } catch (\Exception $e) { /* ignore */ }
                                    }
                                }
                            }
                            // Remove thumbnails
                            try {
                                $thumbs = $templateStorage->getDisk()->files('job-thumbnails');
                                foreach ($thumbs as $thumbFile) {
                                    $base = basename($thumbFile);
                                    if (strpos($base, 'job_' . $jobId . '_') === 0) {
                                        try { $templateStorage->getDisk()->delete($thumbFile); } catch (\Exception $e) { /* ignore */ }
                                    }
                                }
                            } catch (\Exception $e) { /* ignore */ }
                        } catch (\Exception $e) {
                            // ignore storage cleanup failures
                        }
                    } catch (\Throwable $jobDelErr) {
                        Log::warning('Failed to delete job during invoice destroy', ['job_id' => $jobId, 'error' => $jobDelErr->getMessage()]);
                    }
                }
            } catch (\Throwable $e) {
                // ignore
            }

            return response()->json(['message' => 'Invoice and related data deleted successfully']);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['error' => 'Invoice not found'], 404);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Invoice destroy failed', ['invoice_id' => $id, 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Failed to delete invoice'], 500);
        }
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
                    'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
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
                    'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
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

        // Load thumbnails for all jobs
        $this->loadThumbnailsForInvoices($invoices->items());

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

    public function getAvailableUsers()
    {
        $users = \App\Models\User::select('id', 'name')
            ->orderBy('name')
            ->get();

        return response()->json($users);
    }

    /**
     * Load thumbnails for all jobs in the given invoices
     */
    private function loadThumbnailsForInvoices($invoices)
    {
        foreach ($invoices as $invoice) {
            if ($invoice->jobs) {
                foreach ($invoice->jobs as $job) {
                    $job->thumbnails = $this->getJobThumbnails($job->id);
                }
            }
        }
    }

    /**
     * Get thumbnails for a specific job
     */
    private function getJobThumbnails($jobId)
    {
        try {
            $job = \App\Models\Job::find($jobId);
            if (!$job) {
                return [];
            }

            $originalFiles = $job->getOriginalFiles();
            $thumbnailDir = public_path('jobfiles/thumbnails/' . $jobId);
            $thumbnails = [];
            
            if (!is_dir($thumbnailDir)) {
                return [];
            }
            
            $thumbnailFiles = glob($thumbnailDir . '/*.png');
            
            // Group thumbnails by file index
            foreach ($originalFiles as $fileIndex => $originalFile) {
                $originalFileName = pathinfo(basename($originalFile), PATHINFO_FILENAME);
                $fileThumbnails = [];
                
                // Find all thumbnails for this file
                foreach ($thumbnailFiles as $thumbnailFile) {
                    $thumbnailFileName = basename($thumbnailFile);
                    
                    // Match thumbnails that belong to this file
                    if (strpos($thumbnailFileName, $originalFileName) !== false) {
                        $pageNumber = 1;
                        if (preg_match('/_page_(\d+)\.png$/', $thumbnailFileName, $matches)) {
                            $pageNumber = (int)$matches[1];
                        }
                        
                        $fileThumbnails[] = [
                            'file_name' => $thumbnailFileName,
                            'url' => '/jobfiles/thumbnails/' . $jobId . '/' . $thumbnailFileName,
                            'page_number' => $pageNumber,
                            'file_index' => $fileIndex,
                            'size' => filesize($thumbnailFile),
                            'modified' => filemtime($thumbnailFile)
                        ];
                    }
                }
                
                // Sort by page number
                usort($fileThumbnails, function($a, $b) {
                    return $a['page_number'] - $b['page_number'];
                });
                
                $thumbnails = array_merge($thumbnails, $fileThumbnails);
            }
            
            return $thumbnails;
        } catch (\Exception $e) {
            \Log::error('Error loading thumbnails for job ' . $jobId . ': ' . $e->getMessage());
            return [];
        }
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
                    'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown'
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
                    'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
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

    public function getInvoiceClient($id) {
        try {
            $invoice = Invoice::with('client')->findOrFail($id);
            
            return response()->json([
                'client_name' => $invoice->client ? $invoice->client->name : null,
                'client_id' => $invoice->client ? $invoice->client->id : null
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invoice not found'], 404);
        }
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
            $query = Invoice::with([
                'jobs' => function ($query) {
                    $query->select([
                        'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                        'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                        'jobs.faktura_id'
                    ]);
                },
                'user', 
                'client'
            ])
                ->where('status', 'Completed') // Filter by 'Completed' status
                ->whereNull('faktura_id') // Only orders that haven't been fully invoiced
                ->where(function ($query) {
                    // Exclude orders where ALL jobs have been assigned to fakturas (fully split)
                    $query->whereDoesntHave('jobs') // Orders with no jobs (edge case)
                          ->orWhereHas('jobs', function ($jobQuery) {
                              // Or orders that have at least one job not assigned to a faktura
                              $jobQuery->whereNull('faktura_id');
                          });
                });

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
            $query = Invoice::with([
                'jobs' => function ($query) {
                    $query->select([
                        'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                        'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                        'jobs.faktura_id'
                    ]);
                },
                'user:id,name',
                'client:id,name'
            ])
                ->where('status', 'Completed') // Filter by 'Completed' status
                ->whereNull('faktura_id') // Only orders that haven't been fully invoiced
                ->where(function ($query) {
                    // Exclude orders where ALL jobs have been assigned to fakturas (fully split)
                    $query->whereDoesntHave('jobs') // Orders with no jobs (edge case)
                          ->orWhereHas('jobs', function ($jobQuery) {
                              // Or orders that have at least one job not assigned to a faktura
                              $jobQuery->whereNull('faktura_id');
                          });
                });

            // Apply search, client filter, and status filter
            $this->applySearch($query, $request, $request->input('status'));

            // Exclude completed orders for the individual client 'Физичко лице'
            $query->whereDoesntHave('client', function($q){
                $q->where('name', 'Физичко лице');
            });

            // Apply sort order
            $query->orderBy('created_at', $request->input('sortOrder', 'desc'));

            // Get paginated results
            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, min($perPage, 200));
            $invoices = $query->latest()->paginate($perPage);

            // Load thumbnails for all jobs
            $this->loadThumbnailsForInvoices($invoices->items());

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
                'jobs.client:id,name', // For split invoices
                'parentOrder:id,invoice_title', // For split invoices
                'parentOrder.client:id,name', // For split invoices
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
                $query->where(function ($query) use ($client) {
                    // Filter by regular invoices client
                    $query->whereHas('invoices.client', function ($q) use ($client) {
                        $q->where('name', $client);
                    })
                    // Or filter by split invoice jobs client
                    ->orWhereHas('jobs.client', function ($q) use ($client) {
                        $q->where('name', $client);
                    })
                    // Or filter by parent order client for split invoices
                    ->orWhereHas('parentOrder.client', function ($q) use ($client) {
                        $q->where('name', $client);
                    });
                });
            }

            // Apply sort order
            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);

            // Apply pagination with configurable results per page
            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, min($perPage, 200));
            $fakturas = $query->paginate($perPage);

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

            // Get faktura overrides if faktura exists
            $fakturaOverrides = [];
            if ($isAlreadyGenerated && $firstInvoice->faktura) {
                $fakturaOverrides = $firstInvoice->faktura->faktura_overrides ?? [];
            }
            
            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => $transformedInvoices,
                'additionalServices' => is_array($additionalServices) ? $additionalServices : [],
                'jobUnits' => $jobUnits,
                'fakturaOverrides' => $fakturaOverrides,
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
                        'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
                        'jobs.small_material_id', 'jobs.large_material_id', 'jobs.salePrice'
                    ])->with(['articles' => function ($a) {
                        // Ensure unit flags and tax type are present in payload
                        $a->select('article.id', 'article.name', 'article.code', 'article.tax_type', 'article.in_square_meters', 'article.in_pieces', 'article.in_kilograms', 'article.in_meters');
                    }]);
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
        $additionalServices = $request->input('additional_services', []);
        $createdAtInput = $request->input('created_at');
        $mergeGroups = $request->input('merge_groups', []);
        $splitGroups = $request->input('split_groups', []);
        $jobUnits = $request->input('job_units', []);
        
        // Capture overrides for faktura display
        $fakturaOverrides = [
            'order_titles' => $request->input('order_title_overrides', []),
            'job_names' => $request->input('job_name_overrides', []),
            'job_quantities' => $request->input('job_quantity_overrides', [])
        ];
        
        // Debug logging
        \Log::info('GenerateInvoice - Received faktura overrides', [
            'overrides' => $fakturaOverrides,
            'request_data' => $request->only(['order_title_overrides', 'job_name_overrides', 'job_quantity_overrides'])
        ]);

        // Check if this is a split invoice request
        if (!empty($splitGroups) && is_array($splitGroups)) {
            return $this->generateSplitInvoices($request, $splitGroups);
        }

        // Validate that we have at least one order id
        if (empty($invoiceIds) || !is_array($invoiceIds)) {
            return response()->json(['error' => 'No invoices selected'], 400);
        }

        try {
            // Start a database transaction
            DB::beginTransaction();

            // Retrieve the Invoice instances based on the provided IDs
            $invoices = Invoice::with([
                'jobs.actions',
                'jobs.articles' => function ($q) {
                    $q->select('article.id', 'article.name', 'article.price_1', 'article.tax_type', 'article.in_square_meters', 'article.in_pieces', 'article.in_kilograms', 'article.in_meters');
                },
                'contact', 'user', 'client', 'article', 'client.clientCardStatement'
            ])
                ->find($invoiceIds);

            // Abort if none of the provided IDs resolved to invoices
            if (!$invoices || $invoices->count() === 0) {
                DB::rollBack();
                return response()->json(['error' => 'No valid invoices found for generation'], 400);
            }

            // Create a new Faktura instance (only after validation passes)
            $paymentDeadlineOverride = $request->input('payment_deadline_override');
            
            $faktura = Faktura::create([
                'isInvoiced' => 1,
                'comment' => $comment,
                'created_by' => auth()->id(),
                'payment_deadline_override' => is_numeric($paymentDeadlineOverride) ? (int)$paymentDeadlineOverride : null,
                'faktura_overrides' => $fakturaOverrides
            ]);
            
            // Debug logging for faktura creation
            \Log::info('GenerateInvoice - Faktura created with overrides', [
                'faktura_id' => $faktura->id,
                'faktura_overrides' => $faktura->faktura_overrides,
                'overrides_count' => [
                    'order_titles' => count($fakturaOverrides['order_titles'] ?? []),
                    'job_names' => count($fakturaOverrides['job_names'] ?? []),
                    'job_quantities' => count($fakturaOverrides['job_quantities'] ?? [])
                ]
            ]);

            // Persist merge groups if provided (lightweight JSON structure)
            if (!empty($mergeGroups) && is_array($mergeGroups)) {
                try {
                    $faktura->merge_groups = $mergeGroups;
                    $faktura->save();
                } catch (\Throwable $e) {
                    \Log::warning('Failed to persist merge_groups on faktura', [
                        'faktura_id' => $faktura->id,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // If client provided a custom created_at date, apply it to the Faktura
            if (!empty($createdAtInput)) {
                try {
                    // Parse date as start of day in UTC to avoid timezone conversion issues
                    $parsedDate = \Carbon\Carbon::parse($createdAtInput, 'UTC')->startOfDay();
                    \Log::info('Setting faktura created_at', [
                        'input' => $createdAtInput,
                        'parsed' => $parsedDate->toDateTimeString(),
                        'parsed_iso' => $parsedDate->toISOString()
                    ]);
                    $faktura->created_at = $parsedDate;
                    $faktura->save();
                    \Log::info('Faktura saved with created_at', [
                        'faktura_id' => $faktura->id,
                        'created_at' => $faktura->created_at->toDateTimeString()
                    ]);
                } catch (\Throwable $e) {
                    // If parsing fails, keep default timestamp
                }
            }

            // Associate the retrieved Invoice instances with the new Faktura
            foreach ($invoices as $invoice) {
                $invoice->faktura_id = $faktura->id;
                $invoice->save();
            }

            // Update job units directly on job model if provided
            if (!empty($jobUnits) && is_array($jobUnits)) {
                foreach ($jobUnits as $unitData) {
                    if (isset($unitData['id']) && !empty($unitData['unit'])) {
                        $jobId = $unitData['id'];
                        $unit = $unitData['unit'];
                        
                        // Update the job's unit directly
                        Job::where('id', $jobId)->update(['unit' => $unit]);
                    }
                }
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

            // Handle additional services if provided
            \Log::info('GenerateInvoice received additional services', [
                'count' => is_array($additionalServices) ? count($additionalServices) : 0,
                'services' => $additionalServices,
            ]);
            foreach ($additionalServices as $service) {
                \App\Models\AdditionalService::create([
                    'faktura_id' => $faktura->id,
                    'name' => $service['name'] ?? '',
                    'quantity' => $service['quantity'] ?? 0,
                    'unit' => $service['unit'] ?? 'ком',
                    'sale_price' => $service['sale_price'] ?? 0,
                    'vat_rate' => $service['vat_rate'] ?? 18,
                ]);
            }


            // Commit the transaction
            DB::commit();

            // Generate PDF for the created invoice
            $dns1d = new DNS1D();
            // If merge groups persisted on faktura (from request or pre-set), build merged jobs
            $mergeGroups = is_array($request->input('merge_groups')) ? $request->input('merge_groups') : ($faktura->merge_groups ?? []);
            $mergedJobsByInvoice = [];
            if (!empty($mergeGroups)) {
                foreach ($invoices as $inv) {
                    $mergedJobsByInvoice[$inv->id] = $inv->jobs->map(function ($j) { return $j->toArray(); })->all();
                }
                $jobLookup = [];
                $jobInvoiceLookup = [];
                foreach ($invoices as $inv) {
                    foreach ($inv->jobs as $j) { $jobLookup[$j->id] = $j->toArray(); $jobInvoiceLookup[$j->id] = $inv->id; }
                }
                foreach ($mergeGroups as $grp) {
                    $ids = isset($grp['job_ids']) && is_array($grp['job_ids']) ? $grp['job_ids'] : [];
                    $ids = array_values(array_filter($ids, fn($v) => isset($jobLookup[$v])));
                    if (count($ids) < 2) continue;
                    $first = $jobLookup[$ids[0]];
                    $sumQty = 0; $sumArea = 0.0;
                    foreach ($ids as $jid) { $jj = $jobLookup[$jid]; $sumQty += (int)($jj['quantity'] ?? 0); $sumArea += (float)($jj['computed_total_area_m2'] ?? 0); }
                    
                    // Determine the unit for the merged job from merge group or from constituent jobs
                    $mergedUnit = null;
                    if (isset($grp['unit']) && !empty($grp['unit'])) {
                        // Use unit from merge group if specified
                        $mergedUnit = $grp['unit'];
                    } else {
                        // Determine unit from constituent jobs - use the unit from the first job that has a custom unit,
                        // or fall back to the first job's unit
                        foreach ($ids as $jid) {
                            $customUnit = null;
                            if ($jobUnits && is_array($jobUnits)) {
                                foreach ($jobUnits as $unitData) {
                                    if (isset($unitData['id']) && $unitData['id'] == $jid && !empty($unitData['unit'])) {
                                        $customUnit = $unitData['unit'];
                                        break;
                                    }
                                }
                            }
                            if ($customUnit) {
                                $mergedUnit = $customUnit;
                                break;
                            }
                        }
                        // If no custom unit found, use the first job's default unit
                        if (!$mergedUnit) {
                            $mergedUnit = 'ком'; // Default fallback
                        }
                    }
                    
                    $merged = $first; $merged['id'] = $first['id'];
                    $merged['name'] = $grp['title'] ?? ($first['name'] ?? null);
                    $merged['quantity'] = isset($grp['quantity']) ? (float)$grp['quantity'] : $sumQty;
                    $merged['salePrice'] = isset($grp['sale_price']) ? (float)$grp['sale_price'] : (float)($first['salePrice'] ?? 0);
                    $merged['computed_total_area_m2'] = $sumArea; $merged['merged'] = true; $merged['merged_job_ids'] = $ids;
                    $merged['unit'] = $mergedUnit; // Add the determined unit to the merged job
                    $holderInvoiceId = $jobInvoiceLookup[$ids[0]] ?? $invoices->first()->id;
                    foreach ($ids as $jid) {
                        $invId = $jobInvoiceLookup[$jid];
                        $mergedJobsByInvoice[$invId] = array_values(array_filter($mergedJobsByInvoice[$invId], function ($jrow) use ($jid) { return ($jrow['id'] ?? null) !== $jid; }));
                    }
                    $mergedJobsByInvoice[$holderInvoiceId][] = $merged;
                }
            }

            $transformedInvoices = $invoices->map(function ($invoice) use ($dns1d, $tradeItems, $comment, $faktura, $mergedJobsByInvoice, $paymentDeadlineOverride) {
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
                })->toArray();

                // Return a new array for this invoice with the required fields
                $invoiceArr = $invoice->toArray();
                if (!empty($mergedJobsByInvoice) && isset($mergedJobsByInvoice[$invoice->id])) {
                    $invoiceArr['jobs'] = $mergedJobsByInvoice[$invoice->id];
                }
                return array_merge(
                    $invoiceArr,
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
                        'payment_deadline_override' => is_numeric($paymentDeadlineOverride) ? (int)$paymentDeadlineOverride : null,
                    ]
                );
            })->toArray();

            // Ensure trade items are shown only once (per faktura), not repeated per order
            if (is_array($transformedInvoices) && count($transformedInvoices) > 1) {
                for ($i = 1; $i < count($transformedInvoices); $i++) {
                    if (isset($transformedInvoices[$i]['trade_items'])) {
                        $transformedInvoices[$i]['trade_items'] = [];
                    }
                }
            }

            // Debug: return JSON payload when requested
            if ($request->boolean('debug')) {
                return response()->json(['invoices' => $transformedInvoices], 200);
            }

            // Generate PDF using v2 template for preview
            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => $transformedInvoices,
                'additionalServices' => is_array($additionalServices) ? $additionalServices : [],
                'jobUnits' => $jobUnits,
                'fakturaOverrides' => $fakturaOverrides,
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'chroot' => storage_path('fonts'),
                'dpi' => 150,
            ]);

            // Return both: PDF in a new tab and a small JSON for client redirect
            if ($request->wantsJson() || $request->boolean('return_meta')) {
                return response()->json([
                    'success' => true,
                    'faktura_id' => $faktura->id,
                    'pdf' => base64_encode($pdf->output()),
                ]);
            }

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

    public function updateFakturaOverrides(Request $request, $fakturaId)
    {
        try {
            $faktura = Faktura::findOrFail($fakturaId);
            $overrides = $request->input('faktura_overrides', []);
            
            $faktura->faktura_overrides = $overrides;
            $faktura->save();
            
            return response()->json([
                'success' => true,
                'message' => 'Faktura overrides updated successfully'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Error updating faktura overrides', [
                'faktura_id' => $fakturaId,
                'error' => $e->getMessage()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to update faktura overrides'
            ], 500);
        }
    }

    private function generateSplitInvoices(Request $request, array $splitGroups)
    {
        $comment = $request->input('comment');
        $tradeItems = $request->input('trade_items', []);
        $createdAtInput = $request->input('created_at');
        $paymentDeadlineOverride = $request->input('payment_deadline_override');
        $mergeGroups = $request->input('merge_groups', []);
        
        // Capture overrides for faktura display
        $fakturaOverrides = [
            'order_titles' => $request->input('order_title_overrides', []),
            'job_names' => $request->input('job_name_overrides', []),
            'job_quantities' => $request->input('job_quantity_overrides', [])
        ];

        try {
            DB::beginTransaction();

            $generatedInvoices = [];
            $parentOrderIds = [];

            foreach ($splitGroups as $index => $splitGroup) {
                $jobIds = $splitGroup['job_ids'] ?? [];
                $clientOverride = $splitGroup['client'] ?? null;

                if (empty($jobIds)) {
                    continue; // Skip empty groups
                }

                // Get jobs for this split group (can be from existing orders)
                $jobs = Job::with(['client.contacts', 'client.clientCardStatement'])
                    ->whereIn('id', $jobIds)
                    ->get();

                if ($jobs->isEmpty()) {
                    \Log::warning('No jobs found for split generation', ['index' => $index, 'job_ids' => $jobIds]);
                    continue;
                }

                // Get the parent order ID from the first job
                $firstJob = $jobs->first();
                $parentOrderId = null;
                
                // Find parent order through pivot table
                $parentOrder = DB::table('invoice_job')
                    ->where('job_id', $firstJob->id)
                    ->first();
                
                if ($parentOrder) {
                    $parentOrderId = $parentOrder->invoice_id;
                    $parentOrderIds[] = $parentOrderId;
                }

                // Get client information for this split group
                $client = $firstJob->client;
                
                // If client override is specified, try to find that client
                if ($clientOverride && $clientOverride !== ($client ? $client->name : '')) {
                    $overrideClient = \App\Models\Client::where('name', $clientOverride)->first();
                    if ($overrideClient) {
                        $client = $overrideClient;
                    }
                }
                
                $primaryContact = $client ? $client->contacts()->first() : null;

                // Create Faktura for this split group
                $faktura = Faktura::create([
                    'isInvoiced' => 1,
                    'comment' => $comment,
                    'created_by' => auth()->id(),
                    'payment_deadline_override' => is_numeric($paymentDeadlineOverride) ? (int)$paymentDeadlineOverride : null,
                    'is_split_invoice' => true,
                    'split_group_identifier' => 'group_' . ($index + 1),
                    'parent_order_id' => $parentOrderId,
                    'faktura_overrides' => $fakturaOverrides
                ]);

                // Apply custom created_at if provided
                if (!empty($createdAtInput)) {
                    try {
                        $parsedDate = \Carbon\Carbon::parse($createdAtInput, 'UTC')->startOfDay();
                        $faktura->created_at = $parsedDate;
                        $faktura->save();
                    } catch (\Throwable $e) {
                        // Keep default timestamp if parsing fails
                    }
                }

                // Update jobs to link them to this faktura and handle client override
                foreach ($jobs as $job) {
                    $updateData = ['faktura_id' => $faktura->id];
                    
                    // If client override is specified, update the job's client_id
                    if ($clientOverride && $client && $client->id !== $job->client_id) {
                        $updateData['client_id'] = $client->id;
                        \Log::info('Updated job client for split invoice', [
                            'job_id' => $job->id,
                            'original_client_id' => $job->client_id,
                            'new_client_id' => $client->id,
                            'faktura_id' => $faktura->id
                        ]);
                    }
                    
                    $job->update($updateData);
                }

                // Handle trade items (distribute equally among split groups or assign to first group only)
                if ($index === 0 && !empty($tradeItems)) {
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
                }

                $generatedInvoices[] = [
                    'faktura_id' => $faktura->id,
                    'split_group' => $index + 1,
                    'client' => $client ? $client->name : 'Unknown Client',
                    'job_count' => count($jobIds),
                    'parent_order_id' => $parentOrderId
                ];
            }

            DB::commit();

            // Generate PDFs for each split invoice
            $pdfUrls = [];
            foreach ($generatedInvoices as $invoiceInfo) {
                try {
                    $faktura = Faktura::with(['parentOrder.client', 'parentOrder.contact', 'parentOrder.user'])
                        ->find($invoiceInfo['faktura_id']);
                    
                    if ($faktura) {
                        $pdf = $this->generateSplitInvoicePdf($faktura, $tradeItems, $comment, $invoiceInfo['split_group'], $jobUnits);
                        $pdfUrls[] = [
                            'faktura_id' => $faktura->id,
                            'pdf' => base64_encode($pdf->output()),
                            'split_group' => $invoiceInfo['split_group']
                        ];
                    }
                } catch (\Exception $e) {
                    Log::error('Failed to generate PDF for split invoice: ' . $e->getMessage());
                }
            }

            if ($request->wantsJson() || $request->boolean('return_meta')) {
                return response()->json([
                    'success' => true,
                    'is_split' => true,
                    'invoices' => $generatedInvoices,
                    'pdfs' => $pdfUrls,
                    'parent_order_ids' => array_unique($parentOrderIds)
                ]);
            }

            // For non-JSON requests, return the first PDF
            if (!empty($pdfUrls)) {
                $firstPdf = $pdfUrls[0];
                return response(base64_decode($firstPdf['pdf']), 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="split_invoice_' . $firstPdf['faktura_id'] . '.pdf"'
                ]);
            }

            return response()->json(['error' => 'No PDFs generated'], 500);

        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Generate Split Invoices Error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to create split invoices: ' . $e->getMessage()], 500);
        }
    }

    private function generateSplitInvoicePdf($faktura, $tradeItems = [], $comment = '', $splitGroup = null, $jobUnits = [], $mergeGroups = [])
    {
        $dns1d = new DNS1D();
        
        // Get jobs assigned to this faktura
        $jobs = Job::with(['actions', 'articles', 'client', 'client.clientCardStatement'])
            ->where('faktura_id', $faktura->id)
            ->get();

        if ($jobs->isEmpty()) {
            throw new \Exception('No jobs found for faktura ' . $faktura->id);
        }

        // Get parent order information
        $parentOrder = $faktura->parentOrder;
        if (!$parentOrder) {
            throw new \Exception('Parent order not found for split invoice');
        }

        // Load parent order relationships
        $parentOrder->load(['client', 'contact', 'user', 'client.clientCardStatement']);

        // Create a virtual invoice structure for PDF generation
        $barcodeString = $parentOrder->id . '-' . date('m-Y', strtotime($parentOrder->end_date));
        $barcodeImage = base64_encode($dns1d->getBarcodePNG($barcodeString, 'C128'));

        // Calculate totals from jobs assigned to this faktura
        $totalSalePrice = $jobs->sum('salePrice');
        $totalCopies = $jobs->sum('copies');

        $taxRate = 0.18;
        $priceWithTax = $totalSalePrice * (1 + $taxRate);
        $taxAmount = $totalSalePrice * $taxRate;

        // Round values for consistency
        $totalSalePriceRounded = round($totalSalePrice, 2);
        $taxAmountRounded = round($taxAmount, 2);
        $priceWithTaxRounded = round($priceWithTax, 2);
        $vatBreakdown = [18 => $taxAmountRounded];

        // Create invoice data structure
        $invoiceData = [
            'id' => $parentOrder->id,
            'invoice_title' => $parentOrder->invoice_title,
            'start_date' => $parentOrder->start_date,
            'end_date' => $parentOrder->end_date,
            'client' => $parentOrder->client ? $parentOrder->client->toArray() : null,
            'contact' => $parentOrder->contact ? $parentOrder->contact->toArray() : null,
            'user' => $parentOrder->user ? $parentOrder->user->toArray() : null,
            'jobs' => $jobs->toArray(),
            'barcodeImage' => $barcodeImage,
            'totalSalePrice' => $totalSalePriceRounded,
            'taxRate' => $taxRate * 100,
            'priceWithTax' => $priceWithTaxRounded,
            'taxAmount' => $taxAmountRounded,
            'copies' => $totalCopies,
            // Additional fields for template compatibility
            'subtotal_without_vat' => $totalSalePriceRounded,
            'vat_breakdown' => $vatBreakdown,
            'total_with_vat' => $priceWithTaxRounded,
            'trade_items' => $tradeItems,
            'comment' => $comment,
            'generated_at' => $faktura->created_at->toDateTimeString(),
            'faktura_id' => $faktura->id,
            'split_group_identifier' => $faktura->split_group_identifier,
            'merge_groups' => $mergeGroups
        ];

        return PDF::loadView('invoices.outgoing_invoice_v2', [
            'invoices' => [$invoiceData],
            'additionalServices' => ($faktura->additionalServices ?? collect())->toArray(),
            'jobUnits' => $jobUnits,
            'fakturaOverrides' => $faktura->faktura_overrides ?? [],
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
            'dpi' => 150,
        ]);
    }

    public function attachOrders(Request $request, $fakturaId)
    {
        $orderIds = $request->input('orders', []);
        try {
            if (empty($orderIds) || !is_array($orderIds)) {
                return response()->json(['error' => 'No orders provided'], 400);
            }
            $faktura = Faktura::findOrFail($fakturaId);
            // Fetch only invoices that are not already attached to a faktura
            $invoices = Invoice::with([
                'jobs.actions',
                'jobs.articles' => function ($q) {
                    $q->select('article.id', 'article.name', 'article.code', 'article.tax_type', 'article.in_square_meters', 'article.in_pieces', 'article.in_kilograms', 'article.in_meters');
                },
                'jobs.small_material.smallFormatMaterial',
                'user:id,name',
                'client:id,name',
                'article',
            ])->whereNull('faktura_id')->whereIn('id', $orderIds)->get();
            if ($invoices->isEmpty()) {
                return response()->json(['error' => 'No attachable orders found'], 400);
            }
            foreach ($invoices as $inv) {
                $inv->faktura_id = $faktura->id;
                $inv->save();
            }
            // Prepare client payload similar to showGenerateInvoice
            $invoices->each(function ($invoice) {
                $invoice->jobs->each(function ($job) {
                    $job->append('totalPrice');
                });
            });
            $invoiceData = $invoices->reduce(function ($acc, $invoice) {
                $acc[] = [
                    'id' => $invoice->id,
                    'invoice_title' => $invoice->invoice_title,
                    'client' => optional($invoice->client)->name,
                    'jobs' => $invoice->jobs,
                    'user' => optional($invoice->user)->name,
                    'start_date' => $invoice->start_date,
                    'end_date' => $invoice->end_date,
                    'status' => $invoice->status,
                ];
                return $acc;
            }, []);
            return response()->json(['success' => true, 'attached' => $invoices->pluck('id'), 'invoices' => $invoiceData]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to attach orders'], 500);
        }
    }

    public function updateJobUnit(Request $request, $invoiceId, $jobId)
    {
        $request->validate([
            'unit' => 'required|string|in:м,м²,кг,ком'
        ]);

        try {
            // Verify the job exists and belongs to the invoice
            $invoice = Invoice::findOrFail($invoiceId);
            $job = $invoice->jobs()->where('jobs.id', $jobId)->first();
            
            if (!$job) {
                return response()->json(['error' => 'Job not found in this invoice'], 404);
            }
            
            // Update the job's unit directly
            Job::where('id', $jobId)->update(['unit' => $request->input('unit')]);

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to update job unit'], 500);
        }
    }

    public function detachOrders(Request $request, $fakturaId)
    {
        $orderIds = $request->input('orders', []);
        try {
            if (empty($orderIds) || !is_array($orderIds)) {
                return response()->json(['error' => 'No orders provided'], 400);
            }
            $faktura = Faktura::findOrFail($fakturaId);
            // Only detach orders currently linked to this faktura
            $ids = array_values(array_filter((array)$orderIds, fn($v) => is_numeric($v)));
            if (empty($ids)) {
                return response()->json(['error' => 'No valid orders provided'], 400);
            }
            $invoices = Invoice::where('faktura_id', $faktura->id)->whereIn('id', $ids)->get();
            if ($invoices->isEmpty()) {
                return response()->json(['error' => 'No matching orders to detach'], 400);
            }
            foreach ($invoices as $inv) {
                $inv->faktura_id = null;
                $inv->save();
            }
            return response()->json(['success' => true, 'detached' => $invoices->pluck('id')]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Failed to detach orders'], 500);
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
                'jobs.small_material.smallFormatMaterial', // Direct jobs for split invoices
                'jobs.large_material',
                'jobs.actions',
                'jobs.client',
                'jobs.client.clientCardStatement',
                'parentOrder', // For split invoices, load parent order info
                'parentOrder.user',
                'parentOrder.client',
                'parentOrder.contact',
                'tradeItems.article'
            ])->findOrFail($id);
            
            // Handle regular invoices (complete orders)
            $faktura->invoices->each(function ($invoice)  {
                $invoice->jobs->each(function ($job) {
                    $job->append('totalPrice');
                });
            });

            // Handle direct jobs (from split invoices)
            $faktura->jobs->each(function ($job) {
                $job->append('totalPrice');
            });

            $invoiceData = collect();

            // Add regular complete orders
            $regularInvoices = $faktura->invoices->map(function ($invoice) use ($faktura) {
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
                    'createdBy' => $faktura->createdBy->name ?? 'System',
                    'created' => $faktura->created_at,
                    'is_split' => false
                ];
            });

            $invoiceData = $invoiceData->concat($regularInvoices);

            // Add split invoice data if this is a split invoice
            if ($faktura->is_split_invoice && $faktura->jobs->isNotEmpty()) {
                $parentOrder = $faktura->parentOrder;
                $splitJobs = $faktura->jobs;
                
                // Group jobs by client (in case of client overrides)
                $jobsByClient = $splitJobs->groupBy(function ($job) {
                    return $job->client ? $job->client->name : 'Unknown Client';
                });

                foreach ($jobsByClient as $clientName => $clientJobs) {
                    $firstJob = $clientJobs->first();
                    $client = $firstJob->client;
                    
                    $splitInvoiceData = [
                        'id' => $faktura->parent_order_id ?: ('split_' . $faktura->id),
                        'invoice_title' => $parentOrder ? $parentOrder->invoice_title : 'Split Invoice',
                        'client' => $clientName,
                        'client_data' => $client,
                        'jobs' => $clientJobs->values(),
                        'user' => $parentOrder ? $parentOrder->user->name : 'System',
                        'start_date' => $parentOrder ? $parentOrder->start_date : now()->toDateString(),
                        'end_date' => $parentOrder ? $parentOrder->end_date : now()->toDateString(),
                        'status' => 'Split Invoice',
                        'faktura_comment' => $faktura->comment,
                        'fakturaId' => $faktura->id,
                        'createdBy' => $faktura->createdBy->name ?? 'System',
                        'created' => $faktura->created_at,
                        'is_split' => true,
                        'parent_order_id' => $faktura->parent_order_id,
                        'split_group_identifier' => $faktura->split_group_identifier
                    ];
                    
                    $invoiceData->push($splitInvoiceData);
                }
            }

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

            // Additional services are also separate from invoices - they belong to the faktura
            $additionalServices = $faktura->additionalServices->map(function ($service) {
                return [
                    'id' => $service->id,
                    'name' => $service->name,
                    'quantity' => $service->quantity,
                    'unit' => $service->unit,
                    'sale_price' => $service->sale_price,
                    'vat_rate' => $service->vat_rate,
                    'total_price' => $service->total_price,
                    'vat_amount' => $service->vat_amount,
                    'total_price_with_vat' => $service->total_price_with_vat
                ];
            });

            return Inertia::render('Finance/Invoice', [
                'invoice' => $invoiceData,
                'faktura' => $faktura,
                'tradeItems' => $tradeItems,
                'additionalServices' => $additionalServices,
                'mergeGroups' => $faktura->merge_groups ?? []
            ]);
        } catch (Exception $e) {
            // If Faktura with the given ID is not found, return a valid Inertia response (redirect)
            return \Inertia\Inertia::location(url('/allInvoices'));
        }
    }

    public function getFakturaPdf(Request $request, $id)
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
                'jobs.small_material.smallFormatMaterial', // Direct jobs for split invoices
                'jobs.large_material',
                'jobs.actions',
                'jobs.client',
                'jobs.client.clientCardStatement',
                'parentOrder', // For split invoices, load parent order info
                'parentOrder.user',
                'parentOrder.client',
                'parentOrder.contact',
                'tradeItems.article'
            ])->findOrFail($id);

            // Check if this is a split invoice
            if ($faktura->is_split_invoice) {
                // For split invoices, use the existing generateSplitInvoicePdf method
                $tradeItems = $faktura->tradeItems->map(function ($item) {
                    return [
                        'article_id' => $item->article_id,
                        'article_name' => $item->article->name ?? 'Unknown Article',
                        'article_code' => $item->article->code ?? '',
                        'quantity' => $item->quantity,
                        'unit_price' => $item->unit_price,
                        'total_price' => $item->total_price,
                        'vat_rate' => $item->vat_rate,
                        'vat_amount' => $item->vat_amount
                    ];
                })->toArray();

                // Build job units array - use from request if provided, otherwise use defaults
                $requestJobUnits = $request->input('job_units', []);
                $jobUnits = [];
                
                if (!empty($requestJobUnits) && is_array($requestJobUnits)) {
                    // Use job units from request (frontend)
                    $jobUnits = $requestJobUnits;
                } else {
                    // Fallback to default units
                    $jobUnits = $faktura->jobs->map(function ($job) {
                        return [
                            'id' => $job->id,
                            'unit' => 'ком' // Default unit since we don't store it in DB
                        ];
                    })->toArray();
                }

                // Get merge groups from request if provided, otherwise use faktura's merge groups
                $requestMergeGroups = $request->input('merge_groups', []);
                $mergeGroups = !empty($requestMergeGroups) ? $requestMergeGroups : ($faktura->merge_groups ?? []);

                $pdf = $this->generateSplitInvoicePdf(
                    $faktura, 
                    $tradeItems, 
                    $faktura->comment, 
                    null,
                    $jobUnits,
                    $mergeGroups
                );
                
                return response($pdf->output(), 200)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="invoice_' . $faktura->id . '.pdf"');
            }

            // For regular invoices, redirect to the existing preview-invoice endpoint
            // This maintains consistency with the existing print functionality
            return redirect()->route('invoices.getGeneratedInvoice', $id);

        } catch (Exception $e) {
            Log::error('Error generating faktura PDF: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF'], 500);
        }
    }

    public function generateAllInvoicesPdf(Request $request)
    {
        $invoiceIds = explode(',', $request->query->all()['invoices']);

        $invoices = Invoice::with([
            'jobs' => function ($query) {
                $query->select([
                    'jobs.id', 'jobs.invoice_id', 'jobs.name', 'jobs.status', 'jobs.quantity', 'jobs.copies',
                    'jobs.file', 'jobs.originalFile', 'jobs.cuttingFiles', 'jobs.total_area_m2', 'jobs.dimensions_breakdown',
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
                'jobs.client:id,name', // For split invoices
                'parentOrder:id,invoice_title', // For split invoices
                'parentOrder.client:id,name', // For split invoices
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
                $query->where(function ($query) use ($client) {
                    // Filter by regular invoices client
                    $query->whereHas('invoices.client', function ($q) use ($client) {
                        $q->where('name', $client);
                    })
                    // Or filter by split invoice jobs client
                    ->orWhereHas('jobs.client', function ($q) use ($client) {
                        $q->where('name', $client);
                    })
                    // Or filter by parent order client for split invoices
                    ->orWhereHas('parentOrder.client', function ($q) use ($client) {
                        $q->where('name', $client);
                    });
                });
            }

            // Apply sort order
            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);

            // Apply pagination with configurable results per page on initial page
            $perPage = (int) $request->input('per_page', 10);
            $perPage = max(1, min($perPage, 200));
            $fakturas = $query->paginate($perPage);

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
            $additionalServices = $request->input('additional_services', []);
            $createdAtInput = $request->input('created_at');
            $mergeGroups = $request->input('merge_groups', []);
            $splitGroups = $request->input('split_groups', []);
            $paymentDeadlineOverride = $request->input('payment_deadline_override');
            $jobUnits = $request->input('job_units', []);
            
            // Capture overrides for faktura display
            $fakturaOverrides = [
                'order_titles' => $request->input('order_title_overrides', []),
                'job_names' => $request->input('job_name_overrides', []),
                'job_quantities' => $request->input('job_quantity_overrides', [])
            ];

            // Check if this is a split preview request
            if (!empty($splitGroups) && is_array($splitGroups)) {
                return $this->previewSplitInvoicesNew($request, $splitGroups);
            }

            // Basic validation
            if (empty($invoiceIds) || !is_array($invoiceIds)) {
                return response()->json(['error' => 'No invoices selected'], 400);
            }

            // Get the invoices for preview with necessary relationships (same as generateInvoice)
            $invoices = Invoice::with(['jobs.actions', 'contact', 'user', 'client', 'article', 'client.clientCardStatement'])
                ->find($invoiceIds);

            // If merge groups provided, prepare merged jobs view per-invoice for preview
            $mergedJobsByInvoice = [];
            if (!empty($mergeGroups) && is_array($mergeGroups)) {
                // Seed with original jobs as arrays
                foreach ($invoices as $inv) {
                    $mergedJobsByInvoice[$inv->id] = $inv->jobs->map(function ($j) {
                        return $j->toArray();
                    })->all();
                }
                // Build job lookup by id and invoice id
                $jobLookup = [];
                $jobInvoiceLookup = [];
                foreach ($invoices as $inv) {
                    foreach ($inv->jobs as $j) {
                        $jobLookup[$j->id] = $j->toArray();
                        $jobInvoiceLookup[$j->id] = $inv->id;
                    }
                }
                foreach ($mergeGroups as $grp) {
                    $ids = isset($grp['job_ids']) && is_array($grp['job_ids']) ? $grp['job_ids'] : [];
                    $ids = array_values(array_filter($ids, fn($v) => isset($jobLookup[$v])));
                    if (count($ids) < 2) continue;
                    $first = $jobLookup[$ids[0]];
                    // Compute sums
                    $sumQty = 0; $sumArea = 0.0;
                    foreach ($ids as $jid) {
                        $jj = $jobLookup[$jid];
                        $sumQty += (int)($jj['quantity'] ?? 0);
                        $sumArea += (float)($jj['computed_total_area_m2'] ?? 0);
                    }
                    
                    // Determine the unit for the merged job from merge group or from constituent jobs
                    $mergedUnit = null;
                    if (isset($grp['unit']) && !empty($grp['unit'])) {
                        // Use unit from merge group if specified
                        $mergedUnit = $grp['unit'];
                    } else {
                        // Determine unit from constituent jobs - use the unit from the first job that has a custom unit,
                        // or fall back to the first job's unit
                        foreach ($ids as $jid) {
                            $customUnit = null;
                            if ($jobUnits && is_array($jobUnits)) {
                                foreach ($jobUnits as $unitData) {
                                    if (isset($unitData['id']) && $unitData['id'] == $jid && !empty($unitData['unit'])) {
                                        $customUnit = $unitData['unit'];
                                        break;
                                    }
                                }
                            }
                            if ($customUnit) {
                                $mergedUnit = $customUnit;
                                break;
                            }
                        }
                        // If no custom unit found, use the first job's default unit
                        if (!$mergedUnit) {
                            $mergedUnit = 'ком'; // Default fallback
                        }
                    }
                    
                    // Create merged job based on first
                    $merged = $first;
                    $merged['id'] = $first['id'];
                    // Allow explicit override from merge group payload
                    $merged['name'] = $grp['title'] ?? ($first['name'] ?? null);
                    $merged['quantity'] = isset($grp['quantity']) ? (float)$grp['quantity'] : $sumQty;
                    $merged['salePrice'] = isset($grp['sale_price']) ? (float)$grp['sale_price'] : (float)($first['salePrice'] ?? 0);
                    $merged['computed_total_area_m2'] = $sumArea;
                    $merged['merged'] = true;
                    $merged['merged_job_ids'] = $ids;
                    $merged['unit'] = $mergedUnit; // Add the determined unit to the merged job
                    // Holder is the first invoice in selection to keep deterministic
                    $holderInvoiceId = $jobInvoiceLookup[$ids[0]] ?? $invoices->first()->id;
                    // Remove all original jobs from their invoices
                    foreach ($ids as $jid) {
                        $invId = $jobInvoiceLookup[$jid];
                        $mergedJobsByInvoice[$invId] = array_values(array_filter($mergedJobsByInvoice[$invId], function ($jrow) use ($jid) {
                            return ($jrow['id'] ?? null) !== $jid;
                        }));
                    }
                    // Place merged job into holder invoice
                    $mergedJobsByInvoice[$holderInvoiceId][] = $merged;
                }
            }

            // Transform invoices for PDF generation (same as outgoingInvoicePdf)
            $dns1d = new DNS1D();
            $nextFakturaId = (int) (\App\Models\Faktura::max('id') ?? 0) + 1;
            $transformedInvoices = $invoices->map(function ($invoice) use ($dns1d, $tradeItems, $comment, $nextFakturaId, $createdAtInput, $mergedJobsByInvoice, $paymentDeadlineOverride) {
                // Compose barcode safely (GD may be missing in some environments)
                $period = $invoice->end_date ? date('m-Y', strtotime($invoice->end_date)) : date('m-Y');
                $barcodeString = $invoice->id . '-' . $period;
                $barcodeImage = null;
                try {
                    $png = $dns1d->getBarcodePNG($barcodeString, 'C128');
                    if (!empty($png)) {
                        $barcodeImage = base64_encode($png);
                    }
                } catch (\Throwable $e) {
                    $barcodeImage = null;
                }

                $totalCopies = (int) $invoice->jobs->sum('copies');

                // Dynamic VAT/subtotals from job_articles + trade items
                $taxMap = [1 => 18, 2 => 5, 3 => 10];
                $materialsSubtotal = 0.0;
                $materialsVat = 0.0;
                $vatBreakdown = [5 => 0.0, 10 => 0.0, 18 => 0.0];
                foreach ($invoice->jobs as $job) {
                    foreach ($job->articles as $article) {
                        $qty = (float) ($article->pivot->quantity ?? 0);
                        $unitPrice = (float) ($article->price_1 ?? 0);
                        $line = $qty * $unitPrice;
                        $materialsSubtotal += $line;
                        $rate = (float) ($taxMap[$article->tax_type] ?? 0);
                        $lineVat = $line * ($rate / 100);
                        $materialsVat += $lineVat;
                        if (isset($vatBreakdown[(int)$rate])) {
                            $vatBreakdown[(int)$rate] += $lineVat;
                        }
                    }
                }

                // Add trade items contribution
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
                $tradeSubtotal = (float) $invoiceTradeItems->sum('total_price');
                $tradeVat = (float) $invoiceTradeItems->sum('vat_amount');
                // Add trade VAT to breakdown by their explicit vat_rate
                foreach ($invoiceTradeItems as $ti) {
                    $vr = (int)($ti['vat_rate'] ?? 0);
                    if (isset($vatBreakdown[$vr])) {
                        $vatBreakdown[$vr] += (float)($ti['vat_amount'] ?? 0);
                    }
                }

                $subtotal = $materialsSubtotal + $tradeSubtotal;
                $taxAmount = $materialsVat + $tradeVat;
                $priceWithTax = $subtotal + $taxAmount;
                $taxRate = $subtotal > 0 ? ($taxAmount / $subtotal) * 100 : 0;

                // Round monetary values and percentages for presentation
                $subtotalRounded = round($subtotal, 2);
                $taxAmountRounded = round($taxAmount, 2);
                $priceWithTaxRounded = round($priceWithTax, 2);
                $taxRateRounded = round($taxRate, 2);
                $vatBreakdownRounded = [];
                foreach ($vatBreakdown as $r => $amt) { $vatBreakdownRounded[$r] = round($amt, 2); }

                // Return a new array for this invoice with the required fields
                $invoiceArr = $invoice->toArray();
                // If merged view exists for this invoice, override jobs in array form
                if (!empty($mergedJobsByInvoice) && isset($mergedJobsByInvoice[$invoice->id])) {
                    $invoiceArr['jobs'] = $mergedJobsByInvoice[$invoice->id];
                }

                return array_merge(
                    $invoiceArr,
                    [
                        'barcodeImage' => $barcodeImage,
                        'totalSalePrice' => $subtotalRounded,
                        'taxRate' => $taxRateRounded,
                        'priceWithTax' => $priceWithTaxRounded,
                        'taxAmount' => $taxAmountRounded,
                        'copies' => $totalCopies,
                        // Extra fields for per-rate breakdown
                        'subtotal_without_vat' => $subtotalRounded,
                        'vat_breakdown' => $vatBreakdownRounded,
                        'total_with_vat' => $priceWithTaxRounded,
                        'trade_items' => $invoiceTradeItems,
                        'comment' => $comment,
                        // Use provided preview date if valid, otherwise now
                        // Parse date in UTC to avoid timezone conversion issues
                        'generated_at' => !empty($createdAtInput) ? \Carbon\Carbon::parse($createdAtInput, 'UTC')->startOfDay()->toDateTimeString() : now()->toDateTimeString(),
                        'preview_faktura_id' => $nextFakturaId,
                        'payment_deadline_override' => is_numeric($paymentDeadlineOverride) ? (int)$paymentDeadlineOverride : null,
                    ]
                );
            })->toArray();

            // Ensure trade items are shown only once (per previewed invoice, not per order)
            if (is_array($transformedInvoices) && count($transformedInvoices) > 1) {
                for ($i = 1; $i < count($transformedInvoices); $i++) {
                    if (isset($transformedInvoices[$i]['trade_items'])) {
                        $transformedInvoices[$i]['trade_items'] = [];
                    }
                }
            }

            // Generate PDF using v2 template for preview
            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => $transformedInvoices,
                'additionalServices' => is_array($additionalServices) ? $additionalServices : [],
                'jobUnits' => $jobUnits,
                'fakturaOverrides' => $fakturaOverrides,
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
            Log::error('Preview Invoice Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            return response()->json([
                'error' => 'Failed to generate preview',
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ], 500);
        }
    }

    private function previewSplitInvoices(Request $request, array $splitGroups)
    {
        try {
            $comment = $request->input('comment');
            $tradeItems = $request->input('trade_items', []);
            $createdAtInput = $request->input('created_at');
            $paymentDeadlineOverride = $request->input('payment_deadline_override');
            $jobUnits = $request->input('job_units', []);

            $previewPdfs = [];
            $nextFakturaId = (int) (\App\Models\Faktura::max('id') ?? 0) + 1;

            foreach ($splitGroups as $index => $splitGroup) {
                $jobIds = $splitGroup['job_ids'] ?? [];
                $clientOverride = $splitGroup['client'] ?? null;

                if (empty($jobIds)) {
                    continue;
                }

                // Get jobs for this split group (can be from existing orders)
                $jobs = Job::with(['client.contacts', 'client.clientCardStatement', 'actions', 'articles'])
                    ->whereIn('id', $jobIds)
                    ->get();

                if ($jobs->isEmpty()) {
                    continue;
                }

                // Get parent order information from the first job
                $firstJob = $jobs->first();
                $parentOrder = null;
                
                // Find parent order through pivot table
                $parentOrderRecord = DB::table('invoice_job')
                    ->where('job_id', $firstJob->id)
                    ->first();
                
                if ($parentOrderRecord) {
                    $parentOrder = Invoice::with(['client', 'contact', 'user', 'client.clientCardStatement'])
                        ->find($parentOrderRecord->invoice_id);
                }

                // Use parent order data or fallback to job data
                $client = $firstJob->client;
                $contact = null;
                $user = auth()->user();
                $invoiceTitle = $client ? $client->name : 'Invoice';
                $startDate = now()->toDateString();
                $endDate = now()->toDateString();

                if ($parentOrder) {
                    $client = $parentOrder->client ?: $client;
                    $contact = $parentOrder->contact;
                    $user = $parentOrder->user ?: $user;
                    $invoiceTitle = $parentOrder->invoice_title . ' (Split ' . ($index + 1) . ')';
                    $startDate = $parentOrder->start_date;
                    $endDate = $parentOrder->end_date;
                }

                // Handle client override
                if ($clientOverride && $clientOverride !== ($client ? $client->name : '')) {
                    $overrideClient = \App\Models\Client::with(['clientCardStatement', 'contacts'])
                        ->where('name', $clientOverride)
                        ->first();
                    if ($overrideClient) {
                        $client = $overrideClient;
                        $contact = $overrideClient->contacts()->first();
                    }
                }

                // Create preview PDF
                $dns1d = new DNS1D();
                $mockInvoiceId = 'preview_' . ($nextFakturaId + $index);
                $barcodeString = $mockInvoiceId . '-' . date('m-Y');
                $barcodeImage = base64_encode($dns1d->getBarcodePNG($barcodeString, 'C128'));

                // Calculate totals
                $totalSalePrice = $jobs->sum('salePrice');
                $totalCopies = $jobs->sum('copies');
                $taxRate = 0.18;
                $priceWithTax = $totalSalePrice * (1 + $taxRate);
                $taxAmount = $totalSalePrice * $taxRate;

                $totalSalePriceRounded = round($totalSalePrice, 2);
                $taxAmountRounded = round($taxAmount, 2);
                $priceWithTaxRounded = round($priceWithTax, 2);
                $vatBreakdown = [18 => $taxAmountRounded];

                $mockInvoice = [
                    'id' => $mockInvoiceId,
                    'invoice_title' => $splitGroup['order_title'] ?? $invoiceTitle,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'status' => 'Preview',
                    'client' => $client ? [
                        'id' => $client->id,
                        'name' => $client->name,
                        'address' => $client->address,
                        'city' => $client->city,
                        'clientCardStatement' => $client->clientCardStatement ? $client->clientCardStatement->toArray() : null
                    ] : null,
                    'contact' => $contact ? [
                        'id' => $contact->id,
                        'name' => $contact->name ?? '',
                        'email' => $contact->email ?? '',
                        'phone' => $contact->phone ?? ''
                    ] : null,
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name
                    ],
                    'jobs' => $jobs->toArray(),
                    'barcodeImage' => $barcodeImage,
                    'totalSalePrice' => $totalSalePriceRounded,
                    'taxRate' => round($taxRate * 100, 2),
                    'priceWithTax' => $priceWithTaxRounded,
                    'taxAmount' => $taxAmountRounded,
                    'copies' => $totalCopies,
                    'subtotal_without_vat' => $totalSalePriceRounded,
                    'vat_breakdown' => $vatBreakdown,
                    'total_with_vat' => $priceWithTaxRounded,
                    'trade_items' => $index === 0 ? $tradeItems : [],
                    'comment' => $comment,
                    'generated_at' => !empty($createdAtInput) ? \Carbon\Carbon::parse($createdAtInput, 'UTC')->startOfDay()->toDateTimeString() : now()->toDateTimeString(),
                    'preview_faktura_id' => $nextFakturaId + $index,
                    'split_group_identifier' => 'group_' . ($index + 1),
                    'payment_deadline_override' => is_numeric($paymentDeadlineOverride) ? (int)$paymentDeadlineOverride : null,
                ];

            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => [$mockInvoice],
                'additionalServices' => is_array($additionalServices) ? $additionalServices : [],
                'jobUnits' => $jobUnits,
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'chroot' => storage_path('fonts'),
                    'dpi' => 150,
                ]);

                $previewPdfs[] = [
                    'split_group' => $index + 1,
                    'client' => $client ? $client->name : 'Unknown Client',
                    'pdf' => base64_encode($pdf->output()),
                    'job_count' => count($jobIds),
                    'order_title' => $splitGroup['order_title'] ?? null
                ];
            }

            if (empty($previewPdfs)) {
                return response()->json(['error' => 'No valid split groups to preview. Please ensure jobs are assigned to split groups and try again.'], 400);
            }

            // For split previews, return JSON with multiple PDFs
            return response()->json([
                'success' => true,
                'is_split_preview' => true,
                'previews' => $previewPdfs
            ]);

        } catch (\Exception $e) {
            Log::error('Preview Split Invoices Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate split preview',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private function previewSplitInvoicesNew(Request $request, array $splitGroups)
    {
        try {
            $comment = $request->input('comment');
            $tradeItems = $request->input('trade_items', []);
            $createdAtInput = $request->input('created_at');
            $paymentDeadlineOverride = $request->input('payment_deadline_override');
            $jobUnits = $request->input('job_units', []);

            $previewPdfs = [];
            $tempInvoiceIds = [];

            DB::beginTransaction();

            foreach ($splitGroups as $index => $splitGroup) {
                $jobIds = $splitGroup['job_ids'] ?? [];
                $clientOverride = $splitGroup['client'] ?? null;

                if (empty($jobIds)) {
                    continue;
                }

                // Get uninvoiced jobs for this split group
                $jobs = Job::with(['client.contacts', 'client.clientCardStatement'])
                    ->whereIn('id', $jobIds)
                    ->whereNull('invoice_id')
                    ->get();

                if ($jobs->isEmpty()) {
                    continue;
                }

                // Get client information
                $firstJob = $jobs->first();
                $client = $firstJob->client;
                
                // Handle client override
                if ($clientOverride && $clientOverride !== ($client ? $client->name : '')) {
                    $overrideClient = \App\Models\Client::with(['clientCardStatement', 'contacts'])
                        ->where('name', $clientOverride)
                        ->first();
                    if ($overrideClient) {
                        $client = $overrideClient;
                    }
                }
                
                $primaryContact = $client ? $client->contacts()->first() : null;

                // Create temporary Invoice record (same as generation but for preview)
                $tempInvoice = Invoice::create([
                    'invoice_title' => $splitGroup['order_title'] ?? ($client ? $client->name : 'Invoice'),
                    'start_date' => now(),
                    'end_date' => now(),
                    'client_id' => $client ? $client->id : $firstJob->client_id,
                    'contact_id' => $primaryContact ? $primaryContact->id : null,
                    'status' => 'Completed', // Use valid enum value
                    'created_by' => auth()->id(),
                ]);

                $tempInvoiceIds[] = $tempInvoice->id;

                // Temporarily associate jobs with this invoice for preview using pivot table
                $tempInvoice->jobs()->attach($jobs->pluck('id')->toArray());
            }

            // Now use the EXACT same logic as normal preview
            $invoices = Invoice::with(['jobs.actions', 'contact', 'user', 'client', 'article', 'client.clientCardStatement'])
                ->whereIn('id', $tempInvoiceIds)
                ->get();

            // Generate PDFs for each invoice using normal preview logic
            foreach ($invoices as $index => $invoice) {
                // Use same transformation as normal preview
                $dns1d = new DNS1D();
                $transformedInvoices = collect([$invoice])->map(function ($invoice) use ($dns1d, $tradeItems, $comment, $createdAtInput, $paymentDeadlineOverride, $index) {
                    // EXACT same logic as normal preview
                    $period = $invoice->end_date ? date('m-Y', strtotime($invoice->end_date)) : date('m-Y');
                    $barcodeString = $invoice->id . '-' . $period;
                    $barcodeImage = null;
                    try {
                        $png = $dns1d->getBarcodePNG($barcodeString, 'C128');
                        if (!empty($png)) {
                            $barcodeImage = base64_encode($png);
                        }
                    } catch (\Throwable $e) {
                        $barcodeImage = null;
                    }

                    $totalCopies = (int) $invoice->jobs->sum('copies');

                    // Use same VAT calculation as normal preview
                    $taxMap = [1 => 18, 2 => 5, 3 => 10];
                    $materialsSubtotal = 0.0;
                    $materialsVat = 0.0;
                    $vatBreakdown = [5 => 0.0, 10 => 0.0, 18 => 0.0];
                    foreach ($invoice->jobs as $job) {
                        foreach ($job->articles as $article) {
                            $qty = (float) ($article->pivot->quantity ?? 0);
                            $unitPrice = (float) ($article->price_1 ?? 0);
                            $line = $qty * $unitPrice;
                            $materialsSubtotal += $line;
                            $rate = (float) ($taxMap[$article->tax_type] ?? 0);
                            $lineVat = $line * ($rate / 100);
                            $materialsVat += $lineVat;
                            if (isset($vatBreakdown[(int)$rate])) {
                                $vatBreakdown[(int)$rate] += $lineVat;
                            }
                        }
                    }

                    // Add trade items (only to first split)
                    $invoiceTradeItems = collect($index === 0 ? $tradeItems : [])->map(function ($item) {
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
                    $tradeSubtotal = (float) $invoiceTradeItems->sum('total_price');
                    $tradeVat = (float) $invoiceTradeItems->sum('vat_amount');
                    
                    foreach ($invoiceTradeItems as $ti) {
                        $vr = (int)($ti['vat_rate'] ?? 0);
                        if (isset($vatBreakdown[$vr])) {
                            $vatBreakdown[$vr] += (float)($ti['vat_amount'] ?? 0);
                        }
                    }

                    $subtotal = $materialsSubtotal + $tradeSubtotal;
                    $taxAmount = $materialsVat + $tradeVat;
                    $priceWithTax = $subtotal + $taxAmount;
                    $taxRate = $subtotal > 0 ? ($taxAmount / $subtotal) * 100 : 0;

                    // Round values
                    $subtotalRounded = round($subtotal, 2);
                    $taxAmountRounded = round($taxAmount, 2);
                    $priceWithTaxRounded = round($priceWithTax, 2);
                    $taxRateRounded = round($taxRate, 2);
                    $vatBreakdownRounded = [];
                    foreach ($vatBreakdown as $r => $amt) { $vatBreakdownRounded[$r] = round($amt, 2); }

                    $invoiceArr = $invoice->toArray();

                    return array_merge(
                        $invoiceArr,
                        [
                            'barcodeImage' => $barcodeImage,
                            'totalSalePrice' => $subtotalRounded,
                            'taxRate' => $taxRateRounded,
                            'priceWithTax' => $priceWithTaxRounded,
                            'taxAmount' => $taxAmountRounded,
                            'copies' => $totalCopies,
                            'subtotal_without_vat' => $subtotalRounded,
                            'vat_breakdown' => $vatBreakdownRounded,
                            'total_with_vat' => $priceWithTaxRounded,
                            'trade_items' => $invoiceTradeItems,
                            'comment' => $comment,
                            'generated_at' => !empty($createdAtInput) ? \Carbon\Carbon::parse($createdAtInput, 'UTC')->startOfDay()->toDateTimeString() : now()->toDateTimeString(),
                            'preview_faktura_id' => \App\Models\Faktura::max('id') + 1 + $index,
                            'payment_deadline_override' => is_numeric($paymentDeadlineOverride) ? (int)$paymentDeadlineOverride : null,
                        ]
                    );
                })->toArray();

                // Generate PDF using same method as normal preview
            $pdf = PDF::loadView('invoices.outgoing_invoice_v2', [
                'invoices' => $transformedInvoices,
                'additionalServices' => is_array($additionalServices) ? $additionalServices : [],
                'jobUnits' => $jobUnits,
                'fakturaOverrides' => $fakturaOverrides,
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled' => true,
                    'isFontSubsettingEnabled' => true,
                    'chroot' => storage_path('fonts'),
                    'dpi' => 150,
                ]);

                $previewPdfs[] = [
                    'split_group' => $index + 1,
                    'client' => $invoice->client->name,
                    'pdf' => base64_encode($pdf->output()),
                    'job_count' => $invoice->jobs->count(),
                    'order_title' => $invoice->invoice_title
                ];
            }

            // Clean up: Detach jobs and delete temp invoices
            foreach ($tempInvoiceIds as $tempId) {
                $tempInvoice = Invoice::find($tempId);
                if ($tempInvoice) {
                    $tempInvoice->jobs()->detach();
                }
            }
            Invoice::whereIn('id', $tempInvoiceIds)->delete();

            DB::commit();

            if (empty($previewPdfs)) {
                return response()->json(['error' => 'No valid split groups to preview'], 400);
            }

            return response()->json([
                'success' => true,
                'is_split_preview' => true,
                'previews' => $previewPdfs
            ]);

        } catch (\Exception $e) {
            // Clean up on error
            if (!empty($tempInvoiceIds)) {
                foreach ($tempInvoiceIds as $tempId) {
                    $tempInvoice = Invoice::find($tempId);
                    if ($tempInvoice) {
                        $tempInvoice->jobs()->detach();
                    }
                }
                Invoice::whereIn('id', $tempInvoiceIds)->delete();
            }
            DB::rollback();
            
            Log::error('Preview Split Invoices Error: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to generate split preview',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Returns the next faktura id that would be assigned (for pre-generation display)
    public function getNextFakturaId()
    {
        $next = (int) (\App\Models\Faktura::max('id') ?? 0) + 1;
        return response()->json(['next_id' => $next]);
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

    public function updateMergeGroups(Request $request, $id)
    {
        try {
            // Find the Faktura by its ID
            $faktura = Faktura::findOrFail($id);

            // Update the merge groups
            $faktura->update([
                'merge_groups' => $request->input('merge_groups')
            ]);

            // Return success response
            return response()->json([
                'message' => 'Merge groups updated successfully',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update merge groups',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    public function updatePaymentDeadline(Request $request, $id)
    {
        try {
            $request->validate([
                'payment_deadline_override' => 'nullable|integer|min:0'
            ]);

            $faktura = Faktura::findOrFail($id);
            $faktura->update([
                'payment_deadline_override' => $request->input('payment_deadline_override')
            ]);

            return response()->json([
                'message' => 'Payment deadline updated successfully',
                'success' => true
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to update payment deadline',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
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
     * Download selected files from an invoice as a ZIP
     */
    public function downloadSelectedFiles(Request $request)
    {
        try {
            $request->validate([
                'invoiceId' => 'required|integer',
                'clientName' => 'required|string',
                'downloadOptions' => 'required|array',
                'jobs' => 'required|array'
            ]);

            $invoiceId = $request->input('invoiceId');
            $clientName = $request->input('clientName');
            $downloadOptions = $request->input('downloadOptions');
            $jobs = $request->input('jobs');

            \Log::info('Download selected files request', [
                'invoice_id' => $invoiceId,
                'client_name' => $clientName,
                'download_options' => $downloadOptions,
                'jobs_count' => count($jobs)
            ]);

            // Find the invoice
            $invoice = Invoice::with('jobs')->findOrFail($invoiceId);

            // Create a temporary file for the ZIP
            $timestamp = now()->format('Y-m-d_H-i-s');
            $clientNameClean = preg_replace('/[^a-zA-Z0-9]/', '_', $clientName);
            $zipFileName = "Invoice_{$clientNameClean}_{$invoiceId}_{$timestamp}.zip";
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
            $disk = $templateStorageService->getDisk();

            foreach ($jobs as $jobData) {
                $jobId = $jobData['id'];
                $jobName = $jobData['name'];
                
                \Log::info('Processing job for download', [
                    'job_id' => $jobId,
                    'job_name' => $jobName,
                    'has_dimensions_breakdown' => isset($jobData['dimensions_breakdown']),
                    'has_cutting_files' => isset($jobData['cuttingFiles']),
                    'has_original_files' => isset($jobData['originalFile'])
                ]);
                
                // Job Original Thumbnails
                if ($downloadOptions['jobOriginalThumbnails'] && isset($jobData['dimensions_breakdown'])) {
                    $thumbnailDir = public_path("jobfiles/thumbnails/{$jobId}");
                    if (is_dir($thumbnailDir)) {
                        $thumbnailFiles = glob($thumbnailDir . '/*.png');
                        \Log::info('Found thumbnail files', [
                            'job_id' => $jobId,
                            'thumbnail_dir' => $thumbnailDir,
                            'file_count' => count($thumbnailFiles)
                        ]);
                        foreach ($thumbnailFiles as $thumbnailFile) {
                            $fileName = basename($thumbnailFile);
                            $zip->addFile($thumbnailFile, "Job_{$jobId}_Thumbnails/{$fileName}");
                            $addedFiles++;
                        }
                    } else {
                        \Log::info('Thumbnail directory not found', [
                            'job_id' => $jobId,
                            'thumbnail_dir' => $thumbnailDir
                        ]);
                    }
                }
                
                // Cutting Thumbnails
                if ($downloadOptions['cuttingThumbnails'] && isset($jobData['cuttingFiles'])) {
                    $cuttingThumbnailDir = public_path("jobfiles/cutting-thumbnails/{$jobId}");
                    if (is_dir($cuttingThumbnailDir)) {
                        $cuttingThumbnailFiles = glob($cuttingThumbnailDir . '/*.png');
                        \Log::info('Found cutting thumbnail files', [
                            'job_id' => $jobId,
                            'cutting_thumbnail_dir' => $cuttingThumbnailDir,
                            'file_count' => count($cuttingThumbnailFiles)
                        ]);
                        foreach ($cuttingThumbnailFiles as $cuttingThumbnailFile) {
                            $fileName = basename($cuttingThumbnailFile);
                            $zip->addFile($cuttingThumbnailFile, "Job_{$jobId}_CuttingThumbnails/{$fileName}");
                            $addedFiles++;
                        }
                    } else {
                        \Log::info('Cutting thumbnail directory not found', [
                            'job_id' => $jobId,
                            'cutting_thumbnail_dir' => $cuttingThumbnailDir
                        ]);
                    }
                }
                
                // Job Original Files (from R2)
                if ($downloadOptions['jobOriginalFiles'] && isset($jobData['originalFile'])) {
                    foreach ($jobData['originalFile'] as $fileIndex => $originalFilePath) {
                        if ($disk->exists($originalFilePath)) {
                            $fileContent = $disk->get($originalFilePath);
                            $fileName = basename($originalFilePath);
                            $zip->addFromString("Job_{$jobId}_Originals/{$fileName}", $fileContent);
                            $addedFiles++;
                            \Log::info('Added original file to ZIP', [
                                'job_id' => $jobId,
                                'file_path' => $originalFilePath,
                                'file_name' => $fileName
                            ]);
                        } else {
                            \Log::warning('Original file not found in R2', [
                                'job_id' => $jobId,
                                'file_path' => $originalFilePath
                            ]);
                        }
                    }
                }
                
                // Cutting Files (from R2)
                if ($downloadOptions['cuttingFiles'] && isset($jobData['cuttingFiles'])) {
                    foreach ($jobData['cuttingFiles'] as $fileIndex => $cuttingFilePath) {
                        if ($disk->exists($cuttingFilePath)) {
                            $fileContent = $disk->get($cuttingFilePath);
                            $fileName = basename($cuttingFilePath);
                            $zip->addFromString("Job_{$jobId}_CuttingFiles/{$fileName}", $fileContent);
                            $addedFiles++;
                            \Log::info('Added cutting file to ZIP', [
                                'job_id' => $jobId,
                                'file_path' => $cuttingFilePath,
                                'file_name' => $fileName
                            ]);
                        } else {
                            \Log::warning('Cutting file not found in R2', [
                                'job_id' => $jobId,
                                'file_path' => $cuttingFilePath
                            ]);
                        }
                    }
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

            \Log::info('Selected files ZIP created successfully', [
                'invoice_id' => $invoiceId,
                'files_added' => $addedFiles,
                'zip_path' => $tempZipPath
            ]);

            // Return the ZIP file for download
            return response()->download($tempZipPath, $zipFileName, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
                'Cache-Control' => 'no-cache, no-store, must-revalidate',
                'Pragma' => 'no-cache',
                'Expires' => '0'
            ])->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            \Log::error('Error creating selected files ZIP download:', [
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

            // Parse date as start of day in UTC to avoid timezone conversion issues
            $faktura->created_at = \Carbon\Carbon::parse($validatedData['created'], 'UTC')->startOfDay();
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

    /**
     * Update order title before invoice generation
     */
    public function updateOrderTitle(Request $request, $id)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'invoice_title' => 'required|string|max:255',
            ]);

            /** @var Invoice $invoice */
            $invoice = Invoice::findOrFail($id);

            // Only allow updating if it's not yet invoiced
            if (!is_null($invoice->faktura_id)) {
                return response()->json([
                    'error' => 'Order already belongs to a generated invoice',
                ], 422);
            }

            $invoice->invoice_title = $validated['invoice_title'];
            $invoice->save();

            DB::commit();

            return response()->json([
                'message' => 'Order title updated successfully',
                'invoice' => $invoice,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Failed to update order title',
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
