<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\In;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::with('jobs')->get(); // Eager load jobs related to invoices

        if (request()->wantsJson()) {
            return response()->json($invoices);
        }

        return Inertia::render('Invoice/InvoiceForm', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'invoice_title' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'comment' => 'string|nullable',
        ]);

        $invoiceData = $request->all();

        // Create the invoice
        $invoice = new Invoice($invoiceData);
        $invoice->status = 'Not started yet';

        $invoice->save();

        if ($request->hasFile('jobs')) {
            $jobFiles = $request->file('jobs');

            foreach ($jobFiles as $jobFile) {
                $job = new Job([
                    'width' => $jobFile->width,
                    'height' => $jobFile->height,
                ]);

                $filename = time() . '_' . $jobFile->getClientOriginalName();
                $jobFile->storeAs('public/uploads', $filename);
                $job->file = $filename;

                $invoice->jobs()->save($job);
            }
        }

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ]);
    }
}
