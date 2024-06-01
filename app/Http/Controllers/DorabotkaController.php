<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Dorabotka;
use App\Models\Priemnica;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DorabotkaController extends Controller
{
    public function index()
    {
        $dorabotki = Dorabotka::all();
        return Inertia::render('Production/Dorabotka/Index', $dorabotki);
    }

    public function create()
    {
        return Inertia::render('Production/Dorabotka/Create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'isMaterialized' => 'boolean|nullable',
            'material_id' => 'required|integer',
            'material_type' => 'required|string',
        ]);

        $dorabotka = Dorabotka::create($data);

        // Optionally handle the relationship with the material model
        $material = app($data['material_type'])->find($data['material_id']);
        $dorabotka->material()->save($material);

        return response()->json($dorabotka, 201); // Created status code
    }

    public function destroy(Dorabotka $dorabotka): \Illuminate\Http\RedirectResponse
    {
        $dorabotka->delete();

        return redirect()->route('dorabotka.index');
    }
}
