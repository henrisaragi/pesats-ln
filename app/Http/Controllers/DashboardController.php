<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $year = $this->activeYear($request);

        return view('dashboards.index', [
            'user' => $user,
            'year' => $year,
            'komoditasSummary' => $this->dashboardService->komoditasSummary($user, $year),
            'assesmentSummary' => $this->dashboardService->assesmentSummary($user, $year),
        ]);
    }
}
