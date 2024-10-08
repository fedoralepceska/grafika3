<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Client;
use App\Models\LargeFormatMaterial;
use App\Models\OtherMaterial;
use App\Models\Priemnica;
use App\Models\SmallMaterial;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class PriemnicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $receiptsQuery = Priemnica::with(['client', 'articles'])
            ->join('warehouses', 'priemnica.warehouse', '=', 'warehouses.id')
            ->select(
                'priemnica.id',
                'priemnica.client_id',
                'priemnica.warehouse',
                'priemnica.created_at',
                'warehouses.name as warehouse_name'
            );

        if ($request->filled('client_id') && $request->client_id !== 'All') {
            $receiptsQuery->where('client_id', $request->client_id);
        }

        if ($request->filled('warehouse_id') && $request->warehouse_id !== 'All') {
            $receiptsQuery->where('warehouse', $request->warehouse_id);
        }

        if ($request->filled('from_date')) {
            $receiptsQuery->whereDate('priemnica.created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $receiptsQuery->whereDate('priemnica.created_at', '<=', $request->to_date);
        }

        $receipts = $receiptsQuery->get();

        foreach ($receipts as $receipt) {
            $receipt->articles = DB::table('priemnica_articles')
                ->where('priemnica_id', $receipt->id)
                ->select('priemnica_id', 'article_id', 'quantity')
                ->get();
        }

        if ($request->wantsJson()) {
            return response()->json($receipts);
        }

        return Inertia::render('Priemnica/Index', [
            'receipts' => $receipts,
            'clients' => Client::all(),
            'warehouses' => Warehouse::all(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Priemnica/Create');
    }

    public function fetchPriemnica(Request $request)
    {
        $warehouseId = $request->query('warehouse');
        $perPage = $request->query('per_page', 20);

        $receiptsQuery = Priemnica::with(['client', 'articles'])
            ->join('warehouses', 'priemnica.warehouse', '=', 'warehouses.id')
            ->select(
                'priemnica.id',
                'priemnica.client_id',
                'priemnica.warehouse',
                'priemnica.created_at',
                'warehouses.name as warehouse_name'
            );

        if ($warehouseId && $warehouseId !== 'All') {
            $receiptsQuery->where('priemnica.warehouse', $warehouseId);
        }

        $receipts = $receiptsQuery->get();

        foreach ($receipts as $receipt) {
            $receipt->articles = DB::table('priemnica_articles')
                ->where('priemnica_id', $receipt->id)
                ->select('priemnica_id', 'article_id', 'quantity')
                ->get();
        }

        $priemnica = $receiptsQuery->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json($priemnica);
        }

        return Inertia::render('Warehouse/Index', [
            'priemnica' => $priemnica,
        ]);

    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->json()->all();
        $priemnica = new Priemnica();
        $priemnica->client_id = $data[0]['client_id'];
        $priemnica->warehouse = $data[0]['warehouse'];
        $priemnica->save();

        foreach ($data as $row) {
            // Skip processing if the required fields are missing
            if (empty($row['code']) || empty($row['name'])) {
                continue;
            }

            $article = Article::where('code', $row['code'])->first();

            if ($article) {
                $priemnica->articles()->attach($article->id, ['quantity' => $row['quantity']]);
                $materialData = [
                    'name' => $article->name,
                    'width' => $article->width,
                    'height' => $article->height,
                    'price_per_unit' => $article->purchase_price,
                    'article_id' => $article->id
                ];

                if ($article->format_type === 1) {
                    $existingMaterial = SmallMaterial::where('name', $materialData['name'])->first();
                }
                if ($article->format_type === 2) {
                    $existingMaterial = LargeFormatMaterial::where('name', $materialData['name'])->first();
                }
                if ($article->format_type === 3) {
                    $existingMaterial = OtherMaterial::where('name', $materialData['name'])->first();
                }
                if ($existingMaterial && isset($data[0])) {
                    // Update existing material quantity
                    $existingMaterial->quantity += $data[0]['quantity'];
                    $existingMaterial->save();
                } else {
                    // Create a new material with additional data from $data['quantity']
                    if ($article->format_type === 1) {
                        $materialData['quantity'] = $data[0]['quantity'];
                        SmallMaterial::create($materialData);
                    }
                    else if ($article->format_type === 2) {
                        $materialData['quantity'] = $data[0]['quantity'];
                        LargeFormatMaterial::create($materialData);
                    }
                    else {
                        $materialData['quantity'] = $data[0]['quantity'];
                        OtherMaterial::create($materialData);
                    }
                }
            }
        }

        return response()->json(['message' => 'Receipt added successfully'], 201);
    }

}
