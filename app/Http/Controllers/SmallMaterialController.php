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
        $unitFilter = $request->query('unit_filter', '');
        $quantityMin = $request->query('quantity_min', '');
        $quantityMax = $request->query('quantity_max', '');

        $smallMaterialsQuery = SmallMaterial::query()
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

    public function smallDropdown()
    {
        try {
            // Get all small categories (type = 'small', not soft deleted)
            $categories = \App\Models\ArticleCategory::with(['articles' => function($q) {
                $q->whereHas('smallMaterial');
            }, 'articles.smallMaterial'])
                ->where('type', 'small')
                ->whereNull('deleted_at')
                ->get();

            // Get all small materials not in any category
            $categoryArticleIds = $categories->flatMap(function($cat) {
                return $cat->articles->pluck('id');
            })->unique();
            
            $individualMaterials = \App\Models\SmallMaterial::with('article')
                ->whereHas('article')
                ->whereNotIn('article_id', $categoryArticleIds)
                ->get();

            // Format categories for dropdown
            $categoryOptions = $categories->map(function($cat) {
                $materials = $cat->articles->map(function($article) {
                    return $article->smallMaterial;
                })->filter();
                $totalStock = $materials->sum('quantity');
                return [
                    'id' => 'cat_' . $cat->id,
                    'name' => $cat->name,
                    'icon' => $cat->icon,
                    'type' => 'category',
                    'category_type' => 'small',
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
            \Log::error('Error in smallDropdown: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to load materials'], 500);
        }
    }
}
