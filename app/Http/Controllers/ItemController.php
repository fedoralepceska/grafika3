<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    public function getAllByCertificateId($id)
    {

        // Use Eloquent to query items with the specified certificate_id
        $items = Item::where('certificate_id', $id)->with('client')->get();

        // Return the items as a JSON response
        return response()->json($items);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data for the client
        $validatedData = $request->validate([
            'client_id' => 'required',
            'certificate_id' => 'required',
            'income' => 'numeric',
            'expense' => 'numeric',
            'code' => 'string',
            'reference_to' => 'string',
            'comment' => 'nullable|string',
        ]);

        // Create a new client record
        $item = new Item();
        $item->client_id = $validatedData['client_id'];
        $item->certificate_id = $validatedData['certificate_id'];
        $item->income = $validatedData['income'];
        $item->expense = $validatedData['expense'];
        $item->code = $validatedData['code'];
        $item->reference_to = $validatedData['reference_to'];
        $item->comment = $validatedData['comment'] ?? null; // handle nullable field
        $item->save();

        return response()->json(['message' => 'Item added successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Item $item)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'income' => 'numeric',
            'expense' => 'numeric',
            'code' => 'string',
            'reference_to' => 'string',
            'comment' => 'nullable|string',
        ]);

        // Update the item with validated data
        $item->update($validatedData);

        return response()->json(['message' => 'Item updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->delete();
        return response()->json(['message' => 'Item deleted successfully']);
    }
}
