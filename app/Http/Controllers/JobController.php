<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Events\InvoiceCreated;
use App\Events\JobEnded;
use App\Events\JobStarted;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
    public function show($id)
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
            'materials' => $selectedMaterial,
            'small_material_id' => $selectedMaterialSmall,
            'machineCut' => $selectedMachineCut,
            'machinePrint' => $selectedMachinePrint,
            'quantity' => $quantity,
            'copies' => $copies,
        ]);
        foreach ($jobsWithActions as $jobWithActions) {
            $job = Job::findOrFail($jobWithActions['job_id']);
            $job->actions()->sync([]);

            $actions = [];
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

        return response()->json(['jobs' => $jobs]);
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
            $job->actions = Job::find($job->job_id)->with('actions')->get()->toArray();
        }

        // Now, let's retrieve invoices associated with these jobs
        $invoiceIds = DB::table('invoice_job')
            ->whereIn('job_id', $jobs->pluck('job_id'))
            ->pluck('invoice_id');
        // Fetch the invoices based on the retrieved invoice IDs
        $invoices = DB::table('invoices')
            ->whereIn('id', $invoiceIds)
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
            ->whereIn('id', $invoiceIds)
            ->orderBy('start_date', 'asc')
            ->where('status', '!=', 'Completed')
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
            $dpi = $job->file->dpi ?? 96;

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
        $counts = [];

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

            // Iterate through actions
            foreach ($actions as $action) {
                // Count the 'In Progress' actions
                if ($action->status === 'In Progress') {
                    $inProgressCount++;
                }

                // Count the 'Not started yet' actions
                if ($action->status === 'Not started yet') {
                    $notStartedYetCount++;
                }

                // Count the 'On Hold' actions
                $invoiceOnHold = DB::table('invoice_job')
                    ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                    ->join('job_job_action', 'job_job_action.job_id', '=', 'invoice_job.job_id')
                    ->where('job_job_action.job_action_id', $action->id) // Include the join with job_job_action
                    ->where('invoices.onHold', true)
                    ->count();

                if ($invoiceOnHold > 0) {
                    $onHoldCount++;
                }
            }

            if ($inProgressCount > 0 || $notStartedYetCount > 0 || $onHoldCount > 0) {
                // Create an entry for the current machineCut
                if ($job->machineCut !== null) {
                    if (!isset($counts[$job->machineCut])) {
                        $counts[$job->machineCut] = [
                            'name' => $job->machineCut,
                            'total' => 0,
                            'secondaryCount' => 0,
                            'onHoldCount' => 0,
                        ];
                    }

                    // Add the counts to the array
                    $counts[$job->machineCut]['total'] += $inProgressCount;
                    $counts[$job->machineCut]['secondaryCount'] += $notStartedYetCount;
                    $counts[$job->machineCut]['onHoldCount'] += $onHoldCount;
                }

                // Create an entry for the current machinePrint
                if ($job->machinePrint !== null) {
                    if (!isset($counts[$job->machinePrint])) {
                        $counts[$job->machinePrint] = [
                            'name' => $job->machinePrint,
                            'total' => 0,
                            'secondaryCount' => 0,
                            'onHoldCount' => 0,
                        ];
                    }

                    // Add the counts to the array
                    $counts[$job->machinePrint]['total'] += $inProgressCount;
                    $counts[$job->machinePrint]['secondaryCount'] += $notStartedYetCount;
                    $counts[$job->machinePrint]['onHoldCount'] += $onHoldCount;
                }
            }
        }

        // Convert associative array to a simple array
        $result = array_values($counts);

        // Return the counts as a JSON response
        return response()->json($result);
    }


    public function jobActionStatusCounts()
    {
        // Initialize an empty array to hold the final counts
        $counts = [];

        // Get the names of all job actions
        $actionNames = DB::table('job_actions')->pluck('name')->unique();

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

            // Add the counts to the array
            if ($total > 0 || $secondaryCount > 0) {
                $counts[] = [
                    'name' => $name,
                    'total' => $total,
                    'secondaryCount' => $secondaryCount,
                    'onHoldCount' => $onHoldCount
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

    public function fireStartJobEvent(Request $request) {
        $jobData = $request->input('job');
        $invoiceData = $request->input('invoice');

        // Create job and invoice instances
        $job = new Job($jobData);
        $invoice = new Invoice($invoiceData);

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
