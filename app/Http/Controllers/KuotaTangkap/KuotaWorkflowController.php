<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\KuotaTangkap\KuotaReturnRequest;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Services\KuotaTangkap\KuotaPeriodWorkflowService;
use App\Services\KuotaTangkap\KuotaRecommendationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KuotaWorkflowController extends Controller
{
    public function __construct(
        private readonly KuotaRecommendationService $recommendationService,
        private readonly KuotaPeriodWorkflowService $workflowService,
    ) {}

    public function calculate(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('calculate', $period);

        $snapshot = $this->recommendationService->calculate($period, $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Rekomendasi berhasil dihitung (snapshot v:version).', ['version' => $snapshot->version]));
    }

    public function submit(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('submit', $period);

        $this->workflowService->submit($period, $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Periode berhasil diajukan.'));
    }

    public function approve(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('approve', $period);

        $this->workflowService->approve($period, $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Periode berhasil ditetapkan.'));
    }

    public function return(KuotaReturnRequest $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('return', $period);

        $this->workflowService->return($period, (string) $request->validated('catatan_pengembalian'), $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Periode dikembalikan ke pengusul.'));
    }

    public function close(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('close', $period);

        $this->workflowService->close($period, $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Periode berhasil ditutup.'));
    }
}
