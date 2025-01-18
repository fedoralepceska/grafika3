<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\PricePerClient;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ClientPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = PricePerClient::with(['catalogItem', 'client'])
            ->when($request->search, function ($query, $search) {
                $query->whereHas('catalogItem', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                })->orWhereHas('client', function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%");
                });
            })
            ->when($request->catalog_item_id, function ($query, $catalogItemId) {
                $query->where('catalog_item_id', $catalogItemId);
            });

        $clientPrices = $query->paginate(10)
            ->withQueryString();

        return Inertia::render('Pricing/ClientPrices/List', [
            'clientPrices' => $clientPrices->items(),
            'pagination' => [
                'current_page' => $clientPrices->currentPage(),
                'total_pages' => $clientPrices->lastPage(),
                'total_items' => $clientPrices->total(),
                'per_page' => $clientPrices->perPage()
            ],
            'filters' => $request->only(['search', 'catalog_item_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Pricing/ClientPrices/Form', [
            'catalogItems' => CatalogItem::select('id', 'name', 'price')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'client_id' => 'required|exists:clients,id',
            'price' => 'required|numeric|min:0',
        ]);

        // Check for existing price
        $exists = PricePerClient::where([
            'catalog_item_id' => $validated['catalog_item_id'],
            'client_id' => $validated['client_id'],
        ])->exists();

        if ($exists) {
            return back()->withErrors([
                'message' => 'A custom price already exists for this client and catalog item.',
            ]);
        }

        PricePerClient::create($validated);

        return redirect()->route('client-prices.index')
            ->with('success', 'Client price created successfully.');
    }

    public function edit(PricePerClient $clientPrice)
    {
        return Inertia::render('Pricing/ClientPrices/Form', [
            'clientPrice' => $clientPrice->load(['catalogItem', 'client']),
            'catalogItems' => CatalogItem::select('id', 'name', 'price')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, PricePerClient $clientPrice)
    {
        $validated = $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $clientPrice->update($validated);

        return redirect()->route('client-prices.index')
            ->with('success', 'Client price updated successfully.');
    }

    public function destroy(PricePerClient $clientPrice)
    {
        $clientPrice->delete();

        return back()->with('success', 'Client price deleted successfully.');
    }
} 