<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ArticleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $perPage = $request->query('per_page', 20);
        $search = $request->query('search', '');
        $vatFilter = $request->query('vat_filter', '');
        $unitFilter = $request->query('unit_filter', '');

        // Query the articles with optional filtering
        $articlesQuery = Article::query();

        // Search filter
        if ($search) {
            $articlesQuery->where(function($query) use ($search) {
                $query->where('name', 'like', "%{$search}%")
                      ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // VAT filter
        if ($vatFilter) {
            $articlesQuery->where('tax_type', $vatFilter);
        }

        // Unit filter
        if ($unitFilter) {
            switch ($unitFilter) {
                case 'meters':
                    $articlesQuery->where('in_meters', 1);
                    break;
                case 'kilograms':
                    $articlesQuery->where('in_kilograms', 1);
                    break;
                case 'pieces':
                    $articlesQuery->where('in_pieces', 1);
                    break;
                case 'square_meters':
                    $articlesQuery->where('in_square_meters', 1);
                    break;
            }
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
        // Load the article with its categories
        $article->load('categories');
        
        return Inertia::render('Articles/Show', [
            'article' => $article
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Article $article)
    {
        // Load the article with its categories
        $article->load('categories');
        
        // Get all available categories for the dropdown
        $categories = \App\Models\ArticleCategory::all();
        
        return Inertia::render('Articles/Edit', [
            'article' => $article,
            'categories' => $categories
        ]);
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

        $results = collect();

        // Get matching article categories
        $categoryQuery = \App\Models\ArticleCategory::where('name', 'like', "%{$query}%");
        
        if ($type) {
            $categoryQuery->whereHas('articles', function($q) use ($type) {
                $q->where('type', $type);
            });
        }
        
        $categories = $categoryQuery->with(['articles' => function($query) use ($type) {
            if ($type) {
                $query->where('type', $type);
            }
        }])->limit(5)->get();

        // Add categories to results
        foreach ($categories as $category) {
            if ($category->articles->count() > 0) {
                // Get the first available article in the category (with stock)
                $availableArticles = [];
                foreach ($category->articles as $article) {
                    if ($article->hasStock(1)) {
                        $availableArticles[] = $article;
                    }
                }
                
                // Only show category if it has articles with stock
                if (!empty($availableArticles)) {
                    // Sort by creation date (oldest first for FIFO)
                    usort($availableArticles, function($a, $b) {
                        return $a->created_at <=> $b->created_at;
                    });
                    
                    $firstArticle = $availableArticles[0];
                    $currentStock = $firstArticle->getCurrentStock();
                    
                    $results->push([
                        'id' => 'cat_' . $category->id,
                        'name' => "[Category] {$category->name}",
                        'code' => '',
                        'type' => 'category',
                        'purchase_price' => $firstArticle->purchase_price,
                        'in_meters' => $firstArticle->in_meters,
                        'in_kilograms' => $firstArticle->in_kilograms,
                        'in_pieces' => $firstArticle->in_pieces,
                        'in_square_meters' => $firstArticle->in_square_meters,
                        'category_id' => $category->id,
                        'article_count' => count($availableArticles),
                        'first_article_id' => $firstArticle->id,
                        'first_article_name' => $firstArticle->name,
                        'current_stock' => $currentStock
                    ]);
                }
            }
        }

        // Get individual articles not in any category matching the search
        $categoryArticleIds = $categories->flatMap(function($cat) {
            return $cat->articles->pluck('id');
        })->unique();

        $articlesQuery = Article::where(function($q) use ($query) {
            $q->where('name', 'like', "%{$query}%")
              ->orWhere('code', 'like', "%{$query}%");
        });

        if ($type) {
            $articlesQuery->where('type', $type);
        }

        $articles = $articlesQuery->whereNotIn('id', $categoryArticleIds)
            ->select('id', 'name', 'code', 'purchase_price', 'in_meters', 'in_kilograms', 'in_pieces', 'in_square_meters', 'format_type', 'created_at', 'updated_at')
            ->limit(10)
            ->get()
            ->filter(function($article) {
                return $article->hasStock(1); // Only include articles with stock
            })
            ->map(function($article) {
                return [
                    'id' => $article->id,
                    'name' => $article->name,
                    'code' => $article->code,
                    'type' => 'individual',
                    'purchase_price' => $article->purchase_price,
                    'in_meters' => $article->in_meters,
                    'in_kilograms' => $article->in_kilograms,
                    'in_pieces' => $article->in_pieces,
                    'in_square_meters' => $article->in_square_meters,
                    'current_stock' => $article->getCurrentStock()
                ];
            });

        // Merge categories and articles
        $results = $results->merge($articles);

        return $results->take(15);
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

    /**
     * Parse uploaded Excel file and return headers + first rows for column mapping.
     */
    public function parseImportFile(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:51200']); // 50MB

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);

        if (empty($rows)) {
            return response()->json(['headers' => [], 'rows' => [], 'message' => 'File is empty'], 422);
        }

        // PhpSpreadsheet may use 1-based row keys; get first row by value
        $rows = array_values($rows);
        $firstRow = $rows[0] ?? [];
        $headers = array_map(function ($h) {
            return trim((string) $h);
        }, array_values($firstRow));
        $dataRows = array_slice($rows, 1, 15);
        $dataRows = array_values(array_map(function ($row) {
            return array_values(is_array($row) ? $row : []);
        }, $dataRows));

        return response()->json([
            'headers' => $headers,
            'rows' => $dataRows,
        ]);
    }

    /**
     * Preview batch import: apply mapping, detect duplicates, assign next codes.
     * Request: file + mapping { name_col, purchase_price_col, price_col } (0-based column indices).
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB
            'mapping' => 'required|array',
            'mapping.name_col' => 'required|integer|min:0',
            'mapping.purchase_price_col' => 'nullable|integer|min:0',
            'mapping.price_col' => 'nullable|integer|min:0',
        ]);

        $file = $request->file('file');
        $mapping = $request->input('mapping');
        $nameCol = (int) $mapping['name_col'];
        $purchasePriceCol = isset($mapping['purchase_price_col']) ? (int) $mapping['purchase_price_col'] : null;
        $priceCol = isset($mapping['price_col']) ? (int) $mapping['price_col'] : null;

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        array_shift($rows); // skip header

        $existingNames = Article::pluck('name')->map(function ($n) {
            return mb_strtolower(trim((string) $n));
        })->flip();

        $nextCode = $this->getNextArticleCode();
        $preview = [];
        $taxType = 1; // DDV A = 18%
        $unitPcs = true;

        foreach ($rows as $row) {
            $rowArr = array_values($row);
            $name = isset($rowArr[$nameCol]) ? trim((string) $rowArr[$nameCol]) : '';
            if ($name === '') {
                continue;
            }

            $purchasePrice = null;
            if ($purchasePriceCol !== null && isset($rowArr[$purchasePriceCol])) {
                $val = $rowArr[$purchasePriceCol];
                $purchasePrice = is_numeric($val) ? (float) $val : null;
            }
            $price = null;
            if ($priceCol !== null && isset($rowArr[$priceCol])) {
                $val = $rowArr[$priceCol];
                $price = is_numeric($val) ? (float) $val : null;
            }

            $nameNorm = mb_strtolower($name);
            $isDuplicate = $existingNames->has($nameNorm);

            $assignedCode = null;
            if (! $isDuplicate) {
                $assignedCode = (string) $nextCode;
                $nextCode++;
            }

            $preview[] = [
                'name' => $name,
                'purchase_price' => $purchasePrice,
                'price_1' => $price,
                'code' => $assignedCode,
                'tax_type' => $taxType,
                'tax_label' => 'DDV A (18%)',
                'unit' => 'pcs',
                'type' => 'product',
                'is_duplicate' => $isDuplicate,
            ];
        }

        return response()->json(['rows' => $preview]);
    }

    /**
     * Execute batch import: create articles for non-duplicate rows.
     * Optional overrides[]: one per preview row (same order), each with format_type, barcode, comment,
     * height, width, length, weight, color, factory_price, category_ids.
     */
    public function executeImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB
            'mapping' => 'required|array',
            'mapping.name_col' => 'required|integer|min:0',
            'mapping.purchase_price_col' => 'nullable|integer|min:0',
            'mapping.price_col' => 'nullable|integer|min:0',
            'overrides' => 'nullable|array',
            'overrides.*.format_type' => 'nullable|string|in:1,2,3',
            'overrides.*.barcode' => 'nullable|string|max:255',
            'overrides.*.comment' => 'nullable|string|max:255',
            'overrides.*.height' => 'nullable|numeric',
            'overrides.*.width' => 'nullable|numeric',
            'overrides.*.length' => 'nullable|numeric',
            'overrides.*.weight' => 'nullable|numeric',
            'overrides.*.color' => 'nullable|string|max:255',
            'overrides.*.factory_price' => 'nullable|numeric',
            'overrides.*.category_ids' => 'nullable|array',
            'overrides.*.category_ids.*' => 'exists:article_categories,id',
        ]);

        $file = $request->file('file');
        $mapping = $request->input('mapping');
        $overrides = $request->input('overrides', []);
        $nameCol = (int) $mapping['name_col'];
        $purchasePriceCol = isset($mapping['purchase_price_col']) ? (int) $mapping['purchase_price_col'] : null;
        $priceCol = isset($mapping['price_col']) ? (int) $mapping['price_col'] : null;

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        array_shift($rows);

        $existingNames = Article::pluck('name')->map(function ($n) {
            return mb_strtolower(trim((string) $n));
        })->flip();

        $nextCode = $this->getNextArticleCode();
        $created = 0;
        $skipped = 0;
        $overrideIndex = 0;

        foreach ($rows as $row) {
            $rowArr = array_values($row);
            $name = isset($rowArr[$nameCol]) ? trim((string) $rowArr[$nameCol]) : '';
            if ($name === '') {
                continue;
            }

            $nameNorm = mb_strtolower($name);
            if ($existingNames->has($nameNorm)) {
                $skipped++;
                $overrideIndex++;
                continue;
            }

            $override = $overrides[$overrideIndex] ?? [];
            $overrideIndex++;

            $purchasePrice = null;
            if ($purchasePriceCol !== null && isset($rowArr[$purchasePriceCol])) {
                $val = $rowArr[$purchasePriceCol];
                $purchasePrice = is_numeric($val) ? (float) $val : null;
            }
            $price = null;
            if ($priceCol !== null && isset($rowArr[$priceCol])) {
                $val = $rowArr[$priceCol];
                $price = is_numeric($val) ? (float) $val : null;
            }

            $formatType = isset($override['format_type']) ? $override['format_type'] : '2';
            $article = new Article();
            $article->code = (string) $nextCode;
            $article->name = $name;
            $article->tax_type = 1; // DDV A 18%
            $article->type = 'product';
            $article->format_type = $formatType;
            $article->purchase_price = $purchasePrice;
            $article->price_1 = $price;
            $article->factory_price = isset($override['factory_price']) && $override['factory_price'] !== '' && $override['factory_price'] !== null
                ? (float) $override['factory_price'] : null;
            $article->in_pieces = true;
            $article->in_meters = null;
            $article->in_kilograms = null;
            $article->in_square_meters = null;
            $article->barcode = ! empty($override['barcode']) ? $override['barcode'] : null;
            $article->comment = ! empty($override['comment']) ? $override['comment'] : null;
            $article->height = isset($override['height']) && $override['height'] !== '' && $override['height'] !== null ? $override['height'] : null;
            $article->width = isset($override['width']) && $override['width'] !== '' && $override['width'] !== null ? $override['width'] : null;
            $article->length = isset($override['length']) && $override['length'] !== '' && $override['length'] !== null ? $override['length'] : null;
            $article->weight = isset($override['weight']) && $override['weight'] !== '' && $override['weight'] !== null ? $override['weight'] : null;
            $article->color = ! empty($override['color']) ? $override['color'] : null;
            $article->save();

            if (! empty($override['category_ids']) && is_array($override['category_ids'])) {
                $article->categories()->sync($override['category_ids']);
            }

            $existingNames->put($nameNorm, true);
            $nextCode++;
            $created++;
        }

        return response()->json([
            'message' => 'Batch import completed.',
            'created' => $created,
            'skipped' => $skipped,
        ]);
    }

    /**
     * Next numeric code for new articles (max code + 1, or 1 if none).
     */
    private function getNextArticleCode(): int
    {
        $maxCode = Article::selectRaw('MAX(CAST(code AS UNSIGNED)) as max_code')->value('max_code');

        return $maxCode ? (int) $maxCode + 1 : 1;
    }
}
