<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): Response
    {
        return Inertia::render('Client/ClientForm', [
            //
        ]);
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
        // Validate the incoming request data
        $validatedData = $request->validate([
            'name' => 'required|string',
            'company' => 'required|string',
            'email' => 'required|string|email|unique:clients',
            'phone' => 'required|string|max:20',
        ]);

        // Create a new client record
        $client = new Client();
        $client->name = $validatedData['name'];
        $client->company = $validatedData['company'];
        $client->email = $validatedData['email'];
        $client->phone = $validatedData['phone'];
        $client->save();

        // Return a response, redirect, or perform any other action
        return response()->json(['message' => 'Client added successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Client $client)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Client $client)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Client $client)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        //
    }
}
