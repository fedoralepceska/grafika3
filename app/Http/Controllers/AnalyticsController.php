<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\WorkerAnalytics;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AnalyticsController extends Controller
{
    public function index(Request $request)
    {
        return Inertia::render('Analytics/Index', [
        ]);
    }
    public function getUserInvoiceCounts(Request $request)
    {
        $date = $request->query('date');

        // Check if date is provided
        if ($date) {
            $dateFormat = strlen($date);

            switch ($dateFormat) {
                case 4: // Year only
                    $startDate = Carbon::createFromFormat('Y', $date)->startOfYear();
                    $endDate = Carbon::createFromFormat('Y', $date)->endOfYear();
                    break;

                case 7: // Year and Month
                    $startDate = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
                    $endDate = Carbon::createFromFormat('Y-m', $date)->endOfMonth();
                    break;

                case 10: // Full Date (Year, Month, Day)
                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                    break;

                default:
                    return response()->json(['error' => 'Invalid date format'], 400);
            }

            // Filter by date range
            $invoiceCounts = Invoice::join('users', 'invoices.created_by', '=', 'users.id')
                ->whereBetween('invoices.created_at', [$startDate, $endDate])
                ->select('users.name as user_name', DB::raw('count(*) as invoice_count'))
                ->groupBy('users.id', 'users.name')
                ->get();
        } else {
            // No date provided, fetch all records
            $invoiceCounts = Invoice::join('users', 'invoices.created_by', '=', 'users.id')
                ->select('users.name as user_name', DB::raw('count(*) as invoice_count'))
                ->groupBy('users.id', 'users.name')
                ->get();
        }

        return response()->json($invoiceCounts);
    }

    public function getArticlesInvoiceCounts(Request $request)
    {
        $date = $request->query('date');

        // Check if date is provided
        if ($date) {
            $dateFormat = strlen($date);

            switch ($dateFormat) {
                case 4: // Year only
                    $startDate = Carbon::createFromFormat('Y', $date)->startOfYear();
                    $endDate = Carbon::createFromFormat('Y', $date)->endOfYear();
                    break;

                case 7: // Year and Month
                    $startDate = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
                    $endDate = Carbon::createFromFormat('Y-m', $date)->endOfMonth();
                    break;

                case 10: // Full Date (Year, Month, Day)
                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                    break;

                default:
                    return response()->json(['error' => 'Invalid date format'], 400);
            }

            // Filter by date range
            $invoiceCounts = Invoice::join('article', 'invoices.article_id', '=', 'article.id')
                ->whereBetween('invoices.created_at', [$startDate, $endDate])
                ->select('article.name as article_name', DB::raw('count(*) as invoice_count'))
                ->groupBy('article.id', 'article.name')
                ->get();
        } else {
            // No date provided, fetch all records
            $invoiceCounts = Invoice::join('article', 'invoices.article_id', '=', 'article.id')
                ->select('article.name as article_name', DB::raw('count(*) as invoice_count'))
                ->groupBy('article.id', 'article.name')
                ->get();
        }

        return response()->json($invoiceCounts);
    }

    public function getClientsInvoiceCounts(Request $request)
    {
        $date = $request->query('date');

        // Check if date is provided
        if ($date) {
            $dateFormat = strlen($date);

            switch ($dateFormat) {
                case 4: // Year only
                    $startDate = Carbon::createFromFormat('Y', $date)->startOfYear();
                    $endDate = Carbon::createFromFormat('Y', $date)->endOfYear();
                    break;

                case 7: // Year and Month
                    $startDate = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
                    $endDate = Carbon::createFromFormat('Y-m', $date)->endOfMonth();
                    break;

                case 10: // Full Date (Year, Month, Day)
                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                    break;

                default:
                    return response()->json(['error' => 'Invalid date format'], 400);
            }

            // Filter by date range
            $invoiceCounts = Invoice::join('clients', 'invoices.client_id', '=', 'clients.id')
                ->whereBetween('invoices.created_at', [$startDate, $endDate])
                ->select('clients.name as client_name', DB::raw('count(*) as invoice_count'))
                ->groupBy('clients.id', 'clients.name')
                ->get();
        } else {
            // No date provided, fetch all records
            $invoiceCounts = Invoice::join('clients', 'invoices.client_id', '=', 'clients.id')
                ->select('clients.name as client_name', DB::raw('count(*) as invoice_count'))
                ->groupBy('clients.id', 'clients.name')
                ->get();
        }

        return response()->json($invoiceCounts);
    }

    public function getClientCosts(Request $request)
    {
        $date = $request->query('date');

        if ($date) {
            $dateFormat = strlen($date);

            switch ($dateFormat) {
                case 4: // Year only
                    $startDate = Carbon::createFromFormat('Y', $date)->startOfYear();
                    $endDate = Carbon::createFromFormat('Y', $date)->endOfYear();
                    break;

                case 7: // Year and Month
                    $startDate = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
                    $endDate = Carbon::createFromFormat('Y-m', $date)->endOfMonth();
                    break;

                case 10: // Full Date (Year, Month, Day)
                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                    break;

                default:
                    return response()->json(['error' => 'Invalid date format'], 400);
            }

            // Fetch all invoices with their jobs
            $invoices = Invoice::with('jobs')
                ->whereBetween('created_at', [$startDate, $endDate])
                ->get();
        } else {
            // Fetch all invoices with their jobs
            $invoices = Invoice::with('jobs')->get();
        }

        // Compute total costs for each client
        $clientCosts = $invoices->groupBy('client_id')->map(function ($group) {
            $clientName = $group->first()->client->name; // Assuming client relationship exists
            $totalCost = $group->flatMap(function ($invoice) {
                return $invoice->jobs->map(function ($job) {
                    return $job->getTotalPriceAttribute(); // Compute totalPrice
                });
            })->sum();

            return [
                'client_name' => $clientName,
                'total_cost' => $totalCost
            ];
        });

        return response()->json($clientCosts);
    }

    public function getWorkerAnalytics(Request $request)
    {
        $date = $request->query('date');

        if ($date) {
            $dateFormat = strlen($date);

            switch ($dateFormat) {
                case 4: // Year only
                    $startDate = Carbon::createFromFormat('Y', $date)->startOfYear();
                    $endDate = Carbon::createFromFormat('Y', $date)->endOfYear();
                    break;

                case 7: // Year and Month
                    $startDate = Carbon::createFromFormat('Y-m', $date)->startOfMonth();
                    $endDate = Carbon::createFromFormat('Y-m', $date)->endOfMonth();
                    break;

                case 10: // Full Date (Year, Month, Day)
                    $startDate = Carbon::createFromFormat('Y-m-d', $date)->startOfDay();
                    $endDate = Carbon::createFromFormat('Y-m-d', $date)->endOfDay();
                    break;

                default:
                    return response()->json(['error' => 'Invalid date format'], 400);
            }

            // Fetch worker analytics within the selected date range
            $workerAnalytics = WorkerAnalytics::whereBetween('created_at', [$startDate, $endDate])->with(['user'])->get();
        } else {
            // Fetch all worker analytics
            $workerAnalytics = WorkerAnalytics::with(['user'])->get();
        }

        // Compute total time spent and invoice count for each worker
        $workerData = $workerAnalytics->groupBy('user_id')->map(function ($group) {
            $userId = $group->first()->user->name;
            $totalTimeSpent = $group->sum(function ($item) {
                return $this->parseTimeSpent($item->time_spent);
            });
            $invoiceCount = $group->pluck('invoice_id')->unique()->count();

            return [
                'user_id' => $userId,
                'total_time_spent' => $totalTimeSpent,
                'formatted_time_spent' => $this->formatTimeSpent($totalTimeSpent),
                'invoice_count' => $invoiceCount
            ];
        });

        return response()->json($workerData);
    }

    function parseTimeSpent($timeString) {
        // Initialize total seconds
        $totalSeconds = 0;

        // Match minutes and seconds
        if (preg_match('/(\d+)min/', $timeString, $minutes)) {
            $totalSeconds += (int)$minutes[1] * 60; // Convert minutes to seconds
        }
        if (preg_match('/(\d+)sec/', $timeString, $seconds)) {
            $totalSeconds += (int)$seconds[1];
        }

        return $totalSeconds;
    }

    function formatTimeSpent($seconds) {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $remainingSeconds = $seconds % 60;

        return sprintf('%02dh %02dmin %02dsec', $hours, $minutes, $remainingSeconds);
    }
}
