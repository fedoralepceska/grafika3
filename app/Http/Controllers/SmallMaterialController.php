<?php


namespace App\Http\Controllers;

use App\Models\SmallMaterial;
use Barryvdh\DomPDF\Facade\Pdf;
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
        $searchQuery = $request->query('search_query', '');

        $smallMaterialsQuery = SmallMaterial::query()
            ->with(['article'])
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            });

        $smallMaterials = $smallMaterialsQuery->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json($smallMaterials);
        }

        return Inertia::render('Materials/SmallMaterials', [
            'smallMaterials' => $smallMaterials,
            'perPage' => $perPage,
        ]);
    }

    public function getAllMaterials() {
        return SmallMaterial::all();
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
    public function generateSmallMaterialsPdf(Request $request)
    {
        $searchQuery = $request->query('search_query', '');
        $perPage = $request->query('per_page', 20);

        $materials = SmallMaterial::query()
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->take($perPage)
            ->get();

        $pdf = PDF::loadView('materials.small_pdf', compact('materials'));

        return $pdf->stream('Small_Materials.pdf');
    }

    public function generateAllSmallMaterialsPdf()
    {
        $materials = SmallMaterial::orderBy('created_at', 'desc')->get();

        $pdf = PDF::loadView('materials.small_pdf', compact('materials'));

        return $pdf->stream('All_Small_Materials.pdf');
    }
}
