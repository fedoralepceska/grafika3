<?php

namespace App\Http\Controllers;

use App\Models\AdditionalService;
use App\Models\Faktura;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class AdditionalServiceController extends Controller
{
    /**
     * Get all additional services for a faktura
     */
    public function index(Faktura $faktura): JsonResponse
    {
        $services = $faktura->additionalServices()->get();
        
        return response()->json([
            'success' => true,
            'services' => $services
        ]);
    }

    /**
     * Store a new additional service
     */
    public function store(Request $request, Faktura $faktura): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:10',
            'sale_price' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|in:0,5,10,18',
        ]);

        $service = $faktura->additionalServices()->create($validated);

        return response()->json([
            'success' => true,
            'service' => $service,
            'message' => 'Additional service created successfully'
        ], 201);
    }

    /**
     * Update an existing additional service
     */
    public function update(Request $request, Faktura $faktura, AdditionalService $additionalService): JsonResponse
    {
        // Ensure the service belongs to the faktura
        if ($additionalService->faktura_id !== $faktura->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this faktura'
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'quantity' => 'required|numeric|min:0',
            'unit' => 'required|string|max:10',
            'sale_price' => 'required|numeric|min:0',
            'vat_rate' => 'required|numeric|in:0,5,10,18',
        ]);

        $additionalService->update($validated);

        return response()->json([
            'success' => true,
            'service' => $additionalService->fresh(),
            'message' => 'Additional service updated successfully'
        ]);
    }

    /**
     * Delete an additional service
     */
    public function destroy(Faktura $faktura, AdditionalService $additionalService): JsonResponse
    {
        // Ensure the service belongs to the faktura
        if ($additionalService->faktura_id !== $faktura->id) {
            return response()->json([
                'success' => false,
                'message' => 'Service not found for this faktura'
            ], 404);
        }

        $additionalService->delete();

        return response()->json([
            'success' => true,
            'message' => 'Additional service deleted successfully'
        ]);
    }
}