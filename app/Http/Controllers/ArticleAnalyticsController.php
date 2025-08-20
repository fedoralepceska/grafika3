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

            // Optional filters
            $search = trim((string) $request->query('search', ''));
            $clientIdsParam = $request->query('client_id'); // can be single, array, or csv
            $clientIds = collect(is_array($clientIdsParam) ? $clientIdsParam : (isset($clientIdsParam) ? explode(',', (string) $clientIdsParam) : []))
                ->filter(fn($v) => is_numeric($v))
                ->map(fn($v) => (int) $v)
                ->values()
                ->all();
            $startDate = $request->query('start_date');
            $endDate = $request->query('end_date');
            $minUsage = $request->query('min_usage');
            $maxUsage = $request->query('max_usage');
            $minValue = $request->query('min_value');
            $maxValue = $request->query('max_value');

            // Base dataset: invoices x jobs potentially using this article (via pivot or legacy material fields)
            $rows = DB::table('invoice_job')
                ->join('invoices', 'invoices.id', '=', 'invoice_job.invoice_id')
                ->join('jobs', 'jobs.id', '=', 'invoice_job.job_id')
                ->leftJoin('job_articles', function ($join) use ($articleId) {
                    $join->on('job_articles.job_id', '=', 'jobs.id')
                         ->where('job_articles.article_id', '=', $articleId);
                })
                ->leftJoin('small_material', 'small_material.id', '=', 'jobs.small_material_id')
                ->leftJoin('large_format_materials', 'large_format_materials.id', '=', 'jobs.large_material_id')
                ->leftJoin('clients', 'clients.id', '=', 'invoices.client_id')
                ->where(function ($q) use ($articleId, $smallMaterialId, $largeMaterialId, $lowerName) {
                    // Method A: Explicit pivot usage
                    $q->orWhereNotNull('job_articles.article_id');
                    // Method B: FK match on legacy fields
                    if ($smallMaterialId) {
                        $q->orWhere('jobs.small_material_id', $smallMaterialId);
                    }
                    if ($largeMaterialId) {
                        $q->orWhere('jobs.large_material_id', $largeMaterialId);
                    }
                    // Method C: Name matches (case-insensitive) for legacy content
                    $q->orWhereRaw('LOWER(small_material.name) = ?', [$lowerName])
                      ->orWhereRaw('LOWER(large_format_materials.name) = ?', [$lowerName]);
                })
                ->when(!empty($clientIds), function ($q) use ($clientIds) {
                    $q->whereIn('invoices.client_id', $clientIds);
                })
                ->when($startDate, function ($q) use ($startDate) {
                    $q->where(function ($qq) use ($startDate) {
                        $qq->whereDate('invoices.start_date', '>=', $startDate)
                           ->orWhereDate('invoices.created_at', '>=', $startDate);
                    });
                })
                ->when($endDate, function ($q) use ($endDate) {
                    $q->where(function ($qq) use ($endDate) {
                        $qq->whereDate('invoices.start_date', '<=', $endDate)
                           ->orWhereDate('invoices.created_at', '<=', $endDate);
                    });
                })
                ->when($search !== '', function ($q) use ($search) {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('invoices.invoice_title', 'like', "%{$search}%")
                           ->orWhere('clients.name', 'like', "%{$search}%");
                    });
                })
                ->select([
                    'invoices.id as invoice_id',
                    'invoices.invoice_title',
                    'invoices.start_date',
                    'invoices.status',
                    'clients.name as client_name',
                    'jobs.id as job_id',
                    'jobs.quantity as job_quantity',
                    'jobs.copies as job_copies',
                    'jobs.small_material_id',
                    'jobs.large_material_id',
                    'small_material.name as small_material_name',
                    'large_format_materials.name as large_material_name',
                    'job_articles.quantity as pivot_quantity',
                ])
                ->orderByDesc('invoices.start_date')
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

                    $jobs[] = [
                        'job_id' => (int) $r->job_id,
                        'used_via' => $usedVia, // pivot | small_fk | large_fk | small_name | large_name | null
                        'pivot_quantity' => is_null($r->pivot_quantity) ? null : (float) $r->pivot_quantity,
                        'fallback_quantity' => (int) ($r->job_quantity ?? 0) * (int) ($r->job_copies ?? 0),
                        'job_quantity' => (int) ($r->job_quantity ?? 0),
                        'job_copies' => (int) ($r->job_copies ?? 0),
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
                            $qty += (int) ($j['fallback_quantity'] ?? 0);
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
            foreach ($byInvoice as $inv) {
                foreach ($inv['jobs'] as $j) {
                    if ($j['used_via'] === 'pivot') {
                        $summaryPivotQty += (float) ($j['pivot_quantity'] ?? 0);
                    } elseif (in_array($j['used_via'], ['small_fk', 'large_fk', 'small_name', 'large_name'], true)) {
                        $summaryLegacyQty += (int) ($j['fallback_quantity'] ?? 0);
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

                $qty = ($usedVia === 'pivot')
                    ? (float) ($r->pivot_quantity ?? 0)
                    : (int) ($r->job_quantity ?? 0) * (int) ($r->job_copies ?? 0);

                if (!isset($byMonth[$key])) {
                    $byMonth[$key] = [
                        'period' => $key,
                        'label' => $groupByDay ? Carbon::createFromFormat('Y-m-d', $key)->format('d M Y') : Carbon::createFromFormat('Y-m', $key)->format('M Y'),
                        'quantity_used' => 0,
                        'jobs' => [],
                    ];
                }
                $byMonth[$key]['quantity_used'] += $qty;
                $byMonth[$key]['jobs'][$r->job_id] = true; // unique jobs
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
            ]);

        } catch (\Exception $e) {
            Log::error('Error fetching monthly usage data: ' . $e->getMessage());
            return response()->json([
                'monthly_usage' => collect([]),
                'period' => "Last {$months} months",
                'total_quantity' => 0,
                'total_value' => 0,
                'average_monthly_usage' => 0,
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
                    'total_orders' => $orderData['total_orders'] ?? 0,
                    'monthly_average' => $monthlyData['average_monthly_usage'] ?? 0,
                    'last_used' => !empty($orderData['usage']) ? $orderData['usage'][0]['start_date'] ?? null : null
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
}
