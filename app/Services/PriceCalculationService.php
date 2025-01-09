<?php

namespace App\Services;

use App\Models\PricePerQuantity;
use App\Models\PricePerClient;
use App\Models\CatalogItem;

class PriceCalculationService
{
    public function calculateEffectivePrice($catalogItemId, $clientId, $quantity)
    {
        if (!$catalogItemId || !$clientId || !$quantity) {
            return null;
        }

        // 1. Check for quantity-based price
        $quantityPrice = PricePerQuantity::where('catalog_item_id', $catalogItemId)
            ->where('client_id', $clientId)
            ->where(function ($query) use ($quantity) {
                $query->where(function ($q) use ($quantity) {
                    $q->where('quantity_from', '<=', $quantity)
                      ->where(function ($q2) use ($quantity) {
                          $q2->where('quantity_to', '>=', $quantity)
                             ->orWhereNull('quantity_to');
                      });
                });
            })
            ->first();

        if ($quantityPrice) {
            // Return the per-unit price
            return $quantityPrice->price;
        }

        // 2. Check for client-specific price
        $clientPrice = PricePerClient::where('catalog_item_id', $catalogItemId)
            ->where('client_id', $clientId)
            ->first();

        if ($clientPrice) {
            // Return the per-unit price
            return $clientPrice->price;
        }

        // 3. Use catalog item's default price
        $catalogItem = CatalogItem::find($catalogItemId);
        // Return the per-unit price
        return $catalogItem->price;
    }
}
