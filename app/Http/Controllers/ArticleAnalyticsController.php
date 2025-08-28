<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Job;
use App\Models\Invoice;
use App\Models\OtherMaterial;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ArticleAnalyticsController extends Controller
{
    /**
     * Get current stock information for an article
     */
    public function getStockInfo(Article $article)
    {
        try {
            $stockData = [];

            // Check material type and get appropriate stock info
            if ($article->format_type == 1 && $article->smallMaterial) {
                // Small material
                $stockData = [
                    'type' => 'small_material',
                    'current_stock' => $article->smallMaterial->quantity ?? 0,
                    'material_name' => $article->smallMaterial->name ?? $article->name,
                    'dimensions' => [
                        'width' => $article->smallMaterial->width ?? $article->width,
                        'height' => $article->smallMaterial->height ?? $article->height,
                    ],
                    'warehouse_location' => 'Small Format Materials Warehouse'
                ];
            } elseif ($article->format_type == 2 && $article->largeFormatMaterial) {
                // Large material
                $stockData = [
                    'type' => 'large_material',
                    'current_stock' => $article->largeFormatMaterial->quantity ?? 0,
                    'material_name' => $article->largeFormatMaterial->name ?? $article->name,
                    'price_per_unit' => $article->largeFormatMaterial->price_per_unit ?? null,
                    'warehouse_location' => 'Large Format Materials Warehouse'
                ];
            } else {
                // Product/service - calculate from inventory movements
                $stockData = [
                    'type' => 'product',
                    'current_stock' => $article->getCurrentStock(),
                    'material_name' => $article->name,
                    'warehouse_location' => 'General Warehouse'
                ];
            }

            return response()->json([
                'stock' => $stockData,
                'low_stock_threshold' => 10, // You can make this configurable
                'is_low_stock' => ($stockData['current_stock'] ?? 0) < 10
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching stock info: ' . $e->getMessage());
            return response()->json([
                'stock' => [
                    'type' => 'unknown',
                    'current_stock' => 0,
                    'material_name' => $article->name,
                    'warehouse_location' => 'Unknown'
                ],
                'low_stock_threshold' => 10,
                'is_low_stock' => true
            ]);
        }
    }

    /**
     * Get invoices/orders where this article is used
     */
    public function getOrderUsage(Article $article, Request $request)
    {
        try {
            $articleId = (int) $article->id;
            $lowerName = strtolower($article->name);
            $smallMaterialId = optional($article->smallMaterial)->id;
            $largeMaterialId = optional($article->largeFormatMaterial)->id;

            // Base dataset: invoices x jobs potentially using this article (via pivot or legacy material fields)
            $rows = DB::table('invoice_job')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->join('jobs', 'jobs.id', '=', 'invoice_job.job_id')
                ->leftJoin('job_articles', function ($join) use ($articleId) {
                    $join->on('job_articles.job_id', '=', 'jobs.id')
                         ->where('job_articles.article_id', '=', $articleId);
                })
                ->leftJoin('small_material', 'small_material.id', '=', 'jobs.small_material_id')
                ->leftJoin('large_material', 'large_material.id', '=', 'jobs.large_material_id')
                ->select([
                    'invoices.id as invoice_id',
                    'invoices.title as invoice_title',
                    'invoices.start_date',
                    'invoices.status',
                    'jobs.id as job_id',
                    'jobs.quantity as job_quantity',
                    'jobs.copies as job_copies',
                    'jobs.total_area_m2',
                    'job_articles.quantity as pivot_quantity',
                    'small_material.name as small_material_name',
                    'large_material.name as large_material_name',
                    'jobs.small_material_id',
                    'jobs.large_material_id'
                ])
                ->where(function ($query) use ($articleId, $smallMaterialId, $largeMaterialId, $lowerName) {
                    // Match via pivot table (most accurate)
                    $query->whereExists(function ($subQuery) use ($articleId) {
                        $subQuery->select(DB::raw(1))
                                ->from('job_articles')
                                ->whereColumn('job_articles.job_id', 'jobs.id')
                                ->where('job_articles.article_id', $articleId);
                    })
                    // Or match via material foreign keys
                    ->orWhere('jobs.small_material_id', $smallMaterialId)
                    ->orWhere('jobs.large_material_id', $largeMaterialId)
                    // Or match via material names (fallback)
                    ->orWhere('small_material.name', 'LIKE', "%{$lowerName}%")
                    ->orWhere('large_material.name', 'LIKE', "%{$lowerName}%");
                })
                ->when($request->filled('search'), function ($query) use ($request) {
                    $search = $request->search;
                    $query->where(function ($q) use ($search) {
                        $q->where('invoices.title', 'LIKE', "%{$search}%")
                          ->orWhereExists(function ($subQuery) use ($search) {
                              $subQuery->select(DB::raw(1))
                                      ->from('clients')
                                      ->whereColumn('clients.id', 'invoices.client_id')
                                      ->where('clients.name', 'LIKE', "%{$search}%");
                          });
                    });
                })
                ->when($request->filled('client_id'), function ($query) use ($request) {
                    $query->where('invoices.client_id', $request->client_id);
                })
                ->when($request->filled('start_date'), function ($query) use ($request) {
                    $query->where('invoices.start_date', '>=', $request->start_date);
                })
                ->when($request->filled('end_date'), function ($query) use ($request) {
                    $query->where('invoices.start_date', '<=', $request->end_date);
                })
                ->orderBy('invoices.start_date', 'desc')
                ->get();

            // Shape the response per invoice, exposing raw job entries so FE can aggregate per invoice
            $byInvoice = $rows->groupBy('invoice_id')->map(function ($rowsForInvoice, $invoiceId) use ($articleId, $smallMaterialId, $largeMaterialId, $lowerName) {
                $first = $rowsForInvoice->first();
                $jobs = [];
                foreach ($rowsForInvoice as $r) {
                    // Determine how this job matched the article
                    $usedVia = null;
                    if (!is_null($r->pivot_quantity)) {
                        $usedVia = 'pivot';
                    } elseif ($smallMaterialId && $r->small_material_id === $smallMaterialId) {
                        $usedVia = 'small_fk';
                    } elseif ($largeMaterialId && $r->large_material_id === $largeMaterialId) {
                        $usedVia = 'large_fk';
                    } elseif ($r->small_material_name && strtolower($r->small_material_name) === $lowerName) {
                        $usedVia = 'small_name';
                    } elseif ($r->large_material_name && strtolower($r->large_material_name) === $lowerName) {
                        $usedVia = 'large_name';
                    }

                    // Calculate quantity using new area-based method when available
                    $calculatedQuantity = $this->calculateJobQuantity($r);

                    $jobs[] = [
                        'job_id' => (int) $r->job_id,
                        'used_via' => $usedVia, // pivot | small_fk | large_fk | small_name | large_name | null
                        'pivot_quantity' => is_null($r->pivot_quantity) ? null : (float) $r->pivot_quantity,
                        'fallback_quantity' => $calculatedQuantity,
                        'job_quantity' => (int) ($r->job_quantity ?? 0),
                        'job_copies' => (int) ($r->job_copies ?? 0),
                        'total_area_m2' => $r->total_area_m2 ? (float) $r->total_area_m2 : null,
                        'calculation_method' => $r->total_area_m2 ? 'area_based' : 'legacy_quantity_copies'
                    ];
                }

                return [
                    'invoice_id' => (int) $invoiceId,
                    'invoice_title' => $first->invoice_title,
                    'client_name' => $first->client_name,
                    'start_date' => $first->start_date,
                    'status' => $first->status,
                    'jobs' => $jobs,
                ];
            })->values();

            // Apply usage/value filters after computing invoice totals
            if ($minUsage !== null || $maxUsage !== null || $minValue !== null || $maxValue !== null) {
                $unitPrice = (float) ($article->price_1 ?? 0);
                $byInvoice = $byInvoice->filter(function ($inv) use ($minUsage, $maxUsage, $minValue, $maxValue, $unitPrice) {
                    $qty = 0;
                    foreach ($inv['jobs'] as $j) {
                        if (($j['used_via'] ?? null) === 'pivot') {
                            $qty += (float) ($j['pivot_quantity'] ?? 0);
                        } elseif (in_array($j['used_via'] ?? '', ['small_fk','large_fk','small_name','large_name'], true)) {
                            $qty += (float) ($j['fallback_quantity'] ?? 0);
                        }
                    }
                    $val = $qty * $unitPrice;
                    if ($minUsage !== null && $qty < (float) $minUsage) return false;
                    if ($maxUsage !== null && $qty > (float) $maxUsage) return false;
                    if ($minValue !== null && $val < (float) $minValue) return false;
                    if ($maxValue !== null && $val > (float) $maxValue) return false;
                    return true;
                })->values();
            }

            // High-level summary (optional)
            $summaryTotalOrders = $byInvoice->count();
            $summaryPivotQty = 0;
            $summaryLegacyQty = 0;
            $summaryAreaBasedQty = 0;
            foreach ($byInvoice as $inv) {
                foreach ($inv['jobs'] as $j) {
                    if ($j['used_via'] === 'pivot') {
                        $summaryPivotQty += (float) ($j['pivot_quantity'] ?? 0);
                    } elseif (in_array($j['used_via'], ['small_fk', 'large_fk', 'small_name', 'large_name'], true)) {
                        $summaryLegacyQty += (float) ($j['fallback_quantity'] ?? 0);
                        // Track area-based calculations separately
                        if ($j['calculation_method'] === 'area_based') {
                            $summaryAreaBasedQty += (float) ($j['fallback_quantity'] ?? 0);
                        }
                    } else {
                        // Still count these jobs in the legacy summary to avoid losing data
                        $summaryLegacyQty += (float) ($j['fallback_quantity'] ?? 0);
                    }
                }
            }

            // Get unique clients for filter dropdown
            $clients = DB::table('invoices')
                ->join('clients', 'clients.id', '=', 'invoices.client_id')
                ->whereIn('invoices.id', $byInvoice->pluck('invoice_id'))
                ->select('clients.id', 'clients.name')
                ->distinct()
                ->orderBy('clients.name')
                ->get();

            return response()->json([
                'invoices' => $byInvoice,
                'clients' => $clients,
                // Frontend will aggregate per-invoice; summary is provided for convenience
                'summary' => [
                    'total_orders' => $summaryTotalOrders,
                    'total_pivot_quantity' => $summaryPivotQty,
                    'total_legacy_quantity' => $summaryLegacyQty,
                    'total_area_based_quantity' => $summaryAreaBasedQty,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching jobs for article usage: ' . $e->getMessage());
            return response()->json([
                'invoices' => [],
                'summary' => [
                    'total_orders' => 0,
                    'total_pivot_quantity' => 0,
                    'total_legacy_quantity' => 0,
                    'total_area_based_quantity' => 0,
                ],
            ]);
        }
    }

    /**
     * Get monthly usage analytics for the article
     */
    public function getMonthlyUsage(Article $article, Request $request)
    {
        // Support two modes:
        // 1) Aggregated by month for last N months (default months=12)
        // 2) Day-by-day when explicit date range is provided (start_date, end_date)
        $months = (int) $request->get('months', 12);
        $explicitStart = $request->query('start_date');
        $explicitEnd = $request->query('end_date');
        $groupByDay = $explicitStart || $explicitEnd;
        $startDate = $explicitStart ? Carbon::parse($explicitStart)->startOfDay() : Carbon::now()->subMonths($months)->startOfMonth();
        $endDate = $explicitEnd ? Carbon::parse($explicitEnd)->endOfDay() : Carbon::now();

        try {
            $articleId = (int) $article->id;
            $lowerName = strtolower($article->name);
            $smallMaterialId = optional($article->smallMaterial)->id;
            $largeMaterialId = optional($article->largeFormatMaterial)->id;

            $rows = DB::table('invoice_job')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->join('jobs', 'jobs.id', '=', 'invoice_job.job_id')
                ->leftJoin('job_articles', function ($join) use ($articleId) {
                    $join->on('job_articles.job_id', '=', 'jobs.id')
                         ->where('job_articles.article_id', '=', $articleId);
                })
                ->leftJoin('small_material', 'small_material.id', '=', 'jobs.small_material_id')
                ->leftJoin('large_format_materials', 'large_format_materials.id', '=', 'jobs.large_material_id')
                ->where(function ($q) use ($startDate, $endDate) {
                    $q->whereBetween(DB::raw('DATE(invoices.start_date)'), [$startDate->toDateString(), $endDate->toDateString()])
                      ->orWhereBetween(DB::raw('DATE(invoices.created_at)'), [$startDate->toDateString(), $endDate->toDateString()]);
                })
                ->where(function ($q) use ($smallMaterialId, $largeMaterialId, $lowerName) {
                    // pivot join condition is in leftJoin; treat presence as a match when building totals
                    if ($smallMaterialId) {
                        $q->orWhere('jobs.small_material_id', $smallMaterialId);
                    }
                    if ($largeMaterialId) {
                        $q->orWhere('jobs.large_material_id', $largeMaterialId);
                    }
                    $q->orWhereRaw('LOWER(small_material.name) = ?', [$lowerName])
                      ->orWhereRaw('LOWER(large_format_materials.name) = ?', [$lowerName])
                      ->orWhereNotNull('job_articles.article_id');
                })
                ->select([
                    'invoices.start_date',
                    'invoices.created_at',
                    'jobs.id as job_id',
                    'jobs.quantity as job_quantity',
                    'jobs.copies as job_copies',
                    'jobs.total_area_m2',
                    'jobs.small_material_id',
                    'jobs.large_material_id',
                    'small_material.name as small_material_name',
                    'large_format_materials.name as large_material_name',
                    'job_articles.quantity as pivot_quantity',
                ])
                ->get();

            // Aggregate per day or per month
            $byMonth = [];
            $unitPrice = (float) ($article->price_1 ?? 0);

            foreach ($rows as $r) {
                $usedVia = null;
                if (!is_null($r->pivot_quantity)) {
                    $usedVia = 'pivot';
                } elseif ($smallMaterialId && $r->small_material_id === $smallMaterialId) {
                    $usedVia = 'small_fk';
                } elseif ($largeMaterialId && $r->large_material_id === $largeMaterialId) {
                    $usedVia = 'large_fk';
                } elseif ($r->small_material_name && strtolower($r->small_material_name) === $lowerName) {
                    $usedVia = 'small_name';
                } elseif ($r->large_material_name && strtolower($r->large_material_name) === $lowerName) {
                    $usedVia = 'large_name';
                }

                if (!$usedVia) {
                    continue; // skip rows that didn't actually match
                }

                $dt = $r->start_date ?? $r->created_at;
                if (!$dt) {
                    continue;
                }
                $key = Carbon::parse($dt)->format($groupByDay ? 'Y-m-d' : 'Y-m');

                // Calculate quantity using new area-based method when available
                $qty = $this->calculateJobQuantity($r);

                if (!isset($byMonth[$key])) {
                    $byMonth[$key] = [
                        'period' => $key,
                        'label' => $groupByDay ? Carbon::createFromFormat('Y-m-d', $key)->format('d M Y') : Carbon::createFromFormat('Y-m', $key)->format('M Y'),
                        'quantity_used' => 0,
                        'jobs' => [],
                        'area_based_jobs' => 0,
                        'legacy_jobs' => 0,
                    ];
                }
                $byMonth[$key]['quantity_used'] += $qty;
                $byMonth[$key]['jobs'][$r->job_id] = true; // unique jobs
                
                // Track calculation method for analytics
                if ($r->total_area_m2) {
                    $byMonth[$key]['area_based_jobs']++;
                } else {
                    $byMonth[$key]['legacy_jobs']++;
                }
            }

            // Fill gaps for display
            if ($groupByDay) {
                $cursor = $startDate->copy();
                while ($cursor <= $endDate) {
                    $k = $cursor->format('Y-m-d');
                    if (!isset($byMonth[$k])) {
                        $byMonth[$k] = [
                            'period' => $k,
                            'label' => $cursor->format('d M Y'),
                            'quantity_used' => 0,
                            'jobs' => [],
                            'area_based_jobs' => 0,
                            'legacy_jobs' => 0,
                        ];
                    }
                    $cursor->addDay();
                }
            } else {
                for ($i = 0; $i < $months; $i++) {
                    $month = Carbon::now()->subMonths($i)->format('Y-m');
                    if (!isset($byMonth[$month])) {
                        $byMonth[$month] = [
                            'period' => $month,
                            'label' => Carbon::createFromFormat('Y-m', $month)->format('M Y'),
                            'quantity_used' => 0,
                            'jobs' => [],
                            'area_based_jobs' => 0,
                            'legacy_jobs' => 0,
                        ];
                    }
                }
            }

            // Convert to collection and sort
            $monthlyUsage = collect($byMonth)
                ->map(function ($m) use ($unitPrice) {
                    return [
                        'period' => $m['period'],
                        'label' => $m['label'],
                        'quantity_used' => (float) $m['quantity_used'],
                        'jobs_count' => is_array($m['jobs']) ? count($m['jobs']) : 0,
                        'area_based_jobs' => $m['area_based_jobs'] ?? 0,
                        'legacy_jobs' => $m['legacy_jobs'] ?? 0,
                        'value' => (float) $m['quantity_used'] * $unitPrice,
                    ];
                })
                ->sortBy('period')
                ->values();

            return response()->json([
                'monthly_usage' => $monthlyUsage,
                'period' => $groupByDay ? ($startDate->format('d M Y') . ' - ' . $endDate->format('d M Y')) : "Last {$months} months",
                'total_quantity' => $monthlyUsage->sum('quantity_used'),
                'total_value' => $monthlyUsage->sum('value'),
                'average_monthly_usage' => $groupByDay ? 0 : ($months > 0 ? ($monthlyUsage->sum('quantity_used') / $months) : 0),
                'group' => $groupByDay ? 'day' : 'month',
                'calculation_methods' => [
                    'area_based_jobs' => $monthlyUsage->sum('area_based_jobs'),
                    'legacy_jobs' => $monthlyUsage->sum('legacy_jobs'),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching monthly usage data: ' . $e->getMessage());
            return response()->json([
                'monthly_usage' => collect([]),
                'period' => "Last {$months} months",
                'total_quantity' => 0,
                'total_value' => 0,
                'average_monthly_usage' => 0,
                'calculation_methods' => [
                    'area_based_jobs' => 0,
                    'legacy_jobs' => 0,
                ],
            ]);
        }
    }

    /**
     * Get comprehensive analytics dashboard data for an article
     */
    public function getDashboard(Article $article)
    {
        try {
            // Get individual responses
            $stockResponse = $this->getStockInfo($article);
            $orderResponse = $this->getOrderUsage($article);
            $monthlyResponse = $this->getMonthlyUsage($article, new Request(['months' => 6]));

            // Decode JSON data
            $stockData = json_decode($stockResponse->getContent(), true);
            $orderData = json_decode($orderResponse->getContent(), true);
            $monthlyData = json_decode($monthlyResponse->getContent(), true);

            return response()->json([
                'stock' => $stockData,
                'order_usage' => $orderData,
                'monthly_usage' => $monthlyData,
                'summary' => [
                    'current_stock' => $stockData['stock']['current_stock'] ?? 0,
                    'total_orders' => $orderData['summary']['total_orders'] ?? 0,
                    'monthly_average' => $monthlyData['average_monthly_usage'] ?? 0,
                    'last_used' => !empty($orderData['invoices']) ? $orderData['invoices'][0]['start_date'] ?? null : null,
                    'calculation_methods' => [
                        'area_based_usage' => $monthlyData['calculation_methods']['area_based_jobs'] ?? 0,
                        'legacy_usage' => $monthlyData['calculation_methods']['legacy_jobs'] ?? 0,
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error getting dashboard data: ' . $e->getMessage());
            return response()->json([
                'error' => 'Failed to load dashboard data',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Calculate job quantity using new area-based method when available
     * Falls back to legacy quantity * copies calculation
     * This method should match the frontend computeInvoiceQuantity logic
     */
    private function calculateJobQuantity($jobRow)
    {
        // Priority 1: Use pivot quantity if available (most accurate)
        if (!is_null($jobRow->pivot_quantity)) {
            $result = (float) $jobRow->pivot_quantity;
            return $result;
        }

        // Priority 2: Use area-based calculation if total_area_m2 is available
        if ($jobRow->total_area_m2 && is_numeric($jobRow->total_area_m2)) {
            // For area-based materials, the area represents the material consumed
            // We need to consider the job's quantity and copies as multipliers
            $areaQuantity = (float) $jobRow->total_area_m2;
            $jobQuantity = (int) ($jobRow->job_quantity ?? 1);
            $jobCopies = (int) ($jobRow->job_copies ?? 1);
            
            // The total material consumed is: area × quantity × copies
            // This represents the actual material area used for production
            $result = $areaQuantity * $jobQuantity * $jobCopies;
            
            return $result;
        }

        // Priority 3: Fallback to legacy calculation (quantity * copies)
        $quantity = (int) ($jobRow->job_quantity ?? 0);
        $copies = (int) ($jobRow->job_copies ?? 0);
        $result = $quantity * $copies;
        
        return $result;
    }
}
