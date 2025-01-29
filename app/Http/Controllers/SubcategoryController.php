<?php

namespace App\Http\Controllers;

use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class SubcategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $subcategories = Subcategory::all();
        return response()->json($subcategories);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:subcategories,name'
        ]);

        $subcategory = Subcategory::create($request->all());
        return response()->json($subcategory, 201);
    }

    public function update(Request $request, Subcategory $subcategory): JsonResponse
    {
        $request->validate([
            'name' => 'required|string|unique:subcategories,name,' . $subcategory->id
        ]);

        $subcategory->update($request->all());
        return response()->json($subcategory);
    }

    public function destroy(Subcategory $subcategory): JsonResponse
    {
        $subcategory->delete();
        return response()->json(null, 204);
    }
}
