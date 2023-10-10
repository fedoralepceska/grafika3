<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Inertia\Inertia;

class InvoiceController extends Controller
{
    public function index()
    {
        $invoices = Invoice::all();
        if (request()->wantsJson()) {
            return response()->json($invoices);
        }
        return Inertia::render('Invoice/InvoiceForm', [
            'invoices' => $invoices
        ]);
    }

    public function create()
    {
        return view('invoices.create');
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'client_id' => 'required',
            'status' => 'required',
            'invoice_title' => 'required|string',
            'comment' => 'nullable|string',
            'job_ids' => 'array',  // Ensure an array of job IDs is provided
            'job_ids.*' => 'exists:jobs,id', // Ensure each provided job ID exists
        ]);

        $jobIds = $data['job_ids'] ?? [];
        unset($data['job_ids']);
        // Create a new invoice record
        $invoice = new Invoice();
        $invoice->invoice_title = $data['invoice_title'];
        $invoice->start_date = $data['start_date'];
        $invoice->end_date = $data['end_date'];
        $invoice->client_id = $data['client_id'];
        $invoice->comment = $data['comment'];
        $invoice->status = 'Not started yet';
        if (!empty($jobIds)) {
            $invoice->jobs()->attach($jobIds);
        }
        $invoice->save();

        return redirect()->route('invoices.index');
    }
}
