<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Client;
use App\Models\CatalogItem;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class OfferController extends Controller
{
    public function index()
    {
        $offers = Offer::with(['client', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial'])
            ->latest()
            ->get()
            ->map(function ($offer) {
                return [
                    'id' => $offer->id,
                    'client' => $offer->client->name,
                    'validity_days' => $offer->validity_days,
                    'production_start_date' => $offer->production_start_date,
                    'production_end_date' => $offer->production_end_date,
                    'status' => $offer->status,
                    'decline_reason' => $offer->decline_reason,
                    'created_at' => $offer->created_at->format('Y-m-d H:i:s'),
                    'items_count' => $offer->catalogItems->count(),
                    'catalog_items' => $offer->catalogItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'name' => $item->name,
                            'description' => $item->description,
                            'file' => $item->file,
                            'price' => $item->price,
                            'quantity' => $item->pivot->quantity,
                            'custom_description' => $item->pivot->description,
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

        return Inertia::render('Offer/Index', [
            'offers' => $offers
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
            'production_start_date' => 'required|date',
            'production_end_date' => 'required|date|after:production_start_date',
            'price1' => 'required|numeric|min:0',
            'price2' => 'required|numeric|min:0',
            'price3' => 'required|numeric|min:0',
            'catalog_items' => 'required|array|min:1',
            'catalog_items.*.id' => 'required|exists:catalog_items,id',
            'catalog_items.*.quantity' => 'required|integer|min:1',
            'catalog_items.*.description' => 'nullable|string',
        ]);

        $offer = Offer::create([
            'name' => $request->name,
            'description' => $request->description,
            'client_id' => $request->client_id,
            'contact_id' =>  $request->contact_id,
            'validity_days' => $request->validity_days,
            'production_start_date' => $request->production_start_date,
            'production_end_date' => $request->production_end_date,
            'price1' => $request->price1,
            'price2' => $request->price2,
            'price3' => $request->price3,
            'status' => 'pending'
        ]);

        // Attach catalog items with their quantities and descriptions
        foreach ($request->catalog_items as $item) {
            $offer->catalogItems()->attach($item['id'], [
                'quantity' => $item['quantity'],
                'description' => $item['description'] ?? null
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
        $offer = Offer::with(['client', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial'])
            ->findOrFail($offerId);

        // Load the view and pass data
        $pdf = Pdf::loadView('offers.pdf', compact('offer'), [
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'isFontSubsettingEnabled' => true,
            'chroot' => storage_path('fonts'),
        ]);

        return $pdf->stream('Offer_' . $offer->id . '_' . date('Y-m-d') . '.pdf');
    }
}
