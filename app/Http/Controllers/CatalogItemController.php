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
        // Fetch paginated catalog items with related data
        $query = CatalogItem::with(['largeMaterial.article', 'smallMaterial.article']);

        // Optional: Apply search filter
        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%");
        }

        // Paginate results (adjust perPage as needed)
        $perPage = $request->input('per_page', 10);
        $catalogItems = $query->paginate($perPage);

        // Return paginated response with Inertia
        return inertia('CatalogItem/CatalogList', [
            'catalogItems' => $catalogItems,
        ]);
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
