<?php

namespace App\Http\Controllers;

use App\Enums\JobAction;
use Illuminate\Http\Request;
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

        // Update the job with only the validated data that's present in the request
        $action->update($request->only([
            'status'
        ]));

        return response()->json(['message' => 'Action updated successfully']);
    }
}
