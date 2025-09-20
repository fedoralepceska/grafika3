<?php

namespace App\Http\Controllers;

use App\Models\StockRealization;
use App\Models\StockRealizationJob;
use App\Models\StockRealizationArticle;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use PDF;

class StockRealizationController extends Controller
{
    /**
     * Display a listing of stock realizations
     */
    public function index(Request $request)
    {
        try {
            $query = StockRealization::with([
                'client:id,name',
                'realizedBy:id,name',
                'jobs.smallMaterial.smallFormatMaterial',
                'jobs.largeMaterial',
                'jobs.articles.article:id,name,code,format_type'
            ]);

            // Apply search filters
            if ($request->has('searchQuery')) {
                $searchQuery = '%' . $request->input('searchQuery') . '%';
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('invoice_title', 'LIKE', $searchQuery)
                      ->orWhere('id', 'LIKE', $searchQuery)
                      ->orWhereHas('client', function ($clientQuery) use ($searchQuery) {
                          $clientQuery->where('name', 'LIKE', $searchQuery);
                      });
                });
            }

            // Filter by realization status
            if ($request->has('status') && $request->input('status') !== 'All') {
                $status = $request->input('status');
                if ($status === 'Realized') {
                    $query->where('is_realized', true);
                } elseif ($status === 'Pending') {
                    $query->where('is_realized', false);
                }
            }

            // Filter by client
            if ($request->has('client') && $request->input('client') !== 'All') {
                $client = $request->input('client');
                $query->whereHas('client', function ($subquery) use ($client) {
                    $subquery->where('name', $client);
                });
            }

