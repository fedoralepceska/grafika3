<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Client;
use App\Models\CatalogItem;
use App\Services\PriceCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OfferController extends Controller
{
    function getPrice($clientId, $item) {
        $priceCalculationService = app()->make(PriceCalculationService::class);
        return $priceCalculationService->calculateEffectivePrice(
            $item->id,
            $clientId,
            $item->pivot->quantity
        );
    }
    public function index(Request $request)
    {
        $perPage = 15;
        $status = $request->get('status', 'pending'); // Default to 'pending' if no status is provided
        
        $query = Offer::with(['client', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial']);
        
        // Apply status filter (keep existing functionality)
        if ($status) {
            $query->where('status', $status);
        }
        
        // Apply search by offer ID only
        if ($request->has('searchQuery') && !empty($request->input('searchQuery'))) {
            $searchQuery = $request->input('searchQuery');
            $query->where('id', 'like', "%{$searchQuery}%");
        }
        
        // Filter by client
        if ($request->has('filterClient') && !empty($request->input('filterClient'))) {
            $clientId = $request->input('filterClient');
            $query->where('client_id', $clientId);
        }
        
        // Filter by validity days (exact match)
        if ($request->has('filterValidityDays') && !empty($request->input('filterValidityDays'))) {
            $validityDays = $request->input('filterValidityDays');
            $query->where('validity_days', $validityDays);
        }
        
        // Sort by date
        $sortOrder = $request->input('sortOrder', 'desc');
        $query->orderBy('created_at', $sortOrder);
        
        $offers = $query->paginate($perPage)
            ->through(function ($offer) {
                return [
                    'id' => $offer->id,
                    'name' => $offer->name,
                    'client' => $offer->client->name,
                    'client_id' => $offer->client_id,
                    'validity_days' => $offer->validity_days,
                    'production_start_date' => $offer->production_start_date,
                    'production_end_date' => $offer->production_end_date,
                    'status' => $offer->status,
                    'decline_reason' => $offer->decline_reason,
                    'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                    'items_count' => $offer->catalogItems->count(),
                    'catalog_items' => $offer->catalogItems->map(function ($item) use ($offer) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'file' => $item->file,
                            'price' => $item->price,
                            'quantity' => $item->pivot->quantity,
                            'custom_description' => $item->pivot->description,
                            'calculated_price' => $this->getPrice($offer->client->id, $item),
                            'custom_price' => $item->pivot->custom_price,
                            'large_material' => $item->largeMaterial ? [
                                'id' => $item->largeMaterial->id,
                                'name' => $item->largeMaterial->name
                            ] : null,
                            'small_material' => $item->smallMaterial ? [
                                'id' => $item->smallMaterial->id,
                                'name' => $item->smallMaterial->name
                            ] : null,
                        ];
                    })
                ];
            });

        // Get counts for each status for the tabs
        $counts = [
            'pending' => Offer::where('status', 'pending')->count(),
            'accepted' => Offer::where('status', 'accepted')->count(),
            'declined' => Offer::where('status', 'declined')->count(),
        ];
        
        // Get all clients for the filter dropdown
        $clients = Client::select('id', 'name')->orderBy('name')->get();
        
        // Get unique validity days values for the filter dropdown
        $validityDaysOptions = Offer::select('validity_days')
            ->distinct()
            ->orderBy('validity_days')
            ->pluck('validity_days')
            ->toArray();

        return Inertia::render('Offer/Index', [
            'offers' => $offers,
            'filters' => $request->only(['status', 'searchQuery', 'filterClient', 'filterValidityDays', 'sortOrder']),
            'counts' => $counts,
            'clients' => $clients,
            'validityDaysOptions' => $validityDaysOptions
        ]);
    }

    public function create()
    {
        $clients = Client::select('id', 'name')->with('contacts')->get();
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

        return Inertia::render('Offer/Create', [
            'clients' => $clients,
            'catalogItems' => $catalogItems
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'contact_id' => 'required',
            'validity_days' => 'required|integer|min:1',
            'production_start_date' => 'nullable|date',
            'production_end_date' => 'nullable|date|after:production_start_date',
            'catalog_items' => 'required|array|min:1',
            'catalog_items.*.id' => 'required|exists:catalog_items,id',
            'catalog_items.*.quantity' => 'required|integer|min:1',
            'catalog_items.*.description' => 'nullable|string',
            'production_time' => 'nullable|string'
        ]);

        $offer = Offer::create([
            'name' => $request->name,
            'description' => $request->description,
            'client_id' => $request->client_id,
            'contact_id' =>  $request->contact_id,
            'validity_days' => $request->validity_days,
            'production_start_date' => $request->production_start_date ?? null,
            'production_end_date' => $request->production_end_date ?? null,
            'price1' => $request->price1 ?? 0,
            'price2' => $request->price2 ?? 0,
            'price3' => $request->price3 ?? 0,
            'status' => 'pending',
            'production_time' => $request->production_time
        ]);

        // Attach catalog items with their quantities and descriptions
        foreach ($request->catalog_items as $item) {
            $offer->catalogItems()->attach($item['id'], [
                'quantity' => $item['quantity'],
                'description' => $item['description'] ?? null,
                'custom_price' => $item['custom_price'] ?? null
            ]);
        }

        return redirect()->route('offer.index');
    }

    public function show(Offer $offer)
    {
        $offer->load(['client', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial']);

        return Inertia::render('Offer/Show', [
            'offer' => [
                'id' => $offer->id,
                'client' => $offer->client->name,
                'validity_days' => $offer->validity_days,
                'production_start_date' => $offer->production_start_date,
                'production_end_date' => $offer->production_end_date,
                'status' => $offer->status,
                'decline_reason' => $offer->decline_reason,
                'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                'catalog_items' => $offer->catalogItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->pivot->description ?? $item->description,
                        'quantity' => $item->pivot->quantity,
                        'price' => $item->price,
                        'file' => $item->file,
                        'large_material' => $item->largeMaterial ? [
                            'id' => $item->largeMaterial->id,
                            'name' => $item->largeMaterial->name
                        ] : null,
                        'small_material' => $item->smallMaterial ? [
                            'id' => $item->smallMaterial->id,
                            'name' => $item->smallMaterial->name
                        ] : null,
                    ];
                })
            ]
        ]);
    }

    public function updateStatus(Request $request, Offer $offer)
    {
        $request->validate([
            'status' => 'required|in:accepted,declined',
            'decline_reason' => 'nullable|string'
        ]);

        $offer->update([
            'status' => $request->status,
            'decline_reason' => $request->status === 'declined' ? $request->decline_reason : null
        ]);

        $message = $request->status === 'accepted'
            ? 'Offer has been accepted successfully.'
            : 'Offer has been declined successfully.';

        return back()->with('success', $message);
    }

    public function items(Offer $offer)
    {
        $offer->load(['client', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial']);

        return Inertia::render('Offer/Items', [
            'offer' => [
                'id' => $offer->id,
                'client' => $offer->client->name,
                'catalog_items' => $offer->catalogItems->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'file' => $item->file,
                        'price' => $item->price,
                        'quantity' => $item->pivot->quantity,
                        'custom_description' => $item->pivot->description,
                        'custom_price' => $item->pivot->custom_price,
                        'large_material' => $item->largeMaterial ? [
                            'id' => $item->largeMaterial->id,
                            'name' => $item->largeMaterial->name
                        ] : null,
                        'small_material' => $item->smallMaterial ? [
                            'id' => $item->smallMaterial->id,
                            'name' => $item->smallMaterial->name
                        ] : null,
                    ];
                })
            ]
        ]);
    }

    public function generateOfferPdf($offerId)
    {
        $offer = Offer::with(['client', 'contact', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial'])
            ->findOrFail($offerId);

        $offer->catalogItems->transform(function ($item) use ($offer) {
            $item->calculated_price = $this->getPrice($offer->client->id, $item);
            return $item;
        });

        // Load the view and pass data
        $pdf = Pdf::loadView('offers.pdf', compact('offer'), [
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
        ]);

        return $pdf->stream('GrafikaPlus-PonudaBr-' . $offer->id . '-' . date('Y') . '.pdf');
    }

    public function edit(Offer $offer)
    {
        $offer->load(['client', 'contact', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial']);
        $clients = Client::select('id', 'name')->with('contacts')->get();
        $catalogItems = $offer->catalogItems
            ->filter(fn($item) => $item->is_for_offer)
            ->map(function ($item) use ($offer) {
                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'calculatedPrice' => $this->getPrice($offer->client->id, $item),
                    'file' => $item->file,
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

        return response()->json([
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'description' => $offer->description,
                'client_id' => $offer->client_id,
                'contact_id' => $offer->contact_id,
                'validity_days' => $offer->validity_days,
                'production_time' => $offer->production_time,
                'catalog_items' => $offer->catalogItems->map(function ($item) use ($offer) {
                    return [
                        'id' => $item->id,
                        'selection_id' => uniqid(), // Add unique ID for frontend tracking
                        'name' => $item->name,
                        'description' => $item->pivot->description,
                        'quantity' => $item->pivot->quantity,
                        'custom_price' => $item->pivot->custom_price,
                        'calculated_price' => $this->getPrice($offer->client->id, $item),
                        'file' => $item->file,
                        'large_material' => $item->largeMaterial ? [
                            'id' => $item->largeMaterial->id,
                            'name' => $item->largeMaterial->name
                        ] : null,
                        'small_material' => $item->smallMaterial ? [
                            'id' => $item->smallMaterial->id,
                            'name' => $item->smallMaterial->name
                        ] : null,
                    ];
                })
            ],
            'clients' => $clients,
            'catalogItems' => $catalogItems
        ]);
    }

    public function update(Request $request, Offer $offer)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'contact_id' => 'required',
            'validity_days' => 'required|integer|min:1',
            'production_time' => 'nullable|string',
            'catalog_items' => 'required|array|min:1',
            'catalog_items.*.id' => 'required|exists:catalog_items,id',
            'catalog_items.*.quantity' => 'required|integer|min:1',
            'catalog_items.*.description' => 'nullable|string',
            'catalog_items.*.custom_price' => 'nullable|numeric|min:0'
        ]);

        $offer->update([
            'name' => $request->name,
            'description' => $request->description,
            'client_id' => $request->client_id,
            'contact_id' => $request->contact_id,
            'validity_days' => $request->validity_days,
            'production_time' => $request->production_time
        ]);

        // First, detach all existing items
        $offer->catalogItems()->detach();
        
        // Then attach each item individually
        foreach ($request->catalog_items as $item) {
            $offer->catalogItems()->attach($item['id'], [
                'quantity' => $item['quantity'],
                'description' => $item['description'] ?? null,
                'custom_price' => $item['custom_price'] ?? null
            ]);
        }

        return response()->json(['message' => 'Offer updated successfully']);
    }
    public function destroy(Offer $offer)
    {
        try {
            $offer->delete();
            return response()->json(['message' => 'Offer deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete offer'], 500);
        }
    }

}
