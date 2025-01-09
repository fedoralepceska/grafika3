<?php

namespace App\Http\Controllers;

use App\Models\PricePerClient;
use App\Models\CatalogItem;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricePerClientController extends Controller
{
    public function index(Request $request)
    {
        $query = PricePerClient::with(['catalogItem', 'client'])
            ->orderBy('catalog_item_id');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('catalogItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $prices = $query->paginate(10);

        return Inertia::render('Pricing/ClientPrices/Index', [
            'prices' => $prices
        ]);
    }

    public function create()
    {
        $catalogItems = CatalogItem::select('id', 'name', 'price')->get();
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Pricing/ClientPrices/Create', [
            'catalogItems' => $catalogItems,
            'clients' => $clients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'client_id' => 'required|exists:clients,id',
            'price' => 'required|numeric|min:0',
        ]);

        // Check for existing price for this combination
        $existing = PricePerClient::where([
            'catalog_item_id' => $validated['catalog_item_id'],
            'client_id' => $validated['client_id'],
        ])->first();

        if ($existing) {
            return back()->withErrors(['message' => 'A price already exists for this client and catalog item.']);
        }

        PricePerClient::create($validated);

        return redirect()->route('client-prices.index')
            ->with('success', 'Client price created successfully.');
    }

    public function edit(PricePerClient $clientPrice)
    {
        $clientPrice->load(['catalogItem', 'client']);
        $catalogItems = CatalogItem::select('id', 'name', 'price')->get();
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Pricing/ClientPrices/Edit', [
            'clientPrice' => $clientPrice,
            'catalogItems' => $catalogItems,
            'clients' => $clients
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

        return redirect()->route('client-prices.index')
            ->with('success', 'Client price deleted successfully.');
    }

    public function getClientPrices(CatalogItem $catalogItem)
    {
        $prices = $catalogItem->clientPrices()
            ->with('client:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return response()->json($prices);
    }
} 