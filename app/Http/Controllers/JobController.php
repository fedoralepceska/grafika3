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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Imagick;
use Inertia\Inertia;
use App\Services\PriceCalculationService;

class JobController extends Controller
{
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
                $job->large_material_id = $request->input('large_material_id');
                $job->small_material_id = $request->input('small_material_id');
                $job->name = $request->input('name');
                $job->quantity = $request->input('quantity');
                $job->copies = $request->input('copies');
                $job->file = 'placeholder.jpeg';
                $job->width = 0;
                $job->height = 0;
                $job->catalog_item_id = $request->input('catalog_item_id');
                $job->client_id = $request->input('client_id');

                // Calculate unit price based on hierarchy
                $unitPrice = $priceCalculationService->calculateEffectivePrice(
                    $request->input('catalog_item_id'),
                    $request->input('client_id'),
                    $request->input('quantity')
                );

                \Log::info('Creating job from catalog item', [
                    'catalog_item_id' => $request->input('catalog_item_id'),
                    'client_id' => $request->input('client_id'),
                    'quantity' => $request->input('quantity'),
                    'calculated_unit_price' => $unitPrice
                ]);

                // If hierarchy-based price exists, use it; otherwise fall back to the original calculation
                if ($unitPrice !== null) {
                    $job->price = $unitPrice; // Store the per-unit price
                } else {
                    // Original price calculation logic
                    $smallMaterial = SmallMaterial::with('article')->find($request->input('small_material_id'));
                    $largeMaterial = LargeFormatMaterial::with('article')->find($request->input('large_material_id'));
                    $price = 0;

                    if ($request->has('actions')) {
                        foreach ($request->input('actions') as $action) {
                            if (isset($action['quantity'])) {
                                if (isset($smallMaterial)) {
                                    $price = $price + ($action['quantity'] * $smallMaterial->article->price_1);
                                }
                                if (isset($largeMaterial)) {
                                    $price = $price + ($action['quantity'] * $largeMaterial->article->price_1);
                                }
                            }
                        }
                    }
                    $job->price = $price;
                }

                if ($request->input('large_material_id') !== null) {
                    $large_material = LargeFormatMaterial::find($request->input('large_material_id'));
                    if ($large_material->quantity - $request->input('copies') < 0) {
                        throw new \Exception("Insufficient large material quantity.");
                    }
                }

