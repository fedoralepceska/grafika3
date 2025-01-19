<?php

namespace App\Http\Controllers;

use App\Services\PriceCalculationService;
use Illuminate\Http\Request;

class PriceController extends Controller
{
    protected $priceCalculationService;

    public function __construct(PriceCalculationService $priceCalculationService)
    {
        $this->priceCalculationService = $priceCalculationService;
    }

    public function calculatePrice(Request $request)
    {
        $request->validate([
            'catalog_item_id' => 'required|exists:catalog_items,id',
            'client_id' => 'required|exists:clients,id',
            'quantity' => 'required|integer|min:1'
        ]);

        $price = $this->priceCalculationService->calculateEffectivePrice(
            $request->catalog_item_id,
            $request->client_id,
            $request->quantity
        );

        return response()->json(['price' => $price]);
    }
} 