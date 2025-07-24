<?php

namespace App\Http\Controllers;

use App\Models\Dorabotka;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefinementsController extends Controller
{
    public function index()
    {
        $dorabotki = Dorabotka::with(['smallMaterial.article', 'largeFormatMaterial.article'])->get();
        return Inertia::render('Refinements/Index', ['refinements' => $dorabotki]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'isMaterialRefinement' => 'boolean|nullable',
            'material_id' => 'nullable|integer',
            'material_type' => 'nullable',
        ]);

        $dorabotka = Dorabotka::create();

        // Determine material type based on format_type
        $materialType = $data['material_type'] === 'SmallMaterial' ? SmallMaterial::class : LargeFormatMaterial::class;

        $dorabotka->material_type = $materialType;
        if (isset($data['material_id'])) {
            $dorabotka->small_material_id = $data['material_type'] === 'SmallMaterial' ? $data['material_id'] : null;
            $dorabotka->large_material_id = $data['material_type'] === 'LargeFormatMaterial' ? $data['material_id'] : null;
            $dorabotka->isMaterialized = $data['isMaterialRefinement'];
        }
        $dorabotka->name = $data['name'];

        $dorabotka->save();

        return response()->json($dorabotka, 201); // Created status code
    }

    public function update(Request $request, $id)
    {
        // Validate incoming request data
        $validatedData = $request->validate([
            'name' => 'string|nullable',
            'isMaterialRefinement' => 'boolean|nullable',
            'material_type' => 'string|nullable',
            'material_id' => 'integer|nullable',
        ]);

        // Debug: Log the incoming data
        \Log::info('Refinement update request:', [
            'id' => $id,
            'validated_data' => $validatedData,
            'raw_request' => $request->all()
        ]);

        // Find the refinement record
        $dorabotka = Dorabotka::find($id);

        // Update the name if provided
        if (isset($validatedData['name'])) {
            $dorabotka->name = $validatedData['name'];
        }

        // Update the isMaterialized field if provided
        if (isset($validatedData['isMaterialRefinement'])) {
            $dorabotka->isMaterialized = $validatedData['isMaterialRefinement'] ? 1 : null;
        }

        // Update material relationships if material_id is provided
        if (isset($validatedData['material_id']) && isset($validatedData['material_type']) && $validatedData['material_type'] !== null) {
            // Debug: Log material update logic
            \Log::info('Updating material relationships:', [
                'material_type' => $validatedData['material_type'],
                'material_id' => $validatedData['material_id']
            ]);
            
            // Clear existing material relationships first
            $dorabotka->small_material_id = null;
            $dorabotka->large_material_id = null;
            
            // Set the appropriate material ID based on type
            if ($validatedData['material_type'] === 'SmallMaterial') {
                $dorabotka->small_material_id = $validatedData['material_id'];
                $dorabotka->material_type = SmallMaterial::class;
                \Log::info('Set small material:', ['id' => $validatedData['material_id']]);
            } elseif ($validatedData['material_type'] === 'LargeFormatMaterial') {
                $dorabotka->large_material_id = $validatedData['material_id'];
                $dorabotka->material_type = LargeFormatMaterial::class;
                \Log::info('Set large material:', ['id' => $validatedData['material_id']]);
            }
        } elseif (isset($validatedData['material_type']) && $validatedData['material_type'] === null) {
            // If material_type is explicitly set to null, clear all material relationships
            \Log::info('Clearing material relationships');
            $dorabotka->small_material_id = null;
            $dorabotka->large_material_id = null;
            $dorabotka->material_type = null;
        }

        // Save the changes
        $dorabotka->save();

        // Return success response
        return response()->json(['message' => 'Refinement updated successfully', 'refinement' => $dorabotka]);
    }

    public function destroy($id)
    {
        try {
            $dorabotka = Dorabotka::find($id);
            
            if (!$dorabotka) {
                return response()->json(['error' => 'Refinement not found'], 404);
            }
            
            $dorabotka->delete();
            return response()->json(['message' => 'Refinement deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete refinement'], 500);
        }
    }

    public function getRefinements()
    {
        return Dorabotka::with(['smallMaterial.article', 'largeFormatMaterial.article'])->get();
    }
}
