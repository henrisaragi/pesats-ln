<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Services\ServerProcessStatusService;
use Illuminate\View\View;

class ServerStatusController extends Controller
{
    public function index(ServerProcessStatusService $service): View
    {
        return view('settings.server-status.index', [
            'statuses' => $service->getStatuses(),
        ]);
    }
}
