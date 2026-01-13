<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Faktura;
use App\Models\FiscalYearClosure;
use App\Models\Priemnica;
use App\Models\StockRealization;
use App\Models\Article;
use App\Models\SmallMaterial;
use App\Models\LargeFormatMaterial;
use App\Models\OtherMaterial;
use App\Models\Certificate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class YearEndCensusController extends Controller
{
    public function index(Request $request)
    {
        $availableYears = Invoice::selectRaw('DISTINCT fiscal_year')
            ->whereNotNull('fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year');

        $invoiceYears = Faktura::selectRaw('DISTINCT fiscal_year')
            ->whereNotNull('fiscal_year')
            ->where('isInvoiced', true)
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year');

        // Get years from priemnica and stock_realizations for materials tab
        // For stock realizations, use the ORDER's fiscal year (not the realization's)
        $priemnicaYears = Priemnica::selectRaw('DISTINCT fiscal_year')
            ->whereNotNull('fiscal_year')
            ->pluck('fiscal_year');
        
        $stockRealizationYears = DB::table('stock_realizations')
            ->join('invoices', 'stock_realizations.invoice_id', '=', 'invoices.id')
            ->where('stock_realizations.is_realized', true)
            ->whereNotNull('invoices.fiscal_year')
            ->selectRaw('DISTINCT invoices.fiscal_year')
            ->pluck('fiscal_year');
        
        $materialYears = $priemnicaYears->merge($stockRealizationYears)
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        // Get years from bank statements (certificates)
        $bankStatementYears = Certificate::selectRaw('DISTINCT fiscal_year')
            ->whereNotNull('fiscal_year')
            ->orderBy('fiscal_year', 'desc')
            ->pluck('fiscal_year');

        return Inertia::render('YearEndCensus/Index', [
            'availableYears' => $availableYears,
            'invoiceYears' => $invoiceYears,
            'materialYears' => $materialYears,
            'bankStatementYears' => $bankStatementYears,
        ]);
    }

    public function getOrdersSummary(Request $request, int $year)
    {
        $stats = [
            'total' => Invoice::forFiscalYear($year)->count(),
            'completed' => Invoice::forFiscalYear($year)->where('status', 'Completed')->count(),
            'in_progress' => Invoice::forFiscalYear($year)->where('status', 'In Progress')->count(),
            'not_started' => Invoice::forFiscalYear($year)->where('status', 'Not started yet')->count(),
            'archived' => Invoice::forFiscalYear($year)->where('archived', true)->count(),
        ];

        $transferOrders = Invoice::forFiscalYear($year)
            ->whereIn('status', ['In Progress', 'Not started yet'])
            ->with('client:id,name')
            ->select('id', 'order_number', 'fiscal_year', 'invoice_title', 'client_id', 'status')
            ->get();

        $closure = FiscalYearClosure::getClosureInfo($year, 'orders');

        return response()->json([
            'stats' => $stats,
            'transferOrders' => $transferOrders,
            'closure' => $closure,
            'isClosed' => $closure !== null,
        ]);
    }

    public function archiveCompletedOrders(Request $request, int $year)
    {
        if (FiscalYearClosure::isYearClosed($year, 'orders')) {
            return response()->json(['error' => 'Year is already closed'], 422);
        }

        $count = Invoice::forFiscalYear($year)
            ->where('status', 'Completed')
            ->where('archived', false)
            ->update([
                'archived' => true,
                'archived_at' => now(),
            ]);

        return response()->json([
            'message' => "Archived {$count} completed orders",
            'archived_count' => $count,
        ]);
    }

    public function closeYear(Request $request, int $year)
    {
        if (FiscalYearClosure::isYearClosed($year, 'orders')) {
            return response()->json(['error' => 'Year is already closed'], 422);
        }

        $stats = [
            'total' => Invoice::forFiscalYear($year)->count(),
            'completed' => Invoice::forFiscalYear($year)->where('status', 'Completed')->count(),
            'in_progress' => Invoice::forFiscalYear($year)->where('status', 'In Progress')->count(),
            'not_started' => Invoice::forFiscalYear($year)->where('status', 'Not started yet')->count(),
            'archived' => Invoice::forFiscalYear($year)->where('archived', true)->count(),
        ];

        $incompleteCount = $stats['in_progress'] + $stats['not_started'];

        // If there are incomplete orders and user hasn't confirmed, return warning
        if ($incompleteCount > 0 && !$request->boolean('confirmed')) {
            $incompleteOrders = Invoice::forFiscalYear($year)
                ->whereIn('status', ['In Progress', 'Not started yet'])
                ->with('client:id,name')
                ->select('id', 'order_number', 'fiscal_year', 'invoice_title', 'client_id', 'status')
                ->get();

            return response()->json([
                'requires_confirmation' => true,
                'message' => "There are {$incompleteCount} incomplete orders that will need to be transferred to the next fiscal year.",
                'incomplete_orders' => $incompleteOrders,
                'stats' => $stats,
            ], 200);
        }

        $closure = FiscalYearClosure::create([
            'fiscal_year' => $year,
            'module' => 'orders',
            'closed_at' => now(),
            'closed_by' => auth()->id(),
            'summary' => $stats,
        ]);

        return response()->json([
            'message' => "Fiscal year {$year} closed successfully",
            'closure' => $closure->load('closedByUser'),
        ]);
    }

    public function exportYearSummary(Request $request, int $year)
    {
        $stats = [
            'total' => Invoice::forFiscalYear($year)->count(),
            'completed' => Invoice::forFiscalYear($year)->where('status', 'Completed')->count(),
            'in_progress' => Invoice::forFiscalYear($year)->where('status', 'In Progress')->count(),
            'not_started' => Invoice::forFiscalYear($year)->where('status', 'Not started yet')->count(),
        ];

        $completedOrders = Invoice::forFiscalYear($year)
            ->where('status', 'Completed')
            ->with('client:id,name')
            ->select('id', 'order_number', 'fiscal_year', 'invoice_title', 'client_id', 'end_date')
            ->orderBy('order_number')
            ->get();

        $transferOrders = Invoice::forFiscalYear($year)
            ->whereIn('status', ['In Progress', 'Not started yet'])
            ->with('client:id,name')
            ->select('id', 'order_number', 'fiscal_year', 'invoice_title', 'client_id', 'status')
            ->orderBy('order_number')
            ->get();

        $closure = FiscalYearClosure::getClosureInfo($year, 'orders');

        $pdf = Pdf::loadView('pdf.year-end-summary', [
            'year' => $year,
            'stats' => $stats,
            'completedOrders' => $completedOrders,
            'transferOrders' => $transferOrders,
            'closure' => $closure,
            'exportedBy' => auth()->user()->name,
            'exportedAt' => now(),
        ]);

        return $pdf->download("year-end-summary-orders-{$year}.pdf");
    }

    /**
     * Get invoices summary for a fiscal year
     */
    public function getInvoicesSummary(Request $request, int $year)
    {
        $invoices = Faktura::forFiscalYear($year)
            ->where('isInvoiced', true)
            ->with(['client:id,name', 'invoices:id,faktura_id,invoice_title'])
            ->orderBy('faktura_number')
            ->get();

        // Calculate totals from related jobs and additional services
        $invoicesData = $invoices->map(function ($faktura) {
            $totalAmount = 0;
            $totalVat = 0;
            $ordersCount = $faktura->invoices->count();

            // Get jobs total
            $jobs = $faktura->jobs()->get();
            foreach ($jobs as $job) {
                $jobTotal = $job->quantity * $job->price;
                $totalAmount += $jobTotal;
                if ($job->vat_rate) {
                    $totalVat += $jobTotal * ($job->vat_rate / 100);
                }
            }

            // Get additional services total
            $services = $faktura->additionalServices()->get();
            foreach ($services as $service) {
                $serviceTotal = $service->quantity * $service->price;
                $totalAmount += $serviceTotal;
                if ($service->vat_rate) {
                    $totalVat += $serviceTotal * ($service->vat_rate / 100);
                }
            }

            // Get trade items total
            $tradeItems = $faktura->tradeItems()->get();
            foreach ($tradeItems as $item) {
                $itemTotal = $item->quantity * $item->price;
                $totalAmount += $itemTotal;
                if ($item->vat_rate) {
                    $totalVat += $itemTotal * ($item->vat_rate / 100);
                }
            }

            return [
                'id' => $faktura->id,
                'faktura_number' => $faktura->faktura_number,
                'fiscal_year' => $faktura->fiscal_year,
                'created_at' => $faktura->created_at,
                'client_name' => $faktura->client->name ?? null,
                'orders_count' => $ordersCount,
                'total_amount' => $totalAmount,
                'total_vat' => $totalVat,
            ];
        });

        $stats = [
            'total' => $invoices->count(),
            'total_revenue' => $invoicesData->sum('total_amount'),
            'total_vat' => $invoicesData->sum('total_vat'),
            'orders_count' => $invoicesData->sum('orders_count'),
        ];

        return response()->json([
            'stats' => $stats,
            'invoices' => $invoicesData,
        ]);
    }

    /**
     * Export invoices summary PDF for a fiscal year
     */
    public function exportInvoicesSummary(Request $request, int $year)
    {
        $invoices = Faktura::forFiscalYear($year)
            ->where('isInvoiced', true)
            ->with(['client:id,name', 'invoices:id,faktura_id,invoice_title'])
            ->orderBy('faktura_number')
            ->get();

        $invoicesData = $invoices->map(function ($faktura) {
            $totalAmount = 0;
            $totalVat = 0;
            $ordersCount = $faktura->invoices->count();

            $jobs = $faktura->jobs()->get();
            foreach ($jobs as $job) {
                $jobTotal = $job->quantity * $job->price;
                $totalAmount += $jobTotal;
                if ($job->vat_rate) {
                    $totalVat += $jobTotal * ($job->vat_rate / 100);
                }
            }

            $services = $faktura->additionalServices()->get();
            foreach ($services as $service) {
                $serviceTotal = $service->quantity * $service->price;
                $totalAmount += $serviceTotal;
                if ($service->vat_rate) {
                    $totalVat += $serviceTotal * ($service->vat_rate / 100);
                }
            }

            $tradeItems = $faktura->tradeItems()->get();
            foreach ($tradeItems as $item) {
                $itemTotal = $item->quantity * $item->price;
                $totalAmount += $itemTotal;
                if ($item->vat_rate) {
                    $totalVat += $itemTotal * ($item->vat_rate / 100);
                }
            }

            return [
                'id' => $faktura->id,
                'faktura_number' => $faktura->faktura_number,
                'fiscal_year' => $faktura->fiscal_year,
                'created_at' => $faktura->created_at,
                'client_name' => $faktura->client->name ?? null,
                'orders_count' => $ordersCount,
                'total_amount' => $totalAmount,
                'total_vat' => $totalVat,
            ];
        });

        $stats = [
            'total' => $invoices->count(),
            'total_revenue' => $invoicesData->sum('total_amount'),
            'total_vat' => $invoicesData->sum('total_vat'),
            'orders_count' => $invoicesData->sum('orders_count'),
        ];

        $pdf = Pdf::loadView('pdf.year-end-invoices-summary', [
            'year' => $year,
            'stats' => $stats,
            'invoices' => $invoicesData,
            'exportedBy' => auth()->user()->name,
            'exportedAt' => now(),
        ]);

        return $pdf->download("year-end-summary-invoices-{$year}.pdf");
    }

    /**
     * Get materials summary for a fiscal year
     * Shows: Imported (from priemnica) - Used (from stock realizations) = Balance
     * Stock realizations are filtered by the linked ORDER's fiscal year, not the realization's creation date
     */
    public function getMaterialsSummary(Request $request, int $year)
    {
        // Get all imported articles from priemnica for this fiscal year
        $imported = DB::table('priemnica_articles')
            ->join('priemnica', 'priemnica_articles.priemnica_id', '=', 'priemnica.id')
            ->join('article', 'priemnica_articles.article_id', '=', 'article.id')
            ->where('priemnica.fiscal_year', $year)
            ->select(
                'article.id as article_id',
                'article.name as article_name',
                'article.code as article_code',
                DB::raw('SUM(priemnica_articles.quantity) as total_imported')
            )
            ->groupBy('article.id', 'article.name', 'article.code')
            ->get()
            ->keyBy('article_id');

        // Get all used articles from realized stock realizations
        // Filter by the ORDER's fiscal year (via invoice_id -> invoices.fiscal_year)
        $used = DB::table('stock_realization_articles')
            ->join('stock_realization_jobs', 'stock_realization_articles.stock_realization_job_id', '=', 'stock_realization_jobs.id')
            ->join('stock_realizations', 'stock_realization_jobs.stock_realization_id', '=', 'stock_realizations.id')
            ->join('invoices', 'stock_realizations.invoice_id', '=', 'invoices.id')
            ->join('article', 'stock_realization_articles.article_id', '=', 'article.id')
            ->where('invoices.fiscal_year', $year)  // Use ORDER's fiscal year
            ->where('stock_realizations.is_realized', true)
            ->select(
                'article.id as article_id',
                'article.name as article_name',
                'article.code as article_code',
                DB::raw('SUM(stock_realization_articles.quantity) as total_used')
            )
            ->groupBy('article.id', 'article.name', 'article.code')
            ->get()
            ->keyBy('article_id');

        // Merge and calculate balance for all articles
        $allArticleIds = $imported->keys()->merge($used->keys())->unique();
        
        $materials = [];
        foreach ($allArticleIds as $articleId) {
            $importedQty = $imported->get($articleId)->total_imported ?? 0;
            $usedQty = $used->get($articleId)->total_used ?? 0;
            $balance = $importedQty - $usedQty;
            
            $articleName = $imported->get($articleId)->article_name ?? $used->get($articleId)->article_name ?? 'Unknown';
            $articleCode = $imported->get($articleId)->article_code ?? $used->get($articleId)->article_code ?? '';
            
            $materials[] = [
                'article_id' => $articleId,
                'article_name' => $articleName,
                'article_code' => $articleCode,
                'imported' => (float) $importedQty,
                'used' => (float) $usedQty,
                'balance' => (float) $balance,
                'adjustment' => 0, // User can modify this
            ];
        }

        // Sort by article name
        usort($materials, fn($a, $b) => strcmp($a['article_name'], $b['article_name']));

        // Count realizations by order's fiscal year
        $realizationsCount = DB::table('stock_realizations')
            ->join('invoices', 'stock_realizations.invoice_id', '=', 'invoices.id')
            ->where('invoices.fiscal_year', $year)
            ->where('stock_realizations.is_realized', true)
            ->count();

        $stats = [
            'total_articles' => count($materials),
            'total_imported' => array_sum(array_column($materials, 'imported')),
            'total_used' => array_sum(array_column($materials, 'used')),
            'receipts_count' => Priemnica::forFiscalYear($year)->count(),
            'realizations_count' => $realizationsCount,
        ];

        $closure = FiscalYearClosure::getClosureInfo($year, 'materials');

        return response()->json([
            'stats' => $stats,
            'materials' => $materials,
            'closure' => $closure,
            'isClosed' => $closure !== null,
        ]);
    }

    /**
     * Apply material adjustments and close the materials fiscal year
     */
    public function closeMaterialsYear(Request $request, int $year)
    {
        if (FiscalYearClosure::isYearClosed($year, 'materials')) {
            return response()->json(['error' => 'Materials year is already closed'], 422);
        }

        $adjustments = $request->input('adjustments', []);
        
        try {
            DB::beginTransaction();

            $adjustedCount = 0;
            $adjustmentLog = [];

            foreach ($adjustments as $adjustment) {
                $articleId = $adjustment['article_id'];
                $adjustmentQty = (float) ($adjustment['adjustment'] ?? 0);
                
                if ($adjustmentQty == 0) {
                    continue;
                }

                // Find the article and its associated material
                $article = Article::find($articleId);
                if (!$article) {
                    continue;
                }

                $materialUpdated = false;
                $oldQty = 0;
                $newQty = 0;

                // Update the appropriate material table based on format_type
                if ($article->format_type == 1) {
                    // Small material
                    $material = SmallMaterial::where('article_id', $articleId)->first();
                    if ($material) {
                        $oldQty = $material->quantity;
                        $material->quantity = max(0, $material->quantity + $adjustmentQty);
                        $newQty = $material->quantity;
                        $material->save();
                        $materialUpdated = true;
                    }
                } elseif ($article->format_type == 2) {
                    // Large format material
                    $material = LargeFormatMaterial::where('article_id', $articleId)->first();
                    if ($material) {
                        $oldQty = $material->quantity;
                        $material->quantity = max(0, $material->quantity + $adjustmentQty);
                        $newQty = $material->quantity;
                        $material->save();
                        $materialUpdated = true;
                    }
                } elseif ($article->format_type == 3) {
                    // Other material
                    $material = OtherMaterial::where('article_id', $articleId)->first();
                    if ($material) {
                        $oldQty = $material->quantity;
                        $material->quantity = max(0, $material->quantity + $adjustmentQty);
                        $newQty = $material->quantity;
                        $material->save();
                        $materialUpdated = true;
                    }
                }

                if ($materialUpdated) {
                    $adjustedCount++;
                    $adjustmentLog[] = [
                        'article_id' => $articleId,
                        'article_name' => $article->name,
                        'adjustment' => $adjustmentQty,
                        'old_quantity' => $oldQty,
                        'new_quantity' => $newQty,
                    ];
                }
            }

            // Create closure record
            $closure = FiscalYearClosure::create([
                'fiscal_year' => $year,
                'module' => 'materials',
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'summary' => [
                    'adjusted_count' => $adjustedCount,
                    'adjustments' => $adjustmentLog,
                ],
            ]);

            DB::commit();

            return response()->json([
                'message' => "Materials fiscal year {$year} closed successfully. {$adjustedCount} materials adjusted.",
                'closure' => $closure->load('closedByUser'),
                'adjusted_count' => $adjustedCount,
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Materials year close error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to close materials year: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export materials summary PDF for a fiscal year
     */
    public function exportMaterialsSummary(Request $request, int $year)
    {
        // Get the same data as getMaterialsSummary
        $imported = DB::table('priemnica_articles')
            ->join('priemnica', 'priemnica_articles.priemnica_id', '=', 'priemnica.id')
            ->join('article', 'priemnica_articles.article_id', '=', 'article.id')
            ->where('priemnica.fiscal_year', $year)
            ->select(
                'article.id as article_id',
                'article.name as article_name',
                'article.code as article_code',
                DB::raw('SUM(priemnica_articles.quantity) as total_imported')
            )
            ->groupBy('article.id', 'article.name', 'article.code')
            ->get()
            ->keyBy('article_id');

        // Filter by ORDER's fiscal year for stock realizations
        $used = DB::table('stock_realization_articles')
            ->join('stock_realization_jobs', 'stock_realization_articles.stock_realization_job_id', '=', 'stock_realization_jobs.id')
            ->join('stock_realizations', 'stock_realization_jobs.stock_realization_id', '=', 'stock_realizations.id')
            ->join('invoices', 'stock_realizations.invoice_id', '=', 'invoices.id')
            ->join('article', 'stock_realization_articles.article_id', '=', 'article.id')
            ->where('invoices.fiscal_year', $year)  // Use ORDER's fiscal year
            ->where('stock_realizations.is_realized', true)
            ->select(
                'article.id as article_id',
                'article.name as article_name',
                'article.code as article_code',
                DB::raw('SUM(stock_realization_articles.quantity) as total_used')
            )
            ->groupBy('article.id', 'article.name', 'article.code')
            ->get()
            ->keyBy('article_id');

        $allArticleIds = $imported->keys()->merge($used->keys())->unique();
        
        $materials = [];
        foreach ($allArticleIds as $articleId) {
            $importedQty = $imported->get($articleId)->total_imported ?? 0;
            $usedQty = $used->get($articleId)->total_used ?? 0;
            
            $materials[] = [
                'article_name' => $imported->get($articleId)->article_name ?? $used->get($articleId)->article_name ?? 'Unknown',
                'article_code' => $imported->get($articleId)->article_code ?? $used->get($articleId)->article_code ?? '',
                'imported' => (float) $importedQty,
                'used' => (float) $usedQty,
                'balance' => (float) ($importedQty - $usedQty),
            ];
        }

        usort($materials, fn($a, $b) => strcmp($a['article_name'], $b['article_name']));

        $stats = [
            'total_articles' => count($materials),
            'total_imported' => array_sum(array_column($materials, 'imported')),
            'total_used' => array_sum(array_column($materials, 'used')),
        ];

        $closure = FiscalYearClosure::getClosureInfo($year, 'materials');

        $pdf = Pdf::loadView('pdf.year-end-materials-summary', [
            'year' => $year,
            'stats' => $stats,
            'materials' => $materials,
            'closure' => $closure,
            'exportedBy' => auth()->user()->name,
            'exportedAt' => now(),
        ]);

        return $pdf->download("year-end-summary-materials-{$year}.pdf");
    }

    /**
     * Get bank statements summary for a fiscal year
     */
    public function getBankStatementsSummary(Request $request, int $year)
    {
        $statements = Certificate::forFiscalYear($year)
            ->with('createdBy:id,name')
            ->orderBy('id_per_bank')
            ->get();

        $stats = [
            'total' => $statements->count(),
            'archived' => $statements->where('archived', true)->count(),
            'active' => $statements->where('archived', false)->count(),
        ];

        $statementsData = $statements->map(function ($statement) {
            return [
                'id' => $statement->id,
                'id_per_bank' => $statement->id_per_bank,
                'fiscal_year' => $statement->fiscal_year,
                'bank' => $statement->bank,
                'bankAccount' => $statement->bankAccount,
                'date' => $statement->date,
                'created_by' => $statement->createdBy?->name,
                'archived' => $statement->archived,
                'archived_at' => $statement->archived_at,
            ];
        });

        $closure = FiscalYearClosure::getClosureInfo($year, 'bank_statements');

        return response()->json([
            'stats' => $stats,
            'statements' => $statementsData,
            'closure' => $closure,
            'isClosed' => $closure !== null,
        ]);
    }

    /**
     * Archive all bank statements for a fiscal year
     */
    public function archiveBankStatements(Request $request, int $year)
    {
        if (FiscalYearClosure::isYearClosed($year, 'bank_statements')) {
            return response()->json(['error' => 'Year is already closed'], 422);
        }

        $count = Certificate::forFiscalYear($year)
            ->where('archived', false)
            ->update([
                'archived' => true,
                'archived_at' => now(),
            ]);

        return response()->json([
            'message' => "Archived {$count} bank statements",
            'archived_count' => $count,
        ]);
    }

    /**
     * Close the fiscal year for bank statements
     */
    public function closeBankStatementsYear(Request $request, int $year)
    {
        if (FiscalYearClosure::isYearClosed($year, 'bank_statements')) {
            return response()->json(['error' => 'Year is already closed'], 422);
        }

        try {
            DB::beginTransaction();

            // Archive all statements from this fiscal year
            $archivedCount = Certificate::forFiscalYear($year)
                ->where('archived', false)
                ->update([
                    'archived' => true,
                    'archived_at' => now(),
                ]);

            $stats = [
                'total' => Certificate::forFiscalYear($year)->count(),
                'archived_count' => $archivedCount,
            ];

            // Create closure record
            $closure = FiscalYearClosure::create([
                'fiscal_year' => $year,
                'module' => 'bank_statements',
                'closed_at' => now(),
                'closed_by' => auth()->id(),
                'summary' => $stats,
            ]);

            DB::commit();

            return response()->json([
                'message' => "Bank statements fiscal year {$year} closed successfully. {$archivedCount} statements archived.",
                'closure' => $closure->load('closedByUser'),
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Bank statements year close error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to close bank statements year: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Export bank statements summary PDF for a fiscal year
     */
    public function exportBankStatementsSummary(Request $request, int $year)
    {
        $statements = Certificate::forFiscalYear($year)
            ->with('createdBy:id,name')
            ->orderBy('id_per_bank')
            ->get();

        $statementsData = $statements->map(function ($statement) {
            return [
                'id_per_bank' => $statement->id_per_bank,
                'fiscal_year' => $statement->fiscal_year,
                'bank' => $statement->bank,
                'bankAccount' => $statement->bankAccount,
                'date' => $statement->date,
                'created_by' => $statement->createdBy?->name,
                'archived' => $statement->archived,
            ];
        });

        $stats = [
            'total' => $statements->count(),
            'archived' => $statements->where('archived', true)->count(),
        ];

        $closure = FiscalYearClosure::getClosureInfo($year, 'bank_statements');

        $pdf = Pdf::loadView('pdf.year-end-bank-statements-summary', [
            'year' => $year,
            'stats' => $stats,
            'statements' => $statementsData,
            'closure' => $closure,
            'exportedBy' => auth()->user()->name,
            'exportedAt' => now(),
        ]);

        return $pdf->download("year-end-summary-bank-statements-{$year}.pdf");
    }
}
