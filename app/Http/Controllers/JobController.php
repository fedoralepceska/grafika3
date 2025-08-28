<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Events\InvoiceCreated;
use App\Events\JobEnded;
use App\Events\JobStarted;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use App\Models\WorkerAnalytics;
use App\Models\CatalogItem;
use App\Services\TemplateStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use Inertia\Inertia;
use App\Services\PriceCalculationService;

class JobController extends Controller
{
    protected $templateStorageService;

    public function __construct(TemplateStorageService $templateStorageService)
    {
        $this->templateStorageService = $templateStorageService;
    }

    public function index()
    {
        $jobs = Job::with('actions')->get()->toArray(); // Eager load jobs related to invoices

        if (request()->wantsJson()) {
            return response()->json($jobs);
        }

        return Inertia::render('Invoice/InvoiceForm', [
            'jobs' => $jobs,
        ]);
    }
    public function show($id): \Illuminate\Http\JsonResponse
    {
        // Retrieve the job by its ID
        $job = Job::with('actions')->find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        return response()->json($job);
    }

    public function store(Request $request)
    {
        if ($request->has('fromCatalog')) {
            try {
                $priceCalculationService = app()->make(PriceCalculationService::class);

                // Step 1: Create the Job
                $job = new Job();
                $job->machinePrint = $request->input('machinePrint');
                $job->machineCut = $request->input('machineCut');
                
                // Handle articles from catalog item
                $catalogItem = CatalogItem::with('articles')->find($request->input('catalog_item_id'));
                
                // Set legacy material fields to null (we'll use articles instead)
                $job->large_material_id = null;
                $job->small_material_id = null;
                $job->name = $request->input('name');
                $job->quantity = $request->input('quantity');
                $job->copies = $request->input('copies');
                $job->file = 'placeholder.jpeg';
                $job->width = 0;
                $job->height = 0;
                $job->catalog_item_id = $request->input('catalog_item_id');
                $job->client_id = $request->input('client_id');

                // Save question answers if provided
                if ($request->has('question_answers')) {
                    $job->question_answers = $request->input('question_answers');
                }

                // Calculate selling price based on hierarchy (what customer pays)
                $sellingPrice = $priceCalculationService->calculateEffectivePrice(
                    $request->input('catalog_item_id'),
                    $request->input('client_id'),
                    $request->input('quantity')
                );

                // Calculate cost price based on actual component article requirements
                // Create a temporary job object for calculations (before saving)
                $tempJob = new Job();
                $tempJob->quantity = $request->input('quantity');
                $tempJob->copies = $request->input('copies');
                $tempJob->width = $request->input('width', 0);
                $tempJob->height = $request->input('height', 0);
                
                $totalCostPrice = $catalogItem->calculateJobCostPrice($tempJob);

                \Log::info('Creating job from catalog item', [
                    'catalog_item_id' => $request->input('catalog_item_id'),
                    'client_id' => $request->input('client_id'),
                    'quantity' => $request->input('quantity'),
                    'copies' => $request->input('copies'),
                    'width' => $request->input('width', 0),
                    'height' => $request->input('height', 0),
                    'calculated_cost_price' => $totalCostPrice,
                    'calculated_selling_price' => $sellingPrice
                ]);

                // Set the cost price (what it costs us to produce)
                $job->price = $totalCostPrice;
                
                // Set the selling price (what customer pays)
                if ($sellingPrice !== null) {
                    $job->salePrice = $sellingPrice;
                } else {
                    // Fallback to default catalog item price * pricing multiplier
                    $pricingMultiplier = $catalogItem->getPricingMultiplier(
                        $request->input('quantity'),
                        $request->input('copies')
                    );
                    $job->salePrice = ($catalogItem->price ?? 0) * $pricingMultiplier;
                }



                // Step 1.5: Process catalog item articles and check stock availability using new material calculation
                if ($catalogItem->articles()->exists()) {
                    $materialRequirements = $catalogItem->calculateMaterialRequirements($tempJob);
                    
                    foreach ($materialRequirements as $requirement) {
                        $article = $requirement['article'];
                        $requiredQuantity = $requirement['actual_required'];
                        $unitType = $requirement['unit_type'];
                        
                        // If this article was selected from a category, we need to get the actual article to use
                        $actualArticle = $article;
                        if ($article->pivot->category_id) {
                            // Re-resolve the category to get the current first available article
                            $actualArticle = $catalogItem->getFirstArticleFromCategory(
                                $article->pivot->category_id,
                                null,
                                $requiredQuantity
                            );
                            if (!$actualArticle) {
                                throw new \Exception("No available articles with sufficient stock in the selected category (ID: {$article->pivot->category_id}).");
                            }
                        }
                        
                        // Check if the actual article has sufficient stock (validation only, no consumption)
                        if (!$actualArticle->hasStock($requiredQuantity)) {
                            throw new \Exception("Insufficient stock for article: {$actualArticle->name} ({$unitType}). Required: {$requiredQuantity}, Available: {$actualArticle->getCurrentStock()}");
                        }
                    }
                }

                $job->save();

                // Step 1.6: Attach articles from catalog item to job
                if ($catalogItem->articles()->exists()) {
                    $materialRequirements = $catalogItem->calculateMaterialRequirements($tempJob);
                    
                    foreach ($materialRequirements as $requirement) {
                        $article = $requirement['article'];
                        $requiredQuantity = $requirement['actual_required'];
                        
                        // If this article was selected from a category, we need to get the actual article to use
                        $actualArticle = $article;
                        if ($article->pivot->category_id) {
                            // Re-resolve the category to get the current first available article
                            $actualArticle = $catalogItem->getFirstArticleFromCategory(
                                $article->pivot->category_id,
                                null,
                                $requiredQuantity
                            );
                            if (!$actualArticle) {
                                throw new \Exception("No available articles with sufficient stock in the selected category (ID: {$article->pivot->category_id}).");
                            }
                        }
                        
                        // Attach the actual article to the job with the required quantity
                        $job->articles()->attach($actualArticle->id, [
                            'quantity' => $requiredQuantity
                        ]);
                        
                        \Log::info('Attached article to job', [
                            'job_id' => $job->id,
                            'article_id' => $actualArticle->id,
                            'article_name' => $actualArticle->name,
                            'quantity' => $requiredQuantity
                        ]);
                    }
                }

                // Step 2: Retrieve Catalog Item Actions
                if ($request->has('actions')) {
                    $catalogActions = $request->input('actions');

                    // Step 3: Duplicate Actions into `job_actions`
                    $printAction = new JobAction([
                        'name' => $request->input('machinePrint'),
                        'status' => 'Not started yet',
                    ]);
                    $action = DB::table('job_actions')->insertGetId([
                        'name' => $request->input('machinePrint'), // Insert machine print
                        'status' => 'Not started yet',    // Default status
                        'quantity' => 0, // Copy quantity or default to 0
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                    $job->actions()->sync([]);
                    $newActions[] = [
                        'job_action_id' => $action,
                        'quantity' => 0,
                        'status' => 'Not started yet', // Default status
                    ];
                    foreach ($catalogActions as $catalogAction) {
                        $newAction = DB::table('job_actions')->insertGetId([
                            'name' => $catalogAction['name'], // Copy action name
                            'status' => 'Not started yet',    // Default status
                            'quantity' => $catalogAction['quantity'] ?? 0, // Copy quantity or default to 0
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $newActions[] = [
                            'job_action_id' => $newAction,
                            'quantity' => $catalogAction['quantity'] ?? 0,
                            'status' => 'Not started yet', // Default status
                        ];
                    }

                    // Step 4: Link New Actions to the Job in `job_job_action`
                    foreach ($newActions as $action) {
                        DB::table('job_job_action')->insert([
                            'job_id' => $job->id,
                            'job_action_id' => $action['job_action_id'],
                            'quantity' => $action['quantity'],
                            'status' => $action['status'],
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    }
                }

                // Reload the job with its actions for response
                $job->load('actions');
                $job->load('catalogItem');

                return response()->json([
                    'message' => 'Job created successfully',
                    'job' => $job,
                ]);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
        
        // New R2 storage drag-and-drop file upload logic
        try {
            // Validate the request data
            $this->validate($request, [
                'file' => 'required|mimetypes:image/tiff,application/pdf',
            ]);

            // Handle file upload and storage
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileExtension = $file->getClientOriginalExtension();
                
                // Create a new job instance
                $job = new Job();

                if ($fileExtension === 'pdf') {
                    // Store the original PDF file in R2
                    $originalPath = $this->templateStorageService->storeTemplate($file, 'job-originals');
                    
                    // Generate preview image and calculate dimensions for all pages
                    $imagick = new Imagick();
                    $this->setGhostscriptPath($imagick);
                    $imagick->readImage($file->getPathname());
                    $pageCount = $imagick->getNumberImages();
                    
                    \Log::info('Processing single PDF file with multiple pages', [
                        'file' => $file->getClientOriginalName(),
                        'page_count' => $pageCount
                    ]);
                    
                    // Variables to store cumulative area and page dimensions for this file
                    $fileTotalAreaM2 = 0;
                    $pageDimensions = [];
                    
                    // Iterate through all pages of the PDF to calculate total area
                    for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                        $pageImagick = new Imagick();
                        $this->setGhostscriptPath($pageImagick);
                        $pageImagick->readImage($file->getPathname() . '[' . $pageIndex . ']');
                        $pageImagick->setImageFormat('jpg');
                        
                        // Create temporary image for dimension calculation
                        $tempImagePath = storage_path('app/temp/single_dim_calc_' . $pageIndex . '_' . time() . '.jpg');
                        $pageImagick->writeImage($tempImagePath);
                        
                        // Calculate dimensions from the page
                        list($width, $height) = getimagesize($tempImagePath);
                        $dpi = 72; // Default DPI
                        $widthInMm = ($width / $dpi) * 25.4;
                        $heightInMm = ($height / $dpi) * 25.4;
                        $areaM2 = ($widthInMm * $heightInMm) / 1000000;
                        
                        // Store individual page dimensions
                        $pageDimensions[] = [
                            'page' => $pageIndex + 1,
                            'width_mm' => $widthInMm,
                            'height_mm' => $heightInMm,
                            'area_m2' => $areaM2
                        ];
                        
                        // Add to file total area
                        $fileTotalAreaM2 += $areaM2;
                        
                        // Clean up temp file
                        if (file_exists($tempImagePath)) {
                            unlink($tempImagePath);
                        }
                        
                        $pageImagick->clear();
                        
                        \Log::info('Calculated dimensions for page in single file upload', [
                            'file' => $file->getClientOriginalName(),
                            'page' => $pageIndex + 1,
                            'width_mm' => $widthInMm,
                            'height_mm' => $heightInMm,
                            'area_m2' => $areaM2
                        ]);
                    }
                    
                    // Create preview image from first page only
                    $previewImagick = new Imagick();
                    $this->setGhostscriptPath($previewImagick);
                    $previewImagick->readImage($file->getPathname() . '[0]'); // First page only for preview
                    $previewImagick->setImageFormat('jpg');
                    
                    // Create unique filename for preview
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                    $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                    $previewImagick->writeImage($imagePath);
                    
                    $previewImagick->clear();
                    $imagick->clear();

                    // Save job first to get ID
                    $job->file = $imageFilename; // Preview image
                    $job->addOriginalFile($originalPath); // Store in originalFile JSON array
                    $job->total_area_m2 = $fileTotalAreaM2;
                    $job->dimensions_breakdown = [
                        [
                            'filename' => $file->getClientOriginalName(),
                            'page_count' => $pageCount,
                            'total_area_m2' => $fileTotalAreaM2,
                            'page_dimensions' => $pageDimensions,
                            'index' => 0
                        ]
                    ];
                    $job->save(); // Save to get ID
                    
                    // Generate thumbnail and store in R2 (keyed by original file key)
                    $fileKey = pathinfo(basename($originalPath), PATHINFO_FILENAME);
                    $this->generateThumbnail($imagePath, $job->id, $fileKey);
                    
                    \Log::info('Completed single PDF file upload with all pages', [
                        'file' => $file->getClientOriginalName(),
                        'total_pages' => $pageCount,
                        'total_area_m2' => $fileTotalAreaM2
                    ]);
                    
                } else if ($fileExtension === 'tiff' || $fileExtension === 'tif') {
                    // Store the original TIFF file in R2 (fully R2 system)
                    $originalPath = $this->templateStorageService->storeTemplate($file, 'job-originals');
                    
                    // Generate preview JPG and calculate dimensions
                    $imagick = new Imagick();
                    $imagick->readImage($file->getPathname());
                    $imagick->setImageFormat('jpg');
                    
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                    $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                    $imagick->writeImage($imagePath);
                    
                    // Calculate dimensions
                    list($width, $height) = getimagesize($imagePath);
                    $dpi = 72; // Default DPI
                    $widthInMm = ($width / $dpi) * 25.4;
                    $heightInMm = ($height / $dpi) * 25.4;
                    $areaM2 = ($widthInMm * $heightInMm) / 1000000;
                    
                    $imagick->clear();

                    // Save job first to get ID - TIFF now also uses R2 storage
                    $job->file = $imageFilename; // Preview image
                    $job->addOriginalFile($originalPath); // Store TIFF in originalFile JSON array
                    $job->total_area_m2 = $areaM2;
                    $job->dimensions_breakdown = [
                        [
                            'filename' => $file->getClientOriginalName(),
                            'page_count' => 1,
                            'total_area_m2' => $areaM2,
                            'page_dimensions' => [
                                [
                                    'page' => 1,
                                    'width_mm' => $widthInMm,
                                    'height_mm' => $heightInMm,
                                    'area_m2' => $areaM2
                                ]
                            ],
                            'index' => 0
                        ]
                    ];
                    $job->save(); // Save to get ID
                    
                    // Generate thumbnail and store in R2 (keyed by original file key)
                    $fileKey = pathinfo(basename($originalPath), PATHINFO_FILENAME);
                    $this->generateThumbnail($imagePath, $job->id, $fileKey);
                }
                
                \Log::info('Job created via drag-and-drop - R2 storage', [
                    'job_id' => $job->id,
                    'file_type' => $fileExtension,
                    'original_files_count' => count($job->getOriginalFiles()),
                    'dimensions' => ['width' => $job->width, 'height' => $job->height],
                    'storage_type' => 'R2',
                    'original_files' => $job->getOriginalFiles()
                ]);

                return response()->json([
                    'message' => 'Job created successfully',
                    'job' => $job,
                    'has_original_files' => true, // Always true for R2 storage
                    'original_files_count' => count($job->getOriginalFiles()),
                    'storage_type' => 'R2'
                ]);
                
            } else {
                return response()->json(['message' => 'File not provided'], 400);
            }
        } catch (\Exception $e) {
            \Log::error('Error creating job from drag and drop:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * @throws \Exception
     */
    public function syncAllJobs(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'name' => 'nullable|string',
                'quantity' => 'required|integer|min:1',
                'copies' => 'required|integer|min:1',
                'jobs' => 'required|array',
                'jobs.*' => 'exists:jobs,id',
                'jobsWithActions' => 'required|array',
                'client_id' => 'sometimes|exists:clients,id',
                'catalog_item_id' => 'sometimes|nullable|exists:catalog_items,id',
                'articles' => 'sometimes|array',
                'articles.*.id' => 'required_with:articles|exists:article,id',
                'articles.*.quantity' => 'required_with:articles|numeric|min:0',
            ]);

            $priceCalculationService = app()->make(PriceCalculationService::class);

            // Calculate unit price based on hierarchy
            $unitPrice = $priceCalculationService->calculateEffectivePrice(
                $request->input('catalog_item_id'),
                $request->input('client_id'),
                $request->input('quantity')
            );

            \Log::info('Syncing jobs with price calculation', [
                'catalog_item_id' => $request->input('catalog_item_id'),
                'client_id' => $request->input('client_id'),
                'quantity' => $request->input('quantity'),
                'calculated_unit_price' => $unitPrice
            ]);

            // Get the jobs that need to be updated
            $jobsToUpdate = Job::whereIn('id', $request->input('jobs'))->get();

            foreach ($jobsToUpdate as $job) {
                $updateData = [
                    'name' => $request->input('name'),
                    'machineCut' => $request->input('selectedMachineCut'),
                    'machinePrint' => $request->input('selectedMachinePrint'),
                    'quantity' => $request->input('quantity'),
                    'copies' => $request->input('copies'),
                    'catalog_item_id' => $request->input('catalog_item_id'),
                    'client_id' => $request->input('client_id'),
                    // Preserve existing values if they exist
                    'width' => $job->width ?: 0,
                    'height' => $job->height ?: 0,
                ];

                // Determine pricing multiplier based on catalog item settings
                $pricingMultiplier = $this->getPricingMultiplier($job, $request->input('quantity'), $request->input('copies'));

                // Set the job price using the appropriate multiplier
                if ($request->has('price')) {
                    $updateData['salePrice'] = $request->input('price') * $pricingMultiplier; // Selling price
                } elseif ($unitPrice !== null) {
                    $updateData['salePrice'] = $unitPrice * $pricingMultiplier; // Selling price from hierarchy
                }

                // Calculate cost price from articles and actions
                $costPrice = 0;
                
                // Get articles from catalog item if available
                $catalogItem = CatalogItem::with('articles')->find($request->input('catalog_item_id'));
                if ($catalogItem && $catalogItem->articles()->exists()) {
                    $tempJob = new Job();
                    $tempJob->quantity = $request->input('quantity');
                    $tempJob->copies = $request->input('copies');
                    $tempJob->width = $job->width ?: 0;
                    $tempJob->height = $job->height ?: 0;
                    
                    $costRequirements = $catalogItem->calculateCostRequirements($tempJob);
                    foreach ($costRequirements as $requirement) {
                        $costPrice += $requirement['total_cost'];
                    }
                }

                if ($request->has('jobsWithActions')) {
                    foreach ($request->input('jobsWithActions') as $jobWithActions) {
                        foreach ($jobWithActions['actions'] as $action) {
                            if (isset($action['quantity'])) {
                                // Cost calculation for actions will be handled by articles
                            }
                        }
                    }
                }
                $updateData['price'] = $costPrice * $pricingMultiplier; // Cost price

                // Update each job individually to preserve its specific attributes
                $job->update($updateData);
            }

            // Process actions for all jobs if provided
            if ($request->has('jobsWithActions')) {
                foreach ($request->input('jobsWithActions') as $jobWithActions) {
                    $jobId = $jobWithActions['job_id'];
                    $job = Job::find($jobId);
                    
                    if ($job && isset($jobWithActions['actions'])) {
                        // Clear existing actions for this job
                        $existingActionIds = DB::table('job_job_action')
                            ->where('job_id', $jobId)
                            ->pluck('job_action_id');
                        
                        if ($existingActionIds->count() > 0) {
                            // Delete from pivot table
                            DB::table('job_job_action')
                                ->where('job_id', $jobId)
                                ->delete();
                            
                            // Delete from job_actions table
                            DB::table('job_actions')
                                ->whereIn('id', $existingActionIds)
                                ->delete();
                        }
                        
                        // Create new actions
                        $newActions = [];
                        
                        // Add machine print action
                        if ($job->machinePrint) {
                            $machineActionId = DB::table('job_actions')->insertGetId([
                                'name' => $job->machinePrint,
                                'status' => 'Not started yet',
                                'quantity' => 0,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                            
                            $newActions[] = [
                                'job_action_id' => $machineActionId,
                                'quantity' => 0,
                                'status' => 'Not started yet',
                            ];
                        }
                        
                        // Add catalog actions
                        foreach ($jobWithActions['actions'] as $actionData) {
                            if (isset($actionData['action_id'])) {
                                // Get the action name from dorabotka table
                                $refinement = DB::table('dorabotka')
                                    ->where('id', $actionData['action_id'])
                                    ->first();
                                
                                $actionName = $refinement ? $refinement->name : 'Unknown Action';
                                
                                $newActionId = DB::table('job_actions')->insertGetId([
                                    'name' => $actionName,
                                    'status' => 'Not started yet',
                                    'quantity' => $actionData['quantity'] ?? 0,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                                
                                $newActions[] = [
                                    'job_action_id' => $newActionId,
                                    'quantity' => $actionData['quantity'] ?? 0,
                                    'status' => 'Not started yet',
                                ];
                            }
                        }
                        
                        // Create pivot table entries
                        foreach ($newActions as $action) {
                            DB::table('job_job_action')->insert([
                                'job_id' => $jobId,
                                'job_action_id' => $action['job_action_id'],
                                'quantity' => $action['quantity'],
                                'status' => $action['status'],
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }

            // Process component articles for cost calculation if provided (after job updates)
            if ($request->has('articles')) {
                $totalArticlesCost = 0;
                foreach ($request->input('articles') as $articleData) {
                    $article = DB::table('article')
                        ->where('id', $articleData['id'])
                        ->first();
                    
                    if ($article) {
                        $articleCost = ($article->purchase_price ?? 0) * ($articleData['quantity'] ?? 0);
                        $totalArticlesCost += $articleCost;
                    }
                }
                
                // Add articles cost to each job's price using appropriate multiplier
                if ($totalArticlesCost > 0) {
                    foreach ($jobsToUpdate as $job) {
                        $pricingMultiplier = $this->getPricingMultiplier($job, $request->input('quantity'), $request->input('copies'));
                        $costPerJob = $totalArticlesCost * $pricingMultiplier;
                        $job->price = ($job->price ?? 0) + $costPerJob;
                        $job->save();
                    }
                }
            }

            // Return the first job's data for price information
            $updatedJob = Job::with(['actions', 'catalogItem', 'client'])->find($request->input('jobs')[0]);

            return response()->json([
                'message' => 'Jobs synced successfully',
                'price' => $updatedJob->price,
                'jobs' => Job::with(['actions', 'catalogItem', 'client'])
                    ->whereIn('id', $request->input('jobs'))
                    ->get() // Return all updated jobs
            ]);
        } catch (\Exception $e) {
            \Log::error('Error syncing jobs:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to sync jobs',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * @throws \Exception
     */
    public function syncAllJobsWithMachines(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            $jobIds = $request->input('jobs');
            $selectedMachinePrint = $request->input('selectedMachinePrint');

            // Update jobs with materials and machines
            Job::whereIn('id', $jobIds)->update([
                'machinePrint' => $selectedMachinePrint,
            ]);

            return response()->json(['message' => 'Jobs synced successfully']);
        } catch (\Exception $e) {
            \Log::error('Error syncing jobs:', ['error' => $e->getMessage()]);
            return response()->json([
                'error' => 'Failed to sync jobs',
                'details' => $e->getMessage(),
            ], 500);
        }
    }



    public function syncJobsWithShipping(Request $request): \Illuminate\Http\JsonResponse
    {
        $shipping = $request->input('shipping');
        $jobIds = $request->input('jobs');

        // Update all jobs with the selected material
        Job::whereIn('id', $jobIds)->update([
            'shippingInfo' => $shipping
        ]);

        return response()->json([
            'message' => "Synced jobs with shipping: $shipping",
        ]);
    }

    public function getJobsByIds(Request $request)
    {
        // Retrieve the array of job IDs from the request
        $jobIds = $request->input('jobs', []);

        // Fetch the jobs with matching IDs, preserving all stored values
        $jobs = Job::whereIn('id', $jobIds)
            ->with(['actions'])
            ->get()
            ->map(function ($job) {
                // Convert to array while preserving the exact values
                $jobArray = $job->toArray();
                // Ensure numeric values are preserved
                $jobArray['price'] = (float)$job->price; // Cost price
                $jobArray['salePrice'] = (float)($job->salePrice ?? 0); // Selling price
                $jobArray['width'] = (float)$job->width;
                $jobArray['height'] = (float)$job->height;
                $jobArray['total_area_m2'] = (float)($job->total_area_m2 ?? 0);
                $jobArray['dimensions_breakdown'] = $job->dimensions_breakdown ?? [];
                $jobArray['quantity'] = (int)$job->quantity;
                $jobArray['copies'] = (int)$job->copies;
                return $jobArray;
            })
            ->toArray();

        return response()->json(['jobs' => $jobs]);
    }

    public function recalculateJobCost(Request $request)
    {
        try {
            $request->validate([
                'job_id' => 'required|exists:jobs,id',
                'total_area_m2' => 'nullable|numeric|min:0',
                // Width/height are optional if we already have file-based dimensions
                'width' => 'nullable|numeric|min:0',
                'height' => 'nullable|numeric|min:0',
                'quantity' => 'required|integer|min:1',
                'copies' => 'required|integer|min:1',
            ]);

            $job = Job::find($request->input('job_id'));
            
            if (!$job) {
                return response()->json(['error' => 'Job not found'], 404);
            }

            // Update job quantity/copies
            $job->quantity = $request->input('quantity');
            $job->copies = $request->input('copies');

            // Calculate total area prioritizing file-derived dimensions
            $newTotalArea = null;
            $breakdownFromJob = $job->dimensions_breakdown ?? [];
            if (is_array($breakdownFromJob) && count($breakdownFromJob) > 0) {
                // Sum total_area_m2 from breakdown
                $newTotalArea = 0.0;
                foreach ($breakdownFromJob as $fileDims) {
                    $newTotalArea += (float)($fileDims['total_area_m2'] ?? 0);
                }
                $newTotalArea = round($newTotalArea, 6);
            } elseif ($request->filled('width') && $request->filled('height')) {
                // Fallback to width/height if provided
                $job->width = (float)$request->input('width');
                $job->height = (float)$request->input('height');
                $newTotalArea = round($job->total_area_m2 ?? 0, 6);
            }
            if ($newTotalArea !== null) {
                $job->total_area_m2 = $newTotalArea;
            }
            $job->save();

            $priceCalculationService = app()->make(PriceCalculationService::class);

            // Calculate selling price based on hierarchy (what customer pays)
            $sellingPrice = $priceCalculationService->calculateEffectivePrice(
                $job->catalog_item_id,
                $job->client_id,
                $job->quantity
            );

            // Calculate cost price using new component article system
            $costPrice = 0;
            $componentBreakdown = [];
            $materialDeduction = [];
            
            if ($job->catalog_item_id) {
                $catalogItem = CatalogItem::with('articles')->find($job->catalog_item_id);
                if ($catalogItem) {
                    $costPrice = $catalogItem->calculateJobCostPrice($job);
                    
                    // Get detailed breakdown for response
                    $costRequirements = $catalogItem->calculateCostRequirements($job);
                    foreach ($costRequirements as $requirement) {
                        $componentBreakdown[] = [
                            'article_id' => $requirement['article_id'],
                            'article_name' => $requirement['article']->name ?? 'Unknown',
                            'catalog_quantity' => $requirement['catalog_quantity'],
                            'actual_required' => $requirement['actual_required'],
                            'article_price' => $requirement['article_price'],
                            'total_cost' => $requirement['total_cost'],
                            'unit_type' => $requirement['unit_type']
                        ];
                    }
                    
                    // Calculate material deduction information
                    $materialRequirements = $catalogItem->calculateMaterialRequirements($job);
                    foreach ($materialRequirements as $requirement) {
                        $article = $requirement['article'];
                        $neededQuantity = $requirement['actual_required'];
                        
                        // Get current stock information
                        $stockBefore = $article->getCurrentStock();
                        $stockAfter = max(0, $stockBefore - $neededQuantity);
                        
                        $materialDeduction[] = [
                            'article_id' => $article->id,
                            'article_name' => $article->name,
                            'quantity_consumed' => $neededQuantity,
                            'stock_before' => $stockBefore,
                            'stock_after' => $stockAfter,
                            'unit_type' => $requirement['unit_type']
                        ];
                    }
                }
            }

            // Update job prices
            $job->price = $costPrice;
            if ($sellingPrice !== null) {
                $job->salePrice = $sellingPrice;
            }
            $job->save();

            return response()->json([
                'price' => $costPrice,
                'salePrice' => $sellingPrice,
                'component_count' => count($componentBreakdown),
                'component_breakdown' => $componentBreakdown,
                'material_deduction' => $materialDeduction,
                'message' => 'Job cost recalculated successfully'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error recalculating job cost:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to recalculate job cost',
                'details' => $e->getMessage(),
            ], 500);
        }
    }

    private function calculateTotalPrice($job)
    {
        // This method is kept for backwards compatibility but should not be used for catalog items
        if (isset($job['catalog_item_id'])) {
            return $job['price']; // Return the stored price for catalog items
        }

        // Original calculation for non-catalog items
        $smallMaterial = SmallMaterial::with('article')->find($job['small_material_id']);
        $largeMaterial = LargeFormatMaterial::with('article')->find($job['large_material_id']);
        $price = 0;
        $jobWithActions = Job::with('actions')->find($job['id'])->toArray();

        foreach ($jobWithActions['actions'] as $action) {
            if ($action['quantity']) {
                if (isset($smallMaterial)) {
                    $price = $price + ($action['quantity'] * $smallMaterial->article->price_1);
                }
                if (isset($largeMaterial)) {
                    $price = $price + ($action['quantity'] * $largeMaterial->article->price_1);
                }
            }
        }
        return $price;
    }

    public function getJobsWithPrices(Request $request)
    {
        $jobs = $request->input('jobs');
        foreach($jobs as $job) {
            $smallMaterial = SmallMaterial::with('article')->find($job['small_material_id']);
            $largeMaterial = LargeFormatMaterial::with('article')->find($job['large_material_id']);
            $price = 0;
            $jobWithActions = Job::with('actions')->find($job['id'])->toArray();

            foreach ($jobWithActions['actions'] as $action) {
                if ($action['quantity']) {
                    if (isset($smallMaterial)) {
                        $price = $price + ($action['quantity']*$smallMaterial->article->price_1);
                    }
                    if (isset($largeMaterial)) {
                        $price = $price + ($action['quantity']*$largeMaterial->article->price_1);
                    }
                }
            }
            $job['price'] = $price;
        }
        return response()->json([
            'jobs' => $jobs,
        ]);
    }

    public function getJobsByActionId(Request $request, $actionId)
    {
        $page = max((int) $request->query('page', 1), 1);
        $perPage = (int) $request->query('per_page', 10);
        $perPage = $perPage > 0 ? min($perPage, 100) : 10;
        $search = trim((string) $request->query('search', ''));

        // Ensure action exists
        $actions = DB::table('job_actions')->where('name', $actionId)->get();
        if (!$actions->count()) {
            return response()->json(['error' => 'Action not found'], 404);
        }

        // Base query: invoices that have at least one job for this action that is not completed
        $base = DB::table('job_actions')
            ->join('job_job_action', 'job_actions.id', '=', 'job_job_action.job_action_id')
            ->join('jobs', 'jobs.id', '=', 'job_job_action.job_id')
            ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
            ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
            ->leftJoin('users', 'invoices.created_by', '=', 'users.id')
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
            ->where('job_actions.name', $actionId)
            ->where('jobs.status', '!=', 'Completed')
            ->where(function ($q) {
                $q->whereNull('job_actions.status')
                  ->orWhere('job_actions.status', '!=', 'Completed');
            });

        if ($search !== '') {
            $base->where(function ($q) use ($search) {
                $q->where('invoices.invoice_title', 'like', "%$search%")
                  ->orWhere('users.name', 'like', "%$search%")
                  ->orWhere('clients.name', 'like', "%$search%");
                if (is_numeric($search)) {
                    $q->orWhere('invoices.id', (int) $search);
                }
            });
        }

        // Single invoices paginator driven by EXISTS subquery for robustness
        $invoicesPaginator = DB::table('invoices')
            ->leftJoin('users', 'invoices.created_by', '=', 'users.id')
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id')
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($q2) use ($search) {
                    $q2->where('invoices.invoice_title', 'like', "%$search%")
                       ->orWhere('users.name', 'like', "%$search%")
                       ->orWhere('clients.name', 'like', "%$search%");
                    if (is_numeric($search)) {
                        $q2->orWhere('invoices.id', (int) $search);
                    }
                });
            })
            ->whereExists(function ($q) use ($actionId) {
                $q->select(DB::raw(1))
                  ->from('invoice_job')
                  ->join('jobs', 'jobs.id', '=', 'invoice_job.job_id')
                  ->join('job_job_action', 'job_job_action.job_id', '=', 'jobs.id')
                  ->join('job_actions', 'job_actions.id', '=', 'job_job_action.job_action_id')
                  ->whereColumn('invoice_job.invoice_id', 'invoices.id')
                  ->where('job_actions.name', $actionId)
                  ->where('jobs.status', '!=', 'Completed')
                  ->where(function ($q3) {
                      $q3->whereNull('job_actions.status')
                         ->orWhere('job_actions.status', '!=', 'Completed');
                  });
            })
            ->orderBy('invoices.start_date', 'asc')
            ->select('invoices.*', 'users.name as user_name', 'clients.name as client_name')
            ->paginate($perPage, ['*'], 'page', $page);

        $invoices = collect($invoicesPaginator->items());
        if ($invoices->isEmpty()) {
            return response()->json([
                'invoices' => [],
                'actionId' => $actionId,
                'currentUserId' => auth()->id(),
                'pagination' => [
                    'current_page' => $invoicesPaginator->currentPage(),
                    'last_page' => $invoicesPaginator->lastPage(),
                    'per_page' => $invoicesPaginator->perPage(),
                    'total' => $invoicesPaginator->total(),
                ],
            ]);
        }

        // Attach filtered, non-completed jobs for the current action to each invoice
        $finalInvoices = [];
        foreach ($invoices as $invoice) {
            $jobIdsForInvoice = DB::table('invoice_job')
                ->where('invoice_id', $invoice->id)
                ->pluck('job_id');

            if ($jobIdsForInvoice->isEmpty()) {
                continue;
            }

            // Narrow to jobs that have this action not completed
            $matchingJobIds = DB::table('job_job_action')
                ->join('job_actions', 'job_actions.id', '=', 'job_job_action.job_action_id')
                ->whereIn('job_job_action.job_id', $jobIdsForInvoice)
                ->where('job_actions.name', $actionId)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhere('job_actions.status', '!=', 'Completed');
                })
                ->pluck('job_job_action.job_id');

            if ($matchingJobIds->isEmpty()) {
                continue;
            }

            // Load only the matching jobs, exclude completed jobs
            $jobsWithActions = Job::with('actions')
                ->whereIn('id', $matchingJobIds)
                ->where('status', '!=', 'Completed')
                ->get();

            $filteredJobs = [];
            foreach ($jobsWithActions as $eloquentJob) {
                $sortedActions = collect($eloquentJob->actions)->sortBy('id')->values()->map(function($action) {
                    return [
                        'id' => $action->id,
                        'name' => $action->name,
                        'status' => $action->status,
                        'started_at' => $action->started_at ? $action->started_at->toISOString() : null,
                        'ended_at' => $action->ended_at ? $action->ended_at->toISOString() : null,
                        'started_by' => $action->started_by,
                        'hasNote' => $action->hasNote,
                        'quantity' => $action->pivot->quantity ?? null,
                        'pivot' => $action->pivot
                    ];
                })->toArray();

                $actionForJob = collect($sortedActions)->first(function ($a) use ($actionId) {
                    return ($a['name'] ?? null) === $actionId;
                });
                if (($actionForJob['status'] ?? null) === 'Completed') {
                    continue;
                }

                $eloquentJob->actions = $sortedActions;
                $eloquentJob->hasNote = (bool) ($actionForJob['hasNote'] ?? false);
                $filteredJobs[] = $eloquentJob;
            }

            if (!empty($filteredJobs)) {
                $invoice->jobs = $filteredJobs;
                $finalInvoices[] = $invoice;
            }
        }

        return response()->json([
            'invoices' => $finalInvoices,
            'actionId' => $actionId,
            'currentUserId' => auth()->id(),
            'pagination' => [
                'current_page' => $invoicesPaginator->currentPage(),
                'last_page' => $invoicesPaginator->lastPage(),
                'per_page' => $invoicesPaginator->perPage(),
                'total' => $invoicesPaginator->total(),
            ],
        ]);
    }

    public function getActionsByMachineName(Request $request, $machineName)
    {
        // Fetch jobs associated with the specified machineCut or machinePrint
        $jobs = DB::table('jobs')
            ->where('machineCut', $machineName)
            ->orWhere('machinePrint', $machineName)
            ->whereIn('status', ['Not started yet', 'In progress'])
            ->get();

        // If no jobs are found for the specified machine, return an appropriate response
        if (!$jobs->count()) {
            return response()->json(['error' => 'No jobs found for the specified machine'], 404);
        }

        // Fetch actions associated with the found jobs
        $actionIds = DB::table('job_job_action')
            ->whereIn('job_id', $jobs->pluck('id'))
            ->pluck('job_action_id');

        $actions = DB::table('job_actions')
            ->whereIn('id', $actionIds)
            ->get();

        // Attach actions to each job
        foreach ($jobs as $job) {
            $actionIdsForJob = DB::table('job_job_action')
                ->where('job_id', $job->id)
                ->pluck('job_action_id');

            $actionsForJob = DB::table('job_actions')
                ->whereIn('id', $actionIdsForJob)
                ->get();

            // Now, get all jobs with actions in one go
            $jobsWithActions = Job::with('actions')->whereIn('id', $actionIdsForJob)->get()->keyBy('id');

            // Replace each job in $jobsWithActions with the corresponding one from $jobs
            if (isset($jobsWithActions[$job->id])) {
                $jobsWithActions[$job->id] = $job;
            }

            $job->actions = $actionsForJob;
        }

        // Now, let's retrieve invoices associated with these jobs
        $invoiceIds = DB::table('invoice_job')
            ->whereIn('job_id', $jobs->pluck('id'))
            ->pluck('invoice_id');

        // Fetch the invoices based on the retrieved invoice IDs
        $invoices = DB::table('invoices')
            ->whereIn('invoices.id', $invoiceIds)
            ->orderBy('start_date', 'asc')
            ->where('status', '!=', 'Completed')
            ->leftJoin('users', 'invoices.created_by', '=', 'users.id') // Join with users table
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id') // Join with clients table
            ->select('invoices.*', 'users.name as user_name', 'clients.name as client_name')
            ->get();

        // Attach jobs to each invoice
        foreach ($invoices as $invoice) {
            $jobIdsForInvoice = DB::table('invoice_job')
                ->where('invoice_id', $invoice->id)
                ->pluck('job_id');

            $jobsForInvoice = DB::table('jobs')
                ->whereIn('id', $jobIdsForInvoice)
                ->get();

            // Now, get all jobs with actions in one go
            $jobsWithActions = Job::with('actions')->whereIn('id', $jobIdsForInvoice)->get()->keyBy('id');

            // Replace each job in $jobsForInvoice with the corresponding one from $jobsWithActions
            foreach ($jobsForInvoice as $index => $job) {
                if (isset($jobsWithActions[$job->id])) {
                    $jobsForInvoice[$index] = $jobsWithActions[$job->id];
                }
            }
            // Compute job.hasNote for this machine context (machine name is the action name here)
            foreach ($jobsForInvoice as $index => $job) {
                $actionsForJob = DB::table('job_job_action')
                    ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                    ->where('job_job_action.job_id', $job->id)
                    ->select('job_actions.*')
                    ->get();

                $jobsForInvoice[$index]->actions = $actionsForJob;

                $hasNoteForMachine = false;
                foreach ($actionsForJob as $a) {
                    if (($a->name ?? null) === $machineName && ($a->hasNote ?? false)) {
                        $hasNoteForMachine = true;
                        break;
                    }
                }
                $jobsForInvoice[$index]->hasNote = $hasNoteForMachine;
            }

            $invoice->jobs = $jobsForInvoice;
        }

        return response()->json([
            'invoices' => $invoices, // Include invoices in the response
            'machineName' => $machineName,
        ]);
    }

    public function updateFile(Request $request, $id) {
        try {
            // Validate the request data
            $this->validate($request, [
                'file' => 'required|mimetypes:image/tiff,application/pdf',
            ]);

            // Find the job
            $job = Job::findOrFail($id);
            
            // Store reference to old original file for cleanup
            $oldOriginalFile = $job->originalFile;

            // Handle file upload and storage
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileExtension = $file->getClientOriginalExtension();
                
                // Store the original file in R2 if it's a PDF
                $pdfPath = null;
                if ($fileExtension === 'pdf') {
                    $pdfPath = $this->templateStorageService->storeTemplate($file, 'job-originals');
                }

                if ($fileExtension === 'pdf') {
                    $imagick = new Imagick();
                    $this->setGhostscriptPath($imagick);
                    $imagick->readImage($file->getPathname());
                    $pageCount = $imagick->getNumberImages();
                    
                    \Log::info('Processing PDF file replacement with multiple pages', [
                        'job_id' => $id,
                        'file' => $file->getClientOriginalName(),
                        'page_count' => $pageCount
                    ]);
                    
                    // Variables to store cumulative area and page dimensions for this file
                    $fileTotalAreaM2 = 0;
                    $pageDimensions = [];
                    
                    // Iterate through all pages of the PDF to calculate total area
                    for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                        $pageImagick = new Imagick();
                        $this->setGhostscriptPath($pageImagick);
                        $pageImagick->readImage($file->getPathname() . '[' . $pageIndex . ']');
                        $pageImagick->setImageFormat('jpg');
                        
                        // Create temporary image for dimension calculation
                        $tempImagePath = storage_path('app/temp/replace_dim_calc_' . $pageIndex . '_' . time() . '.jpg');
                        $pageImagick->writeImage($tempImagePath);
                        
                        // Calculate dimensions from the page
                        list($width, $height) = getimagesize($tempImagePath);
                        $dpi = 72; // Default DPI if not available
                        $widthInMm = ($width / $dpi) * 25.4;
                        $heightInMm = ($height / $dpi) * 25.4;
                        $areaM2 = ($widthInMm * $heightInMm) / 1000000;
                        
                        // Store individual page dimensions
                        $pageDimensions[] = [
                            'page' => $pageIndex + 1,
                            'width_mm' => $widthInMm,
                            'height_mm' => $heightInMm,
                            'area_m2' => $areaM2
                        ];
                        
                        // Add to file total area
                        $fileTotalAreaM2 += $areaM2;
                        
                        // Clean up temp file
                        if (file_exists($tempImagePath)) {
                            unlink($tempImagePath);
                        }
                        
                        $pageImagick->clear();
                        
                        \Log::info('Calculated dimensions for page in file replacement', [
                            'job_id' => $id,
                            'file' => $file->getClientOriginalName(),
                            'page' => $pageIndex + 1,
                            'width_mm' => $widthInMm,
                            'height_mm' => $heightInMm,
                            'area_m2' => $areaM2
                        ]);
                    }
                    
                    // Create preview image from first page only
                    $previewImagick = new Imagick();
                    $this->setGhostscriptPath($previewImagick);
                    $previewImagick->readImage($file->getPathname() . '[0]'); // First page only for preview
                    $previewImagick->setImageFormat('jpg');
                    
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                    $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                    $previewImagick->writeImage($imagePath);
                    $previewImagick->clear();
                    $imagick->clear();

                    // Update job with file info and dimensions
                    $job->file = $imageFilename;
                    
                    // Clear old original files and add the new one
                    $job->originalFile = [];
                    if ($pdfPath) {
                        $job->addOriginalFile($pdfPath);
                    }
                    
                    $job->total_area_m2 = $fileTotalAreaM2;
                    $job->dimensions_breakdown = [
                        [
                            'filename' => $file->getClientOriginalName(),
                            'page_count' => $pageCount,
                            'total_area_m2' => $fileTotalAreaM2,
                            'page_dimensions' => $pageDimensions,
                            'index' => 0
                        ]
                    ];
                    $job->save();

                    // Clean up old original files if they exist and are different from new file
                    if ($oldOriginalFile) {
                        $oldFiles = is_array($oldOriginalFile) ? $oldOriginalFile : [$oldOriginalFile];
                        foreach ($oldFiles as $oldFile) {
                            if ($oldFile && $oldFile !== $pdfPath) {
                                try {
                                    $this->templateStorageService->deleteTemplate($oldFile);
                                } catch (\Exception $e) {
                                    \Log::warning('Failed to delete old original file: ' . $e->getMessage(), [
                                        'old_file' => $oldFile,
                                        'job_id' => $id
                                    ]);
                                }
                            }
                        }
                    }

                    \Log::info('Successfully replaced job file with PDF (all pages processed)', [
                        'job_id' => $id,
                        'new_file' => $file->getClientOriginalName(),
                        'total_pages' => $pageCount,
                        'new_area_m2' => $fileTotalAreaM2
                    ]);

                    return response()->json([
                        'message' => 'File updated successfully',
                        'job' => $job,
                        'file_url' => '/storage/uploads/' . $imageFilename
                    ]);
                } else if ($fileExtension === 'tiff' || $fileExtension === 'tif') {
                    $imagick = new Imagick();
                    $imagick->readImage($file->getPathname());
                    $imagick->setImageFormat('jpg');
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                    $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                    $imagick->writeImage($imagePath);
                    $imagick->clear();

                    // Calculate dimensions
                    list($width, $height) = getimagesize($imagePath);
                    $dpi = 72; // Default DPI if not available
                    $widthInMm = ($width / $dpi) * 25.4;
                    $heightInMm = ($height / $dpi) * 25.4;

                    // Update job with file info and dimensions
                    $job->file = $imageFilename;
                    $job->width = $widthInMm;
                    $job->height = $heightInMm;
                    $job->save();

                    // Clear original files since TIFF doesn't have an original file, but might be replacing a PDF
                    $job->originalFile = [];
                    
                    // Clean up old original files if they exist (TIFF doesn't have originalFile, but might be replacing a PDF)
                    if ($oldOriginalFile) {
                        $oldFiles = is_array($oldOriginalFile) ? $oldOriginalFile : [$oldOriginalFile];
                        foreach ($oldFiles as $oldFile) {
                            if ($oldFile) {
                                try {
                                    $this->templateStorageService->deleteTemplate($oldFile);
                                } catch (\Exception $e) {
                                    \Log::warning('Failed to delete old original file: ' . $e->getMessage(), [
                                        'old_file' => $oldFile,
                                        'job_id' => $id
                                    ]);
                                }
                            }
                        }
                    }

                    return response()->json([
                        'message' => 'File updated successfully',
                        'job' => $job,
                        'file_url' => '/storage/uploads/' . $imageFilename
                    ]);
                }
            }
            return response()->json(['message' => 'File not provided'], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $job = Job::with(['invoice'])->findOrFail($id);

            // Validate request
            $validatedData = $request->validate([
                'quantity' => 'sometimes|required|numeric',
                'copies' => 'sometimes|required|numeric',
                'catalog_item_id' => 'sometimes|nullable|exists:catalog_items,id',
                'client_id' => 'sometimes|nullable|exists:clients,id',
                'width' => 'sometimes|required|numeric',
                'height' => 'sometimes|required|numeric',
                'file' => 'sometimes|required',
                'status' => 'sometimes|required',
                'salePrice' => 'sometimes|required',
            ]);

            // Recalculate price if quantity or copies is being updated AND the job has catalog/client info
            if ($request->has('quantity') || $request->has('copies')) {
                // Get the catalog_item_id and client_id from request or existing job/invoice
                $catalogItemId = $request->input('catalog_item_id') ?? $job->catalog_item_id ?? $job->invoice?->catalog_item_id;
                $clientId = $request->input('client_id') ?? $job->client_id ?? $job->invoice?->client_id;

                \Log::info('Updating job quantity/copies', [
                    'job_id' => $id,
                    'catalog_item_id' => $catalogItemId,
                    'client_id' => $clientId,
                    'new_quantity' => $request->input('quantity', $job->quantity),
                    'new_copies' => $request->input('copies', $job->copies),
                    'old_quantity' => $job->quantity,
                    'old_copies' => $job->copies,
                    'is_catalog_job' => !is_null($catalogItemId) && !is_null($clientId)
                ]);

                // Only recalculate price for catalog-based jobs (those with catalog_item_id and client_id)
                if (!is_null($catalogItemId) && !is_null($clientId)) {
                    $catalogItem = \App\Models\CatalogItem::find($catalogItemId);
                    
                    if ($catalogItem) {
                        // Get the new quantity and copies values
                        $newQuantity = $request->input('quantity', $job->quantity);
                        $newCopies = $request->input('copies', $job->copies);
                        
                        // Get the pricing multiplier based on catalog item settings
                        $pricingMultiplier = $catalogItem->getPricingMultiplier($newQuantity, $newCopies);
                        
                        // Get the base price from the catalog item
                        $basePrice = $catalogItem->getEffectivePrice($clientId, 1);
                        
                        // Calculate new selling price
                        $newSalePrice = $basePrice * $pricingMultiplier;
                        
                        \Log::info('Price calculation result for catalog job with new pricing method', [
                            'job_id' => $id,
                            'catalog_item_id' => $catalogItemId,
                            'pricing_method' => $catalogItem->getPricingMethod(),
                            'base_price' => $basePrice,
                            'pricing_multiplier' => $pricingMultiplier,
                            'new_quantity' => $newQuantity,
                            'new_copies' => $newCopies,
                            'calculated_sale_price' => $newSalePrice
                        ]);
                        
                        // Update selling price (salePrice)
                        $job->salePrice = $newSalePrice;
                        
                        // Recalculate cost price based on actual component article requirements
                        $job->quantity = $newQuantity;
                        $job->copies = $newCopies;
                        $newCostPrice = $catalogItem->calculateJobCostPrice($job);
                        $job->price = $newCostPrice;
                        
                        \Log::info('Recalculated cost price for catalog job with new pricing method', [
                            'job_id' => $id,
                            'pricing_multiplier' => $pricingMultiplier,
                            'new_cost_price' => $newCostPrice,
                            'new_sale_price' => $newSalePrice
                        ]);
                    }
                } else {
                    \Log::info('Skipping price recalculation for manual job', [
                        'job_id' => $id,
                        'reason' => 'Missing catalog_item_id or client_id'
                    ]);
                    
                    // For manual jobs, scale both selling price and cost price proportionally based on quantity change only
                    if ($request->has('quantity') && $job->quantity > 0) {
                        $quantityRatio = $request->input('quantity') / $job->quantity;
                        
                        // Scale selling price if it exists
                        if ($job->salePrice) {
                            $job->salePrice = $job->salePrice * $quantityRatio;
                        }
                        
                        // Scale cost price if it exists
                        if ($job->price) {
                            $job->price = $job->price * $quantityRatio;
                        }
                        
                        \Log::info('Scaled both prices for manual job', [
                            'job_id' => $id,
                            'old_quantity' => $job->quantity,
                            'new_quantity' => $request->input('quantity'),
                            'quantity_ratio' => $quantityRatio,
                            'new_sale_price' => $job->salePrice,
                            'new_cost_price' => $job->price
                        ]);
                    }
                }
            }

            // Update catalog_item_id and client_id if provided
            if (isset($validatedData['catalog_item_id'])) {
                $job->catalog_item_id = $validatedData['catalog_item_id'];
            }
            if (isset($validatedData['client_id'])) {
                $job->client_id = $validatedData['client_id'];
            }

            // Update other fields from validated data
            if (isset($validatedData['quantity'])) {
                $job->quantity = $validatedData['quantity'];
            }
            if (isset($validatedData['copies'])) {
                $job->copies = $validatedData['copies'];
            }
            if (isset($validatedData['salePrice'])) {
                $job->salePrice = $validatedData['salePrice'];
            }
            if (isset($validatedData['width'])) {
                $job->width = $validatedData['width'];
            }
            if (isset($validatedData['height'])) {
                $job->height = $validatedData['height'];
            }
            if (isset($validatedData['file'])) {
                $job->file = $validatedData['file'];
            }
            if (isset($validatedData['status'])) {
                $job->status = $validatedData['status'];
            }

            $job->load('catalogItem');

            // Save all changes
            $job->save();

            // Reload the job to get fresh data
            $job->refresh();

            return response()->json([
                'message' => 'Job updated successfully',
                'job' => $job
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating job:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to update job',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function calculateImageDimensions($id): array
    {
        $job = Job::with('actions')->find($id);
        // Construct the full path to the image in the storage directory
        $fullImagePath = storage_path('app/public/uploads/' . $job->file);

            // Check if the image file exists
        if (file_exists($fullImagePath)) {
            // Get the image dimensions using PHP's built-in functions
            list($width, $height) = getimagesize($fullImagePath);
            $dpi = $job->file->dpi ?? 72;

            $widthInMm = ($width / $dpi) * 25.4;
            $heightInMm = ($height / $dpi) * 25.4;

            // Return the dimensions
        return ['width' => $widthInMm, 'height' => $heightInMm];
        } else {
            // Handle the case where the image file does not exist
            return ['width' => 0, 'height' => 0];
        }
    }

    public function jobMachinePrintCounts()
    {
        // Initialize an empty array to hold the final counts
        $counts = [];

        // Get the names of machines from the machine_print table
        $machineNames = DB::table('machines_print')
            ->pluck('name')
            ->unique();

        // For each machine name, get the counts of different statuses from job_actions
        foreach ($machineNames as $name) {
            $base = DB::table('job_actions')
                ->join('job_job_action', 'job_actions.id', '=', 'job_job_action.job_action_id')
                ->join('jobs', 'jobs.id', '=', 'job_job_action.job_id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('job_actions.name', $name)
                ->where('jobs.status', '!=', 'Completed');

            $total = (clone $base)
                ->where('job_actions.status', 'In progress')
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $secondaryCount = (clone $base)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhere('job_actions.status', 'Not started yet');
                })
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $onHoldCount = (clone $base)
                ->where('invoices.onHold', true)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhereIn('job_actions.status', ['Not started yet', 'In progress']);
                })
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $onRushCount = (clone $base)
                ->where('invoices.rush', true)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhereIn('job_actions.status', ['Not started yet', 'In progress']);
                })
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            // Add the counts to the array
            if ($total > 0 || $secondaryCount > 0) {
                $counts[] = [
                    'name' => $name,
                    'total' => $total,
                    'secondaryCount' => $secondaryCount,
                    'onHoldCount' => $onHoldCount,
                    'onRushCount' => $onRushCount,
                ];
            }
        }

        // Return the counts as a JSON response
        return response()->json($counts);
    }

    public function jobMachineCutCounts()
    {
        // Initialize an empty array to hold the final counts
        $counts = [];

        // Get the names of machines from the machine_print table
        $machineNames = DB::table('machines_cut')
            ->pluck('name')
            ->unique();

        // For each machine name, get the counts of different statuses
        foreach ($machineNames as $name) {
            $total = DB::table('jobs')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('jobs.machineCut', $name)
                ->where('jobs.status', 'In Progress')
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $secondaryCount = DB::table('jobs')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('jobs.machineCut', $name)
                ->where('jobs.status', 'Not started yet')
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $onHoldCount = DB::table('jobs')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('jobs.machineCut', $name)
                ->where('invoices.onHold', true)
                ->whereIn('jobs.status', ['Not started yet', 'In Progress'])
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $onRushCount = DB::table('jobs')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('jobs.machineCut', $name)
                ->where('invoices.rush', true)
                ->whereIn('jobs.status', ['Not started yet', 'In Progress'])
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            // Add the counts to the array if applicable
            if ($total > 0 || $secondaryCount > 0) {
                $counts[] = [
                    'name' => $name,
                    'total' => $total,
                    'secondaryCount' => $secondaryCount,
                    'onHoldCount' => $onHoldCount,
                    'onRushCount' => $onRushCount,
                ];
            }
        }

        // Return the counts as a JSON response
        return response()->json($counts);
    }

    public function jobActionStatusCounts()
    {
        // Initialize an empty array to hold the final counts
        $counts = [];

        // Get the names of all job actions
        $actionNames = DB::table('job_actions')->pluck('name')->unique()->filter(function ($name) {
            return !Str::startsWith($name, 'Machine');
        });

        // For each action name, get the count of 'In progress' and 'Not started yet' statuses
        foreach ($actionNames as $name) {
            $base = DB::table('job_actions')
                ->join('job_job_action', 'job_actions.id', '=', 'job_job_action.job_action_id')
                ->join('jobs', 'jobs.id', '=', 'job_job_action.job_id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('job_actions.name', $name)
                ->where('jobs.status', '!=', 'Completed');

            $total = (clone $base)
                ->where('job_actions.status', 'In progress')
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $secondaryCount = (clone $base)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhere('job_actions.status', 'Not started yet');
                })
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $onHoldCount = (clone $base)
                ->where('invoices.onHold', true)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhereIn('job_actions.status', ['Not started yet', 'In progress']);
                })
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            $onRushCount = (clone $base)
                ->where('invoices.rush', true)
                ->where(function ($q) {
                    $q->whereNull('job_actions.status')
                      ->orWhereIn('job_actions.status', ['Not started yet', 'In progress']);
                })
                ->distinct('invoice_job.invoice_id')
                ->count('invoice_job.invoice_id');

            // Add the counts to the array
            if ($total > 0 || $secondaryCount > 0) {
                $counts[] = [
                    'name' => $name,
                    'total' => $total,
                    'secondaryCount' => $secondaryCount,
                    'onHoldCount' => $onHoldCount,
                    'onRushCount' => $onRushCount,
                ];
            }
        }

        // Return the counts as a JSON response
        return response()->json($counts);
    }


    public function production()
    {
        return Inertia::render('Production/Dashboard');
    }
    public function machine()
    {
        return Inertia::render('Production/MachineDashboard');
    }

    public function fireStartJobEvent(Request $request) {
        try {
            $actionId = $request->input('action');
            $actionName = $request->input('action_name');
            $jobId = $request->input('job');
            $invoiceId = $request->input('invoice');
            
            \Log::info('fireStartJobEvent called', [
                'action_id' => $actionId,
                'request_data' => $request->all(),
                'user_id' => auth()->id()
            ]);
            
            // Load job and invoice first
            $job = Job::findOrFail($jobId);
            $invoice = Invoice::findOrFail($invoiceId);

            // Resolve action directly by id (model), avoid relation class confusion
            $action = \App\Models\JobAction::findOrFail($actionId);
            
            \Log::info('Action found', [
                'action_id' => $action->id,
                'action_name' => $action->name,
                'current_status' => $action->status
            ]);
            
            // Check if action is already in progress
            if ($action->isInProgress()) {
                \Log::warning('Action already in progress', ['action_id' => $actionId]);
                return response()->json(['error' => 'Action is already in progress'], 400);
            }
            
            // Check if action is already completed
            if ($action->isCompleted()) {
                \Log::warning('Action already completed', ['action_id' => $actionId]);
                return response()->json(['error' => 'Action is already completed'], 400);
            }
            
            // Start the action
            $action->startAction(auth()->id());
            
            \Log::info('Action started', [
                'action_id' => $action->id,
                'new_status' => $action->status,
                'started_at' => $action->started_at
            ]);
            
            // Create analytics record
            DB::table('workers_analytics')->insert([
                'invoice_id' => $request->input('invoice'),
                'job_id' => $request->input('job'),
                'action_id' => $actionId,
                'user_id' => auth()->id(),
                'time_spent' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            
            // Job and invoice already loaded above
            
            \Log::info('Starting job action', [
                'action_id' => $actionId,
                'job_id' => $job->id,
                'invoice_id' => $invoice->id,
                'action_status' => $action->status,
                'started_at' => $action->started_at
            ]);
            
            // Update job status to "In progress"
            $job->update(['status' => 'In progress']);
            
            // Update invoice status based on all job actions
            $this->updateInvoiceStatus($invoice);
            
            // Dispatch the JobStarted event
            try {
                event(new JobStarted($job, $invoice));
                \Log::info('JobStarted event dispatched successfully');
            } catch (\Exception $e) {
                \Log::error('Error dispatching JobStarted event', [
                    'error' => $e->getMessage(),
                    'job_id' => $job->id,
                    'invoice_id' => $invoice->id
                ]);
                // Don't fail the entire request if event dispatch fails
            }
            
            $responseData = [
                'success' => true,
                'action' => [
                    'id' => $action->id,
                    'name' => $action->name,
                    'status' => $action->status
                ],
                'started_at' => $action->started_at ? $action->started_at->toISOString() : null
            ];
            
            \Log::info('Sending success response', [
                'response_data' => $responseData
            ]);
            
            return response()->json($responseData);
        } catch (\Exception $e) {
            \Log::error('Error in fireStartJobEvent', [
                'action_id' => $actionId ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fireEndJobEvent(Request $request) {
        try {
            $actionId = $request->input('action');
            $time_spent = $request->input('time_spent');
            
            // Find the action and end it
            $action = \App\Models\JobAction::findOrFail($actionId);
            
            // Check if action is in progress
            if (!$action->isInProgress()) {
                return response()->json(['error' => 'Action is not in progress'], 400);
            }
            
            // Check if the current user started the action
            if ($action->started_by !== auth()->id()) {
                return response()->json(['error' => 'Only the user who started the action can end it'], 403);
            }
            
            // End the action
            $action->endAction();
            
            // Update analytics record
            $workerAnalytics = DB::table('workers_analytics')
                ->where('job_id', $request->input('job'))
                ->where('invoice_id', $request->input('invoice'))
                ->where('action_id', $actionId)
                ->where('user_id', auth()->id())
                ->first();
                
            if ($workerAnalytics) {
                DB::table('workers_analytics')
                    ->where('id', $workerAnalytics->id)
                    ->update(['time_spent' => $time_spent]);
            }
            
            // Find job and invoice for event
            $job = Job::findOrFail($request->input('job'));
            $invoice = Invoice::findOrFail($request->input('invoice'));
            
            \Log::info('Ending job action', [
                'action_id' => $actionId,
                'job_id' => $job->id,
                'invoice_id' => $invoice->id,
                'action_status' => $action->status
            ]);
            
            // Update job status based on all actions
            $this->updateJobStatus($job);
            
            // Refresh job to get updated status
            $job->refresh();
            
            \Log::info('Job status updated', [
                'job_id' => $job->id,
                'new_job_status' => $job->status
            ]);
            
            // Update invoice status based on all job actions
            $this->updateInvoiceStatus($invoice);
            
            // Refresh invoice to get updated status
            $invoice->refresh();
            
            \Log::info('Invoice status updated', [
                'invoice_id' => $invoice->id,
                'new_invoice_status' => $invoice->status
            ]);
            
            // Dispatch the JobEnded event
            event(new JobEnded($job, $invoice));
            
            return response()->json([
                'success' => true,
                'action' => $action,
                'ended_at' => $action->ended_at ? $action->ended_at->toISOString() : null,
                'duration' => $action->getDurationInSeconds()
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function adminEndJob(Request $request) {
        try {
            $user = auth()->user();
            if (!$user || !$user->role || $user->role->name !== 'Admin') {
                return response()->json(['error' => 'Forbidden'], 403);
            }

            $actionId = $request->input('action');
            $actionName = $request->input('action_name');
            $jobInput = $request->input('job');
            $invoiceInput = $request->input('invoice');
            $startedAt = $request->input('started_at');
            $endedAt = $request->input('ended_at');

            // Resolve invoice if provided
            $invoice = null;
            if ($invoiceInput) {
                $invoice = Invoice::find($invoiceInput);
            }

            // Resolve job from input (can be id or name), or via invoice + action name, or via action relation
            $job = null;
            if ($jobInput) {
                if (is_numeric($jobInput)) {
                    $job = Job::find($jobInput);
                } else {
                    // Try by job name if a string was sent by mistake
                    $job = Job::where('name', $jobInput)->first();
                }
            }
            if (!$job && $invoice && $actionName) {
                // Find a job on the invoice that has this action name
                $job = $invoice->jobs()
                    ->whereHas('actions', function($q) use ($actionName) {
                        $q->where('name', $actionName);
                    })
                    ->first();
            }

            // Try to infer job from action id
            $action = null;
            if ($actionId) {
                $action = \App\Models\JobAction::find($actionId);
                if ($action && !$job) {
                    $job = $action->jobs()->first();
                }
            }
            if (!$job) {
                return response()->json(['error' => 'Job not found'], 404);
            }
            if (!$invoice) {
                $invoice = $job->invoice;
            }

            // Resolve the action strictly within this job
            if (!$action) {
                $query = $job->actions();
                if ($actionId) {
                    $action = (clone $query)->where('job_actions.id', $actionId)->first();
                }
                if (!$action && $actionName) {
                    $action = (clone $query)->where('job_actions.name', $actionName)->first();
                }
            }
            if (!$action) {
                return response()->json(['error' => 'Action not found for job'], 404);
            }

            // Ensure timestamps and mark completed
            $action->started_at = $action->started_at ?: ($startedAt ? new \Carbon\Carbon($startedAt) : now());
            $action->started_by = $action->started_by ?: $user->id;
            $action->ended_at = $endedAt ? new \Carbon\Carbon($endedAt) : now();
            $action->status = 'Completed';
            $action->save();

            // Update job and invoice statuses
            $this->updateJobStatus($job);
            $job->refresh();
            if ($invoice) {
                $this->updateInvoiceStatus($invoice);
                $invoice->refresh();
            }

            // Best-effort analytics insert
            try {
                DB::table('workers_analytics')->insert([
                    'invoice_id' => $invoice?->id,
                    'job_id' => $job->id,
                    'action_id' => $action->id,
                    'user_id' => $user->id,
                    'time_spent' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Throwable $e) {
                // ignore
            }

            return response()->json([
                'success' => true,
                'action' => $action,
                'started_at' => $action->started_at ? $action->started_at->toISOString() : null,
                'ended_at' => $action->ended_at ? $action->ended_at->toISOString() : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function insertAnalytics(Request $request) {
        $jobData = $request->input('job');
        $invoiceData = $request->input('invoice');
        $actionId = $request->input('action');
        $time_spent = $request->input('time_spent');

        // Find the matching record in the workers_analytics table
        $workerAnalytics = WorkerAnalytics::where('job_id', $jobData['id'])
            ->where('invoice_id', $invoiceData['id'])
            ->where('action_id', $actionId)
            ->where('user_id', auth()->id())
            ->first();

        // If the record exists, update the time_spent
        if ($workerAnalytics) {
            $workerAnalytics->time_spent = $time_spent;
            $workerAnalytics->save();
        }
    }

    public function getActionStatus(Request $request, $actionId) {
        try {
            $action = \App\Models\JobAction::findOrFail($actionId);
            
            // Check for abandoned actions (running for more than 24 hours)
            if ($action->isInProgress() && $action->started_at) {
                $hoursSinceStart = $action->started_at->diffInHours(now());
                if ($hoursSinceStart > 24) {
                    // Auto-complete abandoned actions
                    $action->endAction();
                    
                    // Update related job and invoice statuses
                    $jobs = $action->jobs()->get();
                    foreach ($jobs as $job) {
                        $this->updateJobStatus($job);
                        $invoice = $job->invoice;
                        if ($invoice) {
                            $this->updateInvoiceStatus($invoice);
                        }
                    }
                    
                    \Log::warning("Auto-completed abandoned action", [
                        'action_id' => $actionId,
                        'started_at' => $action->started_at,
                        'hours_running' => $hoursSinceStart
                    ]);
                }
            }
            
            return response()->json([
                'success' => true,
                'action' => [
                    'id' => $action->id,
                    'name' => $action->name,
                    'status' => $action->status,
                    'started_at' => $action->started_at ? $action->started_at->toISOString() : null,
                    'ended_at' => $action->ended_at ? $action->ended_at->toISOString() : null,
                    'started_by' => $action->started_by,
                    'duration' => $action->getDurationInSeconds(),
                    'is_in_progress' => $action->isInProgress(),
                    'is_completed' => $action->isCompleted(),
                    'is_not_started' => $action->isNotStarted(),
                    'hours_running' => $action->started_at ? $action->started_at->diffInHours(now()) : 0
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update job status based on the status of all its actions
     */
    private function updateJobStatus(Job $job): void
    {
        try {
            // Get all actions for this job with proper loading
            $actions = $job->actions()->get();
            
            if ($actions->isEmpty()) {
                $job->update(['status' => 'Not started yet']);
                return;
            }
            
            // Debug: Log all action statuses
            \Log::info('Action statuses for job', [
                'job_id' => $job->id,
                'action_statuses' => $actions->pluck('status')->toArray(),
                'action_names' => $actions->pluck('name')->toArray(),
                'has_in_progress' => $actions->contains('status', 'In progress') || $actions->contains('status', 'In Progress'),
                'all_completed' => $actions->every('status', 'Completed'),
                'has_started_actions' => $actions->contains('status', 'In progress') || $actions->contains('status', 'In Progress') || $actions->contains('status', 'Completed')
            ]);
            
            // Check if any action is in progress
            $hasInProgress = $actions->contains('status', 'In progress') || $actions->contains('status', 'In Progress');
            if ($hasInProgress) {
                $job->update(['status' => 'In progress']);
                \Log::info('Job status set to In progress', ['job_id' => $job->id]);
                return;
            }
            
            // Check if all actions are completed
            $allCompleted = $actions->every('status', 'Completed');
            if ($allCompleted) {
                $job->update(['status' => 'Completed']);
                \Log::info('Job status set to Completed', ['job_id' => $job->id]);
                return;
            }
            
            // Check if any actions have been started (either in progress or completed)
            $hasStartedActions = $actions->contains('status', 'In progress') || 
                                $actions->contains('status', 'In Progress') || 
                                $actions->contains('status', 'Completed');
            
            if ($hasStartedActions) {
                // If some actions have been started but not all are completed, keep as "In progress"
                $job->update(['status' => 'In progress']);
                \Log::info('Job status set to In progress (some actions started)', ['job_id' => $job->id]);
                return;
            }
            
            // Default to "Not started yet" only if no actions have been started
            $job->update(['status' => 'Not started yet']);
            \Log::info('Job status set to Not started yet', ['job_id' => $job->id]);
        } catch (\Exception $e) {
            \Log::error('Error updating job status', [
                'job_id' => $job->id,
                'error' => $e->getMessage()
            ]);
            // Default to "Not started yet" on error
            $job->update(['status' => 'Not started yet']);
        }
    }

    /**
     * Update invoice status based on the status of all jobs and their actions
     */
    private function updateInvoiceStatus(Invoice $invoice): void
    {
        try {
            // Get all jobs for this invoice with proper loading
            $jobs = $invoice->jobs()->get();
            
            if ($jobs->isEmpty()) {
                $invoice->update(['status' => 'Not started yet']);
                return;
            }
            
            // Debug: Log all job statuses
            \Log::info('Job statuses for invoice', [
                'invoice_id' => $invoice->id,
                'job_statuses' => $jobs->pluck('status')->toArray(),
                'job_ids' => $jobs->pluck('id')->toArray()
            ]);
            
            // Check if any job is in progress
            $hasInProgress = $jobs->contains('status', 'In progress');
            if ($hasInProgress) {
                $invoice->update(['status' => 'In progress']);
                \Log::info('Invoice status set to In progress', ['invoice_id' => $invoice->id]);
                return;
            }
            
            // Check if all jobs are completed
            $allCompleted = $jobs->every('status', 'Completed');
            if ($allCompleted) {
                $invoice->update(['status' => 'Completed']);
                \Log::info('Invoice status set to Completed', ['invoice_id' => $invoice->id]);
                return;
            }
            
            // Default to "Not started yet"
            $invoice->update(['status' => 'Not started yet']);
            \Log::info('Invoice status set to Not started yet', ['invoice_id' => $invoice->id]);
        } catch (\Exception $e) {
            \Log::error('Error updating invoice status', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage()
            ]);
            // Default to "Not started yet" on error
            $invoice->update(['status' => 'Not started yet']);
        }
    }

    /**
     * Public method to manually update invoice status
     */
    public function updateInvoiceStatusManually(Request $request, $invoiceId)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            $this->updateInvoiceStatus($invoice);
            
            return response()->json([
                'success' => true,
                'message' => 'Invoice status updated successfully',
                'new_status' => $invoice->status
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Test endpoint to check response format
     */
    public function testStartJobResponse(Request $request)
    {
        try {
            return response()->json([
                'success' => true,
                'message' => 'Test response working',
                'timestamp' => now()->toISOString(),
                'timezone' => config('app.timezone'),
                'current_time' => now()->format('Y-m-d H:i:s'),
                'current_time_utc' => now()->utc()->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Debug method to check status of all actions and jobs for an invoice
     */
    public function debugInvoiceStatus(Request $request, $invoiceId)
    {
        try {
            $invoice = Invoice::findOrFail($invoiceId);
            $jobs = $invoice->jobs()->with('actions')->get();
            
            $debugData = [
                'invoice_id' => $invoice->id,
                'invoice_status' => $invoice->status,
                'jobs' => []
            ];
            
            foreach ($jobs as $job) {
                $jobData = [
                    'job_id' => $job->id,
                    'job_status' => $job->status,
                    'actions' => []
                ];
                
                foreach ($job->actions as $action) {
                    $jobData['actions'][] = [
                        'action_id' => $action->id,
                        'action_name' => $action->name,
                        'action_status' => $action->status,
                        'started_at' => $action->started_at,
                        'ended_at' => $action->ended_at
                    ];
                }
                
                $debugData['jobs'][] = $jobData;
            }
            
            return response()->json($debugData);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCatalogItems(Request $request)
    {
        try {
            // Get pagination parameters with defaults
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $searchTerm = $request->input('search', '');
            $category = $request->input('category', '');

            // Start with base query including category relationships
            $query = CatalogItem::with([
                'largeMaterial.article',
                'smallMaterial.article',
                'largeMaterialCategory',
                'smallMaterialCategory'
            ]);

            // Add optional search functionality
            if (!empty($searchTerm)) {
                $query->where('name', 'like', "%{$searchTerm}%");
            }

            // Optional category filter (e.g., 'material' or 'small_format')
            if (!empty($category)) {
                $query->where('category', $category);
            }

            // Paginate the results
            $catalogItems = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform paginated items to align with offers API shape
            $transformedItems = $catalogItems->getCollection()->map(function($item) {
                // Human-readable material display
                $materialDisplay = 'N/A';
                if ($item->large_material_category_id) {
                    $category = \App\Models\ArticleCategory::find($item->large_material_category_id);
                    $materialDisplay = $category ? "[Category] {$category->name} (Large)" : 'N/A';
                } elseif ($item->large_material_id) {
                    $largeMaterialName = $item->largeMaterial ? $item->largeMaterial->name : null;
                    $materialDisplay = $largeMaterialName ? "{$largeMaterialName} (Large)" : 'N/A';
                } elseif ($item->small_material_category_id) {
                    $category = \App\Models\ArticleCategory::find($item->small_material_category_id);
                    $materialDisplay = $category ? "[Category] {$category->name} (Small)" : 'N/A';
                } elseif ($item->small_material_id) {
                    $smallMaterialName = $item->smallMaterial ? $item->smallMaterial->name : null;
                    $materialDisplay = $smallMaterialName ? "{$smallMaterialName} (Small)" : 'N/A';
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'machinePrint' => $item->machinePrint,
                    'machineCut' => $item->machineCut,
                    // Unified IDs (string or cat_#) similar to CatalogItemController@index
                    'large_material_id' => $item->large_material_category_id ? 'cat_' . $item->large_material_category_id : ($item->large_material_id ? (string)$item->large_material_id : null),
                    'small_material_id' => $item->small_material_category_id ? 'cat_' . $item->small_material_category_id : ($item->small_material_id ? (string)$item->small_material_id : null),
                    // Keep legacy fields for backward compatibility
                    'largeMaterial' => $item->large_material_id,
                    'smallMaterial' => $item->small_material_id,
                    'large_material_category_id' => $item->large_material_category_id,
                    'small_material_category_id' => $item->small_material_category_id,
                    'category' => $item->category,
                    'material' => $materialDisplay,
                    'quantity' => $item->quantity,
                    'copies' => $item->copies,
                    'file' => $item->file,
                    'price' => $item->price,
                    'actions' => collect($item->actions ?? [])->map(function($action) {
                        return [
                            'action_id' => [
                                'id' => $action['action_id']['id'] ?? $action['id'],
                                'name' => $action['action_id']['name'] ?? $action['name']
                            ],
                            'status' => 'Not started yet',
                            'quantity' => $action['quantity']
                        ];
                    })->toArray()
                ];
            });

            // Return paginated response
            return response()->json([
                'data' => $transformedItems,
                'pagination' => [
                    'current_page' => $catalogItems->currentPage(),
                    'total_pages' => $catalogItems->lastPage(),
                    'total_items' => $catalogItems->total(),
                    'per_page' => $catalogItems->perPage()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getCatalogItems:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch catalog items',
                'details' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    public function destroy($id)
    {
        try {
            // Find the job
            $job = Job::findOrFail($id);

            // Begin transaction to ensure all related records are deleted
            DB::beginTransaction();

            try {
                // Store original files for cleanup before deleting the job
                $originalFiles = $job->getOriginalFiles();

                // Get all action IDs associated with this job
                $actionIds = DB::table('job_job_action')
                    ->where('job_id', $job->id)
                    ->pluck('job_action_id');

                // Delete records from job_job_action
                DB::table('job_job_action')
                    ->where('job_id', $job->id)
                    ->delete();

                // Delete records from job_actions
                DB::table('job_actions')
                    ->whereIn('id', $actionIds)
                    ->delete();

                // Delete the job from database first
                $job->delete();

                // Commit the database transaction before attempting file cleanup
                DB::commit();

                // Clean up original files from R2 storage after successful database deletion
                if (!empty($originalFiles)) {
                    foreach ($originalFiles as $filePath) {
                        if ($filePath && str_starts_with($filePath, 'job-originals/')) {
                            try {
                                $this->templateStorageService->deleteTemplate($filePath);
                                \Log::info('Successfully deleted original file from R2', [
                                    'job_id' => $id,
                                    'file_path' => $filePath
                                ]);
                            } catch (\Exception $e) {
                                \Log::warning('Failed to delete original file from R2 storage: ' . $e->getMessage(), [
                                    'job_id' => $id,
                                    'file_path' => $filePath,
                                    'error' => $e->getMessage()
                                ]);
                                // Don't fail the entire operation if file cleanup fails
                            }
                        } else if ($filePath) {
                            // Handle legacy local files
                            \Log::info('Skipping cleanup of legacy local file', [
                                'job_id' => $id,
                                'file_path' => $filePath
                            ]);
                        }
                    }
                }

                // Clean up ALL thumbnails for this job from R2 storage
                try {
                    $thumbnailFiles = $this->templateStorageService->getDisk()->files('job-thumbnails');
                    $deletedThumbnails = 0;
                    
                    foreach ($thumbnailFiles as $thumbFile) {
                        $thumbBasename = basename($thumbFile);
                        // Delete all thumbnails that belong to this job
                        if (strpos($thumbBasename, 'job_' . $id . '_') === 0) {
                            try {
                                $this->templateStorageService->getDisk()->delete($thumbFile);
                                $deletedThumbnails++;
                                \Log::info('Successfully deleted thumbnail from R2', [
                                    'job_id' => $id,
                                    'thumbnail_path' => $thumbFile
                                ]);
                            } catch (\Exception $e) {
                                \Log::warning('Failed to delete thumbnail from R2: ' . $e->getMessage(), [
                                    'job_id' => $id,
                                    'thumbnail_path' => $thumbFile
                                ]);
                            }
                        }
                    }
                    
                    \Log::info('Job deletion cleanup completed', [
                        'job_id' => $id,
                        'original_files_deleted' => count($originalFiles),
                        'thumbnails_deleted' => $deletedThumbnails
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to cleanup thumbnails during job deletion: ' . $e->getMessage(), [
                        'job_id' => $id
                    ]);
                }

                return response()->json([
                    'message' => 'Job and related actions deleted successfully'
                ]);

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to delete job',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check if any selected catalog items require questions, and return questions if so.
     */
    public function getQuestionsForCatalogItems(Request $request)
    {
        $catalogItemIds = $request->input('catalog_item_ids', []);
        $catalogItems = \App\Models\CatalogItem::with(['questions' => function($query) {
            $query->where('active', true)->orderBy('order');
        }])->whereIn('id', $catalogItemIds)->get();

        $shouldAsk = $catalogItems->where('should_ask_questions', true)->isNotEmpty();

        if (!$shouldAsk) {
            return response()->json(['shouldAsk' => false]);
        }

        $questionsByCatalogItem = [];
        foreach ($catalogItems as $item) {
            if ($item->should_ask_questions) {
                // Only get questions that are specifically associated with this catalog item
                $questionsByCatalogItem[$item->id] = $item->questions->toArray();
            }
        }

        return response()->json([
            'shouldAsk' => true,
            'questionsByCatalogItem' => $questionsByCatalogItem,
        ]);
    }

    /**
     * Get the next available material from a category with sufficient stock
     */
    private function getNextAvailableMaterial($categoryId, $type, $requiredQuantity)
    {
        $category = \App\Models\ArticleCategory::with([
            'articles.largeFormatMaterial', 
            'articles.smallMaterial'
        ])->find($categoryId);
        
        if (!$category) {
            return null;
        }

        // Get all materials in the category and sort by creation date (oldest first, FIFO)
        $availableMaterials = [];
        
        foreach ($category->articles as $article) {
            if ($type === 'large' && $article->largeFormatMaterial) {
                $material = $article->largeFormatMaterial;
                if ($material->quantity >= $requiredQuantity) {
                    $availableMaterials[] = $material;
                }
            } elseif ($type === 'small' && $article->smallMaterial) {
                $material = $article->smallMaterial;
                if ($material->quantity >= $requiredQuantity) {
                    $availableMaterials[] = $material;
                }
            }
        }

        // Sort by creation date (oldest first for FIFO)
        usort($availableMaterials, function($a, $b) {
            return $a->created_at <=> $b->created_at;
        });

        // Return the ID of the first (oldest) available material
        return !empty($availableMaterials) ? $availableMaterials[0]->id : null;
    }



    /**
     * Upload multiple files to a job with batch processing and progress tracking
     */
    public function uploadMultipleFiles(Request $request, $id)
    {
        try {
            // Validate the request
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|mimes:pdf|max:153600', // 150MB max per file
            ]);

            // Find the job
            $job = Job::findOrFail($id);

            $uploadedFiles = [];
            $thumbnails = [];
            // Start with existing job dimensions (for cumulative uploads)
            $existingAreaM2 = $job->total_area_m2 ?? 0; // Use total_area_m2 if available, otherwise 0
            $totalAreaM2 = $existingAreaM2; // Start with existing area
            $existingBreakdown = $job->dimensions_breakdown ?? []; // Start with existing dimensions
            $allFileDimensions = []; // Will store new file dimensions
            $finalTotalArea = $existingAreaM2; // Initialize final total area
            
            \Log::info('Initial values set', [
                'job_id' => $id,
                'existing_area_m2' => $existingAreaM2,
                'total_area_m2_start' => $totalAreaM2,
                'existing_dimensions_count' => count($existingBreakdown)
            ]);
            $firstFilePreview = null;

            $files = $request->file('files');
            $totalFiles = count($files);
            $batchSize = 2; // Reduced batch size for more granular progress

            // Initialize progress tracking
            $this->updateUploadProgress($id, 'starting', 5, 0, $totalFiles, 'Starting upload...');

            \Log::info('Starting optimized multiple file upload', [
                'job_id' => $id,
                'total_files' => $totalFiles,
                'batch_size' => $batchSize,
                'existing_area_m2' => $existingAreaM2,
                'existing_files_count' => count($existingBreakdown)
            ]);

            // Update to uploading state
            $this->updateUploadProgress($id, 'uploading', 10, 0, $totalFiles, 'Files uploaded, starting processing...');

            // Process files in batches
            for ($batchStart = 0; $batchStart < $totalFiles; $batchStart += $batchSize) {
                $batchEnd = min($batchStart + $batchSize, $totalFiles);
                $batchFiles = array_slice($files, $batchStart, $batchEnd - $batchStart);
                
                \Log::info('Processing batch', [
                    'job_id' => $id,
                    'batch_start' => $batchStart,
                    'batch_end' => $batchEnd,
                    'batch_size' => count($batchFiles)
                ]);

                // Update progress for batch start (10% to 80% for processing)
                $batchProgress = 10 + (($batchStart / $totalFiles) * 70);
                $this->updateUploadProgress($id, 'processing', $batchProgress, $batchStart, $totalFiles, "Processing batch " . ($batchStart / $batchSize + 1));

                // Process each file in the current batch
                foreach ($batchFiles as $batchIndex => $file) {
                    $globalIndex = $batchStart + $batchIndex;
                    // Calculate the actual file index for the job (existing files + new files)
                    $actualFileIndex = count($existingBreakdown) + $globalIndex;
                    
                    // Update progress for individual file (more granular)
                    $fileProgress = 10 + (($globalIndex + 1) / $totalFiles) * 70;
                    $this->updateUploadProgress($id, 'processing', $fileProgress, $globalIndex + 1, $totalFiles, "Processing file " . ($globalIndex + 1) . " of {$totalFiles}");
                    
                    // Store file in R2
                    $filePath = $this->templateStorageService->storeTemplate($file, 'job-originals');
                    $uploadedFiles[] = $filePath;

                    // Add a small delay to make progress visible
                    usleep(200000); // 200ms delay

                    // Calculate dimensions and generate thumbnail in parallel
                    $this->processFileDimensionsAndThumbnail($file, $actualFileIndex, $id, $filePath, $totalAreaM2, $allFileDimensions, $thumbnails, $firstFilePreview);
                    
                    // Debug: Check area after processing this file
                    \Log::info('After processing file', [
                        'job_id' => $id,
                        'file_index' => $actualFileIndex,
                        'filename' => $file->getClientOriginalName(),
                        'total_area_m2_after_file' => $totalAreaM2,
                        'dimensions_count_after_file' => count($allFileDimensions)
                    ]);
                }

                // Small delay between batches to prevent overwhelming the system
                if ($batchEnd < $totalFiles) {
                    usleep(300000); // 300ms delay
                }
                
                // Debug: Check area after processing this batch
                \Log::info('After processing batch', [
                    'job_id' => $id,
                    'batch_start' => $batchStart,
                    'batch_end' => $batchEnd,
                    'total_area_m2_after_batch' => $totalAreaM2,
                    'dimensions_count_after_batch' => count($allFileDimensions)
                ]);
            }

            // Update progress for final processing (80% to 95%)
            $this->updateUploadProgress($id, 'finalizing', 85, $totalFiles, $totalFiles, 'Finalizing upload...');

            // Add a delay to make finalizing visible
            usleep(500000); // 500ms delay

            // Add new files to existing original files
            foreach ($uploadedFiles as $filePath) {
                $job->addOriginalFile($filePath);
            }

            // Update progress to 90%
            $this->updateUploadProgress($id, 'finalizing', 90, $totalFiles, $totalFiles, 'Saving job data...');

            // Update final total area after all files are processed
            if (!empty($allFileDimensions)) {
                $calculatedTotalArea = array_sum(array_column($allFileDimensions, 'total_area_m2'));
                $finalTotalArea = round($existingAreaM2 + $calculatedTotalArea, 6);
            }

            // Update job with total calculated area and dimensions breakdown
            // Always update if we have processed files, regardless of area value
            if (!empty($allFileDimensions)) {
                // Debug: Log the area values before assignment
                \Log::info('Debug: Area values before job update', [
                    'job_id' => $id,
                    'totalAreaM2' => $totalAreaM2,
                    'totalAreaM2_type' => gettype($totalAreaM2),
                    'totalAreaM2_debug' => var_export($totalAreaM2, true),
                    'calculatedTotalArea' => $calculatedTotalArea,
                    'finalTotalArea' => $finalTotalArea,
                    'allFileDimensions_count' => count($allFileDimensions),
                    'allFileDimensions_areas' => array_column($allFileDimensions, 'total_area_m2')
                ]);
                
                $job->total_area_m2 = $finalTotalArea;
                
                // Debug: Log the job object after assignment
                \Log::info('Debug: Job object after area assignment', [
                    'job_id' => $id,
                    'job_total_area_m2' => $job->total_area_m2,
                    'job_total_area_m2_type' => gettype($job->total_area_m2),
                    'job_total_area_m2_debug' => var_export($job->total_area_m2, true),
                    'assigned_value' => $totalAreaM2
                ]);
                
                // Merge new dimensions with existing ones to preserve previous files
                $existingBreakdown = $job->dimensions_breakdown ?? [];
                $job->dimensions_breakdown = array_merge($existingBreakdown, $allFileDimensions);
                
                // Update preview image if we have one
                if ($firstFilePreview) {
                    $job->file = $firstFilePreview;
                }
                
                \Log::info('Updated job with cumulative area and dimensions breakdown', [
                    'job_id' => $id,
                    'existing_area_m2' => $existingAreaM2,
                    'new_files_area_m2' => $calculatedTotalArea,
                    'new_total_area_m2' => $finalTotalArea,
                    'files_processed' => count($allFileDimensions),
                    'preview_image' => $firstFilePreview,
                    'existing_files_before' => count($existingBreakdown),
                    'new_files_added' => $totalFiles,
                    'total_files_after' => count($job->dimensions_breakdown)
                ]);
                
                \Log::info('Job object before save', [
                    'job_id' => $id,
                    'total_area_m2' => $job->total_area_m2,
                    'dimensions_breakdown_count' => count($job->dimensions_breakdown ?? [])
                ]);
            }

            // Debug: Log the job object right before save
            \Log::info('Debug: Job object right before save', [
                'job_id' => $id,
                'job_total_area_m2' => $job->total_area_m2,
                'job_total_area_m2_type' => gettype($job->total_area_m2),
                'job_total_area_m2_debug' => var_export($job->total_area_m2, true),
                'finalTotalArea' => $finalTotalArea,
                'finalTotalArea_type' => gettype($finalTotalArea)
            ]);

            // Save the job
            $saveResult = $job->save();

            // Defensive: ensure DB has the exact rounded value (workaround for any casting anomalies)
            try {
                if (isset($finalTotalArea)) {
                    \Log::info('Debug: Attempting fallback DB update', [
                        'job_id' => $id,
                        'finalTotalArea' => $finalTotalArea,
                        'finalTotalArea_type' => gettype($finalTotalArea)
                    ]);
                    
                    $updateResult = DB::table('jobs')->where('id', $id)->update([
                        'total_area_m2' => $finalTotalArea,
                    ]);
                    
                    \Log::info('Debug: Fallback DB update result', [
                        'job_id' => $id,
                        'updateResult' => $updateResult,
                        'rowsAffected' => $updateResult
                    ]);
                } else {
                    \Log::warning('Debug: finalTotalArea not set for fallback update', [
                        'job_id' => $id,
                        'finalTotalArea' => $finalTotalArea ?? 'undefined'
                    ]);
                }
            } catch (\Exception $e) {
                \Log::warning('Fallback DB update for total_area_m2 failed', [
                    'job_id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
            
            \Log::info('Job save result', [
                'job_id' => $id,
                'save_result' => $saveResult,
                'job_attributes' => $job->getAttributes()
            ]);

            // Verify the save worked by reloading the job
            $job->refresh();
            
            \Log::info('Job saved and reloaded', [
                'job_id' => $id,
                'saved_total_area_m2' => $job->total_area_m2,
                'saved_dimensions_count' => count($job->dimensions_breakdown ?? []),
                'expected_total_area_m2' => $finalTotalArea,
                'expected_dimensions_count' => count($allFileDimensions)
            ]);

            // Update progress to complete
            $this->updateUploadProgress($id, 'complete', 100, $totalFiles, $totalFiles, 'Upload completed successfully');

            // Debug: Log what we're sending back
            \Log::info('Sending response back to frontend', [
                'job_id' => $id,
                'response_total_area_m2' => $finalTotalArea,
                'response_dimensions_breakdown_count' => count($allFileDimensions),
                'job_db_total_area_m2' => $job->total_area_m2,
                'job_db_dimensions_count' => count($job->dimensions_breakdown ?? [])
            ]);

            return response()->json([
                'message' => 'Files uploaded successfully',
                'originalFiles' => $job->getOriginalFiles(),
                'uploadedCount' => count($uploadedFiles),
                'thumbnails' => $thumbnails,
                'dimensions' => [
                    'total_area_m2' => $finalTotalArea,
                    'individual_files' => $allFileDimensions,
                    'files_count' => count($allFileDimensions)
                ],
                'dimensions_breakdown' => $allFileDimensions,
                'total_area_m2' => $finalTotalArea,
                'job_updated' => $finalTotalArea > 0,
                'debug' => [
                    'job_id' => $id,
                    'total_files_processed' => count($uploadedFiles),
                    'thumbnails_generated' => count(array_filter($thumbnails, function($thumb) {
                        return !empty($thumb['thumbnailPath']);
                    })),
                    'thumbnails_failed' => count(array_filter($thumbnails, function($thumb) {
                        return empty($thumb['thumbnailPath']);
                    })),
                    'dimensions_calculated' => $finalTotalArea > 0,
                    'existing_files_before' => count($job->dimensions_breakdown ?? []),
                    'new_files_added' => $totalFiles,
                    'total_dimensions_after' => count($allFileDimensions),
                    'os_family' => PHP_OS_FAMILY,
                    'imagick_available' => class_exists('Imagick'),
                    'imagick_version' => class_exists('Imagick') ? \Imagick::getVersion() : null,
                    'original_files_count' => count($job->getOriginalFiles())
                ]
            ]);

        } catch (\Exception $e) {
            // Update progress to error
            $this->updateUploadProgress($id, 'error', 0, 0, 0, 'Upload failed: ' . $e->getMessage());

            \Log::error('Error uploading multiple files:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to upload files',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process a single file for dimensions and thumbnail generation
     */
    private function processFileDimensionsAndThumbnail($file, $index, $jobId, $filePath, &$totalAreaM2, &$allFileDimensions, &$thumbnails, &$firstFilePreview)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Calculate dimensions for the file
        try {
            $imagick = new \Imagick();
            
            // Set Ghostscript path based on environment
            $this->setGhostscriptPath($imagick);
            
            // Read the PDF file to get page count
            $imagick->readImage($file->getPathname());
            $pageCount = $imagick->getNumberImages();
            
            \Log::info('Processing PDF file with multiple pages', [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'page_count' => $pageCount,
                'index' => $index
            ]);
            
            // Variables to store cumulative dimensions for this file
            $fileTotalAreaM2 = 0;
            $pageDimensions = [];
            
            // Iterate through all pages of the PDF
            for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                // Create a new Imagick instance for each page to avoid conflicts
                $pageImagick = new \Imagick();
                $this->setGhostscriptPath($pageImagick);
                $pageImagick->readImage($file->getPathname() . '[' . $pageIndex . ']');
                $pageImagick->setImageFormat('jpg');
                
                // Create temporary image for dimension calculation
                $tempImagePath = storage_path('app/temp/dim_calc_' . $index . '_page_' . $pageIndex . '_' . time() . '.jpg');
                
                // Ensure temp directory exists
                if (!file_exists(dirname($tempImagePath))) {
                    mkdir(dirname($tempImagePath), 0755, true);
                }
                
                $pageImagick->writeImage($tempImagePath);
                
                // Calculate dimensions from the page
                list($width, $height) = getimagesize($tempImagePath);
                $dpi = 72; // Default DPI if not available
                $widthInMm = ($width / $dpi) * 25.4;
                $heightInMm = ($height / $dpi) * 25.4;
                $areaM2 = ($widthInMm * $heightInMm) / 1000000;
                
                // Store individual page dimensions
                $pageDimensions[] = [
                    'page' => $pageIndex + 1,
                    'width_mm' => $widthInMm,
                    'height_mm' => $heightInMm,
                    'area_m2' => round($areaM2, 6)
                ];
                
                // Add to file totals
                $fileTotalAreaM2 += $areaM2;
                
                // Clean up temp file
                if (file_exists($tempImagePath)) {
                    unlink($tempImagePath);
                }
                
                $pageImagick->clear();
                
                \Log::info('Calculated dimensions for page', [
                    'job_id' => $jobId,
                    'file' => $file->getClientOriginalName(),
                    'page' => $pageIndex + 1,
                    'width_mm' => $widthInMm,
                    'height_mm' => $heightInMm,
                    'area_m2' => $areaM2
                ]);
            }
            
            // Add file totals to job totals
            $oldTotal = $totalAreaM2;
            $totalAreaM2 += $fileTotalAreaM2;
            
            \Log::info('Accumulating file area to job total', [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'file_area_m2' => $fileTotalAreaM2,
                'job_total_before' => $oldTotal,
                'job_total_after' => $totalAreaM2,
                'calculation' => $oldTotal . ' + ' . $fileTotalAreaM2 . ' = ' . $totalAreaM2
            ]);
            
            // Store individual file dimensions (now includes all pages)
            $allFileDimensions[] = [
                'filename' => $file->getClientOriginalName(),
                'page_count' => $pageCount,
                'total_area_m2' => round($fileTotalAreaM2, 6),
                'page_dimensions' => $pageDimensions,
                'index' => $index
            ];
            
            // For the FIRST file, also create preview image for the job (still using first page only)
            if ($index === 0) {
                $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                
                // Create preview from first page only
                $previewImagick = new \Imagick();
                $this->setGhostscriptPath($previewImagick);
                $previewImagick->readImage($file->getPathname() . '[0]'); // First page only for preview
                $previewImagick->setImageFormat('jpg');
                $previewImagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
                $previewImagick->setImageCompressionQuality(70);
                $previewImagick->stripImage();
                // Limit preview to 800x800 while preserving aspect ratio
                $previewImagick->thumbnailImage(800, 800, true, true);
                $previewImagick->writeImage($imagePath);
                $previewImagick->clear();
                $firstFilePreview = $imageFilename;
            }
            
            $imagick->clear();
            
            \Log::info('Completed processing PDF file with all pages', [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'total_pages' => $pageCount,
                'file_total_area_m2' => $fileTotalAreaM2,
                'cumulative_job_area_m2' => $totalAreaM2
            ]);
            
        } catch (\Exception $e) {
            \Log::warning('Failed to calculate dimensions for file: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'job_id' => $jobId,
                'index' => $index,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'os_family' => PHP_OS_FAMILY,
                'imagick_version' => \Imagick::getVersion()
            ]);
            
            // Try fallback dimension calculation
            try {
                $fallbackDimensions = $this->calculatePdfDimensionsFallback($file);
                if ($fallbackDimensions) {
                    $fileTotalAreaM2 = ($fallbackDimensions['width_mm'] * $fallbackDimensions['height_mm']) / 1000000;
                    
                    // Add file totals to job totals
                    $totalAreaM2 += $fileTotalAreaM2;
                    
                    \Log::info('Successfully used fallback dimension calculation', [
                        'job_id' => $jobId,
                        'file' => $file->getClientOriginalName(),
                        'area_m2' => $fileTotalAreaM2
                    ]);
                    
                    // Store individual file dimensions
                    $allFileDimensions[] = [
                        'filename' => $file->getClientOriginalName(),
                        'page_count' => 1, // Fallback assumes single page
                        'total_area_m2' => round($fileTotalAreaM2, 6),
                        'index' => $index,
                        'method' => 'fallback'
                    ];
                }
            } catch (\Exception $fallbackError) {
                \Log::warning('Fallback dimension calculation also failed', [
                    'job_id' => $jobId,
                    'file' => $file->getClientOriginalName(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
            }
        }

        // Generate thumbnail and store in R2
        try {
            $imagick = new \Imagick();
            
            // Set Ghostscript path based on environment
            $this->setGhostscriptPath($imagick);
            
            // Set memory and resource limits for large PDFs
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 128 * 1024 * 1024); // 128MB
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 256 * 1024 * 1024);    // 256MB
            
            // Read PDF at higher resolution for clearer preview
            $imagick->setResolution(150, 150);
            $imagick->readImage($file->getPathname() . '[0]'); // Read the first page
            
            // Use JPG for compatibility and deterministic naming
            $imagick->setImageFormat('jpg');
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(80);
            $imagick->stripImage();
            
            // Create thumbnail - standardized size (max 800x800)
            $imagick->resizeImage(800, 800, \Imagick::FILTER_LANCZOS, 1, true);
            
            // Create thumbnail in memory
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            
            // Store thumbnail in R2 using a stable, index-only name
            $thumbnailPath = 'job-thumbnails/job_' . $jobId . '_' . $index . '.jpg';
            
            // Upload thumbnail to R2
            $this->templateStorageService->getDisk()->put($thumbnailPath, $thumbnailBlob);

            \Log::info('Generated and stored thumbnail in R2', [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'thumbnail_path' => $thumbnailPath,
                'original_file' => $filePath
            ]);

            $thumbnails[] = [
                'originalFile' => $filePath,
                'thumbnailPath' => $thumbnailPath,
                'filename' => $originalFilename,
                'type' => 'pdf',
                'index' => $index
            ];
            // Invalidate cached index map for this job
            \Cache::forget("job_thumb_map_{$jobId}");
        } catch (\Exception $e) {
            \Log::warning('Failed to generate thumbnail for PDF: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'job_id' => $jobId,
                'index' => $index,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'os_family' => PHP_OS_FAMILY,
                'imagick_version' => \Imagick::getVersion(),
                'file_path' => $filePath,
                'file_size_bytes' => filesize($file->getPathname()),
                'memory_limit' => ini_get('memory_limit'),
                'memory_usage' => memory_get_usage(true),
                'memory_peak' => memory_get_peak_usage(true)
            ]);
            // Try alternative thumbnail generation with lower resolution
            $fallbackThumbnailPath = null;
            try {
                $fallbackThumbnailPath = $this->generateThumbnailFallback($file, $jobId, $index, $originalFilename);
                
                if ($fallbackThumbnailPath) {
                    \Log::info('Successfully generated thumbnail using fallback method', [
                        'job_id' => $jobId,
                        'file' => $file->getClientOriginalName(),
                        'thumbnail_path' => $fallbackThumbnailPath
                    ]);
                    
                    $thumbnails[] = [
                        'originalFile' => $filePath,
                        'thumbnailPath' => $fallbackThumbnailPath,
                        'filename' => $originalFilename,
                        'type' => 'pdf',
                        'index' => $index,
                        'method' => 'fallback'
                    ];
                } else {
                    throw new \Exception('Fallback thumbnail generation also failed');
                }
                
            } catch (\Exception $fallbackError) {
                \Log::warning('Fallback thumbnail generation also failed: ' . $fallbackError->getMessage(), [
                    'job_id' => $jobId,
                    'file' => $file->getClientOriginalName(),
                    'original_error' => $e->getMessage(),
                    'fallback_error' => $fallbackError->getMessage()
                ]);
                
                // Final fallback - show PDF icon
                $thumbnails[] = [
                    'originalFile' => $filePath,
                    'thumbnailPath' => null,
                    'filename' => $originalFilename,
                    'type' => 'pdf',
                    'index' => $index,
                    'error' => $e->getMessage()
                ];
            }
        }
    }

    /**
     * Download a specific original file
     */
    public function downloadOriginalFile(Request $request, $id)
    {
        try {
            $request->validate([
                'file_path' => 'required|string'
            ]);

            // Find the job
            $job = Job::findOrFail($id);
            $filePath = $request->input('file_path');

            // Verify the file belongs to this job
            if (!in_array($filePath, $job->getOriginalFiles())) {
                return response()->json(['error' => 'File not found for this job'], 404);
            }

            // Check if file exists in R2
            if (!$this->templateStorageService->templateExists($filePath)) {
                return response()->json(['error' => 'File not found in storage'], 404);
            }

            // Get the file content from R2
            $fileContent = $this->templateStorageService->getDisk()->get($filePath);
            $originalName = $this->templateStorageService->getOriginalFilename($filePath);
            
            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="' . $originalName . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            \Log::error('Error downloading original file:', [
                'job_id' => $id,
                'file_path' => $request->input('file_path'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to download file',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove a specific original file
     */
    public function removeOriginalFile(Request $request, $id)
    {
        try {
            $request->validate([
                'file_index' => 'required|integer|min:0',
                'original_file' => 'sometimes|string'
            ]);

            // Find the job
            $job = Job::findOrFail($id);
            $fileIndex = $request->input('file_index');
            $originalFiles = $job->getOriginalFiles();

            // If an explicit original file path is provided, prefer locating by that
            if ($request->filled('original_file')) {
                $providedPath = $request->input('original_file');
                $foundKey = array_search($providedPath, $originalFiles, true);
                if ($foundKey !== false) {
                    $fileIndex = (int)$foundKey;
                }
            }

            // Check if the index is valid
            if (!isset($originalFiles[$fileIndex])) {
                return response()->json(['error' => 'File index not found'], 404);
            }

            $fileToRemove = $originalFiles[$fileIndex];
            $removedFileDimensions = null;

            // Calculate dimensions of the file being removed before removing it
            try {
                $removedFileDimensions = $this->calculateFileDimensions($fileToRemove, $fileIndex);
                \Log::info('Calculated dimensions for file being removed', [
                    'job_id' => $id,
                    'file_index' => $fileIndex,
                    'file_path' => $fileToRemove,
                    'dimensions' => $removedFileDimensions
                ]);
            } catch (\Exception $e) {
                \Log::warning('Failed to calculate dimensions for removed file: ' . $e->getMessage(), [
                    'job_id' => $id,
                    'file_index' => $fileIndex,
                    'file_path' => $fileToRemove,
                    'error' => $e->getMessage()
                ]);
                // Continue without dimensions - don't fail the operation
            }

            // Remove the file from the job
            if ($job->removeOriginalFile($fileToRemove)) {
                // Recalculate job dimensions by subtracting the removed file's area
                if ($removedFileDimensions && $removedFileDimensions['area_m2'] > 0) {
                    $currentArea = $job->total_area_m2 ?? 0;
                    
                    // Subtract the removed file's area
                    $newArea = round(max(0, $currentArea - $removedFileDimensions['area_m2']), 6);
                    
                    // Update job area - only set to null if no files remain
                    $remainingFiles = $job->getOriginalFiles();
                    if (empty($remainingFiles)) {
                        $job->total_area_m2 = null;
                        $job->dimensions_breakdown = [];
                    } else {
                        $job->total_area_m2 = $newArea;
                        
                        // Update dimensions breakdown by removing the file at the specified index
                        if ($job->dimensions_breakdown) {
                            $breakdown = $job->dimensions_breakdown;
                            if (isset($breakdown[$fileIndex])) {
                                unset($breakdown[$fileIndex]);
                                // Reindex array to maintain sequential indices
                                $breakdown = array_values($breakdown);
                                $job->dimensions_breakdown = $breakdown;
                            }
                        }
                    }
                    
                    \Log::info('Recalculated job area after file removal', [
                        'job_id' => $id,
                        'removed_file_dimensions' => $removedFileDimensions,
                        'previous_area_m2' => $currentArea,
                        'new_area_m2' => $newArea,
                        'remaining_files_count' => count($remainingFiles),
                        'dimensions_breakdown_count' => count($job->dimensions_breakdown ?? [])
                    ]);
                }

                $job->save();

                // Delete the file from R2 storage
                if (str_starts_with($fileToRemove, 'job-originals/')) {
                    try {
                        $this->templateStorageService->deleteTemplate($fileToRemove);
                        \Log::info('Successfully deleted original file from R2', [
                            'job_id' => $id,
                            'file_path' => $fileToRemove
                        ]);
                    } catch (\Exception $e) {
                        \Log::warning('Failed to delete file from R2 storage: ' . $e->getMessage(), [
                            'job_id' => $id,
                            'file_path' => $fileToRemove,
                            'error' => $e->getMessage()
                        ]);
                        // Don't fail the operation if file cleanup fails
                    }
                }

                // Regenerate thumbnails for all remaining files to ensure stable mapping
                $updatedThumbnails = $this->regenerateJobThumbnails($job);

                \Log::info('File removed successfully', [
                    'job_id' => $id,
                    'removed_file' => $fileToRemove,
                    'remaining_files' => $job->getOriginalFiles(),
                    'dimensions_recalculated' => $removedFileDimensions !== null
                ]);

                return response()->json([
                    'message' => 'File removed successfully',
                    'originalFiles' => $job->getOriginalFiles(),
                    'dimensions' => [
                        'area_m2' => $job->total_area_m2 ?? 0
                    ],
                    'total_area_m2' => $job->total_area_m2 ?? 0,
                    'dimensions_breakdown' => $job->dimensions_breakdown ?? [],
                    'removed_file_dimensions' => $removedFileDimensions,
                    'thumbnails' => $updatedThumbnails
                ]);
            } else {
                return response()->json(['error' => 'Failed to remove file'], 500);
            }

        } catch (\Exception $e) {
            \Log::error('Error removing original file:', [
                'job_id' => $id,
                'file_index' => $request->input('file_index'),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Failed to remove file',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate dimensions for a single file (used for removal)
     */
    private function calculateFileDimensions($filePath, $fileIndex)
    {
        try {
            // Get the file content from R2
            $fileContent = $this->templateStorageService->getDisk()->get($filePath);
            
            // Create a temporary file to process with ImageMagick
            $tempFilePath = storage_path('app/temp/remove_calc_' . $fileIndex . '_' . time() . '.pdf');
            
            // Ensure temp directory exists
            if (!file_exists(dirname($tempFilePath))) {
                mkdir(dirname($tempFilePath), 0755, true);
            }
            
            // Write the file content to temp file
            file_put_contents($tempFilePath, $fileContent);
            
            // Process with ImageMagick to get page count
            $imagick = new \Imagick();
            $this->setGhostscriptPath($imagick);
            $imagick->readImage($tempFilePath);
            $pageCount = $imagick->getNumberImages();
            
            \Log::info('Calculating dimensions for file removal with multiple pages', [
                'file_path' => $filePath,
                'file_index' => $fileIndex,
                'page_count' => $pageCount
            ]);
            
            // Variables to store cumulative area for this file
            $fileTotalAreaM2 = 0;
            
            // Iterate through all pages of the PDF
            for ($pageIndex = 0; $pageIndex < $pageCount; $pageIndex++) {
                // Create a new Imagick instance for each page to avoid conflicts
                $pageImagick = new \Imagick();
                $this->setGhostscriptPath($pageImagick);
                $pageImagick->readImage($tempFilePath . '[' . $pageIndex . ']');
                $pageImagick->setImageFormat('jpg');
                
                // Create temporary image for dimension calculation
                $tempImagePath = storage_path('app/temp/remove_dim_calc_' . $fileIndex . '_page_' . $pageIndex . '_' . time() . '.jpg');
                $pageImagick->writeImage($tempImagePath);
                
                // Calculate dimensions from the page
                list($width, $height) = getimagesize($tempImagePath);
                $dpi = 72; // Default DPI if not available
                $widthInMm = ($width / $dpi) * 25.4;
                $heightInMm = ($height / $dpi) * 25.4;
                $areaM2 = ($widthInMm * $heightInMm) / 1000000;
                
                // Add to file total area
                $fileTotalAreaM2 += $areaM2;
                
                // Clean up temp image file
                if (file_exists($tempImagePath)) {
                    unlink($tempImagePath);
                }
                
                $pageImagick->clear();
                
                \Log::info('Calculated dimensions for page during removal', [
                    'file_path' => $filePath,
                    'file_index' => $fileIndex,
                    'page' => $pageIndex + 1,
                    'width_mm' => $widthInMm,
                    'height_mm' => $heightInMm,
                    'area_m2' => $areaM2
                ]);
            }
            
            // Clean up temp PDF file
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            
            $imagick->clear();
            
            \Log::info('Completed calculating dimensions for file removal', [
                'file_path' => $filePath,
                'file_index' => $fileIndex,
                'total_pages' => $pageCount,
                'total_area_m2' => $fileTotalAreaM2
            ]);
            
            return [
                'area_m2' => $fileTotalAreaM2,
                'page_count' => $pageCount,
                'index' => $fileIndex
            ];
            
        } catch (\Exception $e) {
            \Log::warning('Failed to calculate dimensions for file removal: ' . $e->getMessage(), [
                'file_path' => $filePath,
                'file_index' => $fileIndex,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get thumbnails for all files in a job
     */
    public function getJobThumbnails($id)
    {
        try {
            $job = Job::findOrFail($id);
            $thumbnails = [];

            \Log::info('Getting thumbnails for job', [
                'job_id' => $id,
                'original_files' => $job->getOriginalFiles()
            ]);

            // Process all original files and find their thumbnails in R2
            foreach ($job->getOriginalFiles() as $index => $originalFile) {
                $originalFileBase = basename($originalFile);
                $originalFileName = pathinfo($originalFileBase, PATHINFO_FILENAME);

                // Prefer the client filename stored in dimensions_breakdown
                $expectedClientName = null;
                if (is_array($job->dimensions_breakdown) && isset($job->dimensions_breakdown[$index]['filename'])) {
                    $expectedClientName = pathinfo($job->dimensions_breakdown[$index]['filename'], PATHINFO_FILENAME);
                }
                // Fallback: strip leading timestamp_ from stored originalFile name
                if (!$expectedClientName) {
                    $expectedClientName = preg_replace('/^\d+_/', '', $originalFileBase);
                    $expectedClientName = pathinfo($expectedClientName, PATHINFO_FILENAME);
                }

                // Try to find thumbnail in R2 for this file
                $thumbnailPath = null;
                
                try {
                    // List files in the job-thumbnails directory
                    $thumbnailFiles = $this->templateStorageService->getDisk()->files('job-thumbnails');

                    $latestMatch = null;
                    $latestTs = 0;
                    foreach ($thumbnailFiles as $thumbFile) {
                        $thumbBasename = basename($thumbFile);
                        // Must belong to this job
                        if (strpos($thumbBasename, 'job_' . $id . '_') !== 0) {
                            continue;
                        }
                        // Prefer index match first
                        $indexMatch = (strpos($thumbBasename, '_' . $index . '_') !== false);
                        // Then filename match based on expected client name
                        $nameMatch = $expectedClientName ? (strpos($thumbBasename, $expectedClientName) !== false) : false;
                        if (!$indexMatch && !$nameMatch) {
                            continue;
                        }
                        // Track latest by timestamp segment job_{id}_{ts}_
                        $ts = 0;
                        if (preg_match('/job_' . $id . '_(\d+)_/',$thumbBasename,$m)) {
                            $ts = (int)$m[1];
                        }
                        if ($latestMatch === null || $ts >= $latestTs) {
                            $latestTs = $ts;
                            $latestMatch = $thumbFile;
                        }
                    }
                    if ($latestMatch) {
                        $thumbnailPath = $latestMatch;
                    }
                } catch (\Exception $e) {
                    \Log::warning('Failed to list R2 thumbnails: ' . $e->getMessage());
                }

                $thumbnailData = [
                    'type' => 'pdf',
                    'thumbnailPath' => $thumbnailPath,
                    'originalFile' => $originalFile,
                    'index' => $index,
                    'filename' => $originalFileName
                ];

                $thumbnails[] = $thumbnailData;

                \Log::info('Thumbnail search result', [
                    'index' => $index,
                    'original_file' => $originalFile,
                    'thumbnail_path' => $thumbnailPath,
                    'filename' => $originalFileName
                ]);
            }

            return response()->json([
                'thumbnails' => $thumbnails,
                'debug' => [
                    'job_id' => $id,
                    'original_files_count' => count($job->getOriginalFiles())
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error getting job thumbnails:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to get thumbnails',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get articles for a specific job
     */
    public function getJobArticles($id)
    {
        try {
            $job = Job::with('articles')->findOrFail($id);
            
            return response()->json([
                'articles' => $job->articles,
                'job_id' => $id
            ]);

        } catch (\Exception $e) {
            \Log::error('Failed to get job articles: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to get articles'], 500);
        }
    }

    /**
     * Serve job original file with authentication (similar to catalog template download)
     */
    public function viewOriginalFile($jobId, $fileIndex)
    {
        try {
            $job = Job::findOrFail($jobId);
            $originalFiles = $job->getOriginalFiles();

            // Check if the file index is valid
            if (!isset($originalFiles[$fileIndex])) {
                return response()->json(['error' => 'File not found'], 404);
            }

            $filePath = $originalFiles[$fileIndex];

            // Check if file exists in R2
            if (!$this->templateStorageService->templateExists($filePath)) {
                return response()->json(['error' => 'File not found in storage'], 404);
            }

            // Get the file content from R2
            $fileContent = $this->templateStorageService->getDisk()->get($filePath);
            $originalName = $this->templateStorageService->getOriginalFilename($filePath);
            
            \Log::info('Serving job original file', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'file_path' => $filePath,
                'original_name' => $originalName
            ]);

            return response($fileContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="' . $originalName . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            \Log::error('Error serving job original file:', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'error' => $e->getMessage()
            ]);

            return response()->json(['error' => 'Failed to serve file'], 500);
        }
    }

    /**
     * Serve job thumbnail with authentication
     */
    public function viewThumbnail($jobId, $fileIndex)
    {
        try {
            $job = Job::findOrFail($jobId);
            $originalFiles = $job->getOriginalFiles();

            // Check if the file index is valid
            if (!isset($originalFiles[$fileIndex])) {
                \Log::warning('File index not found', [
                    'job_id' => $jobId,
                    'file_index' => $fileIndex,
                    'available_files' => $originalFiles
                ]);
                return response()->json(['error' => 'File not found'], 404);
            }

            // Resolve thumbnail path using cached index map: [original_index => latest_path]
            $thumbnailPath = null;
            // Deterministic lookup: prefer stable filename scheme first, then fall back to old timestamped scheme
            $disk = $this->templateStorageService->getDisk();
            $originalFilePath = $originalFiles[$fileIndex];
            $originalFileName = pathinfo(basename($originalFilePath), PATHINFO_FILENAME);
            // Prefer newest stable index-only filename; if missing, try name-based, then legacy timestamped
            $stableCandidate = 'job-thumbnails/job_' . $jobId . '_' . $fileIndex . '.jpg';
            if ($disk->exists($stableCandidate)) {
                $thumbnailPath = $stableCandidate;
            } else {
                // Fallback: scan and pick the latest timestamped match for this index and filename
                try {
                    $files = $disk->files('job-thumbnails');
                    $latestMatch = null;
                    $latestTs = 0;
                    foreach ($files as $thumbFile) {
                        $thumbBasename = basename($thumbFile);
                        if (strpos($thumbBasename, 'job_' . $jobId . '_') !== 0) continue;
                        // Accept both webp/jpg regardless; require index and filename to match
                        if (strpos($thumbBasename, '_' . $fileIndex . '_') === false) continue;
                        if ($originalFileName && strpos($thumbBasename, $originalFileName) === false) continue;
                        $ts = 0;
                        if (preg_match('/job_' . $jobId . '_(\\d+)_/',$thumbBasename,$m)) { $ts = (int)$m[1]; }
                        if ($latestMatch === null || $ts >= $latestTs) { $latestMatch = $thumbFile; $latestTs = $ts; }
                    }
                    if ($latestMatch) {
                        $thumbnailPath = $latestMatch;
                    } else {
                        // As a final fallback, try stable candidate including name (older stable scheme)
                        $nameCandidate = 'job-thumbnails/job_' . $jobId . '_' . $fileIndex . '_' . $originalFileName . '.jpg';
                        if ($disk->exists($nameCandidate)) {
                            $thumbnailPath = $nameCandidate;
                        }
                    }
                } catch (\Exception $e) {
                    \Log::warning('Thumbnail fallback scan failed: ' . $e->getMessage());
                }
            }

            if (!$thumbnailPath) {
                // Fallback: index may have shifted after a removal; try match by filename
                try {
                    $originalFilePath = $originalFiles[$fileIndex];
                    $originalFileName = pathinfo(basename($originalFilePath), PATHINFO_FILENAME);
                    $files = $this->templateStorageService->getDisk()->files('job-thumbnails');
                    $latestMatch = null;
                    $latestTs = 0;
                    foreach ($files as $thumbFile) {
                        $thumbBasename = basename($thumbFile);
                        if (strpos($thumbBasename, 'job_' . $jobId . '_') !== 0) {
                            continue;
                        }
                        if (strpos($thumbBasename, $originalFileName) === false) {
                            continue;
                        }
                        // extract timestamp if present
                        if (preg_match('/job_' . $jobId . '_(\d+)_/',$thumbBasename,$m)) {
                            $ts = (int)$m[1];
                            if ($ts > $latestTs) {
                                $latestTs = $ts;
                                $latestMatch = $thumbFile;
                            }
                        } else {
                            // no timestamp, still accept as a candidate
                            $latestMatch = $thumbFile;
                        }
                    }
                    if ($latestMatch) {
                        $thumbnailPath = $latestMatch;
                        \Log::info('Thumbnail fallback by filename succeeded', [
                            'job_id' => $jobId,
                            'file_index' => $fileIndex,
                            'thumbnail_path' => $thumbnailPath,
                            'filename' => $originalFileName
                        ]);
                    } else {
                        \Log::warning('No thumbnail found for requested index or filename fallback', [
                            'job_id' => $jobId,
                            'file_index' => $fileIndex,
                            'filename' => $originalFileName
                        ]);
                        return response()->json(['error' => 'Thumbnail not found'], 404);
                    }
                } catch (\Exception $e) {
                    \Log::warning('Thumbnail fallback search failed: ' . $e->getMessage());
                    return response()->json(['error' => 'Thumbnail not found'], 404);
                }
            }

            // Check if thumbnail exists in R2
            if (!$this->templateStorageService->getDisk()->exists($thumbnailPath)) {
                \Log::warning('Thumbnail not found in R2 storage', [
                    'thumbnail_path' => $thumbnailPath
                ]);
                return response()->json(['error' => 'Thumbnail not found in storage'], 404);
            }

            // Get the thumbnail content from R2
            $thumbnailContent = $this->templateStorageService->getDisk()->get($thumbnailPath);
            $thumbBasename = basename($thumbnailPath);
            $etag = '"' . md5($thumbBasename) . '"';
            // Conditional GET handling
            $ifNoneMatch = request()->headers->get('If-None-Match');
            if ($ifNoneMatch && trim($ifNoneMatch) === $etag) {
                return response('', 304)
                    ->header('ETag', $etag)
                    ->header('Cache-Control', 'public, max-age=86400, immutable')
                    ->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));
            }

            \Log::info('Successfully serving job thumbnail', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'thumbnail_path' => $thumbnailPath,
                'content_size' => strlen($thumbnailContent)
            ]);

            return response($thumbnailContent)
                ->header('Content-Type', 'image/jpeg')
                ->header('ETag', $etag)
                ->header('Cache-Control', 'public, max-age=86400, immutable')
                ->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 86400));

        } catch (\Exception $e) {
            \Log::error('Error serving job thumbnail:', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'Failed to serve thumbnail'], 500);
        }
    }

    /**
     * Clean up a specific thumbnail for a removed file
     */
    private function cleanupThumbnailForFile($jobId, $fileIndex, $originalFileName)
    {
        try {
            // List all thumbnails in R2 for this job
            $thumbnailFiles = $this->templateStorageService->getDisk()->files('job-thumbnails');

            // Normalize original filename (without extension)
            $originalBase = pathinfo($originalFileName, PATHINFO_FILENAME);

            $deleted = 0;
            foreach ($thumbnailFiles as $thumbFile) {
                $thumbBasename = basename($thumbFile);
                // Must belong to this job
                if (strpos($thumbBasename, 'job_' . $jobId . '_') !== 0) {
                    continue;
                }
                // Match either the index pattern or the client filename in the thumbnail
                $matchesIndex = (strpos($thumbBasename, '_' . $fileIndex . '_') !== false);
                $matchesName = (strpos($thumbBasename, $originalBase) !== false);
                if (!$matchesIndex && !$matchesName) {
                    continue;
                }
                try {
                    $this->templateStorageService->getDisk()->delete($thumbFile);
                    $deleted++;
                    \Log::info('Deleted thumbnail for removed file', [
                        'job_id' => $jobId,
                        'file_index' => $fileIndex,
                        'thumbnail_path' => $thumbFile,
                        'matched_by' => $matchesIndex ? 'index' : 'name'
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Failed to delete matched thumbnail: ' . $e->getMessage(), [
                        'thumbnail' => $thumbFile
                    ]);
                }
            }

            if ($deleted === 0) {
                \Log::warning('No thumbnails matched for deletion', [
                    'job_id' => $jobId,
                    'file_index' => $fileIndex,
                    'original_file_name' => $originalFileName
                ]);
            }
            
        } catch (\Exception $e) {
            \Log::error('Failed to cleanup specific thumbnail: ' . $e->getMessage(), [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Generate and store thumbnail for a single file
     */
    private function generateThumbnail($imagePath, $jobId, $fileIndex, $originalFileName)
    {
        try {
            // Read the image and create thumbnail
            $imagick = new \Imagick();
            $imagick->readImage($imagePath);
            $imagick->setImageFormat('jpg');
            $imagick->setImageCompression(\Imagick::COMPRESSION_JPEG);
            $imagick->setImageCompressionQuality(70);
            $imagick->stripImage();
            $imagick->resizeImage(800, 800, \Imagick::FILTER_LANCZOS, 1, true); // Resize to standardized thumbnail size
            
            // Create thumbnail in memory
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            
            // Store thumbnail in R2 with stable name (no timestamp)
            $originalFilename = pathinfo($originalFileName, PATHINFO_FILENAME);
            $thumbnailPath = 'job-thumbnails/job_' . $jobId . '_' . $fileIndex . '_' . $originalFilename . '.jpg';
            
            // Upload thumbnail to R2
            $this->templateStorageService->getDisk()->put($thumbnailPath, $thumbnailBlob);

            \Log::info('Generated and stored thumbnail in R2', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'original_filename' => $originalFileName,
                'thumbnail_path' => $thumbnailPath,
                'image_path' => $imagePath
            ]);

            return $thumbnailPath;
            
        } catch (\Exception $e) {
            \Log::warning('Failed to generate thumbnail: ' . $e->getMessage(), [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'original_filename' => $originalFileName,
                'image_path' => $imagePath,
                'error_trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Build stable thumbnails list for a job by strictly matching jobId, index and client filename
     */
    private function regenerateJobThumbnails(Job $job): array
    {
        $jobId = $job->id;
        $thumbnails = [];
        try {
            $disk = $this->templateStorageService->getDisk();
            $thumbFiles = $disk->files('job-thumbnails');
            $originalFiles = $job->getOriginalFiles();
            foreach ($originalFiles as $idx => $orig) {
                $origBase = basename($orig);
                $clientName = preg_replace('/^\d+_/', '', $origBase);
                $clientName = pathinfo($clientName, PATHINFO_FILENAME);
                $best = null; $bestTs = 0;
                foreach ($thumbFiles as $thumb) {
                    $base = basename($thumb);
                    // require job id
                    if (strpos($base, 'job_' . $jobId . '_') !== 0) continue;
                    // require index segment
                    if (strpos($base, '_' . $idx . '_') === false) continue;
                    // require filename match
                    if ($clientName && strpos($base, $clientName) === false) continue;
                    $ts = 0;
                    if (preg_match('/job_' . $jobId . '_(\d+)_/',$base,$m)) { $ts = (int)$m[1]; }
                    if ($best === null || $ts >= $bestTs) { $best = $thumb; $bestTs = $ts; }
                }
                $thumbnails[] = [
                    'type' => 'pdf',
                    'thumbnailPath' => $best,
                    'originalFile' => $orig,
                    'index' => $idx,
                    'filename' => $clientName,
                ];
            }
        } catch (\Exception $e) {
            // ignore
        }
        return $thumbnails;
    }

    /**
     * Get upload progress for a job
     */
    public function getUploadProgress($id)
    {
        try {
            $cacheKey = "upload_progress_{$id}";
            $progress = \Cache::get($cacheKey, [
                'status' => 'idle',
                'progress' => 0,
                'current_file' => 0,
                'total_files' => 0,
                'message' => 'No upload in progress'
            ]);

            return response()->json($progress);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get upload progress',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update upload progress for a job
     */
    private function updateUploadProgress($jobId, $status, $progress, $currentFile = 0, $totalFiles = 0, $message = '')
    {
        $cacheKey = "upload_progress_{$jobId}";
        $progressData = [
            'status' => $status,
            'progress' => $progress,
            'current_file' => $currentFile,
            'total_files' => $totalFiles,
            'message' => $message,
            'timestamp' => now()->timestamp
        ];
        
        \Cache::put($cacheKey, $progressData, 300); // Cache for 5 minutes
    }

    /**
     * Update job machine assignments
     */
    public function updateMachines(Request $request, $id)
    {
        try {
            $request->validate([
                'machinePrint' => 'nullable|string',
                'machineCut' => 'nullable|string'
            ]);

            // Find the job
            $job = Job::findOrFail($id);

            // Validate machine names against registered machines
            if ($request->has('machinePrint') && $request->input('machinePrint') !== null) {
                $machinePrint = $request->input('machinePrint');
                $validPrintMachine = \App\Models\MachinesPrint::where('name', $machinePrint)->exists();
                
                if (!$validPrintMachine) {
                    return response()->json([
                        'error' => 'Invalid print machine specified',
                        'details' => 'The specified print machine is not registered in the system'
                    ], 400);
                }
            }

            if ($request->has('machineCut') && $request->input('machineCut') !== null) {
                $machineCut = $request->input('machineCut');
                $validCutMachine = \App\Models\MachinesCut::where('name', $machineCut)->exists();
                
                if (!$validCutMachine) {
                    return response()->json([
                        'error' => 'Invalid cut machine specified',
                        'details' => 'The specified cut machine is not registered in the system'
                    ], 400);
                }
            }

            // Update the job
            $job->machinePrint = $request->input('machinePrint');
            $job->machineCut = $request->input('machineCut');
            $job->save();

            \Log::info('Job machines updated', [
                'job_id' => $id,
                'machine_print' => $job->machinePrint,
                'machine_cut' => $job->machineCut
            ]);

            return response()->json([
                'message' => 'Job machines updated successfully',
                'job' => $job
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating job machines:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update job machines',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update individual job machine assignment
     */
    public function updateMachine(Request $request, $id)
    {
        try {
            $request->validate([
                'machinePrint' => 'nullable|string',
                'machineCut' => 'nullable|string'
            ]);

            // Find the job
            $job = Job::findOrFail($id);

            // Validate machine names against registered machines
            if ($request->has('machinePrint') && $request->input('machinePrint') !== null) {
                $machinePrint = $request->input('machinePrint');
                $validPrintMachine = \App\Models\MachinesPrint::where('name', $machinePrint)->exists();
                
                if (!$validPrintMachine) {
                    return response()->json([
                        'error' => 'Invalid print machine specified',
                        'details' => 'The specified print machine is not registered in the system'
                    ], 400);
                }
                
                $job->machinePrint = $machinePrint;
            }

            if ($request->has('machineCut') && $request->input('machineCut') !== null) {
                $machineCut = $request->input('machineCut');
                $validCutMachine = \App\Models\MachinesCut::where('name', $machineCut)->exists();
                
                if (!$validCutMachine) {
                    return response()->json([
                        'error' => 'Invalid cut machine specified',
                        'details' => 'The specified cut machine is not registered in the system'
                    ], 400);
                }
                
                $job->machineCut = $machineCut;
            }

            $job->save();

            \Log::info('Individual job machine updated', [
                'job_id' => $id,
                'machine_print' => $job->machinePrint,
                'machine_cut' => $job->machineCut
            ]);

            return response()->json([
                'message' => 'Job machine updated successfully',
                'job' => $job
            ]);

        } catch (\Exception $e) {
            \Log::error('Error updating individual job machine:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to update job machine',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    // --- CUTTING FILES BACKEND ---

    public function uploadCuttingFiles(Request $request, $id)
    {
        try {
            $request->validate([
                'files' => 'required|array',
                'files.*' => 'required|mimes:pdf,svg,dxf,cdr,ai|max:153600', // 150MB max per file
            ]);

            $job = Job::findOrFail($id);
            $uploadedFiles = [];
            $thumbnails = [];
            $allFileDimensions = [];
            $totalFiles = count($request->file('files'));
            $batchSize = 2;

            $this->updateCuttingUploadProgress($id, 'starting', 5, 0, $totalFiles, 'Starting upload...');

            $files = $request->file('files');
            $this->updateCuttingUploadProgress($id, 'uploading', 10, 0, $totalFiles, 'Files uploaded, starting processing...');

            foreach ($files as $index => $file) {
                $fileProgress = 10 + (($index + 1) / $totalFiles) * 70;
                $this->updateCuttingUploadProgress($id, 'processing', $fileProgress, $index + 1, $totalFiles, "Processing file " . ($index + 1) . " of {$totalFiles}");

                // Store file in R2
                $filePath = $this->templateStorageService->storeTemplate($file, 'job-cutting');
                $uploadedFiles[] = $filePath;

                // Try to generate a thumbnail (only for previewable types)
                $ext = strtolower($file->getClientOriginalExtension());
                $thumbPath = null;
                if (in_array($ext, ['pdf', 'svg'])) {
                    $thumbPath = $this->generateCuttingThumbnail($file, $id, $index);
                }
                $thumbnails[] = [
                    'path' => $thumbPath,
                    'type' => $ext,
                    'filename' => $file->getClientOriginalName(),
                    'index' => $index
                ];
            }

            $this->updateCuttingUploadProgress($id, 'finalizing', 90, $totalFiles, $totalFiles, 'Saving job data...');

            foreach ($uploadedFiles as $filePath) {
                $job->addCuttingFile($filePath);
            }
            $job->save();

            $this->updateCuttingUploadProgress($id, 'complete', 100, $totalFiles, $totalFiles, 'Upload completed successfully');

            return response()->json([
                'message' => 'Cutting files uploaded successfully',
                'cuttingFiles' => $job->getCuttingFiles(),
                'uploadedCount' => count($uploadedFiles),
                'thumbnails' => $thumbnails
            ]);
        } catch (\Exception $e) {
            $this->updateCuttingUploadProgress($id, 'error', 0, 0, 0, 'Upload failed: ' . $e->getMessage());
            \Log::error('Error uploading cutting files:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'error' => 'Failed to upload cutting files',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    public function getCuttingFileThumbnails($id)
    {
        $job = Job::findOrFail($id);
        $cuttingFiles = $job->getCuttingFiles();
        $thumbnails = [];
        foreach ($cuttingFiles as $index => $filePath) {
            $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
            $thumbPath = null;
            if (in_array($ext, ['pdf', 'svg'])) {
                $thumbPath = $this->getCuttingThumbnailPath($id, $index, $filePath);
            }
            $thumbnails[] = [
                'path' => $thumbPath,
                'type' => $ext,
                'filename' => basename($filePath),
                'index' => $index
            ];
        }
        return response()->json(['thumbnails' => $thumbnails]);
    }

    public function viewCuttingFile($jobId, $fileIndex)
    {
        $job = Job::findOrFail($jobId);
        $cuttingFiles = $job->getCuttingFiles();
        if (!isset($cuttingFiles[$fileIndex])) {
            return response()->json(['error' => 'File not found'], 404);
        }
        $filePath = $cuttingFiles[$fileIndex];
        $disk = $this->templateStorageService->getDisk();
        if (!$disk->exists($filePath)) {
            return response()->json(['error' => 'File not found in storage'], 404);
        }
        $mime = $disk->mimeType($filePath);
        $stream = $disk->readStream($filePath);
        return response()->stream(function () use ($stream) {
            fpassthru($stream);
        }, 200, [
            'Content-Type' => $mime,
            'Content-Disposition' => 'inline; filename="' . basename($filePath) . '"',
        ]);
    }

    public function viewCuttingFileThumbnail($jobId, $fileIndex)
    {
        $job = Job::findOrFail($jobId);
        $cuttingFiles = $job->getCuttingFiles();
        if (!isset($cuttingFiles[$fileIndex])) {
            return response()->json(['error' => 'File not found'], 404);
        }
        $filePath = $cuttingFiles[$fileIndex];
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $disk = $this->templateStorageService->getDisk();
        if (in_array($ext, ['pdf', 'svg'])) {
            $thumbPath = $this->getCuttingThumbnailPath($jobId, $fileIndex, $filePath);
            if ($disk->exists($thumbPath)) {
                $mime = $disk->mimeType($thumbPath);
                $stream = $disk->readStream($thumbPath);
                return response()->stream(function () use ($stream) {
                    fpassthru($stream);
                }, 200, [
                    'Content-Type' => $mime,
                    'Content-Disposition' => 'inline; filename="' . basename($thumbPath) . '"',
                ]);
            }
        }
        // Fallback: return a generic icon or 404
        return response()->json(['error' => 'No thumbnail available'], 404);
    }

    public function removeCuttingFile(Request $request, $id)
    {
        $request->validate(['fileIndex' => 'required|integer']);
        $job = Job::findOrFail($id);
        $cuttingFiles = $job->getCuttingFiles();
        $fileIndex = $request->input('fileIndex');
        if (!isset($cuttingFiles[$fileIndex]) || !is_string($cuttingFiles[$fileIndex]) || empty($cuttingFiles[$fileIndex])) {
            return response()->json(['error' => 'File not found or invalid file path'], 404);
        }
        $fileToRemove = $cuttingFiles[$fileIndex];
        $disk = $this->templateStorageService->getDisk();
        if ($fileToRemove && $disk->exists($fileToRemove)) {
            $disk->delete($fileToRemove);
        }
        // Remove thumbnail if exists
        $thumbPath = $this->getCuttingThumbnailPath($id, $fileIndex, $fileToRemove);
        if ($thumbPath && $disk->exists($thumbPath)) {
            $disk->delete($thumbPath);
        }
        $job->removeCuttingFile($fileToRemove);
        $job->save();
        return response()->json(['message' => 'Cutting file removed', 'remaining_files' => $job->getCuttingFiles()]);
    }

    public function getCuttingFileUploadProgress($id)
    {
        try {
            $cacheKey = "cutting_upload_progress_{$id}";
            $progress = \Cache::get($cacheKey, [
                'status' => 'idle',
                'progress' => 0,
                'current_file' => 0,
                'total_files' => 0,
                'message' => 'No upload in progress'
            ]);
            return response()->json($progress);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to get cutting file upload progress',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    private function updateCuttingUploadProgress($jobId, $status, $progress, $currentFile = 0, $totalFiles = 0, $message = '')
    {
        $cacheKey = "cutting_upload_progress_{$jobId}";
        $progressData = [
            'status' => $status,
            'progress' => $progress,
            'current_file' => $currentFile,
            'total_files' => $totalFiles,
            'message' => $message,
            'timestamp' => now()->timestamp
        ];
        \Cache::put($cacheKey, $progressData, 300); // Cache for 5 minutes
    }

    private function generateCuttingThumbnail($file, $jobId, $fileIndex)
    {
        try {
            $ext = strtolower($file->getClientOriginalExtension());
            $imagick = new \Imagick();
            $this->setGhostscriptPath($imagick);
            if ($ext === 'pdf') {
                $imagick->readImage($file->getPathname() . '[0]');
            } elseif ($ext === 'svg') {
                $imagick->readImage($file->getPathname());
            } else {
                return null; // No thumbnail for other types
            }
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(50);
            $imagick->stripImage();
            $imagick->resizeImage(800, 800, \Imagick::FILTER_LANCZOS, 1, true); // Standardized preview size
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            // Keep cutting thumbnails deterministic too
            $thumbnailPath = 'job-cutting-thumbnails/job_' . $jobId . '_' . $fileIndex . '_' . $originalFilename . '.webp';
            $this->templateStorageService->getDisk()->put($thumbnailPath, $thumbnailBlob);
            return $thumbnailPath;
        } catch (\Exception $e) {
            \Log::warning('Failed to generate cutting file thumbnail: ' . $e->getMessage(), [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'error_trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    /**
     * Fallback thumbnail generation for large PDFs
     * Uses lower resolution settings and aggressive memory management
     */
    private function generateThumbnailFallback($file, $jobId, $index, $originalFilename)
    {
        try {
            $imagick = new \Imagick();
            $this->setGhostscriptPath($imagick);
            
            // Set very conservative memory limits
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MEMORY, 64 * 1024 * 1024);  // 64MB only
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_MAP, 128 * 1024 * 1024);    // 128MB
            $imagick->setResourceLimit(\Imagick::RESOURCETYPE_DISK, 512 * 1024 * 1024);   // 512MB disk
            
            // Read PDF at extremely low resolution - just for recognition
            $imagick->setResolution(30, 30); // Extremely low DPI
            $imagick->readImage($file->getPathname() . '[0]'); // First page only
            
            // Convert to WebP immediately for better compression
            $imagick->setImageFormat('webp');
            $imagick->setImageCompressionQuality(30); // Very low quality = tiny file
            $imagick->stripImage(); // Remove all metadata
            
            // Standardized thumbnail size
            $imagick->resizeImage(800, 800, \Imagick::FILTER_LANCZOS, 1, true);
            
            // Get blob and clean up immediately
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            
            // Store in R2; keep fallback timestamped suffix but no dependency in lookup
            $thumbnailPath = 'job-thumbnails/job_' . $jobId . '_fallback_' . time() . '_' . $index . '_' . $originalFilename . '.webp';
            $this->templateStorageService->getDisk()->put($thumbnailPath, $thumbnailBlob);
            
            \Log::info('Generated fallback thumbnail for large PDF', [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'thumbnail_path' => $thumbnailPath,
                'method' => 'low_resolution_fallback'
            ]);
            
            return $thumbnailPath;
            
        } catch (\Exception $e) {
            \Log::warning('Fallback thumbnail generation failed: ' . $e->getMessage(), [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Fallback dimension calculation when Imagick fails
     * Uses basic PDF parsing to extract MediaBox dimensions
     */
    private function calculatePdfDimensionsFallback($file)
    {
        try {
            // Read the PDF file content
            $content = file_get_contents($file->getPathname());
            
            // Look for MediaBox entries in the PDF
            // MediaBox defines the page dimensions in points (1/72 inch)
            if (preg_match('/\/MediaBox\s*\[\s*(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s*\]/', $content, $matches)) {
                $x1 = floatval($matches[1]);
                $y1 = floatval($matches[2]);
                $x2 = floatval($matches[3]);
                $y2 = floatval($matches[4]);
                
                // Calculate dimensions in points
                $widthPoints = $x2 - $x1;
                $heightPoints = $y2 - $y1;
                
                // Convert points to millimeters (1 point = 1/72 inch, 1 inch = 25.4 mm)
                $widthMm = ($widthPoints / 72) * 25.4;
                $heightMm = ($heightPoints / 72) * 25.4;
                
                \Log::info('Extracted PDF dimensions using fallback method', [
                    'file' => $file->getClientOriginalName(),
                    'width_points' => $widthPoints,
                    'height_points' => $heightPoints,
                    'width_mm' => $widthMm,
                    'height_mm' => $heightMm,
                    'mediabox' => $matches[0]
                ]);
                
                return [
                    'width_mm' => $widthMm,
                    'height_mm' => $heightMm,
                    'method' => 'pdf_parsing'
                ];
            }
            
            // If MediaBox is not found, try to look for other size indicators
            // Some PDFs use CropBox or TrimBox
            if (preg_match('/\/CropBox\s*\[\s*(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s+(\d+(?:\.\d+)?)\s*\]/', $content, $matches)) {
                $x1 = floatval($matches[1]);
                $y1 = floatval($matches[2]);
                $x2 = floatval($matches[3]);
                $y2 = floatval($matches[4]);
                
                $widthPoints = $x2 - $x1;
                $heightPoints = $y2 - $y1;
                
                $widthMm = ($widthPoints / 72) * 25.4;
                $heightMm = ($heightPoints / 72) * 25.4;
                
                \Log::info('Extracted PDF dimensions using CropBox fallback method', [
                    'file' => $file->getClientOriginalName(),
                    'width_mm' => $widthMm,
                    'height_mm' => $heightMm,
                    'cropbox' => $matches[0]
                ]);
                
                return [
                    'width_mm' => $widthMm,
                    'height_mm' => $heightMm,
                    'method' => 'pdf_parsing_cropbox'
                ];
            }
            
            return null;
            
        } catch (\Exception $e) {
            \Log::warning('PDF parsing fallback failed', [
                'file' => $file->getClientOriginalName(),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Set Ghostscript path based on the current environment
     */
    private function setGhostscriptPath($imagick)
    {
        if (PHP_OS_FAMILY === 'Windows') {
            // Windows path
            $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
        } else {
            // Linux/Unix path - check common locations
            $commonPaths = [
                '/usr/bin/gs',
                '/usr/local/bin/gs',
                '/opt/local/bin/gs',
                '/bin/gs'
            ];
            
            foreach ($commonPaths as $path) {
                if (file_exists($path)) {
                    $imagick->setOption('gs', $path);
                    \Log::info('Set Ghostscript path for PDF processing', ['path' => $path]);
                    return;
                }
            }
            
            // If no specific path found, let Imagick use system default
            \Log::info('Using system default Ghostscript path');
        }
    }

    private function getCuttingThumbnailPath($jobId, $fileIndex, $filePath)
    {
        $originalFilename = pathinfo(basename($filePath), PATHINFO_FILENAME);
        $pattern = 'job-cutting-thumbnails/job_' . $jobId . '_*_'. $fileIndex . '_' . $originalFilename . '.jpg';
        $disk = $this->templateStorageService->getDisk();
        $allThumbs = $disk->files('job-cutting-thumbnails');
        foreach ($allThumbs as $thumb) {
            if (fnmatch($pattern, $thumb)) {
                return $thumb;
            }
        }
        return null;
    }
    // --- END CUTTING FILES BACKEND ---

    /**
     * Get the pricing multiplier based on catalog item settings
     * @param Job $job
     * @param int $quantity
     * @param int $copies
     * @return float
     */
    private function getPricingMultiplier($job, $quantity, $copies)
    {
        // If job has a catalog item, use its pricing method
        if ($job->catalog_item_id && $job->catalogItem) {
            return $job->catalogItem->getPricingMultiplier($quantity, $copies);
        }
        
        // For manually created jobs (no catalog item), default to quantity-based pricing
        return $quantity;
    }
}
