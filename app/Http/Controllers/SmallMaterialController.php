<?php


namespace App\Http\Controllers;

use App\Models\SmallMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmallMaterialController extends Controller
{
    public function index()
    {
        $materials = SmallMaterial::all();
        return Inertia::render('SmallMaterial/Index', [
            'materials' => $materials,
        ]);
    }

    public function getSmallMaterials(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $smallMaterialsQuery = SmallMaterial::query()->with(['article']);


        $smallMaterials = $smallMaterialsQuery->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json($smallMaterials);
        }

        return Inertia::render('Materials/SmallMaterials', [
            'smallMaterials' => $smallMaterials,
            'perPage' => $perPage,
        ]);
    }

    public function create()
    {
        return Inertia::render('SmallFormatMaterial/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'quantity' => 'required|integer',
            'width' => 'required',
            'height' => 'required',
            'small_format_material_id' => 'required'
        ]);

        SmallMaterial::create($validatedData);
    }

    public function edit(SmallMaterial $material)
    {
        return Inertia::render('SmallMaterial/Edit', [
            'material' => $material,
        ]);
    }

    public function update(Request $request, SmallMaterial $material)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer',
        ]);

        $material->update($validatedData);
    }

    public function destroy(SmallMaterial $material)
    {
        $material->delete();

    }
}
