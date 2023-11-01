<?php

namespace App\Http\Controllers;

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

    public function create()
    {
        return Inertia::render('LargeFormatMaterial/Create');
    }

    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string',
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
}
