<?php

namespace App\Http\Controllers;

use App\Models\Bank;
use App\Models\Certificate;
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

    public function updateBank(Request $request, $id)
    {
        $bank = Bank::find($id);

        if (!$bank) {
            return response()->json(['error' => 'Bank not found'], 404);
        }

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'required|string|max:255',
        ]);

        // Store old values before update
        $oldName = $bank->name;
        $oldAddress = $bank->address;

        // Update bank
        $bank->update($validatedData);

        // Update related certificates
        Certificate::where('bank', $oldName)
            ->where('bankAccount', $oldAddress)
            ->update([
                'bank' => $validatedData['name'],
                'bankAccount' => $validatedData['address']
            ]);

        return response()->json([
            'message' => 'Bank and related certificates updated successfully!',
            'bank' => $bank,
        ], 200);
    }
}
