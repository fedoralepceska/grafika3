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
                
                // Handle category resolution for materials
                $catalogItem = CatalogItem::with('articles')->find($request->input('catalog_item_id'));
                $largeMaterialId = null;
                $smallMaterialId = null;
                
                // Resolve large material or category
                if ($request->input('large_material_category_id')) {
                    // Frontend sent a category ID directly
                    $largeMaterialId = $this->getNextAvailableMaterial($request->input('large_material_category_id'), 'large', $request->input('copies'));
                    if (!$largeMaterialId) {
                        throw new \Exception("No available materials in the selected large material category.");
                    }
                } elseif ($request->input('selectedMaterial')) {
                    // Frontend sent a specific material ID
                    $largeMaterialId = $request->input('selectedMaterial');
                } elseif ($catalogItem->large_material_category_id) {
                    // Catalog item has a category, select next available material
                    $largeMaterialId = $this->getNextAvailableMaterial($catalogItem->large_material_category_id, 'large', $request->input('copies'));
                    if (!$largeMaterialId) {
                        throw new \Exception("No available materials in the selected large material category.");
                    }
                } elseif ($catalogItem->large_material_id) {
                    // Catalog item has a specific material
                    $largeMaterialId = $catalogItem->large_material_id;
                }
                
                // Resolve small material or category
                if ($request->input('small_material_category_id')) {
                    // Frontend sent a category ID directly
                    $smallMaterialId = $this->getNextAvailableMaterial($request->input('small_material_category_id'), 'small', $request->input('copies'));
                    if (!$smallMaterialId) {
                        throw new \Exception("No available materials in the selected small material category.");
                    }
                } elseif ($request->input('selectedMaterialsSmall')) {
                    // Frontend sent a specific material ID
                    $smallMaterialId = $request->input('selectedMaterialsSmall');
                } elseif ($catalogItem->small_material_category_id) {
                    // Catalog item has a category, select next available material
                    $smallMaterialId = $this->getNextAvailableMaterial($catalogItem->small_material_category_id, 'small', $request->input('copies'));
                    if (!$smallMaterialId) {
                        throw new \Exception("No available materials in the selected small material category.");
                    }
                } elseif ($catalogItem->small_material_id) {
                    // Catalog item has a specific material
                    $smallMaterialId = $catalogItem->small_material_id;
                }
                
                $job->large_material_id = $largeMaterialId;
                $job->small_material_id = $smallMaterialId;
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
                    
                    // Generate preview image and calculate dimensions
                    $imagick = new Imagick();
                    $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
                    $imagick->readImage($file->getPathname() . '[0]'); // Read first page
                    $imagick->setImageFormat('jpg');
                    
                    // Create unique filename for preview
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                    $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                    $imagick->writeImage($imagePath);
                    
                    // Calculate dimensions
                    list($width, $height) = getimagesize($imagePath);
                    $dpi = 72; // Default DPI
                    $widthInMm = ($width / $dpi) * 25.4;
                    $heightInMm = ($height / $dpi) * 25.4;
                    
                    $imagick->clear();

                    // Save job first to get ID
                    $job->file = $imageFilename; // Preview image
                    $job->addOriginalFile($originalPath); // Store in originalFile JSON array
                    $job->width = $widthInMm;
                    $job->height = $heightInMm;
                    $job->save(); // Save to get ID
                    
                    // Generate thumbnail and store in R2
                    $this->generateThumbnail($imagePath, $job->id, 0, $file->getClientOriginalName());
                    
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
                    
                    $imagick->clear();

                    // Save job first to get ID - TIFF now also uses R2 storage
                    $job->file = $imageFilename; // Preview image
                    $job->addOriginalFile($originalPath); // Store TIFF in originalFile JSON array
                    $job->width = $widthInMm;
                    $job->height = $heightInMm;
                    $job->save(); // Save to get ID
                    
                    // Generate thumbnail and store in R2
                    $this->generateThumbnail($imagePath, $job->id, 0, $file->getClientOriginalName());
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
                'selectedMaterial' => 'nullable|exists:large_format_materials,id',
                'selectedMaterialsSmall' => 'nullable|exists:small_material,id',
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
                    'large_material_id' => $request->input('selectedMaterial'),
                    'small_material_id' => $request->input('selectedMaterialsSmall'),
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

                // Calculate cost price from materials and actions
                $costPrice = 0;
                $smallMaterial = SmallMaterial::with('article')->find($request->input('selectedMaterialsSmall'));
                $largeMaterial = LargeFormatMaterial::with('article')->find($request->input('selectedMaterial'));

                if ($request->has('jobsWithActions')) {
                    foreach ($request->input('jobsWithActions') as $jobWithActions) {
                        foreach ($jobWithActions['actions'] as $action) {
                            if (isset($action['quantity'])) {
                                if (isset($smallMaterial)) {
                                    $costPrice = $costPrice + ($action['quantity'] * $smallMaterial->article->price_1);
                                }
                                if (isset($largeMaterial)) {
                                    $costPrice = $costPrice + ($action['quantity'] * $largeMaterial->article->price_1);
                                }
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
                'width' => 'required|numeric|min:0',
                'height' => 'required|numeric|min:0',
                'quantity' => 'required|integer|min:1',
                'copies' => 'required|integer|min:1',
            ]);

            $job = Job::find($request->input('job_id'));
            
            if (!$job) {
                return response()->json(['error' => 'Job not found'], 404);
            }

            // Update job dimensions
            $job->width = $request->input('width');
            $job->height = $request->input('height');
            $job->quantity = $request->input('quantity');
            $job->copies = $request->input('copies');
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
            
            if ($job->catalog_item_id) {
                $catalogItem = CatalogItem::with('articles')->find($job->catalog_item_id);
                if ($catalogItem) {
                    $costPrice = $catalogItem->calculateJobCostPrice($job);
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
        // Find the action with the specified name
        $actions = DB::table('job_actions')->where('name', $actionId)->get();

        // Find the action with the specified name
        $action = DB::table('job_actions')->where('name', $actionId)->first();

        // If the action is not found, return an appropriate response
        if (!$actions->count()) {
            return response()->json(['error' => 'Action not found'], 404);
        }

        // Fetch the jobs associated with the found action
        $jobs = DB::table('jobs')
            ->join('job_job_action', 'jobs.id', '=', 'job_job_action.job_id')
            ->whereIn('job_job_action.job_action_id', $actions->pluck('id'))
            ->where('jobs.status', '!=', 'Completed') // Filter out completed jobs
            ->get();

        foreach ($jobs as $job) {
            $job->hasNote = $action->hasNote;

            // Fetch actions associated with the current job from job_job_action table
            $actionsForJob = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->where('job_job_action.job_id', $job->job_id)
                ->select('job_actions.*')
                ->get()
                ->toArray();

            // Attach actions to the job
            $job->actions = $actionsForJob;
        }


        // Now, let's retrieve invoices associated with these jobs
        $invoiceIds = DB::table('invoice_job')
            ->whereIn('job_id', $jobs->pluck('job_id'))
            ->pluck('invoice_id');
        // Fetch the invoices based on the retrieved invoice IDs
        $invoices = DB::table('invoices')
            ->whereIn('invoices.id', $invoiceIds)
            ->leftJoin('users', 'invoices.created_by', '=', 'users.id') // Join with users table
            ->leftJoin('clients', 'invoices.client_id', '=', 'clients.id') // Join with clients table
            ->select('invoices.*', 'users.name as user_name', 'clients.name as client_name')
            ->orderBy('start_date', 'asc')
            ->get();

        // Attach jobs to each invoice
        foreach ($invoices as $invoice) {
            $jobIdsForInvoice = DB::table('invoice_job')
                ->where('invoice_id', $invoice->id)
                ->pluck('job_id');

            $jobsForInvoice = DB::table('jobs')
                ->whereIn('id', $jobIdsForInvoice)
                ->where('status', '!=', 'Completed')
                ->get();

            // Now, get all jobs with actions in one go
            $jobsWithActions = Job::with('actions')->whereIn('id', $jobIdsForInvoice)->get()->keyBy('id');

            // Replace each job in $jobsForInvoice with the corresponding one from $jobsWithActions
            foreach ($jobsForInvoice as $index => $job) {
                if (isset($jobsWithActions[$job->id])) {
                    $jobsForInvoice[$index] = $jobsWithActions[$job->id];

                    // Sort the actions for the current job
                    $sortedActions = collect($job->actions)->sortBy('id')->values()->toArray();

                    $jobsForInvoice[$index]->actions = $sortedActions;
                }
            }
            $invoice->jobs = $jobsForInvoice;
        }

        return response()->json([
            'invoices' => $invoices, // Include invoices in the response
            'actionId' => $actionId,
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
                    $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
                    $imagick->readImage($file->getPathname() . '[0]');
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
                    
                    // Clear old original files and add the new one
                    $job->originalFile = [];
                    if ($pdfPath) {
                        $job->addOriginalFile($pdfPath);
                    }
                    
                    $job->width = $widthInMm;
                    $job->height = $heightInMm;
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

        // For each machine name, get the counts of different statuses
        foreach ($machineNames as $name) {
            $total = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->where('job_actions.name', $name)
                ->where('job_job_action.status', 'In Progress')
                ->count();

            $secondaryCount = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->where('job_actions.name', $name)
                ->where('job_job_action.status', 'Not started yet')
                ->count();

            $onHoldCount = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'job_job_action.job_id') // Adjust the join for the pivot table
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id') // Join with invoices table
                ->where('job_actions.name', $name)
                ->where('invoices.onHold', true)
                ->whereIn('job_job_action.status', ['Not started yet', 'In Progress'])
                ->count();

            $onRushCount = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'job_job_action.job_id') // Adjust the join for the pivot table
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id') // Join with invoices table
                ->where('job_actions.name', $name)
                ->where('invoices.rush', true)
                ->whereIn('job_job_action.status', ['Not started yet', 'In Progress'])
                ->count();

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
                ->where('machineCut', $name) // Assuming 'machine_name' is the column storing the machine's name
                ->where('status', 'In Progress')
                ->count();

            $secondaryCount = DB::table('jobs')
                ->where('machineCut', $name)
                ->where('status', 'Not started yet')
                ->count();

            $onHoldCount = DB::table('jobs')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('jobs.machineCut', $name)
                ->where('invoices.onHold', true)
                ->whereIn('jobs.status', ['Not started yet', 'In Progress'])
                ->count();

            $onRushCount = DB::table('jobs')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'jobs.id')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->where('jobs.machineCut', $name)
                ->where('invoices.rush', true)
                ->whereIn('jobs.status', ['Not started yet', 'In Progress'])
                ->count();

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

        // For each action name, get the count of 'In Progress' and 'Not started yet' statuses
        foreach ($actionNames as $name) {
            $total = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'job_job_action.job_id') // Ensure job is linked to an invoice
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id') // Ensure invoice exists
                ->where('job_actions.name', $name)
                ->where('job_job_action.status', 'In Progress')
                ->count();

            $secondaryCount = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'job_job_action.job_id') // Ensure job is linked to an invoice
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id') // Ensure invoice exists
                ->where('job_actions.name', $name)
                ->where('job_job_action.status', 'Not started yet')
                ->count();

            $onHoldCount = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'job_job_action.job_id') // Ensure job is linked to an invoice
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id') // Ensure invoice exists
                ->where('job_actions.name', $name)
                ->where('invoices.onHold', true)
                ->whereIn('job_job_action.status', ['Not started yet', 'In Progress'])
                ->count();

            $onRushCount = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->join('invoice_job', 'invoice_job.job_id', '=', 'job_job_action.job_id') // Ensure job is linked to an invoice
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id') // Ensure invoice exists
                ->where('job_actions.name', $name)
                ->where('invoices.rush', true)
                ->whereIn('job_job_action.status', ['Not started yet', 'In Progress'])
                ->count();

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
            // Retrieve job and invoice IDs from the request
            $jobId = $request->input('job');
            $invoiceId = $request->input('invoice');
            $actionId = $request->input('action');

            // Find existing job and invoice
            $job = Job::findOrFail($jobId);
            $invoice = Invoice::findOrFail($invoiceId);

            // Update job information and save
            $job->started_by = auth()->id();
            $job->save();

            // Create a new record in the workers_analytics table
            DB::table('workers_analytics')->insert([
                'invoice_id' => $invoiceId,
                'job_id' => $jobId,
                'action_id' => $actionId,
                'user_id' => auth()->id(),
                'time_spent' => 0, // Initially set to 0
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Dispatch the JobStarted event with both job and invoice
            event(new JobStarted($job, $invoice));
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function fireEndJobEvent(Request $request) {
        $jobData = $request->input('job');
        $invoiceData = $request->input('invoice');
        $actionId = $request->input('action');
        $time_spent = $request->input('time_spent');

        // Find the matching record in the workers_analytics table
        $workerAnalytics = DB::table('workers_analytics')->where('job_id', $jobData['id'])
            ->where('invoice_id', $invoiceData['id'])
            ->where('action_id', $actionId)
            ->where('user_id', auth()->id())
            ->first();

        // If the record exists, update the time_spent
        if ($workerAnalytics) {
            $workerAnalytics->time_spent = $time_spent;
            $workerAnalytics->save();
        }

        // Create job and invoice instances
        $job = new Job($jobData);
        $invoice = new Invoice($invoiceData);

        // Dispatch the JobEnded event with both job and invoice
        event(new JobEnded($job, $invoice));
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

    public function getCatalogItems(Request $request)
    {
        try {
            // Get pagination parameters with defaults
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $searchTerm = $request->input('search', '');

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

            // Paginate the results
            $catalogItems = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform paginated items
            $transformedItems = $catalogItems->getCollection()->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'machinePrint' => $item->machinePrint,
                    'machineCut' => $item->machineCut,
                    'largeMaterial' => $item->large_material_id,
                    'smallMaterial' => $item->small_material_id,
                    'large_material_category_id' => $item->large_material_category_id,
                    'small_material_category_id' => $item->small_material_category_id,
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
                'files.*' => 'required|mimes:pdf|max:20480', // 20MB max per file
            ]);

            // Find the job
            $job = Job::findOrFail($id);

            $uploadedFiles = [];
            $thumbnails = [];
            $allFileDimensions = [];
            // Start with existing job dimensions (for cumulative uploads)
            $totalWidthMm = $job->width ?? 0;
            $totalHeightMm = $job->height ?? 0;
            $totalAreaM2 = 0; // Area will be calculated from individual files
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
                'existing_width_mm' => $totalWidthMm,
                'existing_height_mm' => $totalHeightMm
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
                    
                    // Update progress for individual file (more granular)
                    $fileProgress = 10 + (($globalIndex + 1) / $totalFiles) * 70;
                    $this->updateUploadProgress($id, 'processing', $fileProgress, $globalIndex + 1, $totalFiles, "Processing file " . ($globalIndex + 1) . " of {$totalFiles}");
                    
                    // Store file in R2
                    $filePath = $this->templateStorageService->storeTemplate($file, 'job-originals');
                    $uploadedFiles[] = $filePath;

                    // Add a small delay to make progress visible
                    usleep(200000); // 200ms delay

                    // Calculate dimensions and generate thumbnail in parallel
                    $this->processFileDimensionsAndThumbnail($file, $globalIndex, $id, $filePath, $totalWidthMm, $totalHeightMm, $totalAreaM2, $allFileDimensions, $thumbnails, $firstFilePreview);
                }

                // Small delay between batches to prevent overwhelming the system
                if ($batchEnd < $totalFiles) {
                    usleep(300000); // 300ms delay
                }
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

            // Update job with total calculated dimensions
            if ($totalWidthMm > 0 && $totalHeightMm > 0) {
                $job->width = $totalWidthMm;
                $job->height = $totalHeightMm;
                
                // Update preview image if we have one
                if ($firstFilePreview) {
                    $job->file = $firstFilePreview;
                }
                
                \Log::info('Updated job with cumulative dimensions', [
                    'job_id' => $id,
                    'new_total_width_mm' => $totalWidthMm,
                    'new_total_height_mm' => $totalHeightMm,
                    'added_area_m2' => $totalAreaM2,
                    'files_processed' => count($allFileDimensions),
                    'preview_image' => $firstFilePreview
                ]);
            }

            $job->save();

            // Update progress to complete
            $this->updateUploadProgress($id, 'complete', 100, $totalFiles, $totalFiles, 'Upload completed successfully');

            return response()->json([
                'message' => 'Files uploaded successfully',
                'originalFiles' => $job->getOriginalFiles(),
                'uploadedCount' => count($uploadedFiles),
                'thumbnails' => $thumbnails,
                'dimensions' => [
                    'total_width_mm' => $totalWidthMm,
                    'total_height_mm' => $totalHeightMm,
                    'total_area_m2' => $totalAreaM2,
                    'individual_files' => $allFileDimensions,
                    'files_count' => count($allFileDimensions)
                ],
                'job_updated' => $totalWidthMm > 0
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
    private function processFileDimensionsAndThumbnail($file, $index, $jobId, $filePath, &$totalWidthMm, &$totalHeightMm, &$totalAreaM2, &$allFileDimensions, &$thumbnails, &$firstFilePreview)
    {
        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        
        // Calculate dimensions for the file
        try {
            $imagick = new \Imagick();
            $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
            $imagick->readImage($file->getPathname() . '[0]'); // Read the first page
            $imagick->setImageFormat('jpg');
            
            // Create temporary image for dimension calculation
            $tempImagePath = storage_path('app/temp/dim_calc_' . $index . '_' . time() . '.jpg');
            
            // Ensure temp directory exists
            if (!file_exists(dirname($tempImagePath))) {
                mkdir(dirname($tempImagePath), 0755, true);
            }
            
            $imagick->writeImage($tempImagePath);
            
            // Calculate dimensions from the image
            list($width, $height) = getimagesize($tempImagePath);
            $dpi = 72; // Default DPI if not available
            $widthInMm = ($width / $dpi) * 25.4;
            $heightInMm = ($height / $dpi) * 25.4;
            $areaM2 = ($widthInMm * $heightInMm) / 1000000;

            // Add to totals
            $totalWidthMm += $widthInMm;
            $totalHeightMm += $heightInMm;
            $totalAreaM2 += $areaM2;

            // Store individual file dimensions
            $allFileDimensions[] = [
                'filename' => $file->getClientOriginalName(),
                'width_mm' => $widthInMm,
                'height_mm' => $heightInMm,
                'area_m2' => $areaM2,
                'index' => $index
            ];

            // For the FIRST file, also create preview image for the job
            if ($index === 0) {
                $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg';
                $imagePath = storage_path('app/public/uploads/' . $imageFilename);
                copy($tempImagePath, $imagePath); // Copy temp image to uploads
                $firstFilePreview = $imageFilename;
            }
            
            // Clean up temp file
            if (file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }
            
            $imagick->clear();
            
            \Log::info('Calculated dimensions for file', [
                'job_id' => $jobId,
                'file' => $file->getClientOriginalName(),
                'index' => $index,
                'width_mm' => $widthInMm,
                'height_mm' => $heightInMm,
                'area_m2' => $areaM2
            ]);
            
        } catch (\Exception $e) {
            \Log::warning('Failed to calculate dimensions for file: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'job_id' => $jobId,
                'index' => $index,
                'error_trace' => $e->getTraceAsString()
            ]);
            // Continue without dimensions for this file - don't fail the upload
        }

        // Generate thumbnail and store in R2
        try {
            $imagick = new \Imagick();
            $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
            $imagick->readImage($file->getPathname() . '[0]'); // Read the first page
            $imagick->setImageFormat('jpg');
            
            // Create thumbnail in memory
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            
            // Store thumbnail in R2
            $thumbnailPath = 'job-thumbnails/job_' . $jobId . '_' . time() . '_' . $index . '_' . $originalFilename . '.jpg';
            
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
        } catch (\Exception $e) {
            \Log::warning('Failed to generate thumbnail for PDF: ' . $e->getMessage(), [
                'file' => $file->getClientOriginalName(),
                'job_id' => $jobId,
                'error_trace' => $e->getTraceAsString()
            ]);
            // Continue without thumbnail - show PDF icon instead
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
                'file_index' => 'required|integer|min:0'
            ]);

            // Find the job
            $job = Job::findOrFail($id);
            $fileIndex = $request->input('file_index');
            $originalFiles = $job->getOriginalFiles();

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
                // Recalculate job dimensions by subtracting the removed file's dimensions
                if ($removedFileDimensions && $removedFileDimensions['width_mm'] > 0 && $removedFileDimensions['height_mm'] > 0) {
                    $currentWidth = $job->width ?? 0;
                    $currentHeight = $job->height ?? 0;
                    $currentArea = ($currentWidth * $currentHeight) / 1000000; // Convert to m²
                    
                    // Subtract the removed file's dimensions
                    $newWidth = max(0, $currentWidth - $removedFileDimensions['width_mm']);
                    $newHeight = max(0, $currentHeight - $removedFileDimensions['height_mm']);
                    $newArea = max(0, $currentArea - $removedFileDimensions['area_m2']);
                    
                    // Update job dimensions
                    $job->width = $newWidth > 0 ? $newWidth : null;
                    $job->height = $newHeight > 0 ? $newHeight : null;
                    
                    \Log::info('Recalculated job dimensions after file removal', [
                        'job_id' => $id,
                        'removed_file_dimensions' => $removedFileDimensions,
                        'previous_dimensions' => [
                            'width_mm' => $currentWidth,
                            'height_mm' => $currentHeight,
                            'area_m2' => $currentArea
                        ],
                        'new_dimensions' => [
                            'width_mm' => $newWidth,
                            'height_mm' => $newHeight,
                            'area_m2' => $newArea
                        ]
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

                // Clean up associated thumbnail from R2
                $this->cleanupThumbnailForFile($id, $fileIndex, pathinfo(basename($fileToRemove), PATHINFO_FILENAME));

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
                        'width_mm' => $job->width,
                        'height_mm' => $job->height,
                        'area_m2' => $job->width && $job->height ? ($job->width * $job->height) / 1000000 : 0
                    ],
                    'removed_file_dimensions' => $removedFileDimensions
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
            
            // Process with ImageMagick
            $imagick = new \Imagick();
            $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
            $imagick->readImage($tempFilePath . '[0]'); // Read the first page
            $imagick->setImageFormat('jpg');
            
            // Create temporary image for dimension calculation
            $tempImagePath = storage_path('app/temp/remove_dim_calc_' . $fileIndex . '_' . time() . '.jpg');
            $imagick->writeImage($tempImagePath);
            
            // Calculate dimensions from the image
            list($width, $height) = getimagesize($tempImagePath);
            $dpi = 72; // Default DPI if not available
            $widthInMm = ($width / $dpi) * 25.4;
            $heightInMm = ($height / $dpi) * 25.4;
            $areaM2 = ($widthInMm * $heightInMm) / 1000000;
            
            // Clean up temp files
            if (file_exists($tempFilePath)) {
                unlink($tempFilePath);
            }
            if (file_exists($tempImagePath)) {
                unlink($tempImagePath);
            }
            
            $imagick->clear();
            
            return [
                'width_mm' => $widthInMm,
                'height_mm' => $heightInMm,
                'area_m2' => $areaM2,
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
                $originalFileName = pathinfo(basename($originalFile), PATHINFO_FILENAME);
                
                // Try to find thumbnail in R2 for this file
                $thumbnailPath = null;
                
                try {
                    // List files in the job-thumbnails directory
                    $thumbnailFiles = $this->templateStorageService->getDisk()->files('job-thumbnails');
                    
                    foreach ($thumbnailFiles as $thumbFile) {
                        $thumbBasename = basename($thumbFile);
                        // Check if this thumbnail belongs to this job and matches the original file
                        if (strpos($thumbBasename, 'job_' . $id . '_') === 0 && 
                            strpos($thumbBasename, '_' . $index . '_') !== false &&
                            strpos($thumbBasename, $originalFileName) !== false) {
                            $thumbnailPath = $thumbFile;
                            break;
                        }
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

            // Find ALL thumbnails for this job and sort them by timestamp
            $thumbnailPath = null;
            
            try {
                // List files in the job-thumbnails directory
                $thumbnailFiles = $this->templateStorageService->getDisk()->files('job-thumbnails');
                
                // Get all thumbnails for this job
                $jobThumbnails = [];
                foreach ($thumbnailFiles as $thumbFile) {
                    $thumbBasename = basename($thumbFile);
                    if (strpos($thumbBasename, 'job_' . $jobId . '_') === 0) {
                        // Extract timestamp from filename: job_ID_TIMESTAMP_INDEX_filename.jpg
                        if (preg_match('/job_' . $jobId . '_(\d+)_(\d+)_/', $thumbBasename, $matches)) {
                            $timestamp = $matches[1];
                            $originalIndex = $matches[2];
                            $jobThumbnails[] = [
                                'path' => $thumbFile,
                                'timestamp' => (int)$timestamp,
                                'original_index' => (int)$originalIndex,
                                'basename' => $thumbBasename
                            ];
                        }
                    }
                }
                
                \Log::info('Found thumbnails for job', [
                    'job_id' => $jobId,
                    'requested_index' => $fileIndex,
                    'found_thumbnails' => $jobThumbnails
                ]);
                
                // Sort by timestamp to get chronological order
                usort($jobThumbnails, function($a, $b) {
                    return $a['timestamp'] <=> $b['timestamp'];
                });
                
                // Get the thumbnail for the requested index (0-based from sorted list)
                if (isset($jobThumbnails[$fileIndex])) {
                    $thumbnailPath = $jobThumbnails[$fileIndex]['path'];
                    \Log::info('Serving thumbnail by position', [
                        'job_id' => $jobId,
                        'requested_index' => $fileIndex,
                        'thumbnail_path' => $thumbnailPath,
                        'original_index_in_filename' => $jobThumbnails[$fileIndex]['original_index']
                    ]);
                }
                
            } catch (\Exception $e) {
                \Log::warning('Failed to find thumbnail: ' . $e->getMessage());
                return response()->json(['error' => 'Thumbnail not found'], 404);
            }

            if (!$thumbnailPath) {
                \Log::warning('No thumbnail found for requested index', [
                    'job_id' => $jobId,
                    'file_index' => $fileIndex,
                    'available_count' => count($jobThumbnails ?? [])
                ]);
                return response()->json(['error' => 'Thumbnail not found'], 404);
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
            
            \Log::info('Successfully serving job thumbnail', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'thumbnail_path' => $thumbnailPath,
                'content_size' => strlen($thumbnailContent)
            ]);

            return response($thumbnailContent)
                ->header('Content-Type', 'image/jpeg')
                ->header('Cache-Control', 'public, max-age=3600')
                ->header('Expires', gmdate('D, d M Y H:i:s \G\M\T', time() + 3600));

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
            
            // Get all thumbnails for this job and sort them by timestamp
            $jobThumbnails = [];
            foreach ($thumbnailFiles as $thumbFile) {
                $thumbBasename = basename($thumbFile);
                if (strpos($thumbBasename, 'job_' . $jobId . '_') === 0) {
                    // Extract timestamp from filename: job_ID_TIMESTAMP_INDEX_filename.jpg
                    if (preg_match('/job_' . $jobId . '_(\d+)_(\d+)_/', $thumbBasename, $matches)) {
                        $timestamp = $matches[1];
                        $originalIndex = $matches[2];
                        $jobThumbnails[] = [
                            'path' => $thumbFile,
                            'timestamp' => (int)$timestamp,
                            'original_index' => (int)$originalIndex,
                            'basename' => $thumbBasename
                        ];
                    }
                }
            }
            
            // Sort by timestamp to get chronological order
            usort($jobThumbnails, function($a, $b) {
                return $a['timestamp'] <=> $b['timestamp'];
            });
            
            \Log::info('Cleaning up thumbnail for removed file', [
                'job_id' => $jobId,
                'file_index' => $fileIndex,
                'available_thumbnails' => count($jobThumbnails),
                'thumbnails' => $jobThumbnails
            ]);
            
            // Delete the thumbnail at the specified index position
            if (isset($jobThumbnails[$fileIndex])) {
                $thumbnailToDelete = $jobThumbnails[$fileIndex];
                
                try {
                    $this->templateStorageService->getDisk()->delete($thumbnailToDelete['path']);
                    \Log::info('Successfully deleted specific thumbnail from R2', [
                        'job_id' => $jobId,
                        'file_index' => $fileIndex,
                        'thumbnail_path' => $thumbnailToDelete['path'],
                        'original_index_in_filename' => $thumbnailToDelete['original_index']
                    ]);
                } catch (\Exception $e) {
                    \Log::error('Failed to delete specific thumbnail: ' . $e->getMessage(), [
                        'job_id' => $jobId,
                        'file_index' => $fileIndex,
                        'thumbnail_path' => $thumbnailToDelete['path']
                    ]);
                }
            } else {
                \Log::warning('No thumbnail found at index for deletion', [
                    'job_id' => $jobId,
                    'file_index' => $fileIndex,
                    'available_count' => count($jobThumbnails)
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
            $imagick->resizeImage(200, 200, \Imagick::FILTER_LANCZOS, 1, true); // Resize to thumbnail size
            
            // Create thumbnail in memory
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            
            // Store thumbnail in R2
            $originalFilename = pathinfo($originalFileName, PATHINFO_FILENAME);
            $thumbnailPath = 'job-thumbnails/job_' . $jobId . '_' . time() . '_' . $fileIndex . '_' . $originalFilename . '.jpg';
            
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
                'files.*' => 'required|mimes:pdf,svg,dxf,cdr,ai|max:20480', // 20MB max per file
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
            if ($ext === 'pdf') {
                $imagick->readImage($file->getPathname() . '[0]');
            } elseif ($ext === 'svg') {
                $imagick->readImage($file->getPathname());
            } else {
                return null; // No thumbnail for other types
            }
            $imagick->setImageFormat('jpg');
            $imagick->resizeImage(200, 200, \Imagick::FILTER_LANCZOS, 1, true);
            $thumbnailBlob = $imagick->getImageBlob();
            $imagick->clear();
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $thumbnailPath = 'job-cutting-thumbnails/job_' . $jobId . '_' . time() . '_' . $fileIndex . '_' . $originalFilename . '.jpg';
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
