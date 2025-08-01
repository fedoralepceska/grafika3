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
        $unitFilter = $request->query('unit_filter', '');
        $quantityMin = $request->query('quantity_min', '');
        $quantityMax = $request->query('quantity_max', '');

        $largeMaterialsQuery = LargeFormatMaterial::query()
            ->with(['article'])
            ->when($searchQuery, function ($query, $searchQuery) {
                $query->where('name', 'like', "%{$searchQuery}%");
            })
            ->when($unitFilter, function ($query, $unitFilter) {
                $query->whereHas('article', function ($q) use ($unitFilter) {
                    switch ($unitFilter) {
                        case 'meters':
                            $q->where('in_meters', 1);
                            break;
                        case 'kilograms':
                            $q->where('in_kilograms', 1);
                            break;
                        case 'pieces':
                            $q->where('in_pieces', 1);
                            break;
                        case 'square_meters':
                            $q->where('in_square_meters', 1);
                            break;
                    }
                });
            })
            ->when($quantityMin, function ($query, $quantityMin) {
                $query->where('quantity', '>=', $quantityMin);
            })
            ->when($quantityMax, function ($query, $quantityMax) {
                $query->where('quantity', '<=', $quantityMax);
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

    public function getAllMaterials() {
        return LargeFormatMaterial::all();
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

    public function largeDropdown()
    {
        try {
            // Get all large categories (type = 'large', not soft deleted)
            $categories = \App\Models\ArticleCategory::with(['articles' => function($q) {
                $q->whereHas('largeFormatMaterial');
            }, 'articles.largeFormatMaterial'])
                ->where('type', 'large')
                ->whereNull('deleted_at')
                ->get();

            // Get all large materials not in any category
            $categoryArticleIds = $categories->flatMap(function($cat) {
                return $cat->articles->pluck('id');
            })->unique();
            
            $individualMaterials = \App\Models\LargeFormatMaterial::with('article')
                ->whereHas('article')
                ->whereNotIn('article_id', $categoryArticleIds)
                ->get();

            // Format categories for dropdown
            $categoryOptions = $categories->map(function($cat) {
                $materials = $cat->articles->map(function($article) {
                    return $article->largeFormatMaterial;
                })->filter();
                $totalStock = $materials->sum('quantity');
                return [
                    'id' => 'cat_' . $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon,
                    'type' => 'category',
                    'category_type' => 'large',
                    'disabled' => $totalStock <= 0,
                    'materials' => $materials->map(function($mat) {
                        return [
                            'id' => $mat->id,
                            'name' => $mat->name,
                            'quantity' => $mat->quantity,
                            'created_at' => $mat->created_at,
                        ];
                    })->sortBy('created_at')->values()->all(),
                ];
            });

            // Format individual materials for dropdown
            $materialOptions = $individualMaterials->map(function($mat) {
                return [
                    'id' => $mat->id,
                    'name' => $mat->name,
                    'type' => 'material',
                    'quantity' => $mat->quantity,
                    'disabled' => $mat->quantity <= 0,
                ];
            });

            // Merge and return
            $allOptions = array_merge($categoryOptions->toArray(), $materialOptions->toArray());
            return response()->json($allOptions);
        } catch (\Exception $e) {
            \Log::error('Error in largeDropdown: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load materials'], 500);
        }
    }
}
