<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 20); // Default to 10 items per page
        $search = $request->query('search', '');

        // Query the articles with optional search filtering
        $articlesQuery = Article::query();

        if ($search) {
            $articlesQuery->where('name', 'like', "%{$search}%")
                ->orWhere('code', 'like', "%{$search}%");
        }

        $articles = $articlesQuery->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json($articles);
        }

        return Inertia::render('Article/Index', [
            'articles' => $articles,
            'perPage' => $perPage,
        ]);
    }

    /**
     * Get count of articles.
     */
    public function getCount()
    {
        $count = Article::all()->count();

        return response()->json(['count' => $count]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return Inertia::render('Article/ArticleCreate');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'code' => 'required|numeric|max:255',
            'name' => 'required|string|max:255',
            'selectedOption' => 'required|string|max:255',
            'type' => 'required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
            'height' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'color' => 'nullable|string|max:255',
            'in_meters'=>'nullable',
            'in_kilograms'=>'nullable',
            'in_pieces'=>'nullable',
            'in_square_meters' => 'nullable',
            'format_type' => 'required|string|max:255',
            'fprice' => 'nullable|numeric',
            'pprice' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:article_categories,id',
        ]);

        // Map the selectedOption to its corresponding integer value
        $taxTypeMap = [
            'DDV A' => 1,
            'DDV B' => 2,
            'DDV C' => 3,
        ];

        // Create a new Article instance
        $article = new Article();

        // Assign the validated data to the article's attributes
        $article->code = $validatedData['code'];
        $article->name = $validatedData['name'];
        $article->tax_type = $taxTypeMap[$validatedData['selectedOption']]; // Map to integer
        $article->type = $validatedData['type'];
        $article->barcode = $validatedData['barcode'] ?? null;
        $article->comment = $validatedData['comment'] ?? null;
        $article->height = $validatedData['height'] ?? null;
        $article->width = $validatedData['width'] ?? null;
        $article->length = $validatedData['length'] ?? null;
        $article->weight = $validatedData['weight'] ?? null;
        $article->color = $validatedData['color'] ?? null;
        $article->format_type = $validatedData['format_type'];
        $article->factory_price = $validatedData['fprice'] ?? null;
        $article->purchase_price = $validatedData['pprice'] ?? null;
        $article->price_1 = $validatedData['price'] ?? null;

        // Handle the unit field
        $article->in_meters =  $validatedData['in_meters'] ?? null;
        $article->in_kilograms = $validatedData['in_kilograms'] ?? null;
        $article->in_pieces = $validatedData['in_pieces'] ?? null;
        $article->in_square_meters = $validatedData['in_square_meters'] ?? null;

        // Save the article to the database
        $article->save();

        // Assign categories if provided
        if (!empty($validatedData['categories'])) {
            $article->categories()->sync($validatedData['categories']);
        }

        // Return a JSON response
        return response()->json(['message' => 'Article added successfully'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Article $article)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Article $article)
    {
        $validatedData = $request->validate([
            'code' => 'sometimes|required|numeric',
            'name' => 'sometimes|required|string|max:255',
            'tax_type' => 'sometimes|required',
            'type' => 'sometimes|required|string|max:255',
            'barcode' => 'nullable|string|max:255',
            'comment' => 'nullable|string|max:255',
            'height' => 'nullable|numeric',
            'width' => 'nullable|numeric',
            'length' => 'nullable|numeric',
            'weight' => 'nullable|numeric',
            'color' => 'nullable|string|max:255',
            'format_type' => 'sometimes|required',
            'factory_price' => 'nullable|numeric',
            'purchase_price' => 'nullable|numeric',
            'price_1' => 'nullable|numeric',
            'in_meters' => 'nullable',
            'in_kilograms' => 'nullable',
            'in_pieces' => 'nullable',
            'in_square_meters' => 'nullable',
            'categories' => 'nullable|array',
            'categories.*' => 'exists:article_categories,id',
        ]);

        $article->update($validatedData);

        // Update categories if provided
        if (isset($validatedData['categories'])) {
            $article->categories()->sync($validatedData['categories']);
        }

        return response()->json(['message' => 'Article updated successfully', 'article' => $article]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Article $article)
    {
        $article->delete();

        return redirect()->route('articles.index');
    }

    public function search(Request $request)
    {
        $query = $request->get('query');
        $type = $request->get('type'); // 'product' or 'service'

        $queryBuilder = Article::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('code', 'like', "%{$query}%");
        });

        if ($type) {
            $queryBuilder->where('type', $type);
        }

        return $queryBuilder->select('id', 'name', 'code', 'purchase_price', 'in_meters', 'in_kilograms', 'in_pieces', 'in_square_meters')
            ->limit(10)
            ->get();
    }

    public function get($id, Request $request)
    {
        $type = $request->get('type'); // 'product' or 'service'

        $query = Article::with('categories');
        
        if ($type) {
            $query->where('type', $type);
        }

        $article = $query->findOrFail($id);
        
        // Convert format_type to string to match frontend expectations
        if ($article->format_type) {
            $article->format_type = (string) $article->format_type;
        }
        
        // Ensure tax_type is properly handled
        if ($article->tax_type) {
            $article->tax_type = (string) $article->tax_type;
        }

        return $article;
    }
}
