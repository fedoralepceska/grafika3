<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
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
            'file' => 'required|image', // Ensure the file is an image
        ]);

        // Handle file upload and storage
        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $filename = time() . '_' . $file->getClientOriginalName();
            $file->storeAs('public/uploads', $filename);
        } else {
            return response()->json(['message' => 'File not provided'], 400);
        }

        // Create a new job
        $job = new Job();
        $job->width = $request->input('width');
        $job->height = $request->input('height');
        $job->file = $filename; // Store the file name

        // Add fields and validation rules for other job properties

        // Save the job to the database
        $job->save();

        // Attach the job to the user or invoice as needed

        return response()->json(['message' => 'Job created successfully', 'job' => $job]);
    }

    public function syncAllJobs(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate the request and ensure the selected material is provided
        $request->validate([
            'selectedMaterial' => 'required|string',
            'selectedMaterialsSmall' => 'required|string',
            'selectedMachineCut' => 'required|string',
            'selectedMachinePrint' => 'required|string',
        ]);

        $selectedMaterial = $request->input('selectedMaterial');
        $selectedMaterialSmall = $request->input('selectedMaterialsSmall');
        $selectedMachineCut = $request->input('selectedMachineCut');
        $selectedMachinePrint = $request->input('selectedMachinePrint');
        $jobIds = $request->input('jobs');
        $jobsWithActions = $request->input('jobsWithActions');

        // Update all jobs with the selected material
        $updatedJobsCount = Job::whereIn('id', $jobIds)
            ->where('materials', '<>', $selectedMaterial)
            ->where('materialsSmall', '<>', $selectedMaterialSmall)
            ->where('machineCut', '<>', $selectedMachineCut)
            ->where('machinePrint', '<>', $selectedMachinePrint)
            ->update([
                'materials' => $selectedMaterial,
                'materialsSmall' => $selectedMaterialSmall,
                'machineCut' => $selectedMachineCut,
                'machinePrint' => $selectedMachinePrint,
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
            'message' => "Synced $updatedJobsCount jobs with material: $selectedMaterial",
            'updatedJobsCount' => $updatedJobsCount,
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
