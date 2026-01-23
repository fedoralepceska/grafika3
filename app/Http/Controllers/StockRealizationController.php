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
use Illuminate\Support\Facades\Hash;
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
                'invoice:id,order_number,fiscal_year',
                'jobs.smallMaterial.smallFormatMaterial',
                'jobs.largeMaterial',
                'jobs.articles.article:id,name,code,format_type'
            ]);

            // Apply search filters
            $hasSearchQuery = $request->has('searchQuery') && trim($request->input('searchQuery')) !== '';
            $hasSearchYear = $request->has('searchYear') && trim($request->input('searchYear')) !== '';
            
            if ($hasSearchQuery && $hasSearchYear) {
                // Both search query and year provided
                $searchQuery = trim($request->input('searchQuery'));
                $fiscalYear = (int) $request->input('searchYear');
                
                // Check if search query is numeric (order number)
                if (preg_match('/^\d+$/', $searchQuery)) {
                    // It's an order number, search by order_number and fiscal_year
                    $orderNumber = (int) $searchQuery;
                    $query->whereHas('invoice', function ($invoiceQuery) use ($orderNumber, $fiscalYear) {
                        $invoiceQuery->where('order_number', $orderNumber)
                                     ->where('fiscal_year', $fiscalYear);
                    });
                } else {
                    // It's text, search by text AND filter by year
                    $searchQuery = '%' . $searchQuery . '%';
                    $query->where(function ($q) use ($searchQuery, $fiscalYear) {
                        $q->where(function ($subQ) use ($searchQuery) {
                            $subQ->where('invoice_title', 'LIKE', $searchQuery)
                                 ->orWhere('id', 'LIKE', $searchQuery)
                                 ->orWhereHas('client', function ($clientQuery) use ($searchQuery) {
                                     $clientQuery->where('name', 'LIKE', $searchQuery);
                                 });
                        })->whereHas('invoice', function ($invoiceQuery) use ($fiscalYear) {
                            $invoiceQuery->where('fiscal_year', $fiscalYear);
                        });
                    });
                }
            } elseif ($hasSearchQuery) {
                // Only search query provided
                $searchQuery = trim($request->input('searchQuery'));
                
                // Check if search query matches "number/year" format (e.g., "5/2026")
                if (preg_match('/^(\d+)\/(\d{4})$/', $searchQuery, $matches)) {
                    $orderNumber = (int) $matches[1];
                    $fiscalYear = (int) $matches[2];
                    
                    $query->whereHas('invoice', function ($invoiceQuery) use ($orderNumber, $fiscalYear) {
                        $invoiceQuery->where('order_number', $orderNumber)
                                     ->where('fiscal_year', $fiscalYear);
                    });
                } else {
                    // Original search behavior for other queries
                    $searchQuery = '%' . $searchQuery . '%';
                    $query->where(function ($q) use ($searchQuery) {
                        $q->where('invoice_title', 'LIKE', $searchQuery)
                          ->orWhere('id', 'LIKE', $searchQuery)
                          ->orWhereHas('client', function ($clientQuery) use ($searchQuery) {
                              $clientQuery->where('name', 'LIKE', $searchQuery);
                          });
                    });
                }
            } elseif ($hasSearchYear) {
                // Only year provided
                $fiscalYear = (int) $request->input('searchYear');
                $query->whereHas('invoice', function ($invoiceQuery) use ($fiscalYear) {
                    $invoiceQuery->where('fiscal_year', $fiscalYear);
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
            $perPage = (int) $request->input('per_page', 10);
            $stockRealizations = $query->paginate($perPage);

            if ($request->wantsJson()) {
                return response()->json($stockRealizations);
            }

            $user = auth()->user();
            $canRevert = $user && $user->role && strcasecmp($user->role->name, 'admin') === 0;

            // Get years that have unrealized stock realizations (for disabling buttons)
            $yearsWithUnrealized = StockRealization::where('is_realized', false)
                ->distinct()
                ->pluck('fiscal_year')
                ->sort()
                ->values()
                ->toArray();

            return Inertia::render('Finance/StockRealization', [
                'stockRealizations' => $stockRealizations,
                'canRevert' => $canRevert,
                'yearsWithUnrealized' => $yearsWithUnrealized,
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
                'invoice:id,order_number,fiscal_year',
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

            $user = auth()->user();
            $canRevert = $user && $user->role && strcasecmp($user->role->name, 'admin') === 0;

            return Inertia::render('Finance/StockRealizationDetails', [
                'stockRealization' => $stockRealization,
                'canRevert' => $canRevert,
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
     * Add a new article to a stock realization job
     */
    public function addArticle(Request $request, $id, $jobId)
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
                'article_id' => 'required|exists:article,id',
                'quantity' => 'required|numeric|min:0.01',
                'unit_type' => 'nullable|string|max:50',
            ]);

            // Check if article already exists for this job - if so, update quantity
            $existingArticle = StockRealizationArticle::where('stock_realization_job_id', $job->id)
                ->where('article_id', $validatedData['article_id'])
                ->first();

            if ($existingArticle) {
                // Update existing article quantity (add to it)
                $existingArticle->quantity += $validatedData['quantity'];
                if ($validatedData['unit_type']) {
                    $existingArticle->unit_type = $validatedData['unit_type'];
                }
                $existingArticle->save();
                
                DB::commit();
                
                return response()->json([
                    'message' => 'Article quantity updated successfully',
                    'article' => $existingArticle->fresh(['article:id,name,code,format_type']),
                    'updated' => true,
                ]);
            }

            $article = $job->articles()->create([
                'article_id' => $validatedData['article_id'],
                'quantity' => $validatedData['quantity'],
                'unit_type' => $validatedData['unit_type'] ?? 'pieces',
                'source' => 'manual',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Article added successfully',
                'article' => $article->fresh(['article:id,name,code,format_type'])
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Stock realization add article error: ' . $e->getMessage() . ' | Trace: ' . $e->getTraceAsString());
            return response()->json(['error' => 'Failed to add article: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove an article from a stock realization job
     */
    public function removeArticle(Request $request, $id, $jobId, $articleId)
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

            $article->delete();

            DB::commit();

            return response()->json([
                'message' => 'Article removed successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error('Stock realization remove article error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to remove article'], 500);
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

            // Check if all previous years' stock realizations are completed
            if (!$stockRealization->canBeRealized()) {
                $unrealizedYears = $stockRealization->getUnrealizedPreviousYears();
                $unrealizedCount = $stockRealization->getUnrealizedPreviousYearsCount();
                $yearsText = implode(', ', $unrealizedYears);
                
                return response()->json([
                    'error' => "Cannot complete this stock realization. There are {$unrealizedCount} unrealized stock realization(s) from previous year(s): {$yearsText}. Please complete all previous years' stock realizations first.",
                    'unrealized_years' => $unrealizedYears,
                    'unrealized_count' => $unrealizedCount,
                ], 422);
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
     * Revert stock deduction (admin only, requires passcode confirmation)
     */
    public function revert(Request $request, $id)
    {
        try {
            // Authorization: only admins can revert (case-insensitive role check)
            $user = auth()->user();
            $isAdmin = $user && $user->role && strcasecmp($user->role->name, 'admin') === 0;
            if (!$isAdmin) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }

            // Validate passcode format and compare against env-configured value
            $validated = $request->validate([
                'passcode' => 'required|string|size:4'
            ]);
            $providedPasscode = $validated['passcode'];
            $expectedPasscode = env('STOCK_REVERT_PASSCODE', '9632');
            if (!hash_equals((string) $expectedPasscode, (string) $providedPasscode)) {
                return response()->json(['error' => 'Invalid passcode'], 422);
            }

            $stockRealization = StockRealization::findOrFail($id);

            if (!$stockRealization->is_realized) {
                return response()->json(['error' => 'Stock realization is not realized'], 400);
            }

            $success = $stockRealization->revert();

            if ($success) {
                return response()->json([
                    'message' => 'Stock realization reverted successfully',
                    'stockRealization' => $stockRealization->fresh(['realizedBy:id,name'])
                ]);
            }

            return response()->json(['error' => 'Failed to revert stock realization'], 500);
        } catch (\Exception $e) {
            Log::error('Stock realization revert error: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to revert stock realization'], 500);
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
            $hasSearchQuery = $request->has('searchQuery') && trim($request->input('searchQuery')) !== '';
            $hasSearchYear = $request->has('searchYear') && trim($request->input('searchYear')) !== '';
            
            if ($hasSearchQuery && $hasSearchYear) {
                // Both search query and year provided
                $searchQuery = trim($request->input('searchQuery'));
                $fiscalYear = (int) $request->input('searchYear');
                
                // Check if search query is numeric (order number)
                if (preg_match('/^\d+$/', $searchQuery)) {
                    // It's an order number, search by order_number and fiscal_year
                    $orderNumber = (int) $searchQuery;
                    $query->whereHas('invoice', function ($invoiceQuery) use ($orderNumber, $fiscalYear) {
                        $invoiceQuery->where('order_number', $orderNumber)
                                     ->where('fiscal_year', $fiscalYear);
                    });
                } else {
                    // It's text, search by text AND filter by year
                    $searchQuery = '%' . $searchQuery . '%';
                    $query->where(function ($q) use ($searchQuery, $fiscalYear) {
                        $q->where(function ($subQ) use ($searchQuery) {
                            $subQ->where('invoice_title', 'LIKE', $searchQuery)
                                 ->orWhere('id', 'LIKE', $searchQuery)
                                 ->orWhereHas('client', function ($clientQuery) use ($searchQuery) {
                                     $clientQuery->where('name', 'LIKE', $searchQuery);
                                 });
                        })->whereHas('invoice', function ($invoiceQuery) use ($fiscalYear) {
                            $invoiceQuery->where('fiscal_year', $fiscalYear);
                        });
                    });
                }
            } elseif ($hasSearchQuery) {
                // Only search query provided
                $searchQuery = trim($request->input('searchQuery'));
                
                // Check if search query matches "number/year" format (e.g., "5/2026")
                if (preg_match('/^(\d+)\/(\d{4})$/', $searchQuery, $matches)) {
                    $orderNumber = (int) $matches[1];
                    $fiscalYear = (int) $matches[2];
                    
                    $query->whereHas('invoice', function ($invoiceQuery) use ($orderNumber, $fiscalYear) {
                        $invoiceQuery->where('order_number', $orderNumber)
                                     ->where('fiscal_year', $fiscalYear);
                    });
                } else {
                    // Original search behavior for other queries
                    $searchQuery = '%' . $searchQuery . '%';
                    $query->where(function ($q) use ($searchQuery) {
                        $q->where('invoice_title', 'LIKE', $searchQuery)
                          ->orWhere('id', 'LIKE', $searchQuery)
                          ->orWhereHas('client', function ($clientQuery) use ($searchQuery) {
                              $clientQuery->where('name', 'LIKE', $searchQuery);
                          });
                    });
                }
            } elseif ($hasSearchYear) {
                // Only year provided
                $fiscalYear = (int) $request->input('searchYear');
                $query->whereHas('invoice', function ($invoiceQuery) use ($fiscalYear) {
                    $invoiceQuery->where('fiscal_year', $fiscalYear);
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
            $articles = Article::select('id', 'name', 'code', 'format_type', 'in_meters', 'in_square_meters', 'in_kilograms', 'in_pieces')
                ->orderBy('name')
                ->get()
                ->map(function ($article) {
                    // Determine the default unit type based on article flags
                    $unitType = 'pieces'; // default
                    if ($article->in_square_meters) {
                        $unitType = 'm²';
                    } elseif ($article->in_meters) {
                        $unitType = 'm';
                    } elseif ($article->in_kilograms) {
                        $unitType = 'kg';
                    }
                    
                    return [
                        'id' => $article->id,
                        'name' => $article->name,
                        'code' => $article->code,
                        'format_type' => $article->format_type,
                        'unit_type' => $unitType,
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
