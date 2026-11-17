<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\KuotaTangkap\KuotaRealisasiRequest;
use App\Models\KuotaTangkap\KuotaAllocation;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Services\KuotaTangkap\KuotaPeriodWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class KuotaRealisasiController extends Controller
{
    public function __construct(private readonly KuotaPeriodWorkflowService $workflowService) {}

    public function update(KuotaRealisasiRequest $request, KuotaPeriod $period, KuotaAllocation $allocation): RedirectResponse
    {
        Gate::authorize('realisasi', $period);

        $this->workflowService->updateRealisasi(
            $allocation,
            $request->safe()->only(['realisasi_tangkap', 'sk_tangkap_nomor', 'sk_tangkap_tanggal']),
            $request->file('sk_tangkap_file'),
            $request->user(),
        );

        return redirect()->route('kuota-tangkap.show', [$period, 'tab' => 'realisasi'])->with('success', __('Realisasi ekspor berhasil disimpan.'));
    }

    public function downloadSk(Request $request, KuotaPeriod $period, KuotaAllocation $allocation): StreamedResponse
    {
        $user = $request->user();
        $isOwner = $allocation->entity_id === $user->entity_id && $user->can('viewOwn', $period);

        abort_unless($isOwner || $user->can('view', $period), 403);
        abort_if($allocation->sk_tangkap_path === null, 404);

        return Storage::disk('s3')->download(
            $allocation->sk_tangkap_path,
            'SK-Kuota-Ekspor-'.str($allocation->entitas?->name)->slug().'.pdf',
        );
    }
}
