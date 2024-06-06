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
        $receipts = Priemnica::with(['client', 'article'])->get();
        return Inertia::render('Priemnica/Index', $receipts);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Priemnica/Create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $priemnica = new Priemnica();

        $priemnica->update($data);
        $article = Article::where('code', $data['code'])->first();
        $priemnica->article_id = $article->id;
        $priemnica->save();

        $materialType = ($article->format_type === 1) ? SmallMaterial::class : LargeFormatMaterial::class;
        $materialData = [
            'name' => $article->name,
            'width' => $article->width,
            'height' => $article->height,
            'price_per_unit' => $article->purchase_price,
        ];

        // Check for existing material based on name (assuming name is unique)
        $existingMaterial = $materialType::where('name', $materialData['name'])->first();

        if ($existingMaterial) {
            // Update existing material quantity
            $existingMaterial->quantity += $data['qty'];
            $existingMaterial->save();
        } else {
            // Create a new material with additional data from $data['qty']
            $materialData['quantity'] = $data['qty'];
            $materialType::create($materialData);
        }

        return response()->json(['message' => 'Receipt added successfully'], 201);
    }

}
