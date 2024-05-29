<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use Illuminate\Http\Request;

class BanksController extends Controller
{
    public function getBanks(Request $request)
    {
        $banks = Bank::all();

        return response()->json($banks, 200); // Return JSON response
    }

    public function createBank(Request $request)
    {
        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        $bank = new Bank($validatedData);
        $bank->save();

        return response()->json([
            'message' => 'Bank created successfully!',
            'bank' => $bank, // Optionally return the created bank object
        ], 201);
    }
    public function deleteBank($id)
    {
        $bank = Bank::find($id);

        if (!$bank) {
            return response()->json(['error' => 'Bank not found'], 404);
        }

        $bank->delete();
        return response()->json(['message' => 'Bank deleted successfully!'], 200);
    }
}
