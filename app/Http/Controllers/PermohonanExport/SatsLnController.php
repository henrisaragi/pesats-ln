<?php

declare(strict_types=1);

namespace App\Http\Controllers\PermohonanExport;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermohonanExport\SatsLnItemRequest;
use App\Http\Requests\PermohonanExport\SatsLnRequest;
use App\Models\Country;
use App\Models\Entitas\Entitas;
use App\Models\HargaPatokan;
use App\Models\Komoditas;
use App\Models\KuotaPerusahaan;
use App\Models\Permohonan;
use App\Models\Port;
use App\Models\SatsLn;
use App\Models\SatsLnItem;
use App\Models\Species;
use App\Models\TradeCode;
use App\Services\PermohonanService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $kuotaPerusahaanId = $request->validated('kuota_perusahaan_id');
        $kuotaPerusahaan = $kuotaPerusahaanId !== null ? KuotaPerusahaan::findOrFail($kuotaPerusahaanId) : null;

        $satsLn = DB::transaction(function () use ($request, $permohonan, $kuotaPerusahaan): SatsLn {
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

            if ($kuotaPerusahaan !== null) {
                $satsLn->items()->create([
                    'kuota_nasional_id' => $kuotaPerusahaan->kuota_nasional_id,
                    'kuota_perusahaan_id' => $kuotaPerusahaan->id,
                    'species_id' => $kuotaPerusahaan->kuotaNasional->species_id,
                    'harga_patokan_id' => $this->resolveHargaPatokanId($kuotaPerusahaan),
                    'qty' => $request->validated('qty'),
                    'peb_or_pib' => $request->validated('peb_or_pib'),
                    'description' => $request->validated('description'),
                ]);
            }

            return $satsLn;
        });

        return $this->redirectAfterSave($request, $permohonan, $satsLn)
            ->with('success', __('SATS-LN berhasil ditambahkan.'));
    }

    public function update(SatsLnRequest $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        $this->authorizeEditable($permohonan);
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        $kuotaPerusahaanId = $request->validated('kuota_perusahaan_id');
        $kuotaPerusahaan = $kuotaPerusahaanId !== null ? KuotaPerusahaan::findOrFail($kuotaPerusahaanId) : null;

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

            if ($kuotaPerusahaan !== null) {
                $satsLn->items()->first()?->update([
                    'kuota_nasional_id' => $kuotaPerusahaan->kuota_nasional_id,
                    'kuota_perusahaan_id' => $kuotaPerusahaan->id,
                    'species_id' => $kuotaPerusahaan->kuotaNasional->species_id,
                    'harga_patokan_id' => $this->resolveHargaPatokanId($kuotaPerusahaan),
                    'qty' => $request->validated('qty'),
                    'peb_or_pib' => $request->validated('peb_or_pib'),
                    'description' => $request->validated('description'),
                ]);
            }
        });

        return $this->redirectAfterSave($request, $permohonan, $satsLn)
            ->with('success', __('SATS-LN berhasil diperbarui.'));
    }

    public function destroy(Request $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        $this->authorizeEditable($permohonan);
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        $satsLn->delete();

        // Satsln yang barusan dihapus sudah tidak ada lagi, jadi return_to=show (dipakai tombol
        // Hapus di navbar halaman detail) selalu mengarah ke rute show umum (permohonan-export.show,
        // otomatis jatuh ke SATS-LN sisa yang terbaru atau empty-state) - bukan ke sats-ln.show milik
        // record yang sudah dihapus.
        if ($request->input('return_to') === 'show') {
            return redirect()->route('permohonan-export.show', $permohonan)
                ->with('success', __('SATS-LN berhasil dihapus.'));
        }

        return redirect()->route('permohonan-export.edit', $permohonan)->with('success', __('SATS-LN berhasil dihapus.'));
    }

    public function storeItem(SatsLnItemRequest $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        $this->authorizeEditable($permohonan);
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);

        $kuotaPerusahaan = $this->permohonanService->resolveKuotaPerusahaanForEntitas(
            $this->currentEntity(),
            $request->integer('source_id'),
            $request->integer('komoditas_id'),
            $request->integer('trade_code_id'),
            $request->integer('species_id'),
        );
        abort_if($kuotaPerusahaan === null, 404);

        $satsLn->items()->create([
            'kuota_nasional_id' => $kuotaPerusahaan->kuota_nasional_id,
            'kuota_perusahaan_id' => $kuotaPerusahaan->id,
            'species_id' => $request->integer('species_id'),
            'harga_patokan_id' => $this->resolveHargaPatokanId($kuotaPerusahaan),
            'qty' => $request->validated('qty'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('permohonan-export.sats-ln.show', [$permohonan, $satsLn])
            ->with('toast_success', __('Spesies berhasil ditambahkan.'));
    }

    public function updateItem(SatsLnItemRequest $request, Permohonan $permohonan, SatsLn $satsLn, SatsLnItem $item): RedirectResponse
    {
        $this->authorizeEditable($permohonan);
        abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        abort_unless($item->sats_ln_id === $satsLn->id, 404);

        $kuotaPerusahaan = $this->permohonanService->resolveKuotaPerusahaanForEntitas(
            $this->currentEntity(),
            $request->integer('source_id'),
            $request->integer('komoditas_id'),
            $request->integer('trade_code_id'),
            $request->integer('species_id'),
        );
        abort_if($kuotaPerusahaan === null, 404);

        $item->update([
            'kuota_nasional_id' => $kuotaPerusahaan->kuota_nasional_id,
            'kuota_perusahaan_id' => $kuotaPerusahaan->id,
            'species_id' => $request->integer('species_id'),
            'harga_patokan_id' => $this->resolveHargaPatokanId($kuotaPerusahaan),
            'qty' => $request->validated('qty'),
            'description' => $request->validated('description'),
        ]);

        return redirect()->route('permohonan-export.sats-ln.show', [$permohonan, $satsLn])
            ->with('toast_success', __('Spesies berhasil diperbarui.'));
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

    public function portOptions(Country $country): JsonResponse
    {
        $ports = Port::query()->where('country_id', $country->id)->orderBy('name')->get(['id', 'name']);

        return response()->json($ports->map(fn (Port $port) => [
            'id' => $port->id,
            'name' => $port->name,
        ])->values());
    }

    public function itemKomoditasOptions(int $source): JsonResponse
    {
        $options = $this->permohonanService->komoditasOptionsForEntitas($this->currentEntity(), $source);

        return response()->json($options->map(fn (Komoditas $item) => [
            'id' => $item->id,
            'name' => $item->name,
        ])->values());
    }

    public function itemTradeCodeOptions(int $source, int $komoditas): JsonResponse
    {
        $options = $this->permohonanService->tradeCodeOptionsForEntitas($this->currentEntity(), $source, $komoditas);

        return response()->json($options->map(fn (TradeCode $item) => [
            'id' => $item->id,
            'name' => $item->code.' - '.$item->name,
        ])->values());
    }

    public function itemSpeciesOptions(int $source, int $komoditas, int $tradeCode): JsonResponse
    {
        $options = $this->permohonanService->speciesOptionsForEntitas($this->currentEntity(), $source, $komoditas, $tradeCode);

        return response()->json($options->map(fn (Species $item) => [
            'id' => $item->id,
            'name' => $item->scientific_name,
        ])->values());
    }

    private function redirectAfterSave(SatsLnRequest $request, Permohonan $permohonan, SatsLn $satsLn): RedirectResponse
    {
        if ($request->input('return_to') === 'show') {
            return redirect()->route('permohonan-export.sats-ln.show', [$permohonan, $satsLn]);
        }

        return redirect()->route('permohonan-export.edit', $permohonan);
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
