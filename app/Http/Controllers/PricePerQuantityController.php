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
            ->select('catalog_item_id', 'client_id')
            ->distinct()
            ->orderBy('catalog_item_id')
            ->orderBy('client_id');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->whereHas('catalogItem', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            })->orWhereHas('client', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%");
            });
        }

        $groupedPrices = $query->paginate(10);

        // Get the actual price data for each group
        $groupedPrices->getCollection()->transform(function ($group) {
            $prices = PricePerQuantity::where('catalog_item_id', $group->catalog_item_id)
                ->where('client_id', $group->client_id)
                ->orderBy('quantity_from')
                ->get();

            $catalogItem = CatalogItem::find($group->catalog_item_id);
            $client = Client::find($group->client_id);

            return [
                'catalog_item' => $catalogItem,
                'client' => $client,
                'price_count' => $prices->count(),
                'ranges_summary' => $this->getRangesSummary($prices)
            ];
        });

        return Inertia::render('Pricing/QuantityPrices/Index', [
            'prices' => $groupedPrices
        ]);
    }

    public function view(CatalogItem $catalogItem, Client $client)
    {
        $prices = PricePerQuantity::where('catalog_item_id', $catalogItem->id)
            ->where('client_id', $client->id)
            ->orderBy('quantity_from')
            ->get();

        return Inertia::render('Pricing/QuantityPrices/View', [
            'catalogItem' => $catalogItem,
            'client' => $client,
            'prices' => $prices
        ]);
    }

    public function editGroup(CatalogItem $catalogItem, Client $client)
    {
        $prices = PricePerQuantity::where('catalog_item_id', $catalogItem->id)
            ->where('client_id', $client->id)
            ->orderBy('quantity_from')
            ->get();

        $catalogItems = CatalogItem::select('id', 'name', 'price')->get();
        $clients = Client::select('id', 'name')->get();

        return Inertia::render('Pricing/QuantityPrices/EditGroup', [
            'catalogItem' => $catalogItem,
            'client' => $client,
            'prices' => $prices,
            'catalogItems' => $catalogItems,
            'clients' => $clients
        ]);
    }

    public function updateGroup(Request $request, CatalogItem $catalogItem, Client $client)
    {
        $validated = $request->validate([
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'client_id' => 'required|exists:clients,id',
            'ranges' => 'required|array|min:1',
            'ranges.*.quantity_from' => 'nullable|integer|min:0',
            'ranges.*.quantity_to' => 'nullable|integer|min:0',
            'ranges.*.price' => 'required|numeric|min:0',
        ]);

        \DB::beginTransaction();

        try {
            // Delete existing prices for this combination
            PricePerQuantity::where('catalog_item_id', $catalogItem->id)
                ->where('client_id', $client->id)
                ->delete();

            // Sort ranges by quantity_from
            $ranges = collect($validated['ranges'])->sortBy(function ($range) {
                return $range['quantity_from'] ?? 0;
            })->values()->all();

            foreach ($ranges as $index => $range) {
                // Ensure at least one boundary is set
                if (empty($range['quantity_from']) && empty($range['quantity_to'])) {
                    throw new \Exception('At least one quantity boundary must be set for each range.');
                }

                // Validate range logic
                if (!empty($range['quantity_from']) && !empty($range['quantity_to'])) {
                    if ($range['quantity_from'] >= $range['quantity_to']) {
                        throw new \Exception('The "from" quantity must be less than the "to" quantity.');
                    }
                }

                // Check for overlap with next range
                if (isset($ranges[$index + 1])) {
                    $nextRange = $ranges[$index + 1];
                    if (!empty($range['quantity_to']) && !empty($nextRange['quantity_from'])) {
                        if ($range['quantity_to'] >= $nextRange['quantity_from']) {
                            throw new \Exception('Ranges cannot overlap.');
                        }
                    }
                }

                // Check for overlapping ranges in database (excluding current combination)
                $overlapping = $this->checkOverlappingRanges(
                    $validated['catalog_item_id'],
                    $validated['client_id'],
                    $range['quantity_from'],
                    $range['quantity_to']
                );

                if ($overlapping) {
                    throw new \Exception('One or more ranges overlap with existing price ranges.');
                }

                // Create the price record
                PricePerQuantity::create([
                    'catalog_item_id' => $validated['catalog_item_id'],
                    'client_id' => $validated['client_id'],
                    'quantity_from' => $range['quantity_from'],
                    'quantity_to' => $range['quantity_to'],
                    'price' => $range['price'],
                ]);
            }

            \DB::commit();

            return redirect()->route('quantity-prices.index')
                ->with('success', 'Quantity prices updated successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function destroyGroup(CatalogItem $catalogItem, Client $client)
    {
        PricePerQuantity::where('catalog_item_id', $catalogItem->id)
            ->where('client_id', $client->id)
            ->delete();

        return redirect()->route('quantity-prices.index')
            ->with('success', 'Quantity prices deleted successfully.');
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
            'ranges' => 'required|array|min:1',
            'ranges.*.quantity_from' => 'nullable|integer|min:0',
            'ranges.*.quantity_to' => 'nullable|integer|min:0',
            'ranges.*.price' => 'required|numeric|min:0',
        ]);

        \DB::beginTransaction();

        try {
            // Sort ranges by quantity_from
            $ranges = collect($validated['ranges'])->sortBy(function ($range) {
                return $range['quantity_from'] ?? 0;
            })->values()->all();

            foreach ($ranges as $index => $range) {
                // Ensure at least one boundary is set
                if (empty($range['quantity_from']) && empty($range['quantity_to'])) {
                    throw new \Exception('At least one quantity boundary must be set for each range.');
                }

                // Validate range logic
                if (!empty($range['quantity_from']) && !empty($range['quantity_to'])) {
                    if ($range['quantity_from'] >= $range['quantity_to']) {
                        throw new \Exception('The "from" quantity must be less than the "to" quantity.');
                    }
                }

                // Check for overlap with next range
                if (isset($ranges[$index + 1])) {
                    $nextRange = $ranges[$index + 1];
                    if (!empty($range['quantity_to']) && !empty($nextRange['quantity_from'])) {
                        if ($range['quantity_to'] >= $nextRange['quantity_from']) {
                            throw new \Exception('Ranges cannot overlap.');
                        }
                    }
                }

                // Check for overlapping ranges in database
                $overlapping = $this->checkOverlappingRanges(
                    $validated['catalog_item_id'],
                    $validated['client_id'],
                    $range['quantity_from'],
                    $range['quantity_to']
                );

                if ($overlapping) {
                    throw new \Exception('One or more ranges overlap with existing price ranges.');
                }

                // Create the price record
                PricePerQuantity::create([
                    'catalog_item_id' => $validated['catalog_item_id'],
                    'client_id' => $validated['client_id'],
                    'quantity_from' => $range['quantity_from'],
                    'quantity_to' => $range['quantity_to'],
                    'price' => $range['price'],
                ]);
            }

            \DB::commit();

            return redirect()->route('quantity-prices.index')
                ->with('success', count($ranges) > 1 ? 'Quantity prices created successfully.' : 'Quantity price created successfully.');

        } catch (\Exception $e) {
            \DB::rollBack();
            return back()->withErrors(['message' => $e->getMessage()]);
        }
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

    private function getRangesSummary($prices)
    {
        if ($prices->isEmpty()) {
            return 'No ranges defined';
        }

        $ranges = $prices->map(function ($price) {
            if ($price->quantity_from === null) {
                return "Up to {$price->quantity_to}";
            }
            if ($price->quantity_to === null) {
                return "{$price->quantity_from}+";
            }
            return "{$price->quantity_from}-{$price->quantity_to}";
        });

        return $ranges->implode(', ');
    }
} 