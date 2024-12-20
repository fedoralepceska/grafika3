<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Enums\MachineCut as MachineCutEnum;
use App\Enums\MachinePrint as MachinePrintEnum;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use ReflectionClass;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CatalogItemController extends Controller

{
    public function fetchAllForOffer()
    {
        $catalogItems = CatalogItem::all()->where('is_for_offer', true);
        return response()->json($catalogItems);

    }

    public function index(Request $request)
    {
        try {
            // Get pagination parameters with defaults
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $searchTerm = $request->input('search', '');

            // Start with base query
            $query = CatalogItem::with([
                'largeMaterial', // Relation to large_material
                'smallMaterial' // Relation to small_material
            ]);

            // Add optional search functionality
            if (!empty($searchTerm)) {
                $query->where('name', 'like', "%{$searchTerm}%");
            }

            // Paginate the results
            $catalogItems = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the paginated items
            $transformedItems = $catalogItems->getCollection()->map(function($item) {
                $largeMaterialName = $item->largeMaterial ? $item->largeMaterial->name : null;
                $smallMaterialName = $item->smallMaterial ? $item->smallMaterial->name : null;

                // Determine which material to show
                $materialDisplay = $largeMaterialName
                    ? "{$largeMaterialName} (Large)"
                    : ($smallMaterialName ? "{$smallMaterialName} (Small)" : 'N/A');

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'machinePrint' => $item->machinePrint,
                    'machineCut' => $item->machineCut,
                    'material' => $materialDisplay,
                    'quantity' => $item->quantity,
                    'copies' => $item->copies,
                    'file' => $item->file,
                    'category' => $item->category,
                    'is_for_offer' => (bool)$item->is_for_offer,
                    'is_for_sales' => (bool)$item->is_for_sales,
                    'large_material_id' => $item->large_material_id,
                    'small_material_id' => $item->small_material_id,
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
                ];
            });

            // Return Inertia view with the transformed data
            return Inertia::render('CatalogItem/CatalogList', [
                'catalogItems' => $transformedItems,
                'pagination' => [
                    'current_page' => $catalogItems->currentPage(),
                    'total_pages' => $catalogItems->lastPage(),
                    'total_items' => $catalogItems->total(),
                    'per_page' => $catalogItems->perPage()
                ]
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

        return Inertia::render('CatalogItem/Create', [
            'actions' => $actions,
            'largeMaterials' => LargeFormatMaterial::with(['article' => function($query) {
                $query->select('id', 'name', 'code');
            }])->get(),
            'smallMaterials' => SmallMaterial::with(['article' => function($query) {
                $query->select('id', 'name', 'code');
            }])->get(),
            'machinesPrint' => $machinesPrint,
            'machinesCut' => $machinesCut,
        ]);
    }

    public function store(Request $request)
    {
        \Log::info('Storing catalog item:', $request->all());

        $request->merge([
            'is_for_offer' => filter_var($request->input('is_for_offer'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'is_for_sales' => filter_var($request->input('is_for_sales'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
            'small_material_id' => $request->input('small_material_id') === 'null' || $request->input('small_material_id') === '' ? null : $request->input('small_material_id'),
            'large_material_id' => $request->input('large_material_id') === 'null' || $request->input('large_material_id') === '' ? null : $request->input('large_material_id'),
        ]);
        $actions = $request->input('actions');
        $actions = array_map(function ($action) {
            $action['isMaterialized'] = filter_var($action['isMaterialized'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            return $action;
        }, $actions);
        $request->merge(['actions' => $actions]);

        $request->validate([
            'name' => 'required|string|unique:catalog_items',
            'machinePrint' => 'nullable|string',
            'machineCut' => 'nullable|string',
            'large_material_id' => 'nullable|exists:large_format_materials,id',
            'small_material_id' => 'nullable|exists:small_material,id',
            'quantity' => 'required|integer|min:1',
            'copies' => 'required|integer|min:1',
            'actions' => 'required|array',
            'actions.*.id' => 'required|exists:dorabotka,id',
            'actions.*.quantity' => 'integer|min:0|required_if:actions.*.isMaterialized,true|nullable',
            'actions.*.isMaterialized' => 'boolean|in:0,1,true,false',
            'is_for_offer' => 'nullable|boolean',
            'is_for_sales' => 'nullable|boolean',
            'category' => 'nullable|string|in:' . implode(',', \App\Models\CatalogItem::CATEGORIES),
            'file' => 'required|mimes:jpg,jpeg,png,pdf|max:20480', // 20MB max
        ]);

        // Create the catalog item without actions for now
        $catalogItem = CatalogItem::create($request->except('actions'));

        $file = $request->file('file');
        $fileName = time() . '_' . $file->getClientOriginalName();
        $catalogItem->file = $fileName;
        // Store the file in the 'public/uploads' directory
        try {
            $file->storeAs('public/uploads', $fileName);
        } catch (\Exception $e) {
            dd('Error storing file: ' . $e->getMessage());
        }

        $catalogItem->save();

        // Process actions and populate both `catalog_items` and `job_actions`
        $catalogItemActions = collect($request->actions)->map(function ($action) {
            $actionData = DB::table('dorabotka')->where('id', $action['id'])->first();

            if (!$actionData) {
                throw new \Exception("Action with ID {$action['id']} does not exist.");
            }

            // Use a default value for `isMaterialized` if not provided
            $isMaterialized = $action['isMaterialized'] ?? false;

            // Return action data for the catalog item
            return [
                'action_id' => [
                    'id' => $action['id'],
                    'name' => $actionData->name,
                ],
                'status' => 'Not started yet',
                'quantity' => $isMaterialized ? ($action['quantity'] ?? 0) : null,
            ];
        });

        // Save the processed actions into the `actions` column of `catalog_items`
        $catalogItem->actions = $catalogItemActions->toArray();
        $catalogItem->save();

        \Log::info('Stored catalog item actions:', ['actions' => $catalogItem->actions]);

        return redirect()->route('catalog.index');
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

        return Inertia::render('CatalogItem/Edit', [
            'catalogItem' => $catalogItem,
            'actions' => $actions,
            'largeMaterials' => LargeFormatMaterial::with(['article' => function($query) {
                $query->select('id', 'name', 'code');
            }])->get(),
            'smallMaterials' => SmallMaterial::with(['article' => function($query) {
                $query->select('id', 'name', 'code');
            }])->get(),
            'machinesPrint' => $machinesPrint,
            'machinesCut' => $machinesCut,
        ]);
    }

    public function update(Request $request, CatalogItem $catalogItem)
    {
        try {
            $request->merge([
                'is_for_offer' => filter_var($request->input('is_for_offer'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'is_for_sales' => filter_var($request->input('is_for_sales'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE),
                'small_material_id' => $request->input('small_material_id') === 'null' || $request->input('small_material_id') === '' ? null : $request->input('small_material_id'),
                'large_material_id' => $request->input('large_material_id') === 'null' || $request->input('large_material_id') === '' ? null : $request->input('large_material_id'),
            ]);

            $request->validate([
                'name' => 'required|string|unique:catalog_items,name,' . $catalogItem->id,
                'machinePrint' => 'nullable|string',
                'machineCut' => 'nullable|string',
                'large_material_id' => 'nullable|exists:large_format_materials,id',
                'small_material_id' => 'nullable|exists:small_material,id',
                'quantity' => 'required|integer|min:1',
                'copies' => 'required|integer|min:1',
                'actions' => 'required|array',
                'actions.*.id' => 'required|exists:dorabotka,id',
                'actions.*.quantity' => 'integer|min:0|required_if:actions.*.isMaterialized,true|nullable',
                'actions.*.isMaterialized' => 'boolean',
                'is_for_offer' => 'nullable|boolean',
                'is_for_sales' => 'nullable|boolean',
                'category' => 'nullable|string|in:' . implode(',', CatalogItem::CATEGORIES),
                'file' => 'nullable|mimes:jpg,jpeg,png,pdf|max:20480',
            ]);

            DB::beginTransaction();

            try {
                // Handle file upload if a new file is provided
                if ($request->hasFile('file')) {
                    // Delete old file if it exists and is not the placeholder
                    if ($catalogItem->file && $catalogItem->file !== 'placeholder.jpeg') {
                        Storage::disk('public')->delete('uploads/' . $catalogItem->file);
                    }
                    
                    $file = $request->file('file');
                    $fileName = time() . '_' . $file->getClientOriginalName();
                    $file->storeAs('public/uploads', $fileName);
                    $catalogItem->file = $fileName;
                }

                // Update the catalog item without actions first
                $catalogItem->update($request->except(['actions', 'file']));

                // Process actions and update them
                $catalogItemActions = collect($request->actions)->map(function ($action) {
                    $actionData = DB::table('dorabotka')->where('id', $action['id'])->first();

                    if (!$actionData) {
                        throw new \Exception("Action with ID {$action['id']} does not exist.");
                    }

                    // Use a default value for `isMaterialized` if not provided
                    $isMaterialized = $action['isMaterialized'] ?? false;

                    // Return action data for the catalog item
                    return [
                        'action_id' => [
                            'id' => $action['id'],
                            'name' => $actionData->name,
                        ],
                        'status' => 'Not started yet',
                        'quantity' => $isMaterialized ? ($action['quantity'] ?? 0) : null,
                    ];
                });

                // Update the actions in the catalog_items table
                $catalogItem->actions = $catalogItemActions->toArray();
                $catalogItem->save();

                DB::commit();

                return response()->json([
                    'message' => 'Catalog item updated successfully',
                    'catalogItem' => $catalogItem
                ]);

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
        $catalogItem->actions()->detach();
        $catalogItem->delete();

        return redirect()->route('catalog.index')->with('success', 'Catalog item deleted successfully.');
    }
}
