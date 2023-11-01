<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
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
    public function store(Request $request)
    {
        // Validate the request data
        $this->validate($request, [
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'file' => 'required|mimes:pdf', // Ensure the file is an image
        ]);

        // Handle file upload and storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $pdfPath = $file->store('public/uploads', ['disk' => 'local']); // Store the PDF file

            $imagick = new Imagick();
            $imagick->setOption('gs', "C:\Program Files\gs\gs10.02.0");
            $imagick->readImage($file->getPathname().'[0]'); // Read the first page of the PDF
            $imagick->setImageFormat('jpg'); // Convert PDF to JPG (you can use other formats too)
            $imageFilename = time() . '_' . pathinfo($pdfPath, PATHINFO_FILENAME) . '.jpg'; // Unique image file name
            $imagick->writeImage(storage_path('app/public/uploads/' . $imageFilename)); // Save the image
            $imagick->clear();

            // Create a new job
            $job = new Job();
            $job->file = $imageFilename; // Store the image file name
            $job->width = $request -> input('width');
            $job->height = $request -> input('height');

            // Set other job properties if needed

            $job->save(); // Save the job to the database

            // Attach the job to the user or invoice as needed

            return response()->json(['message' => 'Job created successfully', 'job' => $job]);
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
        $shipping = $request->input('shipping');
        $jobIds = $request->input('jobs');
        $jobsWithActions = $request->input('jobsWithActions');

        // Update all jobs with the selected material
        Job::whereIn('id', $jobIds)->update([
            'materials' => $selectedMaterial,
            'materialsSmall' => $selectedMaterialSmall,
            'machineCut' => $selectedMachineCut,
            'machinePrint' => $selectedMachinePrint,
            'quantity' => $quantity,
            'copies' => $copies,
            'shippingInfo' => $shipping
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

    public function getJobsByIds(Request $request)
    {
        // Retrieve the array of job IDs from the request
        $jobIds = $request->input('jobs', []);

        // Fetch the jobs with matching IDs
        $jobs = Job::whereIn('id', $jobIds)->get();

        return response()->json(['jobs' => $jobs]);
    }
}