            // Apply date filters
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                $query->whereDate('end_date', '>=', $startDate)
                      ->whereDate('end_date', '<=', $endDate);
            } elseif ($request->has('start_date')) {
                $startDate = $request->input('start_date');
                $query->whereDate('end_date', '>=', $startDate);
            } elseif ($request->has('end_date')) {
                $endDate = $request->input('end_date');
                $query->whereDate('end_date', '<=', $endDate);
            }

            $query->orderBy('created_at', $request->input('sortOrder', 'desc'));
            $stockRealizations = $query->paginate(10);

            if ($request->wantsJson()) {
                return response()->json($stockRealizations);
            }

            return Inertia::render('Finance/StockRealization', [
                'stockRealizations' => $stockRealizations,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock realization index error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Display the specified stock realization
     */
    public function show($id)
    {
        try {
            $stockRealization = StockRealization::with([
                'client:id,name',
                'realizedBy:id,name',
                'jobs.smallMaterial.smallFormatMaterial',
                'jobs.largeMaterial',
                'jobs.catalogItem',
                'jobs.articles.article:id,name,code,format_type'
            ])->findOrFail($id);

            if (request()->wantsJson()) {
                return response()->json([
                    'stockRealization' => $stockRealization,
                ]);
            }

            return Inertia::render('Finance/StockRealizationDetails', [
                'stockRealization' => $stockRealization,
            ]);
        } catch (\Exception $e) {
            Log::error('Stock realization show error: ' . $e->getMessage());
            return response()->json(['error' => 'Stock realization not found'], 404);
        }
    }

    /**
     * Update stock realization job details
     */
    public function updateJob(Request $request, $id, $jobId)
    {
        try {
            DB::beginTransaction();

            $stockRealization = StockRealization::findOrFail($id);
            
            // Check if already realized
            if ($stockRealization->is_realized) {
                return response()->json(['error' => 'Cannot edit realized stock realization'], 403);
            }

            $job = StockRealizationJob::where('stock_realization_id', $stockRealization->id)
                ->findOrFail($jobId);

            $validatedData = $request->validate([
                'name' => 'sometimes|string|max:255',
                'quantity' => 'sometimes|required|integer|min:1',
                'copies' => 'sometimes|required|integer|min:1',
                'width' => 'sometimes|numeric|min:0',
                'height' => 'sometimes|numeric|min:0',
                'total_area_m2' => 'sometimes|numeric|min:0',
            ]);

            // Calculate total_area_m2 if width and height are provided
            if (isset($validatedData['width']) && isset($validatedData['height'])) {
                $validatedData['total_area_m2'] = ($validatedData['width'] * $validatedData['height']) / 10000; // Convert cm² to m²
            }

            $job->update($validatedData);

            // Recalculate articles if quantity or copies changed
            if (isset($validatedData['quantity']) || isset($validatedData['copies'])) {
                $this->recalculateJobArticles($job);
            }

            DB::commit();

            return response()->json([
                'message' => 'Job updated successfully',
                'job' => $job->fresh(['articles.article'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Stock realization job update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update job'], 500);
        }
    }

    /**
     * Update stock realization article quantity
     */
    public function updateArticle(Request $request, $id, $jobId, $articleId)
    {
        try {
            DB::beginTransaction();

            $stockRealization = StockRealization::findOrFail($id);
            
            // Check if already realized
            if ($stockRealization->is_realized) {
                return response()->json(['error' => 'Cannot edit realized stock realization'], 403);
            }

            $job = StockRealizationJob::where('stock_realization_id', $stockRealization->id)
                ->findOrFail($jobId);

            $article = StockRealizationArticle::where('stock_realization_job_id', $job->id)
                ->findOrFail($articleId);

            $validatedData = $request->validate([
                'quantity' => 'required|numeric|min:0',
            ]);

            $article->update($validatedData);

            DB::commit();

            return response()->json([
                'message' => 'Article quantity updated successfully',
                'article' => $article->fresh(['article'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Stock realization article update error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to update article'], 500);
        }
    }

    /**
     * Realize stock deduction
     */
    public function realize(Request $request, $id)
    {
        try {
            $stockRealization = StockRealization::findOrFail($id);

            if ($stockRealization->is_realized) {
                return response()->json(['error' => 'Stock realization already completed'], 400);
            }

            $success = $stockRealization->realize();

            if ($success) {
                return response()->json([
                    'message' => 'Stock realization completed successfully',
                    'stockRealization' => $stockRealization->fresh(['realizedBy:id,name'])
                ]);
            } else {
                return response()->json(['error' => 'Failed to complete stock realization'], 500);
            }
        } catch (\Exception $e) {
            Log::error('Stock realization realize error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to complete stock realization'], 500);
        }
    }

    /**
     * Get pending stock realizations (not yet realized)
     */
    public function pending(Request $request)
    {
        try {
            $query = StockRealization::with([
                'client:id,name',
                'jobs'
            ])->where('is_realized', false);

            // Apply search and filters similar to index
            if ($request->has('searchQuery')) {
                $searchQuery = '%' . $request->input('searchQuery') . '%';
                $query->where(function ($q) use ($searchQuery) {
                    $q->where('invoice_title', 'LIKE', $searchQuery)
                      ->orWhere('id', 'LIKE', $searchQuery)
                      ->orWhereHas('client', function ($clientQuery) use ($searchQuery) {
                          $clientQuery->where('name', 'LIKE', $searchQuery);
                      });
                });
            }

            if ($request->has('client') && $request->input('client') !== 'All') {
                $client = $request->input('client');
                $query->whereHas('client', function ($subquery) use ($client) {
                    $subquery->where('name', $client);
                });
            }

            $query->orderBy('created_at', 'desc');
            $stockRealizations = $query->paginate(10);

            if ($request->wantsJson()) {
                return response()->json($stockRealizations);
            }

            return response()->json($stockRealizations);
        } catch (\Exception $e) {
            Log::error('Pending stock realizations error: ' . $e->getMessage());
            return response()->json(['error' => 'Internal Server Error'], 500);
        }
    }

    /**
     * Recalculate job articles based on updated quantities
     */
    private function recalculateJobArticles(StockRealizationJob $job)
    {
        // Load the original job to get catalog item information
        $originalJob = $job->job()->with(['catalogItem'])->first();
        
        if (!$originalJob || !$originalJob->catalogItem) {
            return; // Can't recalculate without catalog item
        }

        // Create a temporary job object with updated values for calculation
        $tempJob = new \stdClass();
        $tempJob->quantity = $job->quantity;
        $tempJob->copies = $job->copies;
        $tempJob->total_area_m2 = $job->total_area_m2;
        $tempJob->catalogItem = $originalJob->catalogItem;

        // Calculate new material requirements
        $materialRequirements = $originalJob->catalogItem->calculateMaterialRequirements($tempJob);

        // Update existing articles
        foreach ($job->articles as $stockRealizationArticle) {
            $article = $stockRealizationArticle->article;
            
            // Find the corresponding requirement
            foreach ($materialRequirements as $requirement) {
                if ($requirement['article']->id === $article->id) {
                    $stockRealizationArticle->update([
                        'quantity' => $requirement['actual_required']
                    ]);
                    break;
                }
            }
        }
    }

    /**
     * Get available articles for adding to stock realization
     */
    public function getAvailableArticles()
    {
        try {
            $articles = Article::select('id', 'name', 'code', 'format_type')
                ->orderBy('name')
                ->get()
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'name' => $article->name,
                        'code' => $article->code,
                        'format_type' => $article->format_type
                    ];
                });

            return response()->json($articles);
        } catch (\Exception $e) {
            Log::error('Get available articles error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch articles'], 500);
        }
    }

    /**
     * Generate PDF for stock realization
     */
    public function generatePDF($id)
    {
        try {
            $stockRealization = StockRealization::with([
                'client.clientCardStatement',
                'realizedBy:id,name',
                'jobs.articles.article:id,name,purchase_price'
            ])->findOrFail($id);

            // Debug: Log the data structure to understand what we're working with
            Log::info('Stock Realization PDF Data Debug', [
                'stock_realization_id' => $stockRealization->id,
                'jobs_count' => $stockRealization->jobs->count(),
                'jobs_data' => $stockRealization->jobs->map(function($job) {
                    return [
                        'job_id' => $job->id,
                        'job_name' => $job->name,
                        'articles_count' => $job->articles->count(),
                        'articles_data' => $job->articles->map(function($article) {
                            return [
                                'article_id' => $article->article_id,
                                'article_name' => $article->article->name ?? 'No article name',
                                'quantity' => $article->quantity,
                                'unit_type' => $article->unit_type,
                                'purchase_price' => $article->article->purchase_price ?? 0
                            ];
                        })
                    ];
                })
            ]);

            // Convert the model to array to ensure proper data structure
            $stockRealizationArray = $stockRealization->toArray();
            
            // Generate PDF using the stock_pdf blade template
            $pdf = PDF::loadView('stock_realizations.stock_pdf', [
                'stockRealizations' => [$stockRealizationArray], // Wrap in array as expected by template
            ], [
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => true,
                'isFontSubsettingEnabled' => true,
                'chroot' => storage_path('fonts'),
                'dpi' => 150,
            ]);

            $filename = 'stock-realization-' . $stockRealization->id . '-' . str_replace(' ', '-', $stockRealization->invoice_title) . '.pdf';
            
            return response($pdf->output(), 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="' . $filename . '"'
            ]);

        } catch (\Exception $e) {
            Log::error('Stock realization PDF generation error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to generate PDF: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Debug stock realization data structure
     */
    public function debugData($id)
    {
        try {
            $stockRealization = StockRealization::with([
                'client.clientCardStatement',
                'realizedBy:id,name',
                'jobs.articles.article:id,name,purchase_price'
            ])->findOrFail($id);

            return response()->json([
                'stock_realization' => [
                    'id' => $stockRealization->id,
                    'invoice_title' => $stockRealization->invoice_title,
                    'client' => $stockRealization->client,
                    'jobs_count' => $stockRealization->jobs->count(),
                    'jobs' => $stockRealization->jobs->map(function($job) {
                        return [
                            'id' => $job->id,
                            'name' => $job->name,
                            'articles_count' => $job->articles->count(),
                            'articles' => $job->articles->map(function($article) {
                                return [
                                    'id' => $article->id,
                                    'article_id' => $article->article_id,
                                    'quantity' => $article->quantity,
                                    'unit_type' => $article->unit_type,
                                    'article' => $article->article ? [
                                        'id' => $article->article->id,
                                        'name' => $article->article->name,
                                        'purchase_price' => $article->article->purchase_price
                                    ] : null
                                ];
                            })
                        ];
                    })
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Debug failed: ' . $e->getMessage()], 500);
        }
    }
}
