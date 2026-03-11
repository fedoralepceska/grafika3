<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Client;
use App\Models\LargeFormatMaterial;
use App\Models\OtherMaterial;
use App\Models\Priemnica;
use App\Models\SmallMaterial;
use App\Models\TradeWarehouseInventory;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PriemnicaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $receiptsQuery = Priemnica::with(['client', 'articles' => function($query) {
            $query->withPivot('quantity', 'custom_price', 'custom_tax_type');
        }])
            ->leftJoin('warehouses', 'priemnica.warehouse', '=', 'warehouses.id')
            ->select(
                'priemnica.id',
                'priemnica.receipt_number',
                'priemnica.fiscal_year',
                'priemnica.client_id',
                'priemnica.warehouse',
                'priemnica.comment',
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

        $receipts = $receiptsQuery->orderBy('priemnica.fiscal_year', 'desc')
            ->orderBy('priemnica.receipt_number', 'desc')
            ->get();

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

    /**
     * Parse uploaded receipt Excel file and return headers/sample rows for mapping.
     */
    public function parseImportFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        if (empty($rows)) {
            return response()->json(['headers' => [], 'rows' => [], 'message' => 'File is empty'], 422);
        }

        $rows = array_values($rows);
        $headers = array_map(fn($h) => trim((string) $h), array_values($rows[0] ?? []));
        $sampleRows = array_slice($rows, 1, 10);
        $sampleRows = array_values(array_map(fn($r) => array_values($r), $sampleRows));

        return response()->json([
            'headers' => $headers,
            'rows' => $sampleRows,
        ]);
    }

    /**
     * Preview receipt rows from uploaded Excel file using explicit column mapping.
     * Quantity mapping is required.
     */
    public function previewImport(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:51200', // 50MB
            'mapping' => 'required|array',
            'mapping.quantity_col' => 'required|integer|min:0',
            'mapping.code_col' => 'nullable|integer|min:0',
            'mapping.name_col' => 'nullable|integer|min:0',
            'mapping.price_col' => 'nullable|integer|min:0',
            'mapping.vat_col' => 'nullable|integer|min:0',
        ]);

        $mapping = $request->input('mapping', []);
        $quantityCol = (int) $mapping['quantity_col'];
        $codeCol = isset($mapping['code_col']) && $mapping['code_col'] !== '' ? (int) $mapping['code_col'] : null;
        $nameCol = isset($mapping['name_col']) && $mapping['name_col'] !== '' ? (int) $mapping['name_col'] : null;
        $priceCol = isset($mapping['price_col']) && $mapping['price_col'] !== '' ? (int) $mapping['price_col'] : null;
        $vatCol = isset($mapping['vat_col']) && $mapping['vat_col'] !== '' ? (int) $mapping['vat_col'] : null;

        if ($codeCol === null && $nameCol === null) {
            return response()->json(['message' => 'Map at least Code or Name column.'], 422);
        }

        $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        if (empty($rows)) {
            return response()->json(['rows' => [], 'message' => 'File is empty'], 422);
        }

        $rows = array_values($rows);
        $dataRows = array_slice($rows, 1);
        $nextCode = $this->getNextArticleCode();
        $preview = [];

        foreach ($dataRows as $row) {
            $rowArr = array_values($row);
            $code = $codeCol !== null && isset($rowArr[$codeCol]) ? trim((string) $rowArr[$codeCol]) : '';
            $name = $nameCol !== null && isset($rowArr[$nameCol]) ? trim((string) $rowArr[$nameCol]) : '';

            if ($code === '' && $name === '') {
                continue;
            }

            $quantity = 1;
            if (isset($rowArr[$quantityCol]) && is_numeric($rowArr[$quantityCol])) {
                $quantity = max(0.00001, (float) $rowArr[$quantityCol]);
            }

            $price = 0;
            if ($priceCol !== null && isset($rowArr[$priceCol]) && is_numeric($rowArr[$priceCol])) {
                $price = (float) $rowArr[$priceCol];
            }

            $taxType = '1';
            if ($vatCol !== null && isset($rowArr[$vatCol])) {
                $taxType = $this->mapVatToTaxType($rowArr[$vatCol]);
            }

            $article = null;
            if ($code !== '') {
                $article = Article::where('code', $code)->first();
            }
            if (! $article && $name !== '') {
                $article = Article::where('name', $name)->first();
            }

            $isCreate = ! $article;
            if ($article) {
                $code = (string) $article->code;
                $name = (string) $article->name;
                $price = is_numeric($price) && $price > 0 ? $price : (float) ($article->purchase_price ?? 0);
                $taxType = (string) ($article->tax_type ?? $taxType);
            } else {
                // Keep original code/name from file for visibility in preview; row will be dropped on create.
            }

            $preview[] = [
                'code' => $code,
                'name' => $name,
                'quantity' => $quantity,
                'purchase_price' => $price,
                'tax_type' => (string) $taxType,
                'priceWithVAT' => 0,
                'amount' => 0,
                'tax' => 0,
                'total' => 0,
                'comment' => '',
                'import_action' => $isCreate ? 'dropped' : 'update',
                'allow_article_create' => false,
            ];
        }

        return response()->json(['rows' => $preview]);
    }

    public function fetchPriemnica(Request $request)
    {
        $warehouseId = $request->query('warehouse');
        $perPage = $request->query('per_page', 20);

        $receiptsQuery = Priemnica::with(['client', 'articles' => function($query) {
            $query->withPivot('quantity', 'custom_price', 'custom_tax_type');
        }])
            ->leftJoin('warehouses', 'priemnica.warehouse', '=', 'warehouses.id')
            ->select(
                'priemnica.id',
                'priemnica.receipt_number',
                'priemnica.fiscal_year',
                'priemnica.client_id',
                'priemnica.warehouse',
                'priemnica.created_at',
                'warehouses.name as warehouse_name'
            );

        if ($warehouseId && $warehouseId !== 'All') {
            $receiptsQuery->where('priemnica.warehouse', $warehouseId);
        }

        $receipts = $receiptsQuery->get();

        $priemnica = $receiptsQuery->paginate($perPage);

        if ($request->wantsJson()) {
            return response()->json($priemnica);
        }

        return Inertia::render('Warehouse/Index', [
            'priemnica' => $priemnica,
        ]);

    }

    /**
     * Display the specified resource.
     */
    public function show(Priemnica $priemnica)
    {
        $priemnica->load(['client', 'articles' => function($query) {
            $query->withPivot('quantity', 'custom_price', 'custom_tax_type');
        }]);
        
        // Format articles data for frontend
        $articles = $priemnica->articles->map(function ($article) {
            return [
                'id' => $article->id,
                'code' => $article->code,
                'name' => $article->name,
                'purchase_price' => $article->purchase_price,
                'tax_type' => $article->tax_type,
                'quantity' => $article->pivot->quantity,
                'width' => $article->width,
                'height' => $article->height,
            ];
        });

        return response()->json([
            'priemnica' => $priemnica,
            'articles' => $articles
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $priemnica = Priemnica::with(['client', 'articles' => function($query) {
            $query->withPivot('quantity', 'custom_price', 'custom_tax_type');
        }])->findOrFail($id);
        
        // Format articles for the frontend
        $formattedArticles = $priemnica->articles->map(function ($article) {
            return [
                'id' => $article->id,
                'code' => $article->code,
                'name' => $article->name,
                'purchase_price' => $article->purchase_price,
                'tax_type' => $article->tax_type,
                'quantity' => $article->pivot->quantity,
                'width' => $article->width,
                'height' => $article->height,
                'pivot' => [
                    'quantity' => $article->pivot->quantity,
                    'custom_price' => $article->pivot->custom_price,
                    'custom_tax_type' => $article->pivot->custom_tax_type,
                ]
            ];
        });

        return Inertia::render('Priemnica/Edit', [
            'priemnica' => $priemnica,
            'articles' => $formattedArticles,
            'clients' => Client::all(),
            'warehouses' => Warehouse::all(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $data = $request->json()->all();
        $priemnica = Priemnica::findOrFail($id);
        
        // Get original articles with quantities before update
        $originalArticles = $priemnica->articles()
            ->withPivot('quantity')
            ->get()
            ->keyBy('id');

        // Update priemnica basic info
        $priemnica->client_id = $data[0]['client_id'];
        $priemnica->warehouse = $data[0]['warehouse'];
        
        // Update date if provided
        if (isset($data[0]['date']) && !empty($data[0]['date'])) {
            $priemnica->created_at = $data[0]['date'] . ' 00:00:00';
            $priemnica->updated_at = now();
        }
        
        $priemnica->save();

        // Check if this is a special warehouse
        $warehouse = Warehouse::find($priemnica->warehouse);
        $isSpecialWarehouse = $warehouse && $warehouse->isSpecialWarehouse();

        // Process material inventory reversals for original articles
        foreach ($originalArticles as $originalArticle) {
            if ($isSpecialWarehouse && $warehouse->isTradeWarehouse()) {
                // Reverse trade warehouse inventory
                TradeWarehouseInventory::removeStock(
                    $originalArticle->id,
                    $warehouse->id,
                    $originalArticle->pivot->quantity
                );
            } elseif (!$isSpecialWarehouse) {
                // Reverse regular material inventory
                $this->reverseMaterialQuantity($originalArticle, $originalArticle->pivot->quantity);
            }
            // For "магацин 3 основни средства", do nothing
        }

        // Clear existing article associations
        $priemnica->articles()->detach();

        // Add new articles and update materials
        foreach ($data as $row) {
            // Skip processing if required fields are missing
            if (empty($row['name']) && empty($row['code'])) {
                continue;
            }

            $article = null;
            if (!empty($row['code'])) {
                $article = Article::where('code', $row['code'])->first();
            }
            if (!$article && !empty($row['name'])) {
                $article = Article::where('name', $row['name'])->first();
            }

            if (!$article) {
                continue; // Drop rows that do not match an existing article
            }

            if ($article) {
                // Check if custom values are different from original article values
                $customPrice = null;
                $customTaxType = null;
                
                if (isset($row['purchase_price']) && $row['purchase_price'] != $article->purchase_price) {
                    $customPrice = $row['purchase_price'];
                }
                
                if (isset($row['tax_type']) && $row['tax_type'] != $article->tax_type) {
                    $customTaxType = $row['tax_type'];
                }
                
                // Attach new article with quantity and custom values
                $priemnica->articles()->attach($article->id, [
                    'quantity' => $row['quantity'],
                    'custom_price' => $customPrice,
                    'custom_tax_type' => $customTaxType
                ]);
                
                // Update inventory based on warehouse type
                if ($isSpecialWarehouse && $warehouse->isTradeWarehouse()) {
                    // Add to trade warehouse inventory
                    TradeWarehouseInventory::addStock(
                        $article->id,
                        $warehouse->id,
                        $row['quantity'],
                        $customPrice ?? $article->purchase_price,
                        $article->price_1
                    );
                } elseif (!$isSpecialWarehouse) {
                    // Update regular material inventory
                    $this->updateMaterialQuantity($article, $row['quantity']);
                }
                // For "магацин 3 основни средства", do nothing
            }
        }

        return response()->json(['message' => 'Receipt updated successfully'], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->json()->all();
        if (empty($data) || !isset($data[0])) {
            return response()->json(['message' => 'Receipt payload is empty.'], 422);
        }

        $firstRow = $data[0];
        if (empty($firstRow['warehouse']) || empty($firstRow['date'])) {
            return response()->json(['message' => 'Warehouse and date are required.'], 422);
        }

        $client = !empty($firstRow['client_id']) ? Client::find($firstRow['client_id']) : null;
        $warehouseModel = Warehouse::find($firstRow['warehouse']);
        if (($firstRow['client_id'] ?? null) && !$client) {
            return response()->json(['message' => 'Selected client is invalid.'], 422);
        }
        if (!$warehouseModel) {
            return response()->json(['message' => 'Selected warehouse is invalid.'], 422);
        }

        $priemnica = new Priemnica();
        $priemnica->client_id = !empty($firstRow['client_id']) ? $firstRow['client_id'] : null;
        $priemnica->warehouse = $firstRow['warehouse'];
        
        // Set custom date if provided, otherwise use current timestamp
        if (isset($firstRow['date']) && !empty($firstRow['date'])) {
            $priemnica->created_at = $firstRow['date'] . ' 00:00:00';
            $priemnica->updated_at = $firstRow['date'] . ' 00:00:00';
        }
        
        $priemnica->save();

        // Check if this is a special warehouse that should skip material updates
        $warehouse = $warehouseModel;
        $isSpecialWarehouse = $warehouse && $warehouse->isSpecialWarehouse();

        foreach ($data as $row) {
            // Skip processing if the required fields are missing
            if (empty($row['code']) || empty($row['name'])) {
                continue;
            }

            $article = Article::where('code', $row['code'])->first();

            if ($article) {
                // Check if custom values are different from original article values
                $customPrice = null;
                $customTaxType = null;
                
                if (isset($row['purchase_price']) && $row['purchase_price'] != $article->purchase_price) {
                    $customPrice = $row['purchase_price'];
                }
                
                if (isset($row['tax_type']) && $row['tax_type'] != $article->tax_type) {
                    $customTaxType = $row['tax_type'];
                }
                
                $priemnica->articles()->attach($article->id, [
                    'quantity' => $row['quantity'],
                    'custom_price' => $customPrice,
                    'custom_tax_type' => $customTaxType
                ]);

                if ($isSpecialWarehouse) {
                    // For special warehouses, add to trade warehouse inventory instead of material tables
                    if ($warehouse->isTradeWarehouse()) {
                        TradeWarehouseInventory::addStock(
                            $article->id,
                            $warehouse->id,
                            $row['quantity'],
                            $customPrice ?? $article->purchase_price,
                            $article->price_1
                        );
                    }
                    // For "магацин 3 основни средства", we just skip material updates entirely
                } else {
                    // Regular warehouse - update material inventory as before
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
                        $existingMaterial->quantity += $row['quantity'];
                        $existingMaterial->save();
                    } else {
                        // Create a new material with additional data from $data['quantity']
                        if ($article->format_type === 1) {
                            $materialData['quantity'] = $row['quantity'];
                            SmallMaterial::create($materialData);
                        }
                        else if ($article->format_type === 2) {
                            $materialData['quantity'] = $row['quantity'];
                            LargeFormatMaterial::create($materialData);
                        }
                        else {
                            $materialData['quantity'] = $row['quantity'];
                            OtherMaterial::create($materialData);
                        }
                    }
                }
            }
        }

        return response()->json(['message' => 'Receipt added successfully'], 201);
    }

    /**
     * Reverse material quantity (subtract from inventory)
     */
    private function reverseMaterialQuantity($article, $quantity)
    {
        $materialData = [
            'name' => $article->name,
            'width' => $article->width,
            'height' => $article->height,
            'price_per_unit' => $article->purchase_price,
            'article_id' => $article->id
        ];

        if ($article->format_type === 1) {
            $existingMaterial = SmallMaterial::where('name', $materialData['name'])->first();
            if ($existingMaterial) {
                $existingMaterial->quantity = max(0, $existingMaterial->quantity - $quantity);
                $existingMaterial->save();
            }
        } elseif ($article->format_type === 2) {
            $existingMaterial = LargeFormatMaterial::where('name', $materialData['name'])->first();
            if ($existingMaterial) {
                $existingMaterial->quantity = max(0, $existingMaterial->quantity - $quantity);
                $existingMaterial->save();
            }
        } elseif ($article->format_type === 3) {
            $existingMaterial = OtherMaterial::where('name', $materialData['name'])->first();
            if ($existingMaterial) {
                $existingMaterial->quantity = max(0, $existingMaterial->quantity - $quantity);
                $existingMaterial->save();
            }
        }
    }

    /**
     * Update material quantity (add to inventory)
     */
    private function updateMaterialQuantity($article, $quantity)
    {
        $materialData = [
            'name' => $article->name,
            'width' => $article->width,
            'height' => $article->height,
            'price_per_unit' => $article->purchase_price,
            'article_id' => $article->id
        ];

        if ($article->format_type === 1) {
            $existingMaterial = SmallMaterial::where('name', $materialData['name'])->first();
            if ($existingMaterial) {
                $existingMaterial->quantity += $quantity;
                $existingMaterial->save();
            } else {
                $materialData['quantity'] = $quantity;
                SmallMaterial::create($materialData);
            }
        } elseif ($article->format_type === 2) {
            $existingMaterial = LargeFormatMaterial::where('name', $materialData['name'])->first();
            if ($existingMaterial) {
                $existingMaterial->quantity += $quantity;
                $existingMaterial->save();
            } else {
                $materialData['quantity'] = $quantity;
                LargeFormatMaterial::create($materialData);
            }
        } elseif ($article->format_type === 3) {
            $existingMaterial = OtherMaterial::where('name', $materialData['name'])->first();
            if ($existingMaterial) {
                $existingMaterial->quantity += $quantity;
                $existingMaterial->save();
            } else {
                $materialData['quantity'] = $quantity;
                OtherMaterial::create($materialData);
            }
        }
    }

    /**
     * Get material import summary across all receipts
     */
    public function getMaterialImportSummary()
    {
        $materialSummary = DB::table('priemnica_articles')
            ->join('article', 'priemnica_articles.article_id', '=', 'article.id')
            ->join('priemnica', 'priemnica_articles.priemnica_id', '=', 'priemnica.id')
            ->select(
                'article.id as article_id',
                'article.code',
                'article.name',
                'article.format_type',
                'article.width',
                'article.height',
                DB::raw('SUM(priemnica_articles.quantity) as total_imported'),
                DB::raw('AVG(COALESCE(priemnica_articles.custom_price, article.purchase_price)) as average_price'),
                DB::raw('SUM(priemnica_articles.quantity * COALESCE(priemnica_articles.custom_price, article.purchase_price)) as total_value'),
                DB::raw('MAX(priemnica.created_at) as last_import_date')
            )
            ->groupBy('article.id', 'article.code', 'article.name', 'article.format_type', 'article.width', 'article.height')
            ->orderBy('total_imported', 'desc')
            ->get();

        // Add current stock information for each material
        $materialSummary = $materialSummary->map(function ($material) {
            $currentStock = 0;
            
            if ($material->format_type == 1) {
                $smallMaterial = SmallMaterial::where('article_id', $material->article_id)->first();
                $currentStock = $smallMaterial ? $smallMaterial->quantity : 0;
            } elseif ($material->format_type == 2) {
                $largeMaterial = LargeFormatMaterial::where('article_id', $material->article_id)->first();
                $currentStock = $largeMaterial ? $largeMaterial->quantity : 0;
            } elseif ($material->format_type == 3) {
                $otherMaterial = OtherMaterial::where('article_id', $material->article_id)->first();
                $currentStock = $otherMaterial ? $otherMaterial->quantity : 0;
            }
            
            $material->current_stock = $currentStock;
            return $material;
        });

        return response()->json($materialSummary);
    }

    private function findHeaderIndex(array $headers, array $aliases): ?int
    {
        foreach ($headers as $idx => $header) {
            foreach ($aliases as $alias) {
                if ($header === mb_strtolower($alias)) {
                    return $idx;
                }
            }
        }
        return null;
    }

    private function mapVatToTaxType($value): string
    {
        $raw = trim((string) $value);
        if ($raw === '') {
            return '1';
        }
        if (is_numeric($raw)) {
            $num = (float) $raw;
            if ($num == 18 || $num == 1) return '1';
            if ($num == 5 || $num == 2) return '2';
            if ($num == 10 || $num == 3) return '3';
            if ($num == 0) return '0';
        }

        $normalized = mb_strtolower($raw);
        if (str_contains($normalized, '18') || str_contains($normalized, 'ddv a')) return '1';
        if (str_contains($normalized, '5') || str_contains($normalized, 'ddv b')) return '2';
        if (str_contains($normalized, '10') || str_contains($normalized, 'ddv c')) return '3';
        if (str_contains($normalized, '0')) return '0';

        return '1';
    }

    private function getNextArticleCode(): int
    {
        $maxCode = Article::selectRaw('MAX(CAST(code AS UNSIGNED)) as max_code')->value('max_code');
        return $maxCode ? ((int) $maxCode + 1) : 1;
    }
}
