<?php

namespace App\Http\Controllers;

use App\Models\IncomingFaktura;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class IncomingFakturaController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = IncomingFaktura::query();

            if ($request->has('searchQuery')) {
                $searchQuery = $request->input('searchQuery');
                $query->where('id', 'like', "%{$searchQuery}%");
            }


            $sortOrder = $request->input('sortOrder', 'desc');
            $query->orderBy('created_at', $sortOrder);

            $incomingInvoice = $query->latest()->paginate(10);

            if ($request->wantsJson()) {
                return response()->json($incomingInvoice);
            }

            return Inertia::render('Finance/IncomingInvoice', [
                'incomingInvoice' => $incomingInvoice,
            ]);
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }
    public function store(Request $request)
    {
        $data = $request->all();
        $incoming_faktura = new IncomingFaktura();

        $incoming_faktura->update($data);
        $incoming_faktura->save();

        return response()->json(['message' => 'Incoming invoice added successfully'], 201);
    }

}
