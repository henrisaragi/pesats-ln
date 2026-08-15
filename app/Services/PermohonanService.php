<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PermohonanStatus;
use App\Models\Entitas\Entitas;
use App\Models\KuotaPerusahaan;
use App\Models\Permohonan;
use App\Models\SourceStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermohonanService
{
    /**
     * Sisa kuota perusahaan yang belum dipakai di SATS-LN manapun (draft/ditolak tidak dihitung terpakai).
     */
    public function sisaKuotaPerusahaan(KuotaPerusahaan $kuotaPerusahaan, ?int $exceptSatsLnItemId = null): float
    {
        $terpakai = (float) $kuotaPerusahaan->satsLnItems()
            ->whereHas('satsLn.permohonan', fn ($query) => $query->whereNotIn('status', [
                PermohonanStatus::Draft->value,
                PermohonanStatus::Ditolak->value,
            ]))
            ->when($exceptSatsLnItemId !== null, fn ($query) => $query->where('id', '!=', $exceptSatsLnItemId))
            ->sum('qty');

        return (float) $kuotaPerusahaan->quota - $terpakai;
    }

    /**
     * Status Asal yang tampil hanya dari kuota_perusahaan milik entitas (bukan seluruh master data).
     *
     * @return Collection<int, SourceStatus>
     */
    public function sourceStatusOptionsForEntitas(Entitas $entitas): Collection
    {
        $sourceIds = KuotaPerusahaan::query()
            ->join('kuota_nasional', 'kuota_nasional.id', '=', 'kuota_perusahaan.kuota_nasional_id')
            ->where('kuota_perusahaan.entity_id', $entitas->id)
            ->distinct()
            ->pluck('kuota_nasional.source_id');

        return SourceStatus::query()
            ->whereIn('id', $sourceIds)
            ->orderBy('code')
            ->get();
    }

    /**
     * Baris kuota_perusahaan milik entitas yang sisa kuotanya masih tersedia, opsional difilter status asal.
     *
     * @return Collection<int, KuotaPerusahaan>
     */
    public function kuotaPerusahaanOptions(Entitas $entitas, ?int $sourceId = null): Collection
    {
        return KuotaPerusahaan::query()
            ->with(['kuotaNasional.komoditas', 'kuotaNasional.species', 'kuotaNasional.tradeCode', 'kuotaNasional.source'])
            ->where('entity_id', $entitas->id)
            ->when($sourceId !== null, fn ($query) => $query->whereHas(
                'kuotaNasional',
                fn ($sub) => $sub->where('source_id', $sourceId)
            ))
            ->get()
            ->filter(fn (KuotaPerusahaan $kuotaPerusahaan) => $this->sisaKuotaPerusahaan($kuotaPerusahaan) > 0)
            ->values();
    }

    /**
     * Hitung ulang status Permohonan berdasarkan progres cetak/tanda-tangan seluruh SATS-LN di dalamnya.
     * Dipanggil setelah verify/reject/markPrinted/markSigned.
     */
    public function recomputeStatus(Permohonan $permohonan): void
    {
        if (in_array($permohonan->status, [
            PermohonanStatus::Draft,
            PermohonanStatus::Submitted,
            PermohonanStatus::AntrianVerifikasi,
            PermohonanStatus::Ditolak,
        ], true)) {
            return;
        }

        $satsLn = $permohonan->satsLn()->get(['id', 'printed_at', 'signed_at']);

        if ($satsLn->isEmpty()) {
            return;
        }

        $totalPrinted = $satsLn->whereNotNull('printed_at')->count();
        $totalSigned = $satsLn->whereNotNull('signed_at')->count();
        $total = $satsLn->count();

        $status = match (true) {
            $totalPrinted === $total && $totalSigned === $total => PermohonanStatus::SiapDiambil,
            $totalSigned > 0 => PermohonanStatus::DitandatanganiSebagian,
            $totalPrinted > 0 => PermohonanStatus::DicetakSebagian,
            default => PermohonanStatus::DalamProsesVerifikasi,
        };

        if ($status !== $permohonan->status) {
            $permohonan->update(['status' => $status]);
        }
    }

    public function generateNomorPermohonan(): string
    {
        $year = now()->year;

        $sequence = DB::transaction(function () use ($year): int {
            return Permohonan::query()
                ->withTrashed()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->count() + 1;
        });

        return sprintf('PMH-%d-%06d', $year, $sequence);
    }
}
