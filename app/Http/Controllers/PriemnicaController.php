<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\LargeFormatMaterial;
use App\Models\Priemnica;
use App\Models\SmallMaterial;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PriemnicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $receipts = Priemnica::with(['client', 'articles'])->get();
        return Inertia::render('Priemnica/Index', $receipts);
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

        $query = Priemnica::query()
            ->join('article', 'priemnica.article_id', '=', 'article.id')
            ->join('warehouses', 'priemnica.warehouse', '=', 'warehouses.id')
            ->select('priemnica.*', 'article.name as article_name', 'warehouses.name as warehouse_name');

        if ($warehouseId && $warehouseId !== 'All') {
            $query->where('priemnica.warehouse', $warehouseId);
        }

        $priemnica = $query->paginate($perPage);

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
            $article = Article::where('code', $row['code'])->first();

            if ($article) {
                $priemnica->articles()->attach($article->id);
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
                    else {
                        $materialData['quantity'] = $data[0]['quantity'];
                        LargeFormatMaterial::create($materialData);
                    }
                }
            }
        }

        return response()->json(['message' => 'Receipt added successfully'], 201);
    }

}
