<?php

namespace App\Http\Controllers;

use App\Models\Offer;
use App\Models\Client;
use App\Models\CatalogItem;
use App\Services\PriceCalculationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OfferController extends Controller
{
    private function hasRealOfferItem(array $item): bool
    {
        if (!empty($item['id'])) {
            return true;
        }

        return $this->normalizeItemName($item['name'] ?? '') !== '';
    }

    private function normalizeOfferItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => is_array($item) && $this->hasRealOfferItem($item))
            ->map(function ($item) {
                return [
                    'id' => $item['id'] ?? null,
                    'name' => isset($item['name']) ? trim((string) $item['name']) : null,
                    'quantity' => isset($item['quantity']) ? (int) $item['quantity'] : null,
                    'description' => $item['description'] ?? null,
                    'custom_price' => isset($item['custom_price']) && $item['custom_price'] !== ''
                        ? (float) $item['custom_price']
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    private function validateOfferRequest(Request $request): array
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'client_id' => 'required|exists:clients,id',
            'contact_id' => 'required',
            'validity_days' => 'required|integer|min:1',
            'production_start_date' => 'nullable|date',
            'production_end_date' => 'nullable|date|after:production_start_date',
            'catalog_items' => 'required|array|min:1',
            'catalog_items.*.id' => 'nullable',
            'catalog_items.*.name' => 'nullable|string|max:255',
            'catalog_items.*.quantity' => 'required|integer|min:1',
            'catalog_items.*.description' => 'nullable|string',
            'catalog_items.*.custom_price' => 'nullable|numeric|min:0',
            'production_time' => 'nullable|string'
        ]);

        $validator->after(function ($validator) use ($request) {
            $items = $this->normalizeOfferItems((array) $request->input('catalog_items', []));

            if (empty($items)) {
                $validator->errors()->add('catalog_items', 'Offer must contain at least one item.');
                return;
            }

            foreach ($items as $index => $item) {
                if (empty($item['id']) && $this->normalizeItemName($item['name'] ?? '') === '') {
                    $validator->errors()->add("catalog_items.$index.name", 'Custom item name is required.');
                }
            }
        });

        $validated = $validator->validate();
        $validated['catalog_items'] = $this->normalizeOfferItems((array) ($validated['catalog_items'] ?? []));

        return $validated;
    }

    private function buildOfferCreationFingerprint(array $validated): string
    {
        $fingerprintData = [
            'user' => Auth::id(),
            'name' => trim((string) ($validated['name'] ?? '')),
            'description' => trim((string) ($validated['description'] ?? '')),
            'client_id' => $validated['client_id'] ?? null,
            'contact_id' => $validated['contact_id'] ?? null,
            'validity_days' => $validated['validity_days'] ?? null,
            'production_start_date' => $validated['production_start_date'] ?? null,
            'production_end_date' => $validated['production_end_date'] ?? null,
            'production_time' => trim((string) ($validated['production_time'] ?? '')),
            'items' => collect($validated['catalog_items'] ?? [])
                ->map(function ($item) {
                    return [
                        'id' => $item['id'] ?? null,
                        'name' => $this->normalizeItemName($item['name'] ?? ''),
                        'quantity' => (int) ($item['quantity'] ?? 0),
                        'description' => trim((string) ($item['description'] ?? '')),
                        'custom_price' => $item['custom_price'] !== null ? (string) $item['custom_price'] : null,
                    ];
                })
                ->values()
                ->all(),
        ];

        return hash('sha256', json_encode($fingerprintData));
    }

    private function availableOfferCatalogItemsQuery()
    {
        return CatalogItem::withTrashed()
            ->where('is_for_offer', true)
            ->where(function ($query) {
                $query->whereNull('deleted_at')
                    ->orWhere(function ($deletedQuery) {
                        $deletedQuery->whereNotNull('deleted_at')
                            ->where('is_for_sales', false)
                            ->where('category', 'material')
                            ->whereNull('large_material_id')
                            ->whereNull('small_material_id')
                            ->whereDoesntHave('articles');
                    });
            });
    }

    private function normalizeItemName(?string $name): string
    {
        return mb_strtolower(trim((string) $name));
    }

    private function findExistingOfferCatalogItem(string $name): ?CatalogItem
    {
        $normalizedName = $this->normalizeItemName($name);

        if ($normalizedName === '') {
            return null;
        }

        return $this->availableOfferCatalogItemsQuery()
            ->where(function ($query) use ($normalizedName) {
                $query->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName])
                    ->orWhereHas('largeMaterial', function ($relatedQuery) use ($normalizedName) {
                        $relatedQuery->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]);
                    })
                    ->orWhereHas('smallMaterial', function ($relatedQuery) use ($normalizedName) {
                        $relatedQuery->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]);
                    })
                    ->orWhereHas('articles', function ($relatedQuery) use ($normalizedName) {
                        $relatedQuery->whereRaw('LOWER(TRIM(name)) = ?', [$normalizedName]);
                    });
            })
            ->first();
    }

    private function resolveCatalogItemForOffer(array $item): CatalogItem
    {
        if (!empty($item['id'])) {
            return CatalogItem::withTrashed()->findOrFail($item['id']);
        }

        $name = trim((string) ($item['name'] ?? ''));

        if ($name === '') {
            throw ValidationException::withMessages([
                'catalog_items' => ['Custom item name is required.'],
            ]);
        }

        $existingItem = $this->findExistingOfferCatalogItem($name);

        if ($existingItem) {
            return $existingItem;
        }

        $catalogItem = CatalogItem::create([
            'name' => $name,
            'description' => $item['description'] ?? '',
            'price' => $item['custom_price'] ?? 0,
            'is_for_offer' => true,
            'is_for_sales' => false,
            'category' => 'material',
        ]);

        // Hide one-off custom items from the main catalog while keeping them reusable in offers.
        $catalogItem->delete();

        return $catalogItem;
    }

    private function attachCatalogItemToOffer(Offer $offer, array $item): void
    {
        $catalogItem = $this->resolveCatalogItemForOffer($item);

        $offer->catalogItems()->attach($catalogItem->id, [
            'quantity' => $item['quantity'],
            'description' => $item['description'] ?? null,
            'custom_price' => $item['custom_price'] ?? null,
        ]);
    }

    private function buildOfferCatalogItems()
    {
        return $this->availableOfferCatalogItemsQuery()
            ->with(['articles'])
            ->get()
            ->map(function ($item) {
                $isReusableCustom = !is_null($item->deleted_at)
                    && !$item->is_for_sales
                    && $item->category === 'material'
                    && is_null($item->large_material_id)
                    && is_null($item->small_material_id)
                    && $item->articles->isEmpty();

                return [
                    'id' => $item->id,
                    'name' => $item->name,
                    'description' => $item->description,
                    'price' => $item->price,
                    'file' => $item->file,
                    'category' => $isReusableCustom ? 'custom' : $item->category,
                    'is_reusable_custom' => $isReusableCustom,
                    'articles' => $item->articles->map(function ($article) {
                        return [
                            'id' => $article->id,
                            'name' => $article->name,
                            'quantity' => $article->pivot->quantity
                        ];
                    })
                ];
            });
    }

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
        
        $query = Offer::with(['client', 'creator', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial']);
        
        // Apply status filter (keep existing functionality)
        if ($status) {
            $query->where('status', $status);
        }
        
        // Filter by fiscal year - default to current year
        $fiscalYear = $request->input('fiscal_year', date('Y'));
        if ($fiscalYear) {
            $query->where('fiscal_year', (int)$fiscalYear);
        }
        
        // Apply search by offer number instead of id
        if ($request->has('searchQuery') && !empty($request->input('searchQuery'))) {
            $searchQuery = $request->input('searchQuery');
            // Remove # prefix if present for convenience
            $searchQuery = ltrim($searchQuery, '#');
            $query->where('offer_number', 'like', "%{$searchQuery}%");
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
                    'offer_number' => $offer->offer_number,
                    'fiscal_year' => $offer->fiscal_year,
                    'name' => $offer->name,
                    'client' => $offer->client->name,
                    'client_id' => $offer->client_id,
                    'created_by' => $offer->created_by,
                    'created_by_name' => optional($offer->creator)->name,
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

        // Get counts for each status for the tabs (filtered by fiscal year)
        $counts = [
            'pending' => Offer::where('status', 'pending')->where('fiscal_year', (int)$fiscalYear)->count(),
            'accepted' => Offer::where('status', 'accepted')->where('fiscal_year', (int)$fiscalYear)->count(),
            'declined' => Offer::where('status', 'declined')->where('fiscal_year', (int)$fiscalYear)->count(),
        ];
        
        // Get all clients for the filter dropdown
        $clients = Client::select('id', 'name')->orderBy('name')->get();
        
        // Get unique validity days values for the filter dropdown
        $validityDaysOptions = Offer::select('validity_days')
            ->distinct()
            ->orderBy('validity_days')
            ->pluck('validity_days')
            ->toArray();
        
        // Get available years for the year filter
        $availableYears = Offer::select('fiscal_year')
            ->distinct()
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year')
            ->toArray();
        
        // Ensure current year is in the list
        $currentYear = (int)date('Y');
        if (!in_array($currentYear, $availableYears)) {
            array_unshift($availableYears, $currentYear);
        }

        return Inertia::render('Offer/Index', [
            'offers' => $offers,
            'filters' => $request->only(['status', 'searchQuery', 'filterClient', 'filterValidityDays', 'sortOrder', 'fiscal_year']),
            'counts' => $counts,
            'clients' => $clients,
            'validityDaysOptions' => $validityDaysOptions,
            'availableYears' => $availableYears,
            'currentFiscalYear' => (int)$fiscalYear,
        ]);
    }

    public function create()
    {
        $clients = Client::select('id', 'name')->with('contacts')->get();
        $catalogItems = $this->buildOfferCatalogItems();

        return Inertia::render('Offer/Create', [
            'clients' => $clients,
            'catalogItems' => $catalogItems
        ]);
    }

    public function store(Request $request)
    {
        $validated = $this->validateOfferRequest($request);

        $lock = null;
        try {
            $fingerprint = $this->buildOfferCreationFingerprint($validated);
            $lock = Cache::lock('offers:create:' . $fingerprint, 10);

            if (!$lock->get()) {
                return response()->json([
                    'message' => 'This offer is already being created. Please wait a moment.',
                ], 409);
            }
        } catch (\Throwable $exception) {
            // Fail open if the cache lock is unavailable, but keep the request processing.
        }

        // Generate offer number for fiscal year
        $offerNumberData = Offer::generateOfferNumber();

        try {
            DB::transaction(function () use ($validated, $offerNumberData) {
            $offer = Offer::create([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'client_id' => $validated['client_id'],
                'contact_id' =>  $validated['contact_id'],
                'validity_days' => $validated['validity_days'],
                'production_start_date' => $validated['production_start_date'] ?? null,
                'production_end_date' => $validated['production_end_date'] ?? null,
                'price1' => $validated['price1'] ?? 0,
                'price2' => $validated['price2'] ?? 0,
                'price3' => $validated['price3'] ?? 0,
                'status' => 'pending',
                'production_time' => $validated['production_time'] ?? null,
                'created_by' => Auth::id(),
                'offer_number' => $offerNumberData['offer_number'],
                'fiscal_year' => $offerNumberData['fiscal_year'],
            ]);

            foreach ($validated['catalog_items'] as $item) {
                $this->attachCatalogItemToOffer($offer, $item);
            }
        });
        } finally {
            if ($lock) {
                try {
                    $lock->release();
                } catch (\Throwable $exception) {
                    // Ignore lock release failures.
                }
            }
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
                    // Check if this is a custom item (created for this offer)
                    $isCustomItem = $item->large_material_id === null && $item->small_material_id === null;
                    
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->pivot->description ?? $item->description,
                        'quantity' => $item->pivot->quantity,
                        'price' => $item->pivot->custom_price ?? $item->price,
                        'file' => $item->file,
                        'isCustomItem' => $isCustomItem,
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
                    // Check if this is a custom item (created for this offer)
                    $isCustomItem = $item->large_material_id === null && $item->small_material_id === null;
                    
                    return [
                        'id' => $item->id,
                        'name' => $item->name,
                        'description' => $item->description,
                        'file' => $item->file,
                        'price' => $item->pivot->custom_price ?? $item->price,
                        'quantity' => $item->pivot->quantity,
                        'custom_description' => $item->pivot->description,
                        'custom_price' => $item->pivot->custom_price,
                        'isCustomItem' => $isCustomItem,
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

        return $pdf->stream('GrafikaPlus-PonudaBr-' . $offer->offer_number . '-' . $offer->fiscal_year . '.pdf');
    }

    public function edit(Offer $offer)
    {
        $offer->load(['client', 'contact', 'catalogItems.largeMaterial', 'catalogItems.smallMaterial', 'catalogItems.largeMaterialCategory', 'catalogItems.smallMaterialCategory']);
        $clients = Client::select('id', 'name')->with('contacts')->get();
        $catalogItems = $this->buildOfferCatalogItems();

        return Inertia::render('Offer/Edit', [
            'offer' => [
                'id' => $offer->id,
                'name' => $offer->name,
                'description' => $offer->description,
                'client_id' => $offer->client_id,
                'contact_id' => $offer->contact_id,
                'validity_days' => $offer->validity_days,
                'production_time' => $offer->production_time,
                'catalog_items' => $offer->catalogItems->map(function ($item) use ($offer) {
                    // Check if this is a custom item (created for this offer)
                    $isCustomItem = $item->large_material_id === null && $item->small_material_id === null;
                    
                    return [
                        'id' => $item->id,
                        'selection_id' => uniqid(),
                        'name' => $item->name,
                        'description' => $item->pivot->description,
                        'quantity' => $item->pivot->quantity,
                        'custom_price' => $item->pivot->custom_price ?? $item->price,
                        'calculated_price' => $isCustomItem ? ($item->pivot->custom_price ?? $item->price) * $item->pivot->quantity : $this->getPrice($offer->client->id, $item),
                        'file' => $item->file,
                        'isCustomItem' => $isCustomItem,
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
        $validated = $this->validateOfferRequest($request);

        DB::transaction(function () use ($validated, $offer) {
            $offer->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'client_id' => $validated['client_id'],
                'contact_id' => $validated['contact_id'],
                'validity_days' => $validated['validity_days'],
                'production_time' => $validated['production_time'] ?? null
            ]);

            $offer->catalogItems()->detach();

            foreach ($validated['catalog_items'] as $item) {
                $this->attachCatalogItemToOffer($offer, $item);
            }
        });

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
