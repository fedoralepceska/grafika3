<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Enums\MachineCut as MachineCutEnum;
use App\Enums\MachinePrint as MachinePrintEnum;
use App\Models\LargeFormatMaterial;
use App\Models\Offer;
use App\Models\SmallMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;
use ReflectionClass;
use Illuminate\Support\Facades\DB;

class OfferController extends Controller

{
    public function index(Request $request)
    {
        try {
            // Get pagination parameters with defaults
            $page = $request->input('page', 1);
            $perPage = $request->input('per_page', 10);
            $searchTerm = $request->input('search', '');

            // Start with base query
            $query = Offer::with([
                'catalogItems',
            ]);

            // Add optional search functionality
            if (!empty($searchTerm)) {
                $query->where('name', 'like', "%{$searchTerm}%");
            }

            // Paginate the results
            $catalogItems = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the paginated items
            $transformedItems = $catalogItems->getCollection()->map(function($item) {

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price1' => $item->price1,
                    'price2' => $item->price2,
                    'price3' => $item->price3,
                    'catalogItems' => collect($item->catalogItems ?? [])->map(function($item) {
                        return [
                            'catalog_item_id' => $item['catalog_item_id']
                        ];
                    })->toArray()
                ];
            });

            // Return Inertia view with the transformed data
            return Inertia::render('Offer/Index', [
                'offers' => $transformedItems,
                'pagination' => [
                    'current_page' => $catalogItems->currentPage(),
                    'total_pages' => $catalogItems->lastPage(),
                    'total_items' => $catalogItems->total(),
                    'per_page' => $catalogItems->perPage()
                ]
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in getOffers:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'error' => 'Failed to fetch offers',
                'details' => config('app.debug') ? $e->getMessage() : 'An unexpected error occurred'
            ], 500);
        }
    }

    public function create()
    {
        $catalogItems = DB::table('catalog_items')
            ->select('id', 'name', 'category')
            ->whereNotNull('catalog_items.name')
            ->where('catalog_items.is_for_offer', true)
            ->get();

        return Inertia::render('Offer/Create', [
            'catalogItems' => $catalogItems,
        ]);
    }
    public function store(Request $request)
    {
        \Log::info('Storing offer:', $request->all());

        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|unique:offers',
            'description' => 'nullable|string',
            'price1' => 'nullable|numeric',
            'price2' => 'nullable|numeric',
            'price3' => 'nullable|numeric',
            'selectedCatalogItems' => 'nullable|array',
            'selectedCatalogItems.*' => 'exists:catalog_items,id',
        ]);

        // Create a new offer
        $offer = new Offer();
        $offer->name = $request->input('name');
        $offer->description = $request->input('description');
        $offer->price1 = $request->input('price1');
        $offer->price2 = $request->input('price2');
        $offer->price3 = $request->input('price3');
        $offer->save();

        // Associate the selected catalog items with the offer
        if ($request->has('selectedCatalogItems') && is_array($request->input('selectedCatalogItems'))) {
            $offer->catalogItems()->sync($request->input('selectedCatalogItems'));
        }

        \Log::info('Offer stored successfully with ID: ' . $offer->id);

        // Return a response, such as redirect or JSON response
        return response()->json([
            'message' => 'Offer created successfully',
            'offer' => $offer,
        ], 201);
    }

    public function destroy(CatalogItem $catalogItem)
    {
        $catalogItem->actions()->detach();
        $catalogItem->delete();

        return redirect()->route('catalog.index')->with('success', 'Catalog item deleted successfully.');
    }
}
