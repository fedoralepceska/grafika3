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
    public function syncAllJobs(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'selectedMaterial' => 'nullable|exists:large_format_materials,id',
                'selectedMaterialsSmall' => 'nullable|exists:small_material,id',
                'name' => 'nullable|string',
                'quantity' => 'required|integer|min:1',
                'copies' => 'required|integer|min:1',
                'jobs' => 'required|array',
                'jobs.*' => 'exists:jobs,id',
                'jobsWithActions' => 'required|array',
                'jobsWithActions.*.job_id' => 'required|exists:jobs,id',
                'jobsWithActions.*.actions' => 'required|array',
                'jobsWithActions.*.actions.*.action_id' => 'required|array',
                'jobsWithActions.*.actions.*.action_id.name' => 'required|string',
                'jobsWithActions.*.actions.*.quantity' => 'nullable|integer|min:0',
                'jobsWithActions.*.actions.*.status' => 'required|string',
            ]);

            // Extract inputs
            $selectedMaterial = $request->input('selectedMaterial');
            $selectedMaterialSmall = $request->input('selectedMaterialsSmall');
            $name = $request->input('name');
            $quantity = $request->input('quantity');
            $copies = $request->input('copies');
            $jobIds = $request->input('jobs');
            $jobsWithActions = $request->input('jobsWithActions');
            $selectedMachineCut = $request->input('selectedMachineCut');
            $selectedMachinePrint = $request->input('selectedMachinePrint');

            $small_material = null;
            $large_material = null;
            // Update Large Material
            if ($selectedMaterial) {
                $large_material = LargeFormatMaterial::find($selectedMaterial);
                if ($large_material->quantity - $copies < 0) {
                    throw new \Exception("Insufficient large material quantity.");
                }
            }
            // Update Small Material
            if ($selectedMaterialSmall) {
                $small_material = SmallMaterial::find($selectedMaterialSmall);
                if ($small_material->quantity - $copies < 0) {
                    throw new \Exception("Insufficient small material quantity.");
                }
            }

            // Update jobs with materials and machines
            Job::whereIn('id', $jobIds)->update([
                'large_material_id' => $selectedMaterial,
                'small_material_id' => $selectedMaterialSmall,
                'name' => $name,
                'machineCut' => $selectedMachineCut,
                'machinePrint' => $selectedMachinePrint,
                'quantity' => $quantity,
                'copies' => $copies,
            ]);

            foreach ($jobsWithActions as $jobWithActions) {
                $job = Job::findOrFail($jobWithActions['job_id']);
                $printAction = new JobAction([
                    'name' => $selectedMachinePrint,
                    'status' => 'Not started yet',
                ]);

                $job->actions()->sync([]);
                $actions = [$printAction];

                foreach ($jobWithActions['actions'] as $actionData) {
                    $actions[] = new JobAction([
                        'name' => $actionData['action_id']['name'],
                        'status' => $actionData['status'],
                        'quantity' => $actionData['quantity'] ?? 0
                    ]);
                }
                $job->actions()->saveMany($actions);
            }

            return response()->json(['message' => 'Jobs synced successfully']);
        } catch (\Exception $e) {
            \Log::error('Error syncing jobs:', ['error' => $e->getMessage()]);
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

        // Fetch the jobs with matching IDs
        $jobs = Job::whereIn('id', $jobIds)->with('actions')->get()->toArray();

        foreach ($jobs as &$job) {
            $job['totalPrice'] = $this->calculateTotalPrice($job); // Assuming calculateTotalPrice is a method in your controller
        }
        unset($job);

        return response()->json(['jobs' => $jobs]);
    }

    private function calculateTotalPrice($job)
    {
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
            $job['totalPrice'] = $price;
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
                'file' => 'mimetypes:image/tiff,application/pdf', // Ensure the file is an image
            ]);

            // Handle file upload and storage
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileExtension = $file->getClientOriginalExtension();
                $pdfPath = $file->store('public/uploads', ['disk' => 'local']); // Store the PDF file

                if ($fileExtension === 'pdf') {
                    $imagick = new Imagick();
                    $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
                    $imagick->readImage($file->getPathname() . '[0]'); // Read the first page of the PDF
                    $imagick->setImageFormat('jpg'); // Convert PDF to JPG (you can use other formats too)
                    $imageFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.jpg'; // Unique image file name
                    $imagick->writeImage(storage_path('app/public/uploads/' . $imageFilename)); // Save the image
                    $imagick->clear();

                    // Create a new job
                    $job = Job::find($id);
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

    public function update(Request $request, $id)
    {
        // Retrieve the job by its ID
        $job = Job::find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'quantity' => 'sometimes|required|numeric',
                'copies' => 'sometimes|required|numeric',
                'width' => 'sometimes|required|numeric',
                'height' => 'sometimes|required|numeric',
                'file' => 'sometimes|required',
                'status' => 'sometimes|required',
                'salePrice' => 'sometimes|required', // Keep the original salePrice validation
            ]);

            // Update the job with the validated data
            $job->update($validatedData);

            // Return success response with the updated job
            return response()->json([
                'message' => 'Job updated successfully',
                'job' => $job->fresh() // Get a fresh instance with updated data
            ]);
        } catch (\Exception $e) {
            \Log::error('Error updating job:', [
                'job_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Failed to update job',
                'error' => $e->getMessage()
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
            ])->where('is_for_sales', 1);

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
                    'actions' => collect($item->actions ?? [])->map(function($action) {
                        return [
                            'action_id' => [
                                'id' => $action['action_id']['id'] ?? $action['id'],
                                'name' => $action['action_id']['name'] ?? $action['name']
                            ],
                            'status' => $action['status'] ?? 'Not started yet',
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
