<?php

namespace App\Http\Controllers;

use App\Models\TradeWarehouseInventory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Inertia\Inertia;

class TradeArticlesController extends Controller
{
    /**
     * Display a listing of trade articles with current stock
     */
    public function index(Request $request)
    {
        $query = TradeWarehouseInventory::with(['article', 'warehouse'])
            ->where('quantity', '>', 0);

        // Filter by warehouse if specified
        if ($request->filled('warehouse_id') && $request->warehouse_id !== 'All') {
            $query->where('warehouse_id', $request->warehouse_id);
        }

        // Search by article code or name
        if ($request->filled('search')) {
            $search = $request->search;
            $query->whereHas('article', function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        // Sort by specified column
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        if ($sortBy === 'article_name') {
            $query->join('article', 'trade_warehouse_inventory.article_id', '=', 'article.id')
                  ->orderBy('article.name', $sortOrder)
                  ->select('trade_warehouse_inventory.*');
        } elseif ($sortBy === 'article_code') {
            $query->join('article', 'trade_warehouse_inventory.article_id', '=', 'article.id')
                  ->orderBy('article.code', $sortOrder)
                  ->select('trade_warehouse_inventory.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $tradeArticles = $query->paginate(20);

        // Get trade warehouses for filter
        $tradeWarehouses = Warehouse::getSpecialWarehouses();

        if ($request->wantsJson()) {
            return response()->json($tradeArticles);
        }

        return Inertia::render('TradeArticles/Index', [
            'tradeArticles' => $tradeArticles,
            'warehouses' => $tradeWarehouses,
            'filters' => [
                'warehouse_id' => $request->warehouse_id,
                'search' => $request->search,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ]
        ]);
    }

    /**
     * Get trade articles summary/statistics
     */
    public function summary()
    {
        $totalArticles = TradeWarehouseInventory::where('quantity', '>', 0)->count();
        $totalValue = TradeWarehouseInventory::where('quantity', '>', 0)
            ->selectRaw('SUM(quantity * purchase_price) as total_value')
            ->value('total_value') ?? 0;
        
        $lowStockArticles = TradeWarehouseInventory::where('quantity', '<=', 10)
            ->where('quantity', '>', 0)
            ->count();

        $warehouseBreakdown = TradeWarehouseInventory::with('warehouse')
            ->where('quantity', '>', 0)
            ->selectRaw('warehouse_id, COUNT(*) as article_count, SUM(quantity * purchase_price) as warehouse_value')
            ->groupBy('warehouse_id')
            ->get()
            ->map(function ($item) {
                return [
                    'warehouse_name' => $item->warehouse->name,
                    'article_count' => $item->article_count,
                    'warehouse_value' => $item->warehouse_value,
                ];
            });

        return response()->json([
            'total_articles' => $totalArticles,
            'total_value' => $totalValue,
            'low_stock_articles' => $lowStockArticles,
            'warehouse_breakdown' => $warehouseBreakdown,
        ]);
    }

    /**
     * Update article selling price
     */
    public function updateSellingPrice(Request $request, $id)
    {
        $request->validate([
            'selling_price' => 'required|numeric|min:0'
        ]);

        $tradeArticle = TradeWarehouseInventory::findOrFail($id);
        $tradeArticle->update([
            'selling_price' => $request->selling_price
        ]);

        return response()->json([
            'message' => 'Selling price updated successfully',
            'trade_article' => $tradeArticle->load(['article', 'warehouse'])
        ]);
    }

    /**
     * Get low stock articles
     */
    public function lowStock(Request $request)
    {
        $threshold = $request->get('threshold', 10);
        
        $lowStockArticles = TradeWarehouseInventory::with(['article', 'warehouse'])
            ->where('quantity', '<=', $threshold)
            ->where('quantity', '>', 0)
            ->orderBy('quantity', 'asc')
            ->get();

        return response()->json($lowStockArticles);
    }
}
