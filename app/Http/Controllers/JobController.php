<?php

namespace App\Http\Controllers;

use App\Models\Job;
use Illuminate\Http\Request;

class JobController extends Controller
{
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
            'selectedMaterial' => 'required|string', // Adjust validation rules as needed
        ]);

        $selectedMaterial = $request->input('selectedMaterial');
        $jobIds = $request->input('jobs');

        // Update all jobs with the selected material
        $updatedJobsCount = Job::whereIn('id', $jobIds)->where('materials', '<>', $selectedMaterial)->update(['materials' => $selectedMaterial]);

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
