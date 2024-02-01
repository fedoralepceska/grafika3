<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $clients = Client::with('contacts')->get();
        if (request()->wantsJson()) {
            return response()->json($clients);
        }
        return Inertia::render('Client/Index', [
            'clients' => $clients
        ]);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Client/ClientForm');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data for the client
        $validatedData = $request->validate([
            'name' => 'required|string',
            'address' => 'required|string',
            'city' => 'required|string'
        ]);

        // Create a new client record
        $client = new Client();
        $client->name = $validatedData['name'];
        $client->address = $validatedData['address'];
        $client->city = $validatedData['city'];
        $client->save();

        // Get the client's ID
        $clientId = $client->id;

        // Validate and save the contacts associated with this client
        $contacts = $request->input('contacts');

        foreach ($contacts as $contact) {
            // Validate the contact data (name, email, phone)
            $validatedContact = Validator::make($contact, [
                'name' => 'required|string',
                'email' => 'required|email',
                'phone' => 'required|string|max:20',
            ])->validate();

            // Save the contact associated with the client
            $client->contacts()->create([
                'name' => $validatedContact['name'],
                'email' => $validatedContact['email'],
                'phone' => $validatedContact['phone'],
            ]);
        }

        return response()->json(['message' => 'Client added successfully'], 201);
    }


    /**
     * Display the specified resource.
     */
    public function show(Request $request)
    {
        // Retrieve the job by its ID
        $id = $request->input("id", 0);

        $user = Client::find($id);

        if (!$user) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        return response()->json($user);
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
        $validatedData = $request->validate([
            'phone' => 'required|integer',
            'email' => 'required|string',
        ]);

        $client->update($validatedData);

        return redirect()->route('clients.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index');
    }
}