                if ($request->input('small_material_id') !== null) {
                    $small_material = SmallMaterial::find($request->input('small_material_id'));
                    if ($small_material->quantity - $request->input('copies') < 0) {
                        throw new \Exception("Insufficient small material quantity.");
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
        // Original file upload logic
        try {
            // Validate the request data
            $this->validate($request, [
                'file' => 'required|mimetypes:image/tiff,application/pdf', // Ensure the file is an image
            ]);

            // Handle file upload and storage
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileExtension = $file->getClientOriginalExtension();
                $pdfPath = $file->store('public/uploads', ['disk' => 'local']); // Store the PDF file

                if ($fileExtension === 'tiff' || $fileExtension === 'tif') {
                    // Handle TIFF file conversion to an image
                    $imagick = new Imagick();
                    $imagick->readImage($file->getPathname()); // Read the TIFF file
                    $imagick->setImageFormat('jpg'); // Convert TIFF to JPG (you can use other formats too)
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg'; // Unique image file name
                    $imagick->writeImage(storage_path('app/public/uploads/' . $imageFilename)); // Save the image
                    $imagick->clear();

                    // Create a new job
                    $job = new Job();
                    $job->file = $imageFilename; // Store the image file name

                    // Set other job properties if needed

                    $job->save(); // Save the job to the database

                    return response()->json(['message' => 'Job created successfully', 'job' => $job]);
                } elseif ($fileExtension === 'pdf') {
                    $imagick = new Imagick();
                    $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
                    $imagick->readImage($file->getPathname() . '[0]'); // Read the first page of the PDF
                    $imagick->setImageFormat('jpg'); // Convert PDF to JPG (you can use other formats too)
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg'; // Unique image file name
                    $imagick->writeImage(storage_path('app/public/uploads/' . $imageFilename)); // Save the image
                    $imagick->clear();

                    // Create a new job
                    $job = new Job();
                    $job->file = $imageFilename; // Store the image file name
                    $job->originalFile = $pdfPath;

                    // Set other job properties if needed

                    $job->save(); // Save the job to the database

                    // Attach the job to the user or invoice as needed

                    return response()->json(['message' => 'Job created successfully', 'job' => $job]);
                }
            } else {
                return response()->json(['message' => 'File not provided'], 400);
            }
        } catch (\Exception $e) {
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
                'catalog_item_id' => 'sometimes|exists:catalog_items,id',
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

                if ($unitPrice !== null) {
                    $updateData['price'] = $unitPrice;
                } else {
                    // Original price calculation logic
                    $smallMaterial = SmallMaterial::with('article')->find($request->input('selectedMaterialsSmall'));
                    $largeMaterial = LargeFormatMaterial::with('article')->find($request->input('selectedMaterial'));
                    $price = 0;

                    if ($request->has('jobsWithActions')) {
                        foreach ($request->input('jobsWithActions') as $jobWithActions) {
                            foreach ($jobWithActions['actions'] as $action) {
                                if (isset($action['quantity'])) {
                                    if (isset($smallMaterial)) {
                                        $price = $price + ($action['quantity'] * $smallMaterial->article->price_1);
                                    }
                                    if (isset($largeMaterial)) {
                                        $price = $price + ($action['quantity'] * $largeMaterial->article->price_1);
                                    }
                                }
                            }
                        }
                    }
                    $updateData['price'] = $price;
                }

                // Update each job individually to preserve its specific attributes
                $job->update($updateData);
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
                $jobArray['price'] = (float)$job->price;
                $jobArray['width'] = (float)$job->width;
                $jobArray['height'] = (float)$job->height;
                $jobArray['quantity'] = (int)$job->quantity;
                $jobArray['copies'] = (int)$job->copies;
                return $jobArray;
            })
            ->toArray();

        return response()->json(['jobs' => $jobs]);
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

            // Handle file upload and storage
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileExtension = $file->getClientOriginalExtension();
                $pdfPath = $file->store('public/uploads', ['disk' => 'local']);

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
                    $job->originalFile = $pdfPath;
                    $job->width = $widthInMm;
                    $job->height = $heightInMm;
                    $job->save();

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
                'catalog_item_id' => 'sometimes|exists:catalog_items,id',
                'client_id' => 'sometimes|exists:clients,id',
                'width' => 'sometimes|required|numeric',
                'height' => 'sometimes|required|numeric',
                'file' => 'sometimes|required',
                'status' => 'sometimes|required',
                'salePrice' => 'sometimes|required', // Keep the original salePrice validation
            ]);

            // If quantity is being updated, recalculate the price
            if ($request->has('quantity')) {
                $priceCalculationService = app()->make(PriceCalculationService::class);

                // Get the catalog_item_id and client_id from request or existing job/invoice
                $catalogItemId = $request->input('catalog_item_id') ?? $job->catalog_item_id ?? $job->invoice?->catalog_item_id;
                $clientId = $request->input('client_id') ?? $job->client_id ?? $job->invoice?->client_id;

                \Log::info('Recalculating price for job update', [
                    'job_id' => $id,
                    'catalog_item_id' => $catalogItemId,
                    'client_id' => $clientId,
                    'new_quantity' => $request->input('quantity')
                ]);

                // Calculate the new price based on the updated quantity
                $newPrice = $priceCalculationService->calculateEffectivePrice(
                    $catalogItemId,
                    $clientId,
                    $request->input('quantity')
                );

                \Log::info('Price calculation result', [
                    'job_id' => $id,
                    'calculated_price' => $newPrice
                ]);

                if ($newPrice !== null) {
                    $job->price = $newPrice;
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

            // Start with base query
            $query = CatalogItem::with([
                'largeMaterial.article',
                'smallMaterial.article'
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

                // Finally delete the job
                $job->delete();

                DB::commit();

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
}
