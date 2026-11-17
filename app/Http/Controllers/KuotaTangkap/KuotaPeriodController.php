<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaTangkap;

use App\Http\Controllers\Controller;
use App\Http\Requests\KuotaTangkap\KuotaPeriodRequest;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KuotaTangkap\KuotaCalculationSnapshot;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\Provinsi;
use App\Models\TradeCode;
use App\Services\KuotaTangkap\KuotaPeriodService;
use App\Services\KuotaTangkap\KuotaPeriodWorkflowService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class KuotaPeriodController extends Controller
{
    public function __construct(
        private readonly KuotaPeriodService $periodService,
        private readonly KuotaPeriodWorkflowService $workflowService,
    ) {}

    public function index(Request $request): View
    {
        $activeYear = $this->activeYear($request);

        $periods = KuotaPeriod::query()
            ->with(['provinsi', 'komoditas', 'species', 'tradeCode'])
            ->withCount('allocations')
            ->visibleTo($request->user())
            ->where('year', $activeYear)
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('kuota-tangkap.index', [
            'periods' => $periods,
            'activeYear' => $activeYear,
            ...$this->formOptions($request),
        ]);
    }

    public function store(KuotaPeriodRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $scopedProvinsiId = KuotaPeriod::scopedProvinsiIdFor($request->user());

        if ($scopedProvinsiId !== null && (int) $data['provinsi_id'] !== $scopedProvinsiId) {
            abort(403);
        }

        $period = $this->periodService->create($data, $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Periode kuota ekspor berhasil dibuat.'));
    }

    public function show(Request $request, KuotaPeriod $period): View
    {
        Gate::authorize('view', $period);

        $period->load([
            'provinsi', 'komoditas', 'species', 'tradeCode', 'latestSnapshot',
            'allocations' => fn ($query) => $query->with('entitas.provinsi')->orderBy('id'),
            'snapshots' => fn ($query) => $query->with('calculator')->orderByDesc('version'),
            'activities' => fn ($query) => $query->with(['user', 'allocation.entitas'])->latest('id'),
            'submitter', 'approver', 'closer',
        ]);

        $participantIds = $period->allocations->pluck('entity_id');

        return view('kuota-tangkap.show', [
            'period' => $period,
            'cadanganAktual' => $this->workflowService->cadanganAktual($period),
            'cadanganBelowMinimum' => $period->latestSnapshot !== null && $this->workflowService->cadanganBelowMinimum($period),
            'entitasOptions' => Entitas::query()->where('entity_type', 'perusahaan')->whereNotIn('id', $participantIds)->orderBy('name')->get(['id', 'name']),
            ...$this->formOptions($request),
        ]);
    }

    public function update(KuotaPeriodRequest $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('update', $period);

        $data = $request->validated();
        $scopedProvinsiId = KuotaPeriod::scopedProvinsiIdFor($request->user());

        if ($scopedProvinsiId !== null && (int) $data['provinsi_id'] !== $scopedProvinsiId) {
            abort(403);
        }

        $this->periodService->update($period, $data, $request->user());

        return redirect()->route('kuota-tangkap.show', $period)->with('success', __('Periode kuota ekspor berhasil diperbarui.'));
    }

    public function destroy(Request $request, KuotaPeriod $period): RedirectResponse
    {
        Gate::authorize('delete', $period);

        $this->periodService->delete($period, $request->user());

        return redirect()->route('kuota-tangkap.index', ['year' => $period->year])->with('success', __('Periode kuota ekspor berhasil dihapus.'));
    }

    public function snapshot(KuotaPeriod $period, KuotaCalculationSnapshot $snapshot): View
    {
        Gate::authorize('view', $period);

        $period->load(['provinsi', 'komoditas', 'species', 'tradeCode']);
        $snapshot->load('calculator');

        return view('kuota-tangkap.snapshot', ['period' => $period, 'snapshot' => $snapshot]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(Request $request): array
    {
        $scopedProvinsiId = KuotaPeriod::scopedProvinsiIdFor($request->user());

        return [
            'scopedProvinsi' => $scopedProvinsiId !== null ? Provinsi::query()->find($scopedProvinsiId) : null,
            'komoditasOptions' => Komoditas::query()->orderBy('name')->get(['id', 'name']),
            'tradeCodeOptions' => TradeCode::query()->orderBy('code')->get(['id', 'code', 'name']),
        ];
    }
}
