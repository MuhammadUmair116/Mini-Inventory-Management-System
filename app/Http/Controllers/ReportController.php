<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    /**
     * Display the sales, payment, and inventory report.
     */
    public function sales(Request $request): Response
    {
        Gate::authorize('view-reports');

        $filters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d', 'before_or_equal:today'],
            'to_date' => [
                'nullable',
                'date_format:Y-m-d',
                'before_or_equal:today',
                'after_or_equal:from_date',
            ],
        ]);

        $fromDate = $filters['from_date'] ?? now()->startOfYear()->toDateString();
        $toDate = $filters['to_date'] ?? now()->toDateString();

        return Inertia::render('Reports/Sales', $this->reports->salesOverview($fromDate, $toDate));
    }
}
