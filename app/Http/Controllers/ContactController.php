<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Contact;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Database\Eloquent\Collection
    {
         return Contact::all();
    }
    public function store(Request $request, $id)
    {
        // Find the client
        $client = Client::find($id);

        if (!$client) {
            return response()->json(['message' => 'Client not found'], 404);
        }

        // Validate and save the contacts associated with this client
        $name = $request->input('name');
        $phone = $request->input('phone');
        $email = $request->input('email');

        // Validate the contact data (name, email, phone)
        $request->validate([
            'name' => 'sometimes|required',
            'phone' => 'sometimes|required',
            'email' => 'sometimes|required',
        ]);
        // Save the contact associated with the client
        $client->contacts()->create([
            'name' => $name,
            'email' => $phone,
            'phone' => $email,
        ]);

        return response()->json(['message' => 'Client added successfully'], 201);
    }

    public function destroy($clientId, $contactId)
    {
        // Fetch the client based on $clientId
        $client = Client::find($clientId);

        // Check if the client exists and if the contact belongs to the client
        if ($client && $client->contacts()->where('id', $contactId)->exists()) {
            // Delete the contact
            $client->contacts()->find($contactId)->delete();

            return response()->json(['message' => 'Contact deleted successfully'], 200);
        }

        return response()->json(['message' => 'Contact not found or does not belong to the client'], 404);
    }

}
