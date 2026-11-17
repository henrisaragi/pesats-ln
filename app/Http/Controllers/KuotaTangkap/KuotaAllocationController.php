<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\KuotaTangkap\KuotaAllocationRequest;
use App\Http\Requests\KuotaTangkap\KuotaParticipantRequest;
use App\Http\Requests\KuotaTangkap\KuotaPermohonanRequest;
use App\Models\KuotaTangkap\KuotaAllocation;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Services\KuotaTangkap\KuotaPeriodService;
use App\Services\KuotaTangkap\KuotaPeriodWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class KuotaAllocationController extends Controller
{
    public function __construct(
        private readonly KuotaPeriodService $periodService,
        private readonly KuotaPeriodWorkflowService $workflowService,
    ) {}

    public function store(KuotaParticipantRequest $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('update', $period);

        $permohonan = $request->validated('permohonan');
        $this->periodService->addParticipant($period, $request->integer('entity_id'), $permohonan !== null ? (string) $permohonan : null, $request->user());

        return redirect()->route('kuota-tangkap.show', [$period, 'tab' => 'peserta'])->with('success', __('Peserta berhasil ditambahkan.'));
    }

    public function candidates(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('update', $period);

        $count = $this->periodService->addCandidates($period, $request->user());

        return redirect()->route('kuota-tangkap.show', [$period, 'tab' => 'peserta'])->with('success', __(':count kandidat peserta ditambahkan.', ['count' => $count]));
    }

    public function updatePermohonan(KuotaPermohonanRequest $request, KuotaPeriod $period, KuotaAllocation $allocation): RedirectResponse
    {
        Gate::authorize('update', $period);

        $permohonan = $request->validated('permohonan');
        $this->periodService->updatePermohonan($allocation, $permohonan !== null ? (string) $permohonan : null, $request->user());

        return redirect()->route('kuota-tangkap.show', [$period, 'tab' => 'peserta'])->with('success', __('Permohonan berhasil diperbarui.'));
    }

    public function destroy(Request $request, KuotaPeriod $period, KuotaAllocation $allocation): RedirectResponse
    {
        Gate::authorize('update', $period);

        $this->periodService->removeParticipant($allocation, $request->user());

        return redirect()->route('kuota-tangkap.show', [$period, 'tab' => 'peserta'])->with('success', __('Peserta berhasil dihapus.'));
    }

    public function update(KuotaAllocationRequest $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('allocate', $period);

        $this->workflowService->saveAllocations($period, $request->allocationInput(), $request->user());

        return $this->redirectWithCadanganWarning($period, __('Penetapan berhasil disimpan.'));
    }

    public function copyRecommendation(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('allocate', $period);

        $count = $this->workflowService->copyRecommendation($period, $request->user());

        return $this->redirectWithCadanganWarning($period, __(':count baris diisi dari rekomendasi.', ['count' => $count]));
    }

    private function redirectWithCadanganWarning(KuotaPeriod $period, string $message): RedirectResponse
    {
        $period->refresh()->load(['allocations', 'latestSnapshot']);

        $redirect = redirect()->route('kuota-tangkap.show', $period)->with('success', $message);

        if ($this->workflowService->cadanganBelowMinimum($period)) {
            $redirect->with('kuota_warning', __('Cadangan aktual (:cadangan) di bawah :persen% dari kuota ekspor.', [
                'cadangan' => number_format((float) $this->workflowService->cadanganAktual($period), 0, ',', '.'),
                'persen' => rtrim(rtrim(bcmul((string) $period->latestSnapshot->cadangan, '100', 2), '0'), '.'),
            ]));
        }

        return $redirect;
    }
}
