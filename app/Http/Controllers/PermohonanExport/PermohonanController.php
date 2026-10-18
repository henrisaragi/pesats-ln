<?php

declare(strict_types=1);

namespace App\Http\Controllers\PermohonanExport;

use App\Enums\DocumentType;
use App\Enums\JenisPermohonan;
use App\Enums\PermohonanStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\PermohonanExport\PermohonanRequest;
use App\Models\Country;
use App\Models\Customer\Customer;
use App\Models\Document;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\Permohonan;
use App\Models\Port;
use App\Models\PurposeOfTransaction;
use App\Models\SatsLn;
use App\Models\Species;
use App\Models\TradeCode;
use App\Services\PermohonanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PermohonanController extends Controller
{
    public function __construct(private readonly PermohonanService $permohonanService) {}

    public function index(Request $request): View
    {
        $entity = $this->ownEntityOrAbort();

        $permohonan = Permohonan::query()
            ->withCount('satsLn')
            ->where('entity_id', $entity->id)
            ->where('year', $this->activeYear($request))
            ->latest()
            ->paginate(10);

        return view('permohonan-export.index', [
            'permohonan' => $permohonan,
            'documentOptions' => Document::query()
                ->where('entity_id', $entity->id)
                ->where('type_document', DocumentType::Permohonan)
                ->whereNull('permohonan_id')
                ->orderByDesc('id')
                ->get(),
            'jenisPermohonanOptions' => JenisPermohonan::cases(),
            'nomorPermohonanPreview' => $this->permohonanService->nextNomorPermohonanPreview(),
        ]);
    }

    public function store(PermohonanRequest $request): RedirectResponse
    {
        $entity = $this->ownEntityOrAbort();

        $permohonan = DB::transaction(function () use ($entity, $request): Permohonan {
            $permohonan = Permohonan::create([
                'entity_id' => $entity->id,
                'nomor_permohonan' => $this->permohonanService->generateNomorPermohonan(),
                'year' => $request->validated('year') ?? now()->year,
                'jenis_permohonan' => $request->validated('jenis_permohonan'),
                'status' => PermohonanStatus::Draft,
            ]);

            Document::whereKey($request->validated('document_id'))->update(['permohonan_id' => $permohonan->id]);

            return $permohonan;
        });

        return redirect()->route('permohonan-export.show', $permohonan)
            ->with('success', __('Permohonan berhasil dibuat.'));
    }

    public function edit(Permohonan $permohonan): View
    {
        $entity = $this->authorizeOwner($permohonan);

        return view('permohonan-export.edit', [
            'permohonan' => $permohonan->load(['satsLn.items.species', 'satsLn.items.kuotaPerusahaan.kuotaNasional.source', 'satsLn.purposeOfTransaction', 'satsLn.customerCompany']),
            'entity' => $entity,
            'purposeOfTransactionOptions' => PurposeOfTransaction::query()->orderBy('name')->get(),
            'customerCompanyOptions' => Customer::query()->where('entity_id', $entity->id)->orderBy('name')->get(),
            'sourceStatusOptions' => $this->permohonanService->sourceStatusOptionsForEntitas($entity),
        ]);
    }

    public function show(Permohonan $permohonan, ?SatsLn $satsLn = null): View
    {
        $entity = $this->authorizeOwner($permohonan);

        if ($satsLn !== null) {
            abort_unless($satsLn->permohonan_id === $permohonan->id, 404);
        }

        $permohonan->load([
            'entitas',
            'document',
            'satsLn' => fn ($query) => $query->oldest(),
            'satsLn.customerCompany.country',
            'satsLn.purposeOfTransaction',
            'satsLn.portOfExportation',
            'satsLn.portOfDestination',
            'satsLn.items.species',
            'satsLn.items.kuotaNasional.komoditas',
            'satsLn.items.kuotaNasional.source',
            'satsLn.items.kuotaNasional.tradeCode',
            'satsLn.items.kuotaNasional.species',
            'satsLn.items.kuotaPerusahaan.kuotaNasional.tradeCode',
            'satsLn.items.kuotaPerusahaan.kuotaNasional.source',
            'satsLn.documents',
        ]);

        $indonesiaCountry = Country::query()->where('iso_code', 'ID')->first();

        // Redisplay setelah validasi modal Tambah/Ubah Spesies gagal: cascading select Komoditas/
        // Trade Code/Species butuh label dari nilai old() untuk ditampilkan lagi (bukan cuma id
        // kosong) - query di controller, bukan di Blade (lihat CLAUDE.md). Berlaku utk modal create
        // ("satsln-item-create-modal") maupun tiap modal edit ("satsln-item-edit-modal-{id}").
        $itemModalRedisplay = str_starts_with((string) old('_modal'), 'satsln-item-');
        $selectedSatsLn = $satsLn ?? $permohonan->satsLn->last();

        return view('permohonan-export.show', [
            'permohonan' => $permohonan,
            'selectedSatsLn' => $selectedSatsLn,
            'realisasiNasionalRunningTotalByItemId' => $this->permohonanService->realisasiKuotaNasionalRunningTotalForItems(
                $selectedSatsLn?->items ?? collect()
            ),
            'customerOptions' => Customer::query()->where('entity_id', $entity->id)->orderBy('name')->get(),
            'purposeOfTransactionOptions' => PurposeOfTransaction::query()->orderBy('name')->get(),
            'countryOptions' => Country::query()->orderBy('name')->get(),
            'indonesiaCountry' => $indonesiaCountry,
            'indonesiaPortOptions' => Port::query()->where('country_id', $indonesiaCountry?->id)->orderBy('name')->get(),
            'sourceOptions' => $this->permohonanService->sourceStatusOptionsForEntitas($entity)
                ->map(fn ($item) => (object) ['id' => $item->id, 'name' => $item->code.' - '.$item->description])
                ->values(),
            'oldKomoditas' => $itemModalRedisplay && old('komoditas_id') ? Komoditas::find(old('komoditas_id')) : null,
            'oldTradeCode' => $itemModalRedisplay && old('trade_code_id') ? TradeCode::find(old('trade_code_id')) : null,
            'oldSpecies' => $itemModalRedisplay && old('species_id') ? Species::find(old('species_id')) : null,
            // Dokumen bertipe Permohonan dikecualikan - itu sudah dipilih di awal pembuatan
            // Permohonan (Revisi Permohonan 1), bukan untuk dilampirkan ke SATS-LN.
            'satsLnDocumentOptions' => Document::query()
                ->where('entity_id', $entity->id)
                ->where('type_document', '!=', DocumentType::Permohonan)
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function submit(Permohonan $permohonan): RedirectResponse
    {
        $this->authorizeOwner($permohonan);
        abort_unless($permohonan->status->isEditable(), 403);

        if ($permohonan->satsLn()->count() === 0) {
            return redirect()->route('permohonan-export.edit', $permohonan)
                ->with('error', __('Minimal 1 SATS-LN harus diisi sebelum permohonan diajukan.'));
        }

        $this->permohonanService->changeStatus($permohonan, PermohonanStatus::AntrianVerifikasi, [
            'submitted_at' => now(),
            'verified_by' => null,
            'verified_at' => null,
            'catatan_verifikasi' => null,
        ]);

        return redirect()->route('permohonan-export.index')
            ->with('success', __('Permohonan berhasil diajukan, menunggu verifikasi.'));
    }

    public function destroy(Permohonan $permohonan): RedirectResponse
    {
        $this->authorizeOwner($permohonan);
        abort_unless($permohonan->status->isEditable(), 403);

        $permohonan->delete();

        return redirect()->route('permohonan-export.index')->with('success', __('Permohonan berhasil dihapus.'));
    }

    /**
     * Ajukan Permohonan dari halaman detail SATS-LN - beda dari submit() di atas (yang langsung
     * ke AntrianVerifikasi, sudah dipakai tombol "Ajukan Permohonan" di halaman edit): aksi ini
     * validasi tiap SATS-LN sudah punya minimal 1 item & 1 lampiran dokumen, lalu memindahkan
     * status ke Submitted (bukan AntrianVerifikasi - dua tahap terpisah, tidak saling menggantikan).
     */
    public function submitSatsLn(Permohonan $permohonan): RedirectResponse
    {
        $this->authorizeOwner($permohonan);
        abort_unless($permohonan->status->isEditable(), 403);

        $satsLn = $permohonan->satsLn()->withCount(['items', 'documents'])->get();

        if ($satsLn->count() < 1 || $satsLn->count() > 25) {
            return redirect()->route('permohonan-export.show', $permohonan)
                ->with('error', __('Jumlah SATS-LN harus antara 1 sampai 25 untuk dapat diajukan.'));
        }

        if ($satsLn->contains(fn (SatsLn $item): bool => $item->items_count === 0)) {
            return redirect()->route('permohonan-export.show', $permohonan)
                ->with('error', __('Setiap SATS-LN harus memiliki minimal 1 spesies sebelum diajukan.'));
        }

        if ($satsLn->contains(fn (SatsLn $item): bool => $item->documents_count === 0)) {
            return redirect()->route('permohonan-export.show', $permohonan)
                ->with('error', __('Setiap SATS-LN harus memiliki lampiran dokumen sebelum diajukan.'));
        }

        $this->permohonanService->changeStatus($permohonan, PermohonanStatus::Submitted, [
            'submitted_at' => now(),
        ]);

        return redirect()->route('permohonan-export.show', $permohonan)
            ->with('success', __('SATS-LN berhasil diajukan.'));
    }

    private function authorizeOwner(Permohonan $permohonan): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null && $entity->id === $permohonan->entity_id, 403);

        return $entity;
    }

    private function ownEntityOrAbort(): Entitas
    {
        $entity = Auth::user()->entity;

        abort_unless($entity !== null && $entity->entity_type === 'perusahaan', 403);

        return $entity;
    }
}
