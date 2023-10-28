<?php

namespace App\Http\Controllers;

use App\Models\SmallFormatMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class SmallFormatMaterialController extends Controller
{
    public function index()
    {
        $materials = SmallFormatMaterial::all();
        return Inertia::render('SmallFormatMaterial/Index', [
            'materials' => $materials,
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
            'price_per_unit' => 'required|numeric',
        ]);

        SmallFormatMaterial::create($validatedData);

        return redirect()->route('materials.index');
    }

    public function edit(SmallFormatMaterial $material)
    {
        return Inertia::render('SmallFormatMaterial/Edit', [
            'material' => $material,
        ]);
    }

    public function update(Request $request, SmallFormatMaterial $material)
    {
        $validatedData = $request->validate([
            'quantity' => 'required|integer',
            'price_per_unit' => 'required|numeric',
        ]);

        $material->update($validatedData);

        return redirect()->route('materials.index');
    }

    public function destroy(SmallFormatMaterial $material)
    {
        $material->delete();

        return redirect()->route('materials.index');
    }
}
