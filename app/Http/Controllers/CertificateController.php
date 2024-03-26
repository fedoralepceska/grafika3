<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Certificate::query()->with('createdBy');

            if ($request->has('searchQuery')) {
                $searchQuery = $request->input('searchQuery');
                $query->where('id', 'like', "%{$searchQuery}%");
            }

            if ($request->has('bankAccount') && $request->input('bankAccount') !== 'All') {
                $bankAccount = $request->input('bankAccount');
                $query->where('bankAccount', $bankAccount);
            }

            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);

            $certificates = $query->latest()->paginate(10);

            if ($request->wantsJson()) {
                return response()->json($certificates);
            }

            return Inertia::render('Finance/BankCertificate', [
                'certificates' => $certificates,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function getUniqueBanks()
    {
        $uniqueBankAccounts = Certificate::distinct()->pluck('bankAccount');

        return response()->json($uniqueBankAccounts);
    }
    public function getCertificate($id)
    {
        try {
            $certificate = Certificate::with('createdBy')->findOrFail($id);

            if (request()->wantsJson()) {
                return response()->json($certificate);
            }

            return Inertia::render('Finance/Certificate', [
                'certificate' => $certificate,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Certificate not found'], 404);
        }
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
        // Validate the incoming request data for the certificate
        $validatedData = $request->validate([
            'date' => 'date',
            'bank' => 'string',
            'bankAccount' => 'string',
        ]);

        // Create a new certificate record
        $certificate = new Certificate();
        $certificate->date = $validatedData['date'];
        $certificate->bank = $validatedData['bank'];
        $certificate->bankAccount = $validatedData['bankAccount'];
        $certificate->created_by = auth()->id();

        // Save the certificate
        $certificate->save();

        return response()->json(['message' => 'Certificate added successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Certificate $certificate)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Certificate $certificate)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Certificate $certificate)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Certificate $certificate)
    {
        //
    }
}
