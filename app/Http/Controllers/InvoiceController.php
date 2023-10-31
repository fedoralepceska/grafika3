<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\Job;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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

        return Inertia::render('Invoice/Index', [
            'invoices' => $invoices,
        ]);
    }

    public function create()
    {
        return Inertia::render('Invoice/InvoiceForm');
    }
    public function store(Request $request)
    {
        $request->validate([
            'client_id' => 'required',
            'invoice_title' => 'required',
            'start_date' => 'required|date',
            'end_date' => 'required|date',
            'comment' => 'string|nullable',
            'jobs' => 'array',
        ]);

        $invoiceData = $request->all();

        // Create the invoice
        $invoice = new Invoice($invoiceData);
        $invoice->status = 'Not started yet';
        $invoice->created_by = Auth::id();

        $invoice->save();
        $jobs = $request->jobs;
        foreach ($jobs as $job) {
            $job_id = $job['id']; // Assuming 'id' holds the job ID

            $invoice->jobs()->attach($job_id);
        }

        return response()->json([
            'message' => 'Invoice created successfully',
            'invoice' => $invoice,
        ]);
    }
}
