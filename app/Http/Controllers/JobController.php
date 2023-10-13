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
}
