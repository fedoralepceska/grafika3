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
        $validatedData = $request->validate([
            'name' => 'string|nullable',
            'isMaterialRefinement' => 'boolean|nullable',
            'material_type' => 'string|nullable',
            'material_id' => 'integer|nullable',
        ]);

        $dorabotka = Dorabotka::find($id);

        if (isset($validatedData['name'])) {
            $dorabotka->name = $validatedData['name'];
        }

        if (isset($validatedData['isMaterialRefinement'])) {
            $dorabotka->isMaterialized = $validatedData['isMaterialRefinement'] ? 1 : null;
        }

        if (isset($validatedData['material_id']) && isset($validatedData['material_type']) && $validatedData['material_type'] !== null) {
            
            $dorabotka->small_material_id = null;
            $dorabotka->large_material_id = null;
            
            if ($validatedData['material_type'] === 'SmallMaterial') {
                $dorabotka->small_material_id = $validatedData['material_id'];
                $dorabotka->material_type = SmallMaterial::class;
            } elseif ($validatedData['material_type'] === 'LargeFormatMaterial') {
                $dorabotka->large_material_id = $validatedData['material_id'];
                $dorabotka->material_type = LargeFormatMaterial::class;
            }
        } elseif (isset($validatedData['material_type']) && $validatedData['material_type'] === null) {
            $dorabotka->small_material_id = null;
            $dorabotka->large_material_id = null;
            $dorabotka->material_type = null;
        }

        $dorabotka->save();

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
