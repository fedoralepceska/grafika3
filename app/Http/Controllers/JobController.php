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
                $imageFilename = time() . '_' . pathinfo($pdfPath, PATHINFO_FILENAME) . '.jpg'; // Unique image file name
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
                $imageFilename = time() . '_' . pathinfo($pdfPath, PATHINFO_FILENAME) . '.jpg'; // Unique image file name
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
            'materialsSmall' => $selectedMaterialSmall,
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
        $jobs = Job::whereIn('id', $jobIds)->get();

        return response()->json(['jobs' => $jobs]);
    }

    public function update(Request $request, $id)
    {
        // Retrieve the job by its ID
        $job = Job::find($id);

        if (!$job) {
            return response()->json(['message' => 'Job not found'], 404);
        }

        // Validate and update the width and height
        $this->validate($request, [
            'width' => 'required',
            'height' => 'required',
        ]);

        $job->update([
            'width' => $request->input('width'),
            'height' => $request->input('height'),
        ]);

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
}
