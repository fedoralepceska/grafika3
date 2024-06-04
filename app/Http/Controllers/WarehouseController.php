<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class WarehouseController extends Controller
{
    public function index(){

        return Inertia::render('Warehouse/Index');
    }

    public function getWarehouses(Request $request)
    {
        $warehouses = Warehouse::all();

        return response()->json($warehouses, 200); // Return JSON response
    }

    public function createWarehouse(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
            'phone' => 'required|string|max:255',
        ]);

        $warehouse = new Warehouse($validatedData);
        $warehouse->save();

        return response()->json([
            'message' => 'Warehouse created successfully!',
            'warehouse' => $warehouse, // Optionally return the created warehouse object
        ], 201);
    }
    public function deleteWarehouse($id)
    {
        $warehouse = Warehouse::find($id);

        if (!$warehouse) {
            return response()->json(['error' => 'Warehouse not found'], 404);
        }

        $warehouse->delete();
        return response()->json(['message' => 'Warehouse deleted successfully!'], 200);
    }
}
