<?php

namespace App\Http\Controllers;

use App\Models\SmallMaterial;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\LargeFormatMaterial;

class LargeFormatMaterialController extends Controller
{
    public function index()
    {
        $materials = LargeFormatMaterial::all();
        return Inertia::render('LargeFormatMaterial/Index', [
            'materials' => $materials,
        ]);
    }

    public function getLargeMaterials(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $searchQuery = $request->query('search_query', '');

        $largeMaterialsQuery = LargeFormatMaterial::query()
            ->with(['article'])
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            });

        $largeMaterials = $largeMaterialsQuery->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json($largeMaterials);
        }

        return Inertia::render('Materials/LargeMaterials', [
            'largeMaterials' => $largeMaterials,
            'perPage' => $perPage,
        ]);
    }
    public function create()
    {
        return Inertia::render('LargeFormatMaterial/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
            'width' => 'required|numeric',
            'height' => 'required|numeric',
            'quantity' => 'required|integer',
            'price_per_unit' => 'required|numeric',
        ]);

        LargeFormatMaterial::create($validatedData);

        return redirect()->route('materials.index');
    }

    public function edit(LargeFormatMaterial $material)
    {
        return Inertia::render('LargeFormatMaterial/Edit', [
            'material' => $material,
        ]);
    }

    public function update(Request $request, LargeFormatMaterial $material)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer',
            'price_per_unit' => 'required|numeric',
        ]);

        $material->update($validatedData);

        return redirect()->route('materials.index');
    }

    public function destroy(LargeFormatMaterial $material)
    {
        $material->delete();

        return redirect()->route('materials.index');
    }
    public function generateLargeMaterialsPdf(Request $request)
    {
        $searchQuery = $request->query('search_query', '');
        $perPage = $request->query('per_page', 20);

        $materials = LargeFormatMaterial::query()
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->take($perPage)
            ->get();

        $pdf = PDF::loadView('materials.large_pdf', compact('materials'));

        return $pdf->stream('Large_Materials.pdf');
    }

    public function generateAllLargeMaterialsPdf()
    {
        $materials = LargeFormatMaterial::orderBy('created_at', 'desc')->get();

        $pdf = PDF::loadView('materials.large_pdf', compact('materials'));

        return $pdf->stream('All_Large_Materials.pdf');
    }
}
