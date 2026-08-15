<?php

declare(strict_types=1);

namespace App\Http\Controllers\KuotaPerusahaan;

use App\Http\Controllers\Controller;
use App\Http\Requests\KuotaPerusahaan\KuotaPerusahaanRequest;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KuotaPerusahaan;
use App\Models\Species;
use App\Models\TradeCode;
use App\Services\KuotaPerusahaanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class KuotaPerusahaanController extends Controller
{
    public function __construct(private readonly KuotaPerusahaanService $kuotaPerusahaanService) {}

    public function index(Request $request): View
    {
        $activeYear = $request->filled('year') ? $request->integer('year') : now()->year;
        $activeKomoditasId = $request->filled('komoditas_id') ? $request->integer('komoditas_id') : null;

        $kuotaPerusahaan = KuotaPerusahaan::query()
            ->with(['entitas', 'kuotaNasional.komoditas', 'kuotaNasional.species', 'kuotaNasional.source', 'kuotaNasional.tradeCode'])
            ->where('year', $activeYear)
            ->when(
                $activeKomoditasId !== null,
                fn ($query) => $query->whereHas('kuotaNasional', fn ($sub) => $sub->where('komoditas_id', $activeKomoditasId))
            )
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('kuota-perusahaan.index', [
            'kuotaPerusahaan' => $kuotaPerusahaan,
            'activeYear' => $activeYear,
            'activeKomoditasId' => $activeKomoditasId,
            'entitasOptions' => Entitas::query()->where('entity_type', 'perusahaan')->orderBy('name')->get(),
            'komoditasFilterOptions' => Komoditas::query()->orderBy('name')->get(),
        ]);
    }

    public function store(KuotaPerusahaanRequest $request): RedirectResponse
    {
        $kuotaNasional = $this->kuotaPerusahaanService->resolveKuotaNasional(
            $request->integer('komoditas_id'),
            $request->integer('source_id'),
            $request->integer('trade_code_id'),
            $request->integer('species_id'),
            $request->integer('year'),
        );

        KuotaPerusahaan::create([
            'entity_id' => $request->validated('entity_id'),
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => $request->validated('quota'),
            'year' => $kuotaNasional->year,
        ]);

        return redirect()->route('kuota-perusahaan.index')->with('success', __('Kuota perusahaan berhasil ditambahkan.'));
    }

    public function update(KuotaPerusahaanRequest $request, KuotaPerusahaan $kuotaPerusahaan): RedirectResponse
    {
        $kuotaNasional = $this->kuotaPerusahaanService->resolveKuotaNasional(
            $request->integer('komoditas_id'),
            $request->integer('source_id'),
            $request->integer('trade_code_id'),
            $request->integer('species_id'),
            $request->integer('year'),
        );

        $kuotaPerusahaan->update([
            'entity_id' => $request->validated('entity_id'),
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => $request->validated('quota'),
            'year' => $kuotaNasional->year,
        ]);

        return redirect()->route('kuota-perusahaan.index')->with('success', __('Kuota perusahaan berhasil diperbarui.'));
    }

    public function destroy(KuotaPerusahaan $kuotaPerusahaan): RedirectResponse
    {
        $kuotaPerusahaan->delete();

        return redirect()->route('kuota-perusahaan.index')->with('success', __('Kuota perusahaan berhasil dihapus.'));
    }

    public function komoditasOptions(Entitas $entity): JsonResponse
    {
        $komoditas = $this->kuotaPerusahaanService->komoditasVerifiedUntukEntitas($entity);

        return response()->json($komoditas->map(fn ($item) => ['id' => $item->id, 'name' => $item->name])->values());
    }

    public function sourceStatusOptions(int $komoditas, int $year): JsonResponse
    {
        $options = $this->kuotaPerusahaanService->sourceStatusOptions($komoditas, $year);

        return response()->json($options->map(fn ($item) => [
            'id' => $item->id,
            'name' => $item->code.' - '.$item->description,
        ])->values());
    }

    public function tradeCodeOptions(int $komoditas, int $source, int $year): JsonResponse
    {
        $options = $this->kuotaPerusahaanService->tradeCodeOptions($komoditas, $source, $year);

        return response()->json($options->map(fn (TradeCode $item) => [
            'id' => $item->id,
            'name' => $item->code.' - '.$item->name,
        ])->values());
    }

    public function speciesOptions(int $komoditas, int $source, int $tradeCode, int $year): JsonResponse
    {
        $options = $this->kuotaPerusahaanService->speciesOptions($komoditas, $source, $tradeCode, $year);

        return response()->json($options->map(fn (Species $item) => [
            'id' => $item->id,
            'name' => $item->scientific_name,
        ])->values());
    }
}
