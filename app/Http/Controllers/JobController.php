<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Events\InvoiceCreated;
use App\Events\JobEnded;
use App\Events\JobStarted;
use App\Models\Invoice;
use App\Models\Job;
use App\Models\SmallMaterial;
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

    /**
     * @throws \ImagickException
     * @throws ValidationException
     */
    public function store(Request $request)
    {
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
            }

            elseif ($fileExtension === 'pdf') {
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
    }

    public function syncAllJobs(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the request and ensure the selected material is provided
        $request->validate([
            'selectedMaterial',
            'selectedMaterialsSmall',
            'quantity' => 'required',
            'copies' => 'required',
            'selectedMachineCut' => 'required|string',
            'selectedMachinePrint' => 'required|string',
        ]);

        $selectedMaterial = $request->input('selectedMaterial');
        $selectedMaterialSmall = $request->input('selectedMaterialsSmall');
        $selectedMachineCut = $request->input('selectedMachineCut');
        $selectedMachinePrint = $request->input('selectedMachinePrint');
        $quantity = $request->input('quantity');
        $copies = $request->input('copies');
        $jobIds = $request->input('jobs');
        $jobsWithActions = $request->input('jobsWithActions');

        // Update all jobs with the selected material
        Job::whereIn('id', $jobIds)->update([
            'large_material_id' => $selectedMaterial,
            'small_material_id' => $selectedMaterialSmall,
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
                    'name' => $actionData['action_id'],
                    'status' => $actionData['status'],
                ]);
            }

            $job->actions()->saveMany($actions);
        }

        return response()->json([
            'message' => "Synced jobs with material: $selectedMaterial",
        ]);
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
        // Check if the job has a small material
        if (!isset($job['small_material_id'])) {
            return 0; // Return a default value if no small material is set
        }

        // Fetch the small material
        $smallMaterial = SmallMaterial::with('smallFormatMaterial')->find($job['small_material_id']);

        // If small material not found or format material not set, return 0
        if (!$smallMaterial || !$smallMaterial->smallFormatMaterial) {
            return 0;
        }

        // Extract required data
        $formatQuantity = $smallMaterial->smallFormatMaterial->quantity ?? 0;
        $formatPrice = intval($smallMaterial->smallFormatMaterial->price_per_unit ?? 0);
        $baseCopies = $job['copies'] ?? 0;
        $materialQuantity = $smallMaterial->quantity ?? 0;

        $remainingQuantity = $formatQuantity - ($baseCopies / $materialQuantity); // total - used

        $smallMaterial->smallFormatMaterial->update(['quantity' => $remainingQuantity]);

        // Calculate total price
        $totalPrice = ceil($baseCopies / $materialQuantity) * $formatPrice;

        return $totalPrice;
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



    public function update(Request $request, $id)
    {
        // Retrieve the job by its ID
        $job = Job::find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Validate and update the width and height
        $validatedData = $request->validate([
            'width' => 'sometimes|required|numeric',
            'height' => 'sometimes|required|numeric',
            'quantity' => 'sometimes|required|numeric',
            'status' => 'sometimes|required',

        ]);

        // Update the job with only the validated data that's present in the request
        $job->update($request->only([
            'width',
            'height',
            'quantity',
            'status'
        ]));

        return response()->json(['message' => 'Job updated successfully']);
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

    public function jobMachinesCounts() {
        // Initialize an empty array to hold the final counts
        $machineCutCounts = [];
        $machinePrintCounts = [];

        // Fetch jobs with specified statuses
        $jobs = DB::table('jobs')
            ->whereIn('status', ['Not started yet', 'In progress'])
            ->get();

        // Iterate through jobs
        foreach ($jobs as $job) {
            // Fetch actions associated with the current job
            $actions = DB::table('job_job_action')
                ->join('job_actions', 'job_job_action.job_action_id', '=', 'job_actions.id')
                ->where('job_job_action.job_id', $job->id)
                ->get();

            // Counters for 'In Progress' and 'Not started yet' statuses
            $inProgressCount = 0;
            $notStartedYetCount = 0;
            $onHoldCount = 0; // Initialize onHoldCount
            $onRushCount = 0; // Counter for onRush

            // Iterate through actions
            foreach ($actions as $action) {
                switch ($action->status) {
                    case 'In progress':
                        $inProgressCount++;
                        break;
                    case 'Not started yet':
                        $notStartedYetCount++;
                        break;
                }


                // Count the 'Rush' actions and the 'On Hold' actions
                $invoiceStatusCounts = DB::table('invoice_job')
                    ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                    ->join('job_job_action', 'job_job_action.job_id', '=', 'invoice_job.job_id')
                    ->where('job_job_action.job_action_id', $action->id)
                    ->groupBy('job_job_action.job_action_id')  // Group by job action ID
                    ->select(DB::raw('COUNT(*) AS totalCount'),
                        DB::raw('SUM(CASE WHEN invoices.onHold THEN 1 ELSE 0 END) AS onHoldCount'),
                        DB::raw('SUM(CASE WHEN invoices.rush THEN 1 ELSE 0 END) AS onRushCount'))
                    ->first();

                if ($invoiceStatusCounts) {
                    $onHoldCount = $invoiceStatusCounts->onHoldCount;
                    $onRushCount = $invoiceStatusCounts->onRushCount;
                }
            }

            if ($inProgressCount > 0 || $notStartedYetCount > 0 || $onHoldCount > 0 || $onRushCount > 0) {
                // Create an entry for the current machineCut
                if ($job->machineCut !== null) {
                    $machineCutKey = $job->machineCut;
                    if (!isset($machineCutCounts[$machineCutKey])) {
                        $machineCutCounts[$machineCutKey] = [
                            'name' => $machineCutKey,
                            'total' => 0,
                            'secondaryCount' => 0,
                            'onHoldCount' => 0,
                            'onRushCount' => 0,
                        ];
                    }

                    // Add the counts to the array
                    $machineCutCounts[$machineCutKey]['total'] += $inProgressCount;
                    $machineCutCounts[$machineCutKey]['secondaryCount'] += $notStartedYetCount;
                    $machineCutCounts[$machineCutKey]['onHoldCount'] += $onHoldCount;
                    $machineCutCounts[$machineCutKey]['onRushCount'] += $onRushCount;

                }

                // Create an entry for the current machinePrint
                if ($job->machinePrint !== null) {
                    $machinePrintKey = $job->machinePrint;
                    if (!isset($machinePrintCounts[$machinePrintKey])) {
                        $machinePrintCounts[$machinePrintKey] = [
                            'name' => $machinePrintKey,
                            'total' => 0,
                            'secondaryCount' => 0,
                            'onHoldCount' => 0,
                            'onRushCount' => 0,
                        ];
                    }

                    // Add the counts to the array
                    $machinePrintCounts[$machinePrintKey]['total'] += $inProgressCount;
                    $machinePrintCounts[$machinePrintKey]['secondaryCount'] += $notStartedYetCount;
                    $machinePrintCounts[$machinePrintKey]['onHoldCount'] += $onHoldCount;
                    $machinePrintCounts[$machinePrintKey]['onRushCount'] += $onRushCount;

                }
            }
        }

        // Convert associative arrays to simple arrays
        $resultMachineCut = array_values($machineCutCounts);
        $resultMachinePrint = array_values($machinePrintCounts);

        // Return the counts as a JSON response
        return response()->json([
            'machineCutCounts' => $resultMachineCut,
            'machinePrintCounts' => $resultMachinePrint,
        ]);
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


    public function production()
    {
        return Inertia::render('Production/Dashboard');
    }
    public function machine()
    {
        return Inertia::render('Production/MachineDashboard');
    }

    public function fireStartJobEvent(Request $request) {
        $jobData = $request->input('job');
        $invoiceData = $request->input('invoice');

        // Create job and invoice instances
        $job = new Job($jobData);
        $invoice = new Invoice($invoiceData);

        $job->started_by = auth()->id();

        $job->save();

        // Dispatch the JobStarted event with both job and invoice
        event(new JobStarted($job, $invoice));
    }

    public function fireEndJobEvent(Request $request) {
        $jobData = $request->input('job');
        $invoiceData = $request->input('invoice');

        // Create job and invoice instances
        $job = new Job($jobData);
        $invoice = new Invoice($invoiceData);

        // Dispatch the JobEnded event with both job and invoice
        event(new JobEnded($job, $invoice));
    }

}
