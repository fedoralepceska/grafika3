<?php

namespace App\Http\Controllers;

use App\Models\PricePerQuantity;
use App\Models\CatalogItem;
use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PricePerQuantityController extends Controller
{
    public function index(Request $request)
    {
        $query = PricePerQuantity::with(['catalogItem', 'client'])
            ->orderBy('catalog_item_id')
            ->orderBy('client_id')
            ->orderBy('quantity_from');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('catalogItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $prices = $query->paginate(10);

        return Inertia::render('Pricing/QuantityPrices/Index', [
            'prices' => $prices
        ]);
    }

    public function create()
    {
        $catalogItems = CatalogItem::select('id', 'name', 'price')->get();
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Pricing/QuantityPrices/Create', [
            'catalogItems' => $catalogItems,
            'clients' => $clients
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'client_id' => 'required|exists:clients,id',
            'quantity_from' => 'nullable|integer|min:0',
            'quantity_to' => 'nullable|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        // Ensure at least one boundary is set
        if (empty($validated['quantity_from']) && empty($validated['quantity_to'])) {
            return back()->withErrors(['message' => 'At least one quantity boundary must be set.']);
        }

        // Validate range logic
        if (!empty($validated['quantity_from']) && !empty($validated['quantity_to'])) {
            if ($validated['quantity_from'] >= $validated['quantity_to']) {
                return back()->withErrors(['message' => 'The "from" quantity must be less than the "to" quantity.']);
            }
        }

        // Check for overlapping ranges
        $overlapping = $this->checkOverlappingRanges(
            $validated['catalog_item_id'],
            $validated['client_id'],
            $validated['quantity_from'],
            $validated['quantity_to']
        );

        if ($overlapping) {
            return back()->withErrors(['message' => 'This range overlaps with an existing price range.']);
        }

        PricePerQuantity::create($validated);

        return redirect()->route('quantity-prices.index')
            ->with('success', 'Quantity price created successfully.');
    }

    public function edit(PricePerQuantity $quantityPrice)
    {
        $quantityPrice->load(['catalogItem', 'client']);
        $catalogItems = CatalogItem::select('id', 'name', 'price')->get();
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Pricing/QuantityPrices/Form', [
            'quantityPrice' => $quantityPrice,
            'catalogItems' => $catalogItems,
            'clients' => $clients
        ]);
    }

    public function update(Request $request, PricePerQuantity $quantityPrice)
    {
        $validated = $request->validate([
            'quantity_from' => 'nullable|integer|min:0',
            'quantity_to' => 'nullable|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        // Ensure at least one boundary is set
        if (empty($validated['quantity_from']) && empty($validated['quantity_to'])) {
            return back()->withErrors(['message' => 'At least one quantity boundary must be set.']);
        }

        // Validate range logic
        if (!empty($validated['quantity_from']) && !empty($validated['quantity_to'])) {
            if ($validated['quantity_from'] >= $validated['quantity_to']) {
                return back()->withErrors(['message' => 'The "from" quantity must be less than the "to" quantity.']);
            }
        }

        // Check for overlapping ranges (excluding current record)
        $overlapping = $this->checkOverlappingRanges(
            $quantityPrice->catalog_item_id,
            $quantityPrice->client_id,
            $validated['quantity_from'],
            $validated['quantity_to'],
            $quantityPrice->id
        );

        if ($overlapping) {
            return back()->withErrors(['message' => 'This range overlaps with an existing price range.']);
        }

        $quantityPrice->update($validated);

        return redirect()->route('quantity-prices.index')
            ->with('success', 'Quantity price updated successfully.');
    }

    public function destroy(PricePerQuantity $quantityPrice)
    {
        $quantityPrice->delete();

        return redirect()->route('quantity-prices.index')
            ->with('success', 'Quantity price deleted successfully.');
    }

    public function getQuantityPrices(CatalogItem $catalogItem, Client $client)
    {
        $prices = $catalogItem->quantityPrices()
            ->where('client_id', $client->id)
            ->with('client:id,name')
            ->orderBy('quantity_from')
            ->paginate(5);

        return response()->json($prices);
    }

    private function checkOverlappingRanges($catalogItemId, $clientId, $from, $to, $excludeId = null)
    {
        $query = PricePerQuantity::where('catalog_item_id', $catalogItemId)
            ->where('client_id', $clientId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->where(function ($q) use ($from, $to) {
            if ($from === null) {
                // New range is "up to X"
                $q->where(function ($q) use ($to) {
                    $q->whereNull('quantity_from')
                        ->where('quantity_to', '>=', $to)
                        ->orWhere(function ($q) use ($to) {
                            $q->whereNotNull('quantity_from')
                                ->where('quantity_from', '<=', $to);
                        });
                });
            } elseif ($to === null) {
                // New range is "X or more"
                $q->where(function ($q) use ($from) {
                    $q->whereNull('quantity_to')
                        ->where('quantity_from', '<=', $from)
                        ->orWhere(function ($q) use ($from) {
                            $q->whereNotNull('quantity_to')
                                ->where('quantity_to', '>=', $from);
                        });
                });
            } else {
                // New range has both boundaries
                $q->where(function ($q) use ($from, $to) {
                    $q->whereBetween('quantity_from', [$from, $to])
                        ->orWhereBetween('quantity_to', [$from, $to])
                        ->orWhere(function ($q) use ($from, $to) {
                            $q->where('quantity_from', '<=', $from)
                                ->where('quantity_to', '>=', $to);
                        });
                });
            }
        })->exists();
    }
} 