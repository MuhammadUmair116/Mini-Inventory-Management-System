<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboard) {}

    /**
     * Display the operational dashboard with real sales, payment, and stock data.
     */
    public function __invoke(): Response
    {
        return Inertia::render('Dashboard/Index', $this->dashboard->overview());
    }
}
