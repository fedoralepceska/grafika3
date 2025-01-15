<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\Offer;
use App\Models\OfferClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
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
            $query = Offer::with(['catalogItems' => function($query) {
                $query->with(['largeMaterial', 'smallMaterial']);
            }]);

            // Add optional search functionality
            if (!empty($searchTerm)) {
                $query->where('name', 'like', "%{$searchTerm}%");
            }

            // Paginate the results
            $offers = $query->paginate($perPage, ['*'], 'page', $page);

            // Transform the paginated items
            $transformedItems = $offers->getCollection()->map(function($offer) {
                return [
                    'id' => $offer->id,
                    'name' => $offer->name,
                    'description' => $offer->description,
                    'price1' => $offer->price1,
                    'price2' => $offer->price2,
                    'price3' => $offer->price3,
                    'catalogItems' => $offer->catalogItems->map(function($item) {
                        return [
                            'id' => $item->id,
                            'catalog_item_id' => $item->id,
                            'name' => $item->name,
                            'file' => $item->file,
                            'price' => $item->price,
                            'large_material' => $item->largeMaterial ? [
                                'id' => $item->largeMaterial->id,
                                'name' => $item->largeMaterial->name
                            ] : null,
                            'small_material' => $item->smallMaterial ? [
                                'id' => $item->smallMaterial->id,
                                'name' => $item->smallMaterial->name
                            ] : null
                        ];
                    })->values()->toArray()
                ];
            });

            // Return Inertia view with the transformed data
            return Inertia::render('Offer/Index', [
                'offers' => $transformedItems,
                'pagination' => [
                    'current_page' => $offers->currentPage(),
                    'total_pages' => $offers->lastPage(),
                    'total_items' => $offers->total(),
                    'per_page' => $offers->perPage(),
                    'links' => [
                        'prev' => $offers->previousPageUrl() ? true : false,
                        'next' => $offers->nextPageUrl() ? true : false
                    ]
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
        $catalogItems = CatalogItem::with(['largeMaterial', 'smallMaterial'])
            ->where('is_for_offer', true)
            ->get()
            ->map(function($item) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'file' => $item->file,
                    'large_material_id' => $item->large_material_id,
                    'small_material_id' => $item->small_material_id,
                    'large_material' => $item->largeMaterial ? [
                        'id' => $item->largeMaterial->id,
                        'name' => $item->largeMaterial->name,
                    ] : null,
                    'small_material' => $item->smallMaterial ? [
                        'id' => $item->smallMaterial->id,
                        'name' => $item->smallMaterial->name,
                    ] : null,
                ];
            });

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

    public function destroy(Offer $offer)
    {
        try {
            $offer->catalogItems()->detach();
            $offer->delete();
            return response()->json(['message' => 'Offer deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to delete offer'], 500);
        }
    }

    public function getOffers()
    {
        return response()->json(Offer::all(), 200);
    }

    public function displayOfferClientPage()
    {
        return Inertia::render('OfferClient/Create');
    }

    public function storeOfferClient(Request $request)
    {
        foreach ($request->associations as $association) {
            DB::table('offer_client')->insert($association);
        }

        return response()->json(['message' => 'Associations created successfully.']);
    }

    public function getOffersClients()
    {
        $offersClients = DB::table('offer_client')
            ->join('offers', 'offer_client.offer_id', '=', 'offers.id')
            ->join('clients', 'offer_client.client_id', '=', 'clients.id')
            ->select(
                'offer_client.*',
                'offers.name as offer_name',
                'clients.name as client_name'
            )
            ->get();

        return Inertia::render('OfferClient/Index', [
            'offers_clients' => $offersClients
        ]);
    }

    public function acceptOffer(Request $request)
    {
        // Validate the incoming request
        $request->validate([
            'offer_client' => 'required|exists:offer_client,id',
            'accept' => 'required|boolean',
            'description' => 'sometimes'
        ]);

        // Update the is_accepted field for the given offer_client ID
        DB::table('offer_client')
            ->where('id', $request->offer_client)
            ->update(['is_accepted' => $request->accept, 'decription' => $request->description]);

        return response()->json(['message' => 'Offer status updated successfully.'], 200);
    }

    public function getDetails($id): JsonResponse
    {
        // Fetch the offer_client record based on the offer_client table's pivot data
        $offerClient = DB::table('offer_client')
            ->where('id', $id)
            ->first();

        if (!$offerClient) {
            return response()->json(['message' => 'OfferClient not found'], 404);
        }

        // Get related offer and client using their IDs
        $offer = Offer::findOrFail($offerClient->offer_id);
        $client = Client::findOrFail($offerClient->client_id);

        // Fetch catalog items linked to the offer
        $catalogItems = $offer->catalogItems;

        // Return the data
        return response()->json([
            'offer' => $offer,
            'client' => $client,
            'catalog_items' => $catalogItems,
        ]);
    }
}
