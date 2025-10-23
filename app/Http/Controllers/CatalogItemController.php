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
use Illuminate\Support\Str;

class CatalogItemController extends Controller

{
    protected $templateStorageService;

    public function __construct(TemplateStorageService $templateStorageService)
    {
        $this->templateStorageService = $templateStorageService;
    }

    public function fetchAllForOffer()
    {
        $catalogItems = CatalogItem::with(['largeMaterial', 'smallMaterial', 'largeMaterialCategory', 'smallMaterialCategory'])
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
                    'large_material_category' => $item->largeMaterialCategory ? [
                        'id' => $item->largeMaterialCategory->id,
                        'name' => $item->largeMaterialCategory->name
                    ] : null,
                    'small_material_category' => $item->smallMaterialCategory ? [
                        'id' => $item->smallMaterialCategory->id,
                        'name' => $item->smallMaterialCategory->name
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
            $subcategoryIds = $request->input('subcategory_ids', []);

            // Start with base query
            $query = CatalogItem::with([
                'largeMaterial',
                'smallMaterial',
                'subcategories',
                'articles'
            ]);

            // Add search functionality
            if (!empty($searchTerm)) {
                $query->where(function($q) use ($searchTerm) {
                    $q->where('name', 'like', "%{$searchTerm}%")
                      ->orWhere('description', 'like', "%{$searchTerm}%")
                      ->orWhereHas('subcategories', function($q) use ($searchTerm) {
                          $q->where('name', 'like', "%{$searchTerm}%");
                      });
                });
            }

            // Add category filter
            if (!empty($category)) {
                $query->where('category', $category);
            }

            // Add subcategory filter (supports multi)
            if (!empty($subcategoryIds) && is_array($subcategoryIds)) {
                $ids = array_filter(array_map('intval', $subcategoryIds));
                if (!empty($ids)) {
                    $query->whereHas('subcategories', function($q) use ($ids) {
                        $q->whereIn('subcategories.id', $ids);
                    });
                }
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
                    'subcategory_ids' => $item->subcategories->pluck('id')->values()->all(),
                    'subcategory_names' => $item->subcategories->pluck('name')->values()->all(),
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
                            'action_id' => $action['action_id'],
                            'quantity' => $action['quantity'],
                            'isMaterialized' => $action['isMaterialized'] ?? false
                        ];
                    })->toArray(),
                    'should_ask_questions' => (bool)$item->should_ask_questions,
                    'by_quantity' => (bool)$item->by_quantity,
                    'by_copies' => (bool)$item->by_copies,
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
                'canViewPrice' => !auth()->user()->hasRole('Rabotnik'),
                'canDelete' => !auth()->user()->hasRole('Rabotnik')
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

        // Get all active questions for the inline selector
        $availableQuestions = \App\Models\Question::active()
            ->select('id', 'question', 'order')
            ->get();

