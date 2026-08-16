<?php

declare(strict_types=1);

namespace App\Http\Controllers\PermohonanExport;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermohonanExport\SatsLnRequest;
use App\Models\Entitas\Entitas;
use App\Models\HargaPatokan;
use App\Models\KuotaPerusahaan;
use App\Models\Permohonan;
use App\Models\SatsLn;
use App\Services\PermohonanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SatsLnController extends Controller
{
    public function __construct(private readonly PermohonanService $permohonanService) {}

    public function store(SatsLnRequest $request, Permohonan $permohonan): RedirectResponse
    {
        $this->authorizeEditable($permohonan);

        if ($permohonan->hasReachedMaxSatsLn()) {
            return redirect()->route('permohonan-export.edit', $permohonan)
                ->with('error', __('Maksimal 25 SATS-LN per permohonan.'));
        }

        $kuotaPerusahaan = KuotaPerusahaan::findOrFail($request->validated('kuota_perusahaan_id'));

        DB::transaction(function () use ($request, $permohonan, $kuotaPerusahaan): void {
            $satsLn = SatsLn::create([
                'permohonan_id' => $permohonan->id,
                'entity_id' => $permohonan->entity_id,
                'customer_company_id' => $request->validated('customer_company_id'),
                'type_satsln' => 'Export',
                'country_of_exportation' => $request->validated('country_of_exportation'),
                'country_of_destination' => $request->validated('country_of_destination'),
                'port_of_exportation' => $request->validated('port_of_exportation'),
                'port_of_destination' => $request->validated('port_of_destination'),
                'purpose_of_transaction_id' => $request->validated('purpose_of_transaction_id'),
                'valid_until' => $request->validated('valid_until'),
                'special_conditions' => $request->validated('special_conditions'),
                'satsln_issued_type' => 'P_0',
                'satsln_number_issued' => $this->generateNomorSatsLn(),
                'year' => now()->year,
            ]);

            $satsLn->items()->create([
                'kuota_nasional_id' => $kuotaPerusahaan->kuota_nasional_id,
                'kuota_perusahaan_id' => $kuotaPerusahaan->id,
                'species_id' => $kuotaPerusahaan->kuotaNasional->species_id,
                'harga_patokan_id' => $this->resolveHargaPatokanId($kuotaPerusahaan),
                'qty' => $request->validated('qty'),
                'peb_or_pib' => $request->validated('peb_or_pib'),
                'description' => $request->validated('description'),
            ]);
        });

        return redirect()->route('permohonan-export.edit', $permohonan)->with('success', __('SATS-LN berhasil ditambahkan.'));
    }

    public function update(SatsLnRequest $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        $this->authorizeEditable($permohonan);
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        $kuotaPerusahaan = KuotaPerusahaan::findOrFail($request->validated('kuota_perusahaan_id'));

        DB::transaction(function () use ($request, $satsLn, $kuotaPerusahaan): void {
            $satsLn->update([
                'customer_company_id' => $request->validated('customer_company_id'),
                'country_of_exportation' => $request->validated('country_of_exportation'),
                'country_of_destination' => $request->validated('country_of_destination'),
                'port_of_exportation' => $request->validated('port_of_exportation'),
                'port_of_destination' => $request->validated('port_of_destination'),
                'purpose_of_transaction_id' => $request->validated('purpose_of_transaction_id'),
                'valid_until' => $request->validated('valid_until'),
                'special_conditions' => $request->validated('special_conditions'),
            ]);

            $satsLn->items()->first()?->update([
                'kuota_nasional_id' => $kuotaPerusahaan->kuota_nasional_id,
                'kuota_perusahaan_id' => $kuotaPerusahaan->id,
                'species_id' => $kuotaPerusahaan->kuotaNasional->species_id,
                'harga_patokan_id' => $this->resolveHargaPatokanId($kuotaPerusahaan),
                'qty' => $request->validated('qty'),
                'peb_or_pib' => $request->validated('peb_or_pib'),
                'description' => $request->validated('description'),
            ]);
        });

        return redirect()->route('permohonan-export.edit', $permohonan)->with('success', __('SATS-LN berhasil diperbarui.'));
    }

    public function destroy(Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        $this->authorizeEditable($permohonan);
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        $satsLn->delete();

        return redirect()->route('permohonan-export.edit', $permohonan)->with('success', __('SATS-LN berhasil dihapus.'));
    }

    public function kuotaPerusahaanOptions(?int $source = null): JsonResponse
    {
        $entity = $this->currentEntity();
        $options = $this->permohonanService->kuotaPerusahaanOptions($entity, $source);

        return response()->json($options->map(fn (KuotaPerusahaan $item) => [
            'id' => $item->id,
            'name' => sprintf(
                '%s - %s - %s (Sisa %s)',
                $item->kuotaNasional?->komoditas?->name,
                $item->kuotaNasional?->species?->scientific_name,
                $item->kuotaNasional?->tradeCode?->code,
                number_format($this->permohonanService->sisaKuotaPerusahaan($item), 2, ',', '.')
            ),
        ])->values());
    }

    private function resolveHargaPatokanId(KuotaPerusahaan $kuotaPerusahaan): ?int
    {
        $kuotaNasional = $kuotaPerusahaan->kuotaNasional;

        return HargaPatokan::query()
            ->where('source_id', $kuotaNasional->source_id)
            ->where('trade_code_id', $kuotaNasional->trade_code_id)
            ->where('species_id', $kuotaNasional->species_id)
            ->value('id');
    }

    private function generateNomorSatsLn(): string
    {
        $year = now()->year;
        $yy = substr((string) $year, -2);

        $sequence = SatsLn::query()->withTrashed()->where('year', $year)->count() + 1;

        return $yy.str_pad((string) $sequence, 5, '0', STR_PAD_LEFT);
    }

    private function authorizeEditable(Permohonan $permohonan): void
    {
        $entity = $this->currentEntity();

        abort_unless($entity->id === $permohonan->entity_id, 403);
        abort_unless($permohonan->status->isEditable(), 403);
    }

    private function currentEntity(): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null, 403);

        return $entity;
    }
}
