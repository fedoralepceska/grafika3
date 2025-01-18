<?php

namespace App\Services;

use App\Models\PricePerQuantity;
use App\Models\PricePerClient;
use App\Models\CatalogItem;
use Illuminate\Support\Facades\Log;

class PriceCalculationService
{
    public function calculateEffectivePrice($catalogItemId, $clientId, $quantity)
    {
        Log::info('Starting price calculation', [
            'catalog_item_id' => $catalogItemId,
            'client_id' => $clientId,
            'quantity' => $quantity
        ]);

        if (!$catalogItemId || !$clientId || !$quantity) {
            Log::warning('Missing required parameters', [
                'catalog_item_id' => $catalogItemId,
                'client_id' => $clientId,
                'quantity' => $quantity
            ]);
            return null;
        }

        // 1. Check for quantity-based price
        $quantityPrice = PricePerQuantity::where('catalog_item_id', $catalogItemId)
            ->where('client_id', $clientId)
            ->where(function ($query) use ($quantity) {
                $query->where(function ($q) use ($quantity) {
                    // Case 1: quantity_from is NULL (up to quantity_to)
                    $q->whereNull('quantity_from')
                      ->where('quantity_to', '>=', $quantity);
                })->orWhere(function ($q) use ($quantity) {
                    // Case 2: quantity_to is NULL (quantity_from and above)
                    $q->whereNull('quantity_to')
                      ->where('quantity_from', '<=', $quantity);
                })->orWhere(function ($q) use ($quantity) {
                    // Case 3: between quantity_from and quantity_to
                    $q->whereNotNull('quantity_from')
                      ->whereNotNull('quantity_to')
                      ->where('quantity_from', '<=', $quantity)
                      ->where('quantity_to', '>=', $quantity);
                });
            })
            ->first();

        Log::info('Quantity-based price check', [
            'catalog_item_id' => $catalogItemId,
            'client_id' => $clientId,
            'quantity' => $quantity,
            'found_price' => $quantityPrice ? $quantityPrice->price : null,
            'sql' => $quantityPrice ? PricePerQuantity::where('catalog_item_id', $catalogItemId)
                ->where('client_id', $clientId)
                ->where(function ($query) use ($quantity) {
                    $query->where(function ($q) use ($quantity) {
                        $q->whereNull('quantity_from')
                          ->where('quantity_to', '>=', $quantity);
                    })->orWhere(function ($q) use ($quantity) {
                        $q->whereNull('quantity_to')
                          ->where('quantity_from', '<=', $quantity);
                    })->orWhere(function ($q) use ($quantity) {
                        $q->whereNotNull('quantity_from')
                          ->whereNotNull('quantity_to')
                          ->where('quantity_from', '<=', $quantity)
                          ->where('quantity_to', '>=', $quantity);
                    });
                })->toSql() : null
        ]);

        if ($quantityPrice) {
            Log::info('Using quantity-based price', [
                'price' => $quantityPrice->price,
                'quantity_from' => $quantityPrice->quantity_from,
                'quantity_to' => $quantityPrice->quantity_to,
                'total_price' => $quantityPrice->price * $quantity
            ]);
            return $quantityPrice->price * $quantity;
        }

        // 2. Check for client-specific price
        $clientPrice = PricePerClient::where('catalog_item_id', $catalogItemId)
            ->where('client_id', $clientId)
            ->first();

        Log::info('Client-specific price check', [
            'catalog_item_id' => $catalogItemId,
            'client_id' => $clientId,
            'found_price' => $clientPrice ? $clientPrice->price : null,
            'total_price' => $clientPrice ? $clientPrice->price * $quantity : null
        ]);

        if ($clientPrice) {
            Log::info('Using client-specific price', [
                'unit_price' => $clientPrice->price,
                'total_price' => $clientPrice->price * $quantity
            ]);
            return $clientPrice->price * $quantity;
        }

        // 3. Use catalog item's default price
        $catalogItem = CatalogItem::find($catalogItemId);
        $totalPrice = $catalogItem ? $catalogItem->price * $quantity : null;
        
        Log::info('Using default catalog price', [
            'catalog_item_id' => $catalogItemId,
            'unit_price' => $catalogItem ? $catalogItem->price : null,
            'total_price' => $totalPrice
        ]);

        return $totalPrice;
    }
}