        return Inertia::render('CatalogItem/Create', [
            'actions' => $actions,
            'largeMaterials' => $largeMaterials->values(),
            'smallMaterials' => $smallMaterials->values(),
            'machinesPrint' => $machinesPrint,
            'machinesCut' => $machinesCut,
            'availableQuestions' => $availableQuestions,
            // Provide subcategories list for multi-select on create
            'subcategories' => \App\Models\Subcategory::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $request->merge([
            'is_for_offer' => filter_var($request->input('is_for_offer'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'is_for_sales' => filter_var($request->input('is_for_sales'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'large_material_id' => $request->input('large_material_id') === '' ? null : $request->input('large_material_id'),
            'small_material_id' => $request->input('small_material_id') === '' ? null : $request->input('small_material_id'),
            'large_material_category_id' => $request->input('large_material_category_id') === '' ? null : $request->input('large_material_category_id'),
            'small_material_category_id' => $request->input('small_material_category_id') === '' ? null : $request->input('small_material_category_id'),
            'by_quantity' => filter_var($request->input('by_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'by_copies' => filter_var($request->input('by_copies'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
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
            'by_quantity' => 'nullable|boolean',
            'by_copies' => 'nullable|boolean',
            'category' => 'nullable|string|in:' . implode(',', \App\Models\CatalogItem::CATEGORIES),
            'file' => 'nullable|mimes:jpg,jpeg,png|max:20480',
            'template_file' => 'nullable|mimes:pdf|max:20480',
            'price' => 'required|numeric|min:0',
            'articles' => 'nullable|array',
            'articles.*.id' => ['required', function ($attribute, $value, $fail) {
                if (str_starts_with($value, 'cat_')) {
                    $categoryId = str_replace('cat_', '', $value);
                    if (!\App\Models\ArticleCategory::where('id', $categoryId)->exists()) {
                        $fail('The selected article category does not exist.');
                    }
                } else {
                    if (!\App\Models\Article::where('id', $value)->exists()) {
                        $fail('The selected article does not exist.');
                    }
                }
            }],
            'articles.*.quantity' => 'required|numeric|min:0.0000',
            'subcategory_ids' => 'nullable|array',
            'subcategory_ids.*' => 'integer|exists:subcategories,id'
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
            $createData = $request->except(['actions', 'articles', 'template_file', 'subcategory_ids', 'subcategory_id']);
            
            // Ensure material ID fields are properly null if empty
            $createData['large_material_id'] = $createData['large_material_id'] ?: null;
            $createData['small_material_id'] = $createData['small_material_id'] ?: null;
            $createData['large_material_category_id'] = $createData['large_material_category_id'] ?: null;
            $createData['small_material_category_id'] = $createData['small_material_category_id'] ?: null;
            
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
                    $articleId = $articleData['id'];
                    
                    // If this is a category (starts with 'cat_'), resolve to first article but store category info
                    if (str_starts_with($articleId, 'cat_')) {
                        $categoryId = str_replace('cat_', '', $articleId);
                        // Just check if articles have any stock (1 unit) during catalog item creation
                        $firstArticle = $catalogItem->getFirstArticleFromCategory($categoryId, null, 1);
                        if ($firstArticle) {
                            $catalogItem->articles()->attach($firstArticle->id, [
                                'quantity' => $articleData['quantity'],
                                'category_id' => $categoryId // Store the original category selection
                            ]);
                        } else {
                            \Log::notice('Stock validation disabled: no available articles with sufficient stock in selected category during catalog item store.');
                        }
                    } else {
                        // This is a regular article
                        $catalogItem->articles()->attach($articleId, [
                            'quantity' => $articleData['quantity']
                        ]);
                    }
                }
                // Calculate and update cost price
                $catalogItem->load('articles');
                $catalogItem->calculateCostPrice();
            }

            $catalogItem->save();

            // Sync multiple subcategories if provided
            $subcategoryIds = $request->input('subcategory_ids', []);
            if (is_string($subcategoryIds)) {
                $decoded = json_decode($subcategoryIds, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $subcategoryIds = $decoded;
                }
            }
            if (is_array($subcategoryIds) && !empty($subcategoryIds)) {
                $catalogItem->subcategories()->sync($subcategoryIds);
            }

            DB::commit();
            \Log::info('Stored catalog item actions:', ['actions' => $catalogItem->actions]);

            return response()->json([
                'message' => 'Catalog item created successfully',
                'catalog_item' => ['id' => $catalogItem->id]
            ], 201);

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
            'smallMaterial.article',
            'articles',
            'questions',
            'subcategories'
        ]);

        // Transform articles to show original category selections
        $getUnitLabel = function($article) {
            if ($article->in_kilograms) return 'kg';
            if ($article->in_meters) return 'm';
            if ($article->in_square_meters) return 'm²';
            if ($article->in_pieces) return 'pcs';
            return '';
        };
        
        $transformedArticles = $catalogItem->articles->map(function($article) use ($catalogItem, $getUnitLabel) {
            if ($article->pivot->category_id) {
                // This was originally a category selection, transform it back
                $category = \App\Models\ArticleCategory::find($article->pivot->category_id);
                
                // Get the first available article from the category for display info
                $firstArticle = $catalogItem->getFirstArticleFromCategory($article->pivot->category_id, null, 1);
                
                // For component articles, we need to determine the type from the resolved article, not the category
                // The category type ('large'/'small') is for materials, but component articles need 'product'/'service'
                $componentType = 'product'; // default
                if ($firstArticle && $firstArticle->type) {
                    $componentType = $firstArticle->type; // Use the actual article type (product/service)
                }
                
                return [
                    'id' => 'cat_' . $article->pivot->category_id,
                    'name' => $category ? "[Category] {$category->name}" : 'Unknown Category',
                    'type' => $componentType, // Use the resolved article type (product/service)
                    'purchase_price' => $firstArticle ? $firstArticle->purchase_price : 0,
                    'unit_label' => $firstArticle ? $getUnitLabel($firstArticle) : '',
                    'quantity' => $article->pivot->quantity,
                    'original_category_id' => $article->pivot->category_id,
                    'resolved_article_id' => $article->id
                ];
            } else {
                // This was an individual article selection
                return [
                    'id' => $article->id,
                    'name' => $article->name,
                    'type' => $article->type,
                    'purchase_price' => $article->purchase_price,
                    'unit_label' => $getUnitLabel($article),
                    'quantity' => $article->pivot->quantity
                ];
            }
        });

        // Replace the articles collection with the transformed data
        $catalogItem->setRelation('articles', $transformedArticles);



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

        // Get subcategories
        $subcategories = \App\Models\Subcategory::all();

        // Get all active questions for the inline selector
        $availableQuestions = \App\Models\Question::active()
            ->select('id', 'question', 'order')
            ->get();

        // Transform material IDs back to category selections if they were originally categories
        if ($catalogItem->large_material_category_id) {
            $catalogItem->large_material_id = 'cat_' . $catalogItem->large_material_category_id;
        }
        
        if ($catalogItem->small_material_category_id) {
            $catalogItem->small_material_id = 'cat_' . $catalogItem->small_material_category_id;
        }

        return Inertia::render('CatalogItem/Edit', [
            'catalogItem' => $catalogItem,
            'actions' => $actions,
            'largeMaterials' => $largeMaterials->values(),
            'smallMaterials' => $smallMaterials->values(),
            'machinesPrint' => $machinesPrint,
            'machinesCut' => $machinesCut,
            'subcategories' => $subcategories,
            // Provide preselected IDs for multi-select
            'selectedSubcategoryIds' => $catalogItem->subcategories->pluck('id')->values(),
            'availableQuestions' => $availableQuestions,
            'canViewCostSummary' => !auth()->user()->hasRole('Rabotnik'),
            'canViewPrice' => !auth()->user()->hasRole('Rabotnik'),
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        try {
            // Decode JSON strings to arrays first
            $actions = json_decode($request->input('actions'), true);
            $articles = json_decode($request->input('articles'), true);

            // Handle multi subcategories
            $subcategoryIds = $request->input('subcategory_ids');
            if (is_string($subcategoryIds)) {
                $decoded = json_decode($subcategoryIds, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $subcategoryIds = $decoded;
                }
            }
            if (!is_array($subcategoryIds)) {
                $subcategoryIds = [];
            }

            // The frontend already processes category vs material logic and sends the correct field names
            // So we don't need to do the 'cat_' prefix processing here like in the store method
            
            // Merge the decoded data into the request
            $request->merge([
                'is_for_offer' => filter_var($request->input('is_for_offer'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'is_for_sales' => filter_var($request->input('is_for_sales'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'by_quantity' => filter_var($request->input('by_quantity'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'by_copies' => filter_var($request->input('by_copies'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'subcategory_ids' => $subcategoryIds,
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
                'by_quantity' => 'nullable|boolean',
                'by_copies' => 'nullable|boolean',
                'category' => 'nullable|string|in:' . implode(',', CatalogItem::CATEGORIES),
                'file' => 'nullable|mimes:jpg,jpeg,png|max:20480',
                'template_file' => 'nullable|mimes:pdf|max:20480',
                'price' => 'required|numeric|min:0',
                'articles' => 'nullable|array',
                'articles.*.id' => ['required', function ($attribute, $value, $fail) {
                    if (str_starts_with($value, 'cat_')) {
                        $categoryId = str_replace('cat_', '', $value);
                        if (!\App\Models\ArticleCategory::where('id', $categoryId)->exists()) {
                            $fail('The selected article category does not exist.');
                        }
                    } else {
                        if (!\App\Models\Article::where('id', $value)->exists()) {
                            $fail('The selected article does not exist.');
                        }
                    }
                }],
                'articles.*.quantity' => 'required|numeric|min:0.0000',
                 'subcategory_ids' => 'nullable|array',
                 'subcategory_ids.*' => 'integer|exists:subcategories,id'
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
                $updateData = $request->except(['actions', 'file', 'articles', 'template_file', 'remove_template', 'subcategory_ids', 'subcategory_id']);
                
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
                        $articleId = $articleData['id'];
                        
                        // If this is a category (starts with 'cat_'), resolve to first article but store category info
                        if (str_starts_with($articleId, 'cat_')) {
                            $categoryId = str_replace('cat_', '', $articleId);
                            // Just check if articles have any stock (1 unit) during catalog item update
                            $firstArticle = $catalogItem->getFirstArticleFromCategory($categoryId, null, 1);
                            if ($firstArticle) {
                                $catalogItem->articles()->attach($firstArticle->id, [
                                    'quantity' => $articleData['quantity'],
                                    'category_id' => $categoryId // Store the original category selection
                                ]);
                            } else {
                                \Log::notice('Stock validation disabled: no available articles with sufficient stock in selected category during catalog item update.');
                            }
                        } else {
                            // This is a regular article
                            $catalogItem->articles()->attach($articleId, [
                                'quantity' => $articleData['quantity']
                            ]);
                        }
                    }

                    // Recalculate cost price
                    $catalogItem->load('articles');
                    $catalogItem->calculateCostPrice();
                }

                $catalogItem->save();

                // Sync subcategories
                if ($request->has('subcategory_ids')) {
                    $catalogItem->subcategories()->sync($subcategoryIds);
                }

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

    public function destroy(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            // Find the catalog item by ID (including soft deleted ones)
            $catalogItem = CatalogItem::withTrashed()->findOrFail($id);
            
            // Perform soft delete - this will set deleted_at timestamp
            // The catalog item will be hidden from normal queries but still exist in the database
            $catalogItem->delete();
            
            DB::commit();
            
            // Return JSON/204 for AJAX/API requests to avoid Inertia redirect conflicts
            if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                return response()->noContent();
            }

            return redirect()->route('catalog.index')->with('success', 'Catalog item deleted successfully.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Failed to delete catalog item.'], 500);
            }
            return redirect()->route('catalog.index')->with('error', 'Failed to delete catalog item.');
        }
    }

    /**
     * Copy a catalog item with all its data except the name
     * 
     * This method creates a new catalog item that is an exact copy of the original,
     * including files, templates, actions, questions, and component articles.
     * Only the name is different and must be provided by the user.
     * 
     * Note: Template files are referenced rather than copied to save storage space.
     * 
     * @param Request $request
     * @param int $id The ID of the catalog item to copy
     * @return \Illuminate\Http\JsonResponse
     */
    public function copy(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            // Find the original catalog item
            $originalItem = CatalogItem::with(['articles', 'questions', 'subcategories'])->findOrFail($id);
            
            // Validate the new name
            $request->validate([
                'name' => 'required|string|max:255|unique:catalog_items,name'
            ], [
                'name.required' => 'The catalog item name is required.',
                'name.unique' => 'A catalog item with this name already exists.',
                'name.max' => 'The catalog item name cannot exceed 255 characters.'
            ]);
            
            // Create a new catalog item with the same data except name
            $newItem = $originalItem->replicate();
            $newItem->name = trim($request->input('name'));
            $newItem->save();
            
            // Copy the file if it exists
            if ($originalItem->file && $originalItem->file !== 'placeholder.jpeg') {
                $fileExtension = pathinfo($originalItem->file, PATHINFO_EXTENSION);
                $newFileName = time() . '_' . Str::slug($request->input('name')) . '.' . $fileExtension;
                
                // Copy the file
                if (Storage::disk('public')->exists('uploads/' . $originalItem->file)) {
                    Storage::disk('public')->copy('uploads/' . $originalItem->file, 'uploads/' . $newFileName);
                    $newItem->file = $newFileName;
                }
            }
            
            // Copy the template file if it exists
            if ($originalItem->template_file) {
                // Instead of copying the template file, just reference the same file
                // This saves storage space and is more efficient
                $newItem->template_file = $originalItem->template_file;
            }
            
            // Copy articles with their quantities and category information
            if ($originalItem->articles->isNotEmpty()) {
                foreach ($originalItem->articles as $article) {
                    $pivotData = [
                        'quantity' => $article->pivot->quantity
                    ];
                    
                    // Preserve category information if it exists
                    if (isset($article->pivot->category_id)) {
                        $pivotData['category_id'] = $article->pivot->category_id;
                    }
                    
                    $newItem->articles()->attach($article->id, $pivotData);
                }
                
                // Recalculate cost price for the new item
                $newItem->load('articles');
                $newItem->calculateCostPrice();
            }
            
            // Copy questions if the original item has questions
            if ($originalItem->should_ask_questions && $originalItem->questions->isNotEmpty()) {
                $questionIds = $originalItem->questions->pluck('id')->toArray();
                $newItem->questions()->attach($questionIds);
            }
            
            // Copy subcategories if the original item has subcategories
            if ($originalItem->subcategories && $originalItem->subcategories->isNotEmpty()) {
                $subcategoryIds = $originalItem->subcategories->pluck('id')->toArray();
                $newItem->subcategories()->attach($subcategoryIds);
            }
            
            $newItem->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Catalog item copied successfully',
                'new_item_id' => $newItem->id
            ]);
            
        } catch (\Illuminate\Validation\ValidationException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error copying catalog item:', [
                'original_item_id' => $id,
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy catalog item: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Permanently delete a catalog item (use with caution)
     */
    public function forceDelete(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            // Find the catalog item by ID (including soft deleted ones)
            $catalogItem = CatalogItem::withTrashed()->findOrFail($id);
            
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
            
            // Detach related records and permanently delete the catalog item
            $catalogItem->actions()->detach();
            $catalogItem->articles()->detach();
            $catalogItem->forceDelete();
            
            DB::commit();
            
            if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                return response()->noContent();
            }
            return redirect()->route('catalog.index')->with('success', 'Catalog item permanently deleted.');
            
        } catch (\Exception $e) {
            DB::rollBack();
            if ($request->ajax() || $request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'Failed to permanently delete catalog item.'], 500);
            }
            return redirect()->route('catalog.index')->with('error', 'Failed to permanently delete catalog item.');
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
