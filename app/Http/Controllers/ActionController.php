<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class ActionController extends Controller
{
    public function index($actionId)
    {
        return Inertia::render('Production/ActionPage', [
        'actionId' => $actionId,
        ]);
    }
    public function update(Request $request, $id)
    {
        // Retrieve the job by its ID
        $action = JobAction::where('id', $id)->first();

        if (!$action) {
            return response()->json(['message' => 'Action not found'], 404);
        }

        // Validate and update the width and height
        $validatedData = $request->validate([
            'status' => 'sometimes|required',
        ]);

        $jobAction = DB::table('job_job_action')
            ->where('job_job_action.job_action_id', $action->id);

        $jobAction->update($request->only([
            'status'
        ]));

        // Update the job with only the validated data that's present in the request
        $action->update($request->only([
            'status'
        ]));

        return response()->json(['message' => 'Action updated successfully']);
    }
}
