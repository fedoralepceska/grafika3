<?php

namespace App\Http\Controllers;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\PricePerQuantity;
use Illuminate\Http\Request;
use Inertia\Inertia;

class QuantityPriceController extends Controller
{
    public function index(Request $request)
    {
        $query = PricePerQuantity::with(['catalogItem', 'client'])
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

        $quantityPrices = $query->paginate(10)
            ->withQueryString();

        return Inertia::render('Pricing/QuantityPrices/List', [
            'quantityPrices' => $quantityPrices->items(),
            'pagination' => [
                'current_page' => $quantityPrices->currentPage(),
                'total_pages' => $quantityPrices->lastPage(),
                'total_items' => $quantityPrices->total(),
                'per_page' => $quantityPrices->perPage()
            ],
            'filters' => $request->only(['search', 'catalog_item_id']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Pricing/QuantityPrices/Form', [
            'catalogItems' => CatalogItem::select('id', 'name', 'price')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'client_id' => 'required|exists:clients,id',
            'ranges' => 'required|array|min:1',
            'ranges.*.quantity_from' => 'nullable|integer|min:0',
            'ranges.*.quantity_to' => 'nullable|integer|min:0',
            'ranges.*.price' => 'required|numeric|min:0',
        ]);

        // Sort ranges by quantity_from to check for overlaps
        $ranges = collect($validated['ranges'])->sortBy(function ($range) {
            return $range['quantity_from'] ?? 0;
        })->values()->all();

        // Validate ranges
        foreach ($ranges as $index => $range) {
            // Ensure at least one boundary is set
            if (empty($range['quantity_from']) && empty($range['quantity_to'])) {
                return back()->withErrors([
                    'message' => 'At least one quantity boundary must be set for each range.',
                ]);
            }

            // If both boundaries are set, ensure from is less than to
            if (!empty($range['quantity_from']) && !empty($range['quantity_to'])) {
                if ($range['quantity_from'] >= $range['quantity_to']) {
                    return back()->withErrors([
                        'message' => 'The "from" quantity must be less than the "to" quantity.',
                    ]);
                }
            }

            // Check for overlap with next range
            if (isset($ranges[$index + 1])) {
                $nextRange = $ranges[$index + 1];
                if (!empty($range['quantity_to']) && !empty($nextRange['quantity_from'])) {
                    if ($range['quantity_to'] >= $nextRange['quantity_from']) {
                        return back()->withErrors([
                            'message' => 'Ranges cannot overlap.',
                        ]);
                    }
                }
            }
        }

        // Create all ranges
        foreach ($ranges as $range) {
            PricePerQuantity::create([
                'catalog_item_id' => $validated['catalog_item_id'],
                'client_id' => $validated['client_id'],
                'quantity_from' => $range['quantity_from'],
                'quantity_to' => $range['quantity_to'],
                'price' => $range['price'],
            ]);
        }

        return redirect()->route('quantity-prices.index')
            ->with('success', count($ranges) > 1 ? 'Quantity prices created successfully.' : 'Quantity price created successfully.');
    }

    public function edit(PricePerQuantity $quantityPrice)
    {
        return Inertia::render('Pricing/QuantityPrices/Form', [
            'quantityPrice' => $quantityPrice->load(['catalogItem', 'client']),
            'catalogItems' => CatalogItem::select('id', 'name', 'price')->get(),
            'clients' => Client::select('id', 'name')->get(),
        ]);
    }

    public function update(Request $request, PricePerQuantity $quantityPrice)
    {
        $validated = $request->validate([
            'quantity_from' => 'nullable|integer|min:0',
            'quantity_to' => 'nullable|integer|min:0',
            'price' => 'required|numeric|min:0',
        ]);

        // Ensure at least one quantity boundary is set
        if (empty($validated['quantity_from']) && empty($validated['quantity_to'])) {
            return back()->withErrors([
                'message' => 'At least one quantity boundary must be set.',
            ]);
        }

        // If both boundaries are set, ensure from is less than to
        if (!empty($validated['quantity_from']) && !empty($validated['quantity_to'])) {
            if ($validated['quantity_from'] >= $validated['quantity_to']) {
                return back()->withErrors([
                    'message' => 'The "from" quantity must be less than the "to" quantity.',
                ]);
            }
        }

        // Check for overlapping ranges, excluding the current price
        $overlapping = $this->checkOverlappingRanges(
            $quantityPrice->catalog_item_id,
            $quantityPrice->client_id,
            $validated['quantity_from'],
            $validated['quantity_to'],
            $quantityPrice->id
        );

        if ($overlapping) {
            return back()->withErrors([
                'message' => 'This range overlaps with an existing quantity price range.',
            ]);
        }

        $quantityPrice->update($validated);

        return redirect()->route('quantity-prices.index')
            ->with('success', 'Quantity price updated successfully.');
    }

    public function destroy(PricePerQuantity $quantityPrice)
    {
        $quantityPrice->delete();

        return back()->with('success', 'Quantity price deleted successfully.');
    }

    private function checkOverlappingRanges($catalogItemId, $clientId, $from, $to, $excludeId = null)
    {
        $query = PricePerQuantity::where('catalog_item_id', $catalogItemId)
            ->where('client_id', $clientId);

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->where(function ($query) use ($from, $to) {
            // If only 'from' is set (X and above)
            if ($from && !$to) {
                $query->where(function ($q) use ($from) {
                    $q->whereNull('quantity_to')
                        ->where('quantity_from', '<=', $from)
                        ->orWhere(function ($q) use ($from) {
                            $q->whereNotNull('quantity_to')
                                ->where('quantity_to', '>', $from);
                        });
                });
            }
            // If only 'to' is set (up to X)
            elseif (!$from && $to) {
                $query->where(function ($q) use ($to) {
                    $q->whereNull('quantity_from')
                        ->where('quantity_to', '>=', $to)
                        ->orWhere(function ($q) use ($to) {
                            $q->whereNotNull('quantity_from')
                                ->where('quantity_from', '<', $to);
                        });
                });
            }
            // If both are set (range)
            else {
                $query->where(function ($q) use ($from, $to) {
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