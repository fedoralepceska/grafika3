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
            'material_id' => 'required|integer',
            'material_type' => 'required',
        ]);

        $dorabotka = Dorabotka::create();

        // Determine material type based on format_type
        $materialType = $data['material_type'] === 'SmallMaterial' ? SmallMaterial::class : LargeFormatMaterial::class;

        $dorabotka->material_type = $materialType;
        $dorabotka->small_material_id = $data['material_type'] === 'SmallMaterial' ? $data['material_id'] : null;
        $dorabotka->large_material_id = $data['material_type'] === 'LargeFormatMaterial' ? $data['material_id'] : null;
        $dorabotka->isMaterialized = $data['isMaterialRefinement'];
        $dorabotka->name = $data['name'];

        $dorabotka->save();

        return response()->json($dorabotka, 201); // Created status code
    }

    public function destroy(Dorabotka $dorabotka): \Illuminate\Http\RedirectResponse
    {
        $dorabotka->delete();

        return redirect()->route('dorabotka.index');
    }

    public function getRefinements()
    {
        return Dorabotka::with(['smallMaterial.article', 'largeFormatMaterial.article'])->get();
    }
}
