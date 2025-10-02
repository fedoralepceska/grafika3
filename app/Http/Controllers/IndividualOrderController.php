<?php

namespace App\Http\Controllers;

use App\Models\IndividualOrder;
use Illuminate\Http\Request;
use Inertia\Inertia;

class IndividualOrderController extends Controller
{
    public function index(Request $request)
    {
        // Get completed orders for 'Физичко лице' client
        $completedQuery = IndividualOrder::with(['invoice.jobs', 'invoice.user', 'invoice.contact', 'client']);

        if ($request->has('searchQuery')) {
            $search = '%' . preg_replace('/[^A-Za-z0-9\-]/', '', $request->input('searchQuery')) . '%';
            $completedQuery->whereHas('invoice', function ($q) use ($search) {
                $q->where('invoice_title', 'LIKE', $search)
                  ->orWhere('id', 'LIKE', $search)
                  ->orWhereHas('contact', function ($contactQuery) use ($search) {
                      $contactQuery->where('name', 'LIKE', $search);
                  });
            });
        }

        $completedOrders = $completedQuery->get();

        // Get uncompleted orders for 'Физичко лице' client
        $uncompletedQuery = \App\Models\Invoice::with(['jobs', 'user', 'contact', 'client'])
            ->where('status', '!=', 'Completed')
            ->whereHas('client', function($q) {
                $q->where('name', 'Физичко лице');
            });

        if ($request->has('searchQuery')) {
            $search = '%' . preg_replace('/[^A-Za-z0-9\-]/', '', $request->input('searchQuery')) . '%';
            $uncompletedQuery->where(function ($q) use ($search) {
                $q->where('invoice_title', 'LIKE', $search)
                  ->orWhere('id', 'LIKE', $search)
                  ->orWhereHas('contact', function ($contactQuery) use ($search) {
                      $contactQuery->where('name', 'LIKE', $search);
                  });
            });
        }

        $uncompletedOrders = $uncompletedQuery->get();

        // Combine both collections
        $allOrders = collect();
        
        // Add completed orders
        foreach ($completedOrders as $order) {
            $allOrders->push([
                'id' => $order->id,
                'invoice_id' => $order->invoice_id,
                'client_id' => $order->client_id,
                'total_amount' => $order->total_amount,
                'paid_status' => $order->paid_status,
                'notes' => $order->notes,
                'created_at' => $order->created_at,
                'updated_at' => $order->updated_at,
                'invoice' => $order->invoice,
                'client' => $order->client,
                'is_completed' => true,
                'completion_status' => 'Completed'
            ]);
        }

        // Add uncompleted orders
        foreach ($uncompletedOrders as $invoice) {
            $totalAmount = (float) $invoice->jobs->sum('salePrice');
            $allOrders->push([
                'id' => 'temp_' . $invoice->id, // Temporary ID for uncompleted
                'invoice_id' => $invoice->id,
                'client_id' => $invoice->client_id,
                'total_amount' => $totalAmount,
                'paid_status' => 'unpaid', // Default for uncompleted
                'notes' => null,
                'created_at' => $invoice->created_at,
                'updated_at' => $invoice->updated_at,
                'invoice' => $invoice,
                'client' => $invoice->client,
                'is_completed' => false,
                'completion_status' => $invoice->status
            ]);
        }

        // Apply payment status filter if provided
        if ($request->has('status') && in_array($request->input('status'), ['paid','unpaid'])) {
            $statusFilter = $request->input('status');
            $allOrders = $allOrders->filter(function($order) use ($statusFilter) {
                return $order['paid_status'] === $statusFilter;
            });
        }

        // Apply completion status filter if provided
        if ($request->has('completionStatus') && $request->input('completionStatus') !== '') {
            $completionFilter = $request->input('completionStatus');
            $allOrders = $allOrders->filter(function($order) use ($completionFilter) {
                return $order['completion_status'] === $completionFilter;
            });
        }

        // Apply contact filter if provided
        if ($request->has('contactFilter') && $request->input('contactFilter') !== '') {
            $contactFilter = $request->input('contactFilter');
            $allOrders = $allOrders->filter(function($order) use ($contactFilter) {
                return isset($order['invoice']['contact']['id']) && 
                       $order['invoice']['contact']['id'] == $contactFilter;
            });
        }

        // Sort combined collection
        $sortOrder = $request->input('sortOrder', 'desc');
        if ($sortOrder === 'desc') {
            $allOrders = $allOrders->sortByDesc('created_at');
        } else {
            $allOrders = $allOrders->sortBy('created_at');
        }

        // Reset array keys after filtering/sorting
        $allOrders = $allOrders->values();

        // Create pagination manually
        $perPage = 10;
        $currentPage = $request->input('page', 1);
        $total = $allOrders->count();
        $offset = ($currentPage - 1) * $perPage;
        $paginatedOrders = $allOrders->slice($offset, $perPage)->values();

        // Create pagination links array for Laravel pagination format
        $lastPage = ceil($total / $perPage);
        $links = [];
        
        // Previous page link
        if ($currentPage > 1) {
            $links[] = [
                'url' => $this->buildPaginationUrl($request, $currentPage - 1),
                'label' => '&laquo; Previous',
                'active' => false
            ];
        }
        
        // Page number links
        for ($i = 1; $i <= $lastPage; $i++) {
            $links[] = [
                'url' => $this->buildPaginationUrl($request, $i),
                'label' => (string)$i,
                'active' => $i == $currentPage
            ];
        }
        
        // Next page link
        if ($currentPage < $lastPage) {
            $links[] = [
                'url' => $this->buildPaginationUrl($request, $currentPage + 1),
                'label' => 'Next &raquo;',
                'active' => false
            ];
        }

        $paginationData = [
            'current_page' => $currentPage,
            'data' => $paginatedOrders,
            'first_page_url' => $this->buildPaginationUrl($request, 1),
            'from' => $total > 0 ? $offset + 1 : null,
            'last_page' => $lastPage,
            'last_page_url' => $this->buildPaginationUrl($request, $lastPage),
            'next_page_url' => $currentPage < $lastPage ? $this->buildPaginationUrl($request, $currentPage + 1) : null,
            'path' => $request->url(),
            'per_page' => $perPage,
            'prev_page_url' => $currentPage > 1 ? $this->buildPaginationUrl($request, $currentPage - 1) : null,
            'to' => $total > 0 ? min($offset + $perPage, $total) : null,
            'total' => $total,
            'links' => $links
        ];

        // Get available contacts for dropdown
        $availableContacts = \App\Models\Contact::with('client')
            ->whereHas('client', function($q) {
                $q->where('name', 'Физичко лице');
            })
            ->orderBy('name')
            ->get(['id', 'name', 'client_id']);

        if ($request->wantsJson()) {
            return response()->json([
                'orders' => $paginationData,
                'availableContacts' => $availableContacts
            ]);
        }

        return Inertia::render('Finance/IndividualOrders', [
            'orders' => $paginationData,
            'availableContacts' => $availableContacts,
        ]);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'paid_status' => 'required|in:paid,unpaid'
        ]);

        // Check if this is a temporary ID (uncompleted order)
        if (str_starts_with($id, 'temp_')) {
            return response()->json(['error' => 'Cannot update status for uncompleted orders'], 400);
        }

        $order = IndividualOrder::findOrFail($id);
        $order->paid_status = $request->input('paid_status');
        $order->save();

        return response()->json(['message' => 'Status updated']);
    }

    public function updateNotes(Request $request, $id)
    {
        $request->validate([
            'notes' => 'nullable|string|max:1000'
        ]);

        // Check if this is a temporary ID (uncompleted order)
        if (str_starts_with($id, 'temp_')) {
            return response()->json(['error' => 'Cannot update notes for uncompleted orders'], 400);
        }

        $order = IndividualOrder::findOrFail($id);
        $order->notes = $request->input('notes');
        $order->save();

        return response()->json(['message' => 'Notes updated']);
    }

	private function buildPaginationUrl(Request $request, $page)
	{
		$queryParams = $request->query();
		$queryParams['page'] = $page;
		
		return $request->url() . '?' . http_build_query($queryParams);
	}
}
