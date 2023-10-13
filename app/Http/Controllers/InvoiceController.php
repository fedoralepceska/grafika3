<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

    public function store(Request $request)
    {
        $data = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'client_id' => 'required|exists:clients,id',
            'status' => 'required|in:NOT_STARTED_YET,IN_PROGRESS,COMPLETED', // Adjust this enum based on your needs
            'invoice_title' => 'required|string',
            'comment' => 'nullable|string',
            'job_ids' => 'array',  // Ensure an array of job IDs is provided
            'job_ids.*' => 'exists:jobs,id', // Ensure each provided job ID exists
        ]);

        DB::beginTransaction();

        try {
            // Create a new invoice record
            $invoice = new Invoice();
            $invoice->invoice_title = $data['invoice_title'];
            $invoice->start_date = $data['start_date'];
            $invoice->end_date = $data['end_date'];
            $invoice->client_id = $data['client_id'];
            $invoice->comment = $data['comment'];
            $invoice->status = $data['status']; // Use the provided status value

            // Save the invoice to the database
            $invoice->save();

            // Attach the selected jobs to the invoice
            $invoice->jobs()->attach($data['job_ids']);

            DB::commit();

            return redirect()->route('invoices.index')->with('success', 'Invoice created successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->route('invoices.index')->with('error', 'Failed to create invoice. Please check your inputs and try again.');
        }
    }
}
