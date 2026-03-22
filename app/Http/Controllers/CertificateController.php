<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\FiscalYearClosure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class CertificateController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = Certificate::query()->with('createdBy');

            // Filter out archived statements by default
            if (!$request->has('includeArchived') || !$request->boolean('includeArchived')) {
                $query->active();
            }

            if ($request->filled('searchQuery')) {
                $searchQuery = trim((string) $request->input('searchQuery'));
                if ($searchQuery !== '') {
                    $query->where(function ($q) use ($searchQuery) {
                        $q->where('id', 'like', "%{$searchQuery}%")
                            ->orWhere('bank', 'like', "%{$searchQuery}%")
                            ->orWhere('bankAccount', 'like', "%{$searchQuery}%")
                            ->orWhere('id_per_bank', 'like', "%{$searchQuery}%");
                    });
                }
            }

            if ($request->has('bankAccount') && $request->input('bankAccount') !== 'All') {
                $bankAccount = $request->input('bankAccount');
                $query->where('bankAccount', $bankAccount);
            }

            $fiscalYearFilter = $request->input('fiscal_year', $request->input('fiscalYear'));
            if ($fiscalYearFilter && $fiscalYearFilter !== 'All') {
                $query->forFiscalYear((int) $fiscalYearFilter);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->input('date_from'));
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->input('date_to'));
            }
            if ($request->filled('month')) {
                $month = (int) $request->input('month');
                if ($month >= 1 && $month <= 12) {
                    $query->whereMonth('date', $month);
                }
            }

            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);

            $perPage = (int) $request->input('per_page', 20);
            $perPage = max(1, min($perPage, 200));
            $certificates = $query->paginate($perPage)->withQueryString();

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

        $year = now()->year;

        // Check if fiscal year is closed for bank statements
        if (FiscalYearClosure::isYearClosed($year, 'bank_statements')) {
            return response()->json([
                'error' => 'Cannot create statements for closed fiscal year'
            ], 422);
        }

        // Generate id_per_bank for this bank and fiscal year
        $numberData = Certificate::generateIdPerBank($validatedData['bank']);

        // Create a new certificate record
        $certificate = new Certificate();
        $certificate->date = $validatedData['date'];
        $certificate->bank = $validatedData['bank'];
        $certificate->bankAccount = $validatedData['bankAccount'];
        $certificate->id_per_bank = $numberData['id_per_bank'];
        $certificate->fiscal_year = $numberData['fiscal_year'];
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
        $validatedData = $request->validate([
            'date' => 'required|date',
            'bank' => 'required|string',
            'bankAccount' => 'required|string',
        ]);

        $certificate->update($validatedData);

        return response()->json([
            'message' => 'Certificate updated successfully',
            'certificate' => $certificate
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Certificate $certificate)
    {
        //
    }

    public function getBanksList()
    {
        $banks = \App\Models\Bank::select('id', 'name', 'address as bankAccount')->get();
        return response()->json($banks);
    }

    /**
     * Get available fiscal years for filtering
     */
    public function getAvailableYears()
    {
        $years = Certificate::selectRaw('DISTINCT fiscal_year')
            ->whereNotNull('fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year');

        return response()->json($years);
    }
}
