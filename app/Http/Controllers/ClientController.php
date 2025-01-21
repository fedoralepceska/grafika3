<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\ClientCardStatement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class ClientController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 10); // Use default of 10 if not provided
        $clients = Client::with('contacts');

        // Apply search filtering (explained later)
        $clients = $this->applySearch($clients, $request);

        $clients = $clients->paginate($perPage);

        if (request()->wantsJson()) {
            return response()->json($clients);
        }

        return Inertia::render('Client/Index', [
            'clients' => $clients,
            'perPage' => $perPage, // Pass perPage value to the view
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

        $clientCardStatement = new ClientCardStatement();
        $clientCardStatement->client_id = $client->id;
        $clientCardStatement->save();

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
        $request->validate([
            'name' => 'required|string',
            'city' => 'required|string',
            'address' => 'required|string',
        ]);

        $client->update([
            'name' => $request->name,
            'city' => $request->city,
            'address' => $request->address,
        ]);

        return response()->json(['message' => 'Client updated successfully', 'client' => $client]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Client $client)
    {
        $client->delete();

        return redirect()->route('clients.index');
    }

    private function applySearch($query, Request $request)
    {
        $search = $request->get('search');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
            });
        }

        return $query;
    }
    public function getUniqueClients()
    {
        $uniqueClients = Client::query()
            ->distinct()
            ->select('name', 'id')
            ->get();

        return response()->json($uniqueClients);
    }
    public function getClients(Request $request)
    {
        $clients = Client::with('clientCardStatement')->get();

        return response()->json($clients, 200);
    }
    public function getAllClients()
    {
        $clients = Client::with('contacts')
            ->select('id', 'name')
            ->orderBy('name')
            ->get();
        
        return response()->json($clients);
    }

    public function getClientDetails($id)
    {
        $client = Client::find($id);
        
        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        return response()->json([
            'id' => $client->id,
            'name' => $client->name,
            'address' => $client->address,
            'city' => $client->city
        ]);
    }
}
