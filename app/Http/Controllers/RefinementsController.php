<?php

namespace App\Http\Controllers;

use App\Models\Dorabotka;
use App\Models\LargeFormatMaterial;
use App\Models\SmallMaterial;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefinementsController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $perPage = (int) $request->query('per_page', 10);

        $query = Dorabotka::with(['smallMaterial.article', 'largeFormatMaterial.article']);

        if (!empty($search)) {
            $search = trim($search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhereHas('smallMaterial', function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('largeFormatMaterial', function ($qq) use ($search) {
                        $qq->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $dorabotki = $query->orderByDesc('id')->paginate($perPage)->withQueryString();

        // Add computed flag indicating whether this refinement has any active (not finished) job actions
        // A job_action is considered active if ended_at IS NULL (covers not started and in progress)
        $names = $dorabotki->getCollection()->pluck('name')->filter()->unique()->values();
        if ($names->isNotEmpty()) {
            $activeNames = DB::table('job_actions')
                ->whereIn('name', $names)
                ->whereNull('ended_at')
                ->pluck('name')
                ->unique();

            $dorabotki->getCollection()->transform(function ($item) use ($activeNames) {
                $item->has_active_job_actions = $item->name ? $activeNames->contains($item->name) : false;
                return $item;
            });
        }

        return Inertia::render('Refinements/Index', [
            'refinements' => $dorabotki,
            'filters' => [
                'search' => $request->query('search', ''),
                'perPage' => $perPage,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'isMaterialRefinement' => 'boolean|nullable',
            'material_id' => 'nullable|integer',
            'material_type' => 'nullable',
        ]);

        $dorabotka = Dorabotka::create();

        // Determine material type based on format_type
        $materialType = $data['material_type'] === 'SmallMaterial' ? SmallMaterial::class : LargeFormatMaterial::class;

        $dorabotka->material_type = $materialType;
        if (isset($data['material_id'])) {
            $dorabotka->small_material_id = $data['material_type'] === 'SmallMaterial' ? $data['material_id'] : null;
            $dorabotka->large_material_id = $data['material_type'] === 'LargeFormatMaterial' ? $data['material_id'] : null;
            $dorabotka->isMaterialized = $data['isMaterialRefinement'];
        }
        $dorabotka->name = $data['name'];

        $dorabotka->save();

        return response()->json($dorabotka, 201); // Created status code
    }

    public function update(Request $request, $id)
    {
        $validatedData = $request->validate([
            'name' => 'string|nullable',
            'isMaterialRefinement' => 'boolean|nullable',
            'material_type' => 'string|nullable',
            'material_id' => 'integer|nullable',
        ]);

        $dorabotka = Dorabotka::find($id);

        if (!$dorabotka) {
            return response()->json(['error' => 'Refinement not found'], 404);
        }

        // Prevent updating when there are active job actions using this refinement name
        $hasActive = DB::table('job_actions')
            ->where('name', $dorabotka->name)
            ->whereNull('ended_at')
            ->exists();

        if ($hasActive) {
            return response()->json([
                'error' => 'This refinement cannot be edited because there are active job actions using it.'
            ], 422);
        }

        if (isset($validatedData['name'])) {
            $dorabotka->name = $validatedData['name'];
        }

        if (isset($validatedData['isMaterialRefinement'])) {
            $dorabotka->isMaterialized = $validatedData['isMaterialRefinement'] ? 1 : null;
        }

        if (isset($validatedData['material_id']) && isset($validatedData['material_type']) && $validatedData['material_type'] !== null) {
            
            $dorabotka->small_material_id = null;
            $dorabotka->large_material_id = null;
            
            if ($validatedData['material_type'] === 'SmallMaterial') {
                $dorabotka->small_material_id = $validatedData['material_id'];
                $dorabotka->material_type = SmallMaterial::class;
            } elseif ($validatedData['material_type'] === 'LargeFormatMaterial') {
                $dorabotka->large_material_id = $validatedData['material_id'];
                $dorabotka->material_type = LargeFormatMaterial::class;
            }
        } elseif (isset($validatedData['material_type']) && $validatedData['material_type'] === null) {
            $dorabotka->small_material_id = null;
            $dorabotka->large_material_id = null;
            $dorabotka->material_type = null;
        }

        $dorabotka->save();

        return response()->json(['message' => 'Refinement updated successfully', 'refinement' => $dorabotka]);
    }

    public function destroy($id)
    {
        try {
            $dorabotka = Dorabotka::find($id);
            
            if (!$dorabotka) {
                return response()->json(['error' => 'Refinement not found'], 404);
            }

            // Check usage counts to inform user before soft-delete
            $activeJobActionsCount = DB::table('job_actions')
                ->where('name', $dorabotka->name)
                ->whereNull('ended_at')
                ->count();

            // Catalog items referencing this refinement by name inside JSON 'actions'
            $catalogItemsCount = DB::table('catalog_items')
                ->whereJsonContains('actions', [
                    'action_id' => [ 'name' => $dorabotka->name ]
                ])
                ->count();

            // Proceed with soft delete
            $dorabotka->delete();
            return response()->json([
                'message' => 'Refinement deleted successfully',
                'soft_deleted' => true,
                'usage' => [
                    'active_job_actions' => $activeJobActionsCount,
                    'catalog_items' => $catalogItemsCount,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete refinement'], 500);
        }
    }

    public function usage($id)
    {
        $dorabotka = Dorabotka::find($id);
        if (!$dorabotka) {
            return response()->json(['error' => 'Refinement not found'], 404);
        }

        $activeJobActionsCount = DB::table('job_actions')
            ->where('name', $dorabotka->name)
            ->whereNull('ended_at')
            ->count();

        $jobActionsTotal = DB::table('job_actions')
            ->where('name', $dorabotka->name)
            ->count();

        $catalogItemsCount = DB::table('catalog_items')
            ->whereJsonContains('actions', [
                'action_id' => [ 'name' => $dorabotka->name ]
            ])
            ->count();

        return response()->json([
            'usage' => [
                'active_job_actions' => $activeJobActionsCount,
                'job_actions_total' => $jobActionsTotal,
                'catalog_items' => $catalogItemsCount,
            ]
        ]);
    }

    public function getRefinements()
    {
        return Dorabotka::with(['smallMaterial.article', 'largeFormatMaterial.article'])->get();
    }
}
