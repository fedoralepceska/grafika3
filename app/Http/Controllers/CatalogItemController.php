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

class CatalogItemController extends Controller
{
//    public function index()
//    {
//        $catalogItems = CatalogItem::with([
//            'largeMaterial',
//            'smallMaterial',
//            'largeMaterial.article',
//            'smallMaterial.article'
//        ])->get();
//        return Inertia::render('CatalogItem/CatalogList', [
//            'catalogItems' => $catalogItems
//        ]);
//    }

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
                    'material' => $materialDisplay, // Use material display
                    'quantity' => $item->quantity,
                    'copies' => $item->copies,
                    'actions' => collect($item->actions ?? [])->map(function($action) {
                        return [
                            'action_id' => [
                                'id' => $action['action_id']['id'] ?? $action['id'],
                                'name' => $action['action_id']['name'] ?? $action['name']
                            ],
                            'status' => $action['status'] ?? 'Not started yet',
                            'quantity' => $action['quantity']
                        ];
                    })->toArray()
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
            'actions.*.isMaterialized' => 'boolean'
        ]);

        // Create the catalog item without actions for now
        $catalogItem = CatalogItem::create($request->except('actions'));

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
        $request->validate([
            'name' => 'required|string|unique:catalog_items,name,' . $catalogItem->id,
            'machinePrint' => 'nullable|string',
            'machineCut' => 'nullable|string',
            'large_material_id' => 'nullable|exists:large_format_materials,id',
            'small_material_id' => 'nullable|exists:small_material,id',
            'quantity' => 'required|integer|min:1',
            'copies' => 'required|integer|min:1',
            'actions' => 'required|array',
            'actions.*.id' => 'required|string',
            'actions.*.quantity' => 'required|integer|min:0',
        ]);

        $catalogItem->update($request->except('actions'));

        // Store actions as JSON
        $catalogItem->actions = collect($request->actions)->map(function ($action) {
            return [
                'name' => $action['id'],
                'quantity' => $action['quantity']
            ];
        })->toArray();
        $catalogItem->save();

        return redirect()->route('catalog.index');
    }


    public function destroy(CatalogItem $catalogItem)
    {
        $catalogItem->actions()->detach();
        $catalogItem->delete();

        return redirect()->route('catalog.index')->with('success', 'Catalog item deleted successfully.');
    }
}
