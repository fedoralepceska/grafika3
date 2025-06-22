<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Enums\MachineCut as MachineCutEnum;
use App\Enums\MachinePrint as MachinePrintEnum;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use App\Services\TemplateStorageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use ReflectionClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CatalogItemController extends Controller

{
    protected $templateStorageService;

    public function __construct(TemplateStorageService $templateStorageService)
    {
        $this->templateStorageService = $templateStorageService;
    }

    public function fetchAllForOffer()
    {
        $catalogItems = CatalogItem::with(['largeMaterial', 'smallMaterial'])
            ->where('is_for_offer', true)
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'file' => $item->file,
                    'template_file' => $item->template_file ? $this->templateStorageService->getSignedTemplateUrl($item->template_file) : null,
                    'large_material' => $item->largeMaterial ? [
                        'id' => $item->largeMaterial->id,
                        'name' => $item->largeMaterial->name
                    ] : null,
                    'small_material' => $item->smallMaterial ? [
                        'id' => $item->smallMaterial->id,
                        'name' => $item->smallMaterial->name
                    ] : null,
                ];
            });
        return response()->json($catalogItems);
    }

    public function index(Request $request)
    {
        try {
            // Get pagination parameters with defaults
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $searchTerm = $request->input('search', '');
            $category = $request->input('category', '');
            $subcategoryId = $request->input('subcategory_id');

            // Start with base query
            $query = CatalogItem::with([
                'largeMaterial',
                'smallMaterial',
                'subcategory'
            ]);

            // Add search functionality
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhereHas('subcategory', function($q) use ($searchTerm) {
                          $q->where('name', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // Add category filter
            if (!empty($category)) {
                $query->where('category', $category);
            }

            // Add subcategory filter
            if (!empty($subcategoryId)) {
                $query->where('subcategory_id', $subcategoryId);
            }

            // Paginate the results
            $catalogItems = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the paginated items
            $transformedItems = $catalogItems->getCollection()->map(function($item) {
                // Handle material display for both individual materials and categories
                $materialDisplay = 'N/A';
                
                if ($item->large_material_category_id) {
                    // This is a category
                    $category = \App\Models\ArticleCategory::find($item->large_material_category_id);
                    $materialDisplay = $category ? "[Category] {$category->name} (Large)" : 'N/A';
                } elseif ($item->large_material_id) {
                    // This is an individual material
                    $largeMaterialName = $item->largeMaterial ? $item->largeMaterial->name : null;
                    $materialDisplay = $largeMaterialName ? "{$largeMaterialName} (Large)" : 'N/A';
                } elseif ($item->small_material_category_id) {
                    // This is a category
                    $category = \App\Models\ArticleCategory::find($item->small_material_category_id);
                    $materialDisplay = $category ? "[Category] {$category->name} (Small)" : 'N/A';
                } elseif ($item->small_material_id) {
                    // This is an individual material
                    $smallMaterialName = $item->smallMaterial ? $item->smallMaterial->name : null;
                    $materialDisplay = $smallMaterialName ? "{$smallMaterialName} (Small)" : 'N/A';
                }

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'machinePrint' => $item->machinePrint,
                    'machineCut' => $item->machineCut,
                    'material' => $materialDisplay,
                    'quantity' => $item->quantity,
                    'copies' => $item->copies,
                    'file' => $item->file,
                    'category' => $item->category,
                    'is_for_offer' => (bool)$item->is_for_offer,
                    'is_for_sales' => (bool)$item->is_for_sales,
                    'large_material_id' => $item->large_material_category_id ? 'cat_' . $item->large_material_category_id : ($item->large_material_id ? (string)$item->large_material_id : null),
                    'small_material_id' => $item->small_material_category_id ? 'cat_' . $item->small_material_category_id : ($item->small_material_id ? (string)$item->small_material_id : null),
                    'price' => $item->price,
                    'template_file' => $item->template_file ? $this->templateStorageService->getSignedTemplateUrl($item->template_file) : null,
                    'subcategory' => $item->subcategory ? [
                        'id' => $item->subcategory->id,
                        'name' => $item->subcategory->name
                    ] : null,
                    'articles' => $item->articles->map(function($article) {
                        return [
                            'id' => $article->id,
                            'name' => $article->name,
                            'type' => $article->type,
                            'purchase_price' => $article->purchase_price,
                            'unit_label' => $this->getUnitLabel($article),
                            'quantity' => $article->pivot->quantity,
                            'pivot' => [
                                'quantity' => $article->pivot->quantity
                            ]
                        ];
                    })->toArray(),
                    'actions' => collect($item->actions ?? [])->map(function($action) {
                        return [
                            'action_id' => [
                                'id' => $action['action_id']['id'] ?? $action['id'],
                                'name' => $action['action_id']['name'] ?? $action['name']
                            ],
                            'status' => $action['status'] ?? 'Not started yet',
                            'quantity' => $action['quantity'],
                            'isMaterialized' => $action['isMaterialized'] ?? false
                        ];
                    })->toArray(),
                    'subcategory_id' => $item->subcategory_id,
                    'subcategory_name' => $item->subcategory ? $item->subcategory->name : null,
                    'should_ask_questions' => (bool)$item->should_ask_questions,
                ];
            });

            // Return Inertia view with the transformed data
            return Inertia::render('CatalogItem/CatalogList', [
                'catalogItems' => $transformedItems,
                'pagination' => [
                    'current_page' => $catalogItems->currentPage(),
                    'last_page' => $catalogItems->lastPage(),
                    'total' => $catalogItems->total(),
                    'per_page' => $catalogItems->perPage(),
                    'links' => [
                        'prev' => $catalogItems->currentPage() > 1,
                        'next' => $catalogItems->hasMorePages()
                    ]
                ],
                'canViewPrice' => !auth()->user()->hasRole('Rabotnik')
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getCatalogItems:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch catalog items',
                'details' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    public function create()
    {
        $machinesPrint = DB::table('machines_print')
            ->select('id', 'name')
            ->get();

        $machinesCut = DB::table('machines_cut')
            ->select('id', 'name')
            ->get();

        $actions = DB::table('dorabotka')
            ->select('id', 'name', 'isMaterialized')
            ->whereNotNull('dorabotka.name')
            ->get()
            ->map(function($action) {
                return [
                    'id' => $action->id,
                    'name' => $action->name,
                    'isMaterialized' => (bool)$action->isMaterialized
                ];
            });

        // Get large format materials and categories
        $largeMaterials = collect();
        
        // Get individual large materials not in any category
        $largeMaterialCategories = \App\Models\ArticleCategory::with(['articles' => function($q) {
            $q->whereHas('largeFormatMaterial');
        }, 'articles.largeFormatMaterial'])
            ->where('type', 'large')
            ->whereNull('deleted_at')
            ->get();

        $categoryArticleIds = $largeMaterialCategories->flatMap(function($cat) {
            return $cat->articles->pluck('id');
        })->unique();
        
        $individualLargeMaterials = LargeFormatMaterial::with(['article' => function($query) {
            $query->select('id', 'name', 'code');
        }])
            ->whereHas('article')
            ->whereNotIn('article_id', $categoryArticleIds)
            ->get();

        // Add categories to large materials
        foreach ($largeMaterialCategories as $category) {
            $materials = $category->articles->map(function($article) {
                return $article->largeFormatMaterial;
            })->filter();
            
            $largeMaterials->push([
                'id' => 'cat_' . $category->id,
                'name' => "[Category] {$category->name}",
                'type' => 'category',
                'category_type' => 'large',
                'article' => null
            ]);
        }

        // Add individual materials
        foreach ($individualLargeMaterials as $material) {
            $largeMaterials->push([
                'id' => $material->id,
                'name' => $material->name,
                'type' => 'individual',
                'article' => $material->article
            ]);
        }

        // Get small format materials and categories
        $smallMaterials = collect();
        
        // Get individual small materials not in any category
        $smallMaterialCategories = \App\Models\ArticleCategory::with(['articles' => function($q) {
            $q->whereHas('smallMaterial');
        }, 'articles.smallMaterial'])
            ->where('type', 'small')
            ->whereNull('deleted_at')
            ->get();

        $categoryArticleIds = $smallMaterialCategories->flatMap(function($cat) {
            return $cat->articles->pluck('id');
        })->unique();
        
        $individualSmallMaterials = SmallMaterial::with(['article' => function($query) {
            $query->select('id', 'name', 'code');
        }])
            ->whereHas('article')
            ->whereNotIn('article_id', $categoryArticleIds)
            ->get();

        // Add categories to small materials
        foreach ($smallMaterialCategories as $category) {
            $materials = $category->articles->map(function($article) {
                return $article->smallMaterial;
            })->filter();
            
            $smallMaterials->push([
                'id' => 'cat_' . $category->id,
                'name' => "[Category] {$category->name}",
                'type' => 'category',
                'category_type' => 'small',
                'article' => null
            ]);
        }

        // Add individual materials
        foreach ($individualSmallMaterials as $material) {
            $smallMaterials->push([
                'id' => $material->id,
                'name' => $material->name,
                'type' => 'individual',
                'article' => $material->article
            ]);
        }

        return Inertia::render('CatalogItem/Create', [
            'actions' => $actions,
            'largeMaterials' => $largeMaterials->values(),
            'smallMaterials' => $smallMaterials->values(),
            'machinesPrint' => $machinesPrint,
            'machinesCut' => $machinesCut,
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_for_offer' => filter_var($request->input('is_for_offer'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'is_for_sales' => filter_var($request->input('is_for_sales'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'subcategory_id' => $request->input('subcategory_id') === 'null' || $request->input('subcategory_id') === '' ? null : $request->input('subcategory_id'),
            'large_material_id' => $request->input('large_material_id') === '' ? null : $request->input('large_material_id'),
            'small_material_id' => $request->input('small_material_id') === '' ? null : $request->input('small_material_id'),
            'large_material_category_id' => $request->input('large_material_category_id') === '' ? null : $request->input('large_material_category_id'),
            'small_material_category_id' => $request->input('small_material_category_id') === '' ? null : $request->input('small_material_category_id'),
        ]);
        
        $actions = $request->input('actions');
        $actions = array_map(function ($action) {
            if ($action['isMaterialized'] === '') {
                $action['isMaterialized'] = null;
            } else {
                $action['isMaterialized'] = filter_var($action['isMaterialized'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            }
            return $action;
        }, $actions);
        $request->merge(['actions' => $actions]);

        $request->validate([
            'name' => 'required|string|unique:catalog_items',
            'description' => 'nullable|string',
            'machinePrint' => 'nullable|string',
            'machineCut' => 'nullable|string',
            'large_material_id' => 'nullable|integer',
            'small_material_id' => 'nullable|integer',
            'large_material_category_id' => 'nullable|integer|exists:article_categories,id',
            'small_material_category_id' => 'nullable|integer|exists:article_categories,id',
            'quantity' => 'required|integer|min:1',
            'copies' => 'required|integer|min:1',
            'actions' => 'required|array',
            'actions.*.id' => 'required|exists:dorabotka,id',
            'actions.*.quantity' => 'integer|min:0|required_if:actions.*.isMaterialized,true|nullable',
            'actions.*.isMaterialized' => 'nullable',
            'is_for_offer' => 'nullable|boolean',
            'is_for_sales' => 'nullable|boolean',
            'category' => 'nullable|string|in:' . implode(',', \App\Models\CatalogItem::CATEGORIES),
            'file' => 'nullable|mimes:jpg,jpeg,png|max:20480',
            'template_file' => 'nullable|mimes:pdf|max:20480',
            'price' => 'required|numeric|min:0',
            'articles' => 'nullable|array',
            'articles.*.id' => 'required|exists:article,id',
            'articles.*.quantity' => 'required|numeric|min:0.01',
            'subcategory_id' => 'nullable|exists:subcategories,id'
        ], [], [
            'large_material_id' => 'large material',
            'small_material_id' => 'small material', 
            'large_material_category_id' => 'large material category',
            'small_material_category_id' => 'small material category',
        ]);

        \Log::info('After validation - processed data:', [
            'large_material_id' => $request->input('large_material_id'),
            'small_material_id' => $request->input('small_material_id'),
            'large_material_category_id' => $request->input('large_material_category_id'),
            'small_material_category_id' => $request->input('small_material_category_id'),
        ]);

        DB::beginTransaction();
        try {
            // Clean up the request data before creating the catalog item
            $createData = $request->except(['actions', 'articles', 'template_file']);
            
            // Ensure material ID fields are properly null if empty
            $createData['large_material_id'] = $createData['large_material_id'] ?: null;
            $createData['small_material_id'] = $createData['small_material_id'] ?: null;
            $createData['large_material_category_id'] = $createData['large_material_category_id'] ?: null;
            $createData['small_material_category_id'] = $createData['small_material_category_id'] ?: null;
            $createData['subcategory_id'] = $createData['subcategory_id'] ?: null;
            
            // Create the catalog item without actions for now
            $catalogItem = CatalogItem::create($createData);

            // Handle file upload if provided
            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $fileName = time() . '_' . $file->getClientOriginalName();
                $file->storeAs('public/uploads', $fileName);
                $catalogItem->file = $fileName;
            }

            // Handle template file upload if provided
            if ($request->hasFile('template_file')) {
                $templateFile = $request->file('template_file');
                
                // Add debugging information
                \Log::info('Template file received in controller', [
                    'catalog_item_id' => $catalogItem->id,
                    'original_name' => $templateFile->getClientOriginalName(),
                    'original_extension' => $templateFile->getClientOriginalExtension(),
                    'mime_type' => $templateFile->getMimeType(),
                    'size' => $templateFile->getSize(),
                    'temp_path' => $templateFile->getPathname()
                ]);
                
                $templatePath = $this->templateStorageService->storeTemplate($templateFile);
                $catalogItem->template_file = $templatePath;
                
                \Log::info('New template file uploaded successfully', [
                    'catalog_item_id' => $catalogItem->id,
                    'new_template' => $templatePath
                ]);
            }

            // Process actions
            $catalogItemActions = collect($request->actions)->map(function ($action) {
                $actionData = DB::table('dorabotka')->where('id', $action['id'])->first();

                if (!$actionData) {
                    throw new \Exception("Action with ID {$action['id']} does not exist.");
                }

                $isMaterialized = $action['isMaterialized'] ?? false;

                return [
                    'action_id' => [
                        'id' => $action['id'],
                        'name' => $actionData->name,
                    ],
                    'status' => 'Not started yet',
                    'quantity' => $isMaterialized ? ($action['quantity'] ?? 0) : null,
                ];
            });

            $catalogItem->actions = $catalogItemActions->toArray();

            // Process articles if provided
            if ($request->has('articles')) {
                foreach ($request->articles as $articleData) {
                    $catalogItem->articles()->attach($articleData['id'], [
                        'quantity' => $articleData['quantity']
                    ]);
                }
                // Calculate and update cost price
                $catalogItem->calculateCostPrice();
            }

            $catalogItem->save();

            DB::commit();
            \Log::info('Stored catalog item actions:', ['actions' => $catalogItem->actions]);

            return redirect()->route('catalog.index');

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function edit(CatalogItem $catalogItem)
    {
        $machinesPrint = DB::table('machines_print')
            ->select('id', 'name')
            ->get();

        $machinesCut = DB::table('machines_cut')
            ->select('id', 'name')
            ->get();

        $actions = DB::table('dorabotka')
            ->select('id', 'name')
            ->get()
            ->map(function($action) {
                return [
                    'id' => $action->id,
                    'name' => $action->name
                ];
            });

        $catalogItem->load([
            'largeMaterial',
            'smallMaterial',
            'largeMaterial.article',
            'smallMaterial.article'
        ]);

        // Get large format materials and categories - same as in create method
        $largeMaterials = collect();
        
        $largeMaterialCategories = \App\Models\ArticleCategory::with(['articles' => function($q) {
            $q->whereHas('largeFormatMaterial');
        }, 'articles.largeFormatMaterial'])
            ->where('type', 'large')
            ->whereNull('deleted_at')
            ->get();

        $categoryArticleIds = $largeMaterialCategories->flatMap(function($cat) {
            return $cat->articles->pluck('id');
        })->unique();
        
        $individualLargeMaterials = LargeFormatMaterial::with(['article' => function($query) {
            $query->select('id', 'name', 'code');
        }])
            ->whereHas('article')
            ->whereNotIn('article_id', $categoryArticleIds)
            ->get();

        // Add categories to large materials
        foreach ($largeMaterialCategories as $category) {
            $largeMaterials->push([
                'id' => 'cat_' . $category->id,
                'name' => "[Category] {$category->name}",
                'type' => 'category',
                'category_type' => 'large',
                'article' => null
            ]);
        }

        // Add individual materials
        foreach ($individualLargeMaterials as $material) {
            $largeMaterials->push([
                'id' => $material->id,
                'name' => $material->name,
                'type' => 'individual',
                'article' => $material->article
            ]);
        }

        // Get small format materials and categories - same as in create method
        $smallMaterials = collect();
        
        $smallMaterialCategories = \App\Models\ArticleCategory::with(['articles' => function($q) {
            $q->whereHas('smallMaterial');
        }, 'articles.smallMaterial'])
            ->where('type', 'small')
            ->whereNull('deleted_at')
            ->get();

        $categoryArticleIds = $smallMaterialCategories->flatMap(function($cat) {
            return $cat->articles->pluck('id');
        })->unique();
        
        $individualSmallMaterials = SmallMaterial::with(['article' => function($query) {
            $query->select('id', 'name', 'code');
        }])
            ->whereHas('article')
            ->whereNotIn('article_id', $categoryArticleIds)
            ->get();

        // Add categories to small materials
        foreach ($smallMaterialCategories as $category) {
            $smallMaterials->push([
                'id' => 'cat_' . $category->id,
                'name' => "[Category] {$category->name}",
                'type' => 'category',
                'category_type' => 'small',
                'article' => null
            ]);
        }

        // Add individual materials
        foreach ($individualSmallMaterials as $material) {
            $smallMaterials->push([
                'id' => $material->id,
                'name' => $material->name,
                'type' => 'individual',
                'article' => $material->article
            ]);
        }

        return Inertia::render('CatalogItem/Edit', [
            'catalogItem' => $catalogItem,
            'actions' => $actions,
            'largeMaterials' => $largeMaterials->values(),
            'smallMaterials' => $smallMaterials->values(),
            'machinesPrint' => $machinesPrint,
            'machinesCut' => $machinesCut,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        try {
            // Decode JSON strings to arrays first
            $actions = json_decode($request->input('actions'), true);
            $articles = json_decode($request->input('articles'), true);

            // Handle subcategory_id before merging other data
            $subcategoryId = $request->input('subcategory_id');
            if ($subcategoryId === 'null' || $subcategoryId === '' || $subcategoryId === null) {
                $subcategoryId = null;
            }

            // The frontend already processes category vs material logic and sends the correct field names
            // So we don't need to do the 'cat_' prefix processing here like in the store method
            
            // Merge the decoded data into the request
            $request->merge([
                'is_for_offer' => filter_var($request->input('is_for_offer'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'is_for_sales' => filter_var($request->input('is_for_sales'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'subcategory_id' => $subcategoryId,
                'actions' => $actions,
                'articles' => $articles
            ]);

            $request->validate([
                'name' => 'required|string|unique:catalog_items,name,' . $catalogItem->id,
                'description' => 'nullable|string',
                'machinePrint' => 'nullable|string',
                'machineCut' => 'nullable|string',
                'large_material_id' => 'nullable',
                'small_material_id' => 'nullable',
                'large_material_category_id' => 'nullable|integer|exists:article_categories,id',
                'small_material_category_id' => 'nullable|integer|exists:article_categories,id',
                'quantity' => 'required|integer|min:1',
                'copies' => 'required|integer|min:1',
                'actions' => 'required|array',
                'actions.*.selectedAction' => 'required|exists:dorabotka,id',
                'actions.*.quantity' => 'integer|min:0|nullable',
                'is_for_offer' => 'nullable|boolean',
                'is_for_sales' => 'nullable|boolean',
                'category' => 'nullable|string|in:' . implode(',', CatalogItem::CATEGORIES),
                'file' => 'nullable|mimes:jpg,jpeg,png|max:20480',
                'template_file' => 'nullable|mimes:pdf|max:20480',
                'price' => 'required|numeric|min:0',
                'articles' => 'nullable|array',
                'articles.*.id' => 'required|exists:article,id',
                'articles.*.quantity' => 'required|numeric|min:0.01',
                'subcategory_id' => 'nullable|exists:subcategories,id'
            ]);

            DB::beginTransaction();

            try {
                // Handle file upload if a new file is provided
                if ($request->hasFile('file')) {
                    if ($catalogItem->file && $catalogItem->file !== 'placeholder.jpeg') {
                        Storage::disk('public')->delete('uploads/' . $catalogItem->file);
                    }

                    $file = $request->file('file');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->storeAs('public/uploads', $fileName);
                    $catalogItem->file = $fileName;
                }

                if ($request->hasFile('template_file')) {
                    if ($catalogItem->template_file) {
                        try {
                            $this->templateStorageService->deleteTemplate($catalogItem->template_file);
                        } catch (\Exception $e) {
                            \Log::error('Failed to delete old template file', [
                                'catalog_item_id' => $catalogItem->id,
                                'old_template' => $catalogItem->template_file,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }

                    try {
                        $templateFile = $request->file('template_file');
                        $templatePath = $this->templateStorageService->storeTemplate($templateFile);
                        $catalogItem->template_file = $templatePath;
                    } catch (\Exception $e) {
                        throw new \Exception('Failed to upload template file: ' . $e->getMessage());
                    }
                }

                // Handle template removal if flag is set
                if ($request->input('remove_template') === '1') {
                    if ($catalogItem->template_file) {
                        try {
                            $this->templateStorageService->deleteTemplate($catalogItem->template_file);
                            $catalogItem->template_file = null;
                        } catch (\Exception $e) {
                            throw new \Exception('Failed to remove template file: ' . $e->getMessage());
                        }
                    }
                }

                // Update the catalog item without actions, file, and template_file first
                $updateData = $request->except(['actions', 'file', 'articles', 'template_file', 'remove_template']);
                
                // Handle empty strings as null for material/category fields
                $updateData['large_material_id'] = $request->input('large_material_id') ?: null;
                $updateData['small_material_id'] = $request->input('small_material_id') ?: null;
                $updateData['large_material_category_id'] = $request->input('large_material_category_id') ?: null;
                $updateData['small_material_category_id'] = $request->input('small_material_category_id') ?: null;
                
                $catalogItem->update($updateData);

                // Process actions and update them
                $catalogItemActions = collect($actions)->map(function ($action) {
                    $actionData = DB::table('dorabotka')->where('id', $action['selectedAction'])->first();

                    if (!$actionData) {
                        throw new \Exception("Action with ID {$action['selectedAction']} does not exist.");
                    }

                    return [
                        'action_id' => [
                            'id' => $action['selectedAction'],
                            'name' => $actionData->name,
                        ],
                        'status' => 'Not started yet',
                        'quantity' => $action['quantity'] ?? null,
                        'isMaterialized' => $actionData->isMaterialized
                    ];
                });

                $catalogItem->actions = $catalogItemActions->toArray();

                // Update articles if provided
                if ($articles) {
                    // Detach all existing articles
                    $catalogItem->articles()->detach();

                    // Attach new articles with quantities
                    foreach ($articles as $articleData) {
                        $catalogItem->articles()->attach($articleData['id'], [
                            'quantity' => $articleData['quantity']
                        ]);
                    }

                    // Recalculate cost price
                    $catalogItem->calculateCostPrice();
                }

                $catalogItem->save();

                DB::commit();

                return redirect()->route('catalog.index')
                    ->with('success', 'Catalog item updated successfully.');

            } catch (\Exception $e) {
                DB::rollBack();
                throw $e;
            }

        } catch (\Exception $e) {
            \Log::error('Error updating catalog item:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'error' => 'Failed to update catalog item',
                'details' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    public function destroy(CatalogItem $catalogItem)
    {
        try {
            DB::beginTransaction();
            
            if ($catalogItem->template_file) {
                try {
                    $this->templateStorageService->deleteTemplate($catalogItem->template_file);
                } catch (\Exception $e) {
                }
            }
            
            // Delete regular file if it exists (for backward compatibility)
            if ($catalogItem->file && $catalogItem->file !== 'placeholder.jpeg') {
                try {
                    Storage::disk('public')->delete('uploads/' . $catalogItem->file);
                } catch (\Exception $e) {
                }
            }
            
            // Detach related records and delete the catalog item
            $catalogItem->actions()->detach();
            $catalogItem->articles()->detach();
            $catalogItem->delete();
            
            DB::commit();
            
            return redirect()->route('catalog.index')->with('success', 'Catalog item deleted successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('catalog.index')->with('error', 'Failed to delete catalog item.');
        }
    }

    public function downloadTemplate(CatalogItem $catalogItem)
    {
        if (!$catalogItem->template_file) {
            return response()->json(['error' => 'No template file found'], 404);
        }

        try {
            // Check if file exists in R2
            if ($this->templateStorageService->templateExists($catalogItem->template_file)) {
                // Get the file content from R2
                $fileContent = $this->templateStorageService->disk->get($catalogItem->template_file);
                $originalName = $this->templateStorageService->getOriginalFilename($catalogItem->template_file);
                
                return response($fileContent)
                    ->header('Content-Type', 'application/pdf')
                    ->header('Content-Disposition', 'inline; filename="' . $originalName . '"')
                    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                    ->header('Pragma', 'no-cache')
                    ->header('Expires', '0');
            }
            
            // Fallback for legacy local files
            $filePath = storage_path('app/public/templates/' . $catalogItem->template_file);
            if (file_exists($filePath)) {
                return response()->download($filePath, $this->getOriginalFileName($catalogItem->template_file));
            }
            
            return response()->json(['error' => 'Template file not found'], 404);
            
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to download template'], 500);
        }
    }

    private function getOriginalFileName($templateFile)
    {
        return $this->templateStorageService->getOriginalFilename($templateFile);
    }

    private function getUnitLabel($article)
    {
        if ($article->in_kilograms) return 'kg';
        if ($article->in_meters) return 'm';
        if ($article->in_square_meters) return 'm²';
        if ($article->in_pieces) return 'pcs';
        return '';
    }
}
