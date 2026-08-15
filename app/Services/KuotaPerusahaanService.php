<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\KuotaNasional;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use Illuminate\Support\Collection;

class KuotaPerusahaanService
{
    /**
     * Sisa kuota nasional yang belum dialokasikan ke perusahaan mana pun.
     */
    public function sisaKuotaNasional(KuotaNasional $kuotaNasional, ?int $exceptKuotaPerusahaanId = null): float
    {
        $terpakai = (float) $kuotaNasional->kuotaPerusahaan()
            ->when($exceptKuotaPerusahaanId !== null, fn ($query) => $query->where('id', '!=', $exceptKuotaPerusahaanId))
            ->sum('quota');

        return (float) $kuotaNasional->quota - $terpakai;
    }

    /**
     * Komoditas hanya bisa diberi kuota kalau entitas sudah punya izin komoditas yang terverifikasi.
     */
    public function entitasBisaMengajukan(Entitas $entitas, int $komoditasId): bool
    {
        return KomoditasEntitas::query()
            ->where('entitas_id', $entitas->id)
            ->where('komoditas_id', $komoditasId)
            ->where('status', KomoditasEntitasStatus::Verified)
            ->exists();
    }

    /**
     * Komoditas milik entitas yang sudah terverifikasi (untuk dropdown form Kuota Perusahaan).
     *
     * @return Collection<int, Komoditas>
     */
    public function komoditasVerifiedUntukEntitas(Entitas $entitas): Collection
    {
        return KomoditasEntitas::query()
            ->with('komoditas')
            ->where('entitas_id', $entitas->id)
            ->where('status', KomoditasEntitasStatus::Verified)
            ->get()
            ->pluck('komoditas')
            ->filter()
            ->unique('id')
            ->values();
    }

    /**
     * Status Asal yang benar-benar punya baris di kuota_nasional (bukan seluruh master data),
     * untuk satu komoditas + tahun aktif.
     *
     * @return Collection<int, SourceStatus>
     */
    public function sourceStatusOptions(int $komoditasId, int $year): Collection
    {
        $sourceIds = KuotaNasional::query()
            ->where('komoditas_id', $komoditasId)
            ->where('year', $year)
            ->distinct()
            ->pluck('source_id');

        return SourceStatus::query()
            ->whereIn('id', $sourceIds)
            ->orderBy('code')
            ->get();
    }

    /**
     * Kode Perdagangan yang benar-benar punya baris di kuota_nasional (bukan seluruh master data),
     * untuk kombinasi komoditas + status asal + tahun aktif.
     *
     * @return Collection<int, TradeCode>
     */
    public function tradeCodeOptions(int $komoditasId, int $sourceId, int $year): Collection
    {
        $tradeCodeIds = KuotaNasional::query()
            ->where('komoditas_id', $komoditasId)
            ->where('source_id', $sourceId)
            ->where('year', $year)
            ->distinct()
            ->pluck('trade_code_id');

        return TradeCode::query()
            ->whereIn('id', $tradeCodeIds)
            ->orderBy('code')
            ->get();
    }

    /**
     * Spesies yang benar-benar punya baris di kuota_nasional dan masih ada sisa, untuk kombinasi
     * komoditas + status asal + kode perdagangan + tahun aktif (bukan seluruh master data spesies).
     *
     * @return Collection<int, Species>
     */
    public function speciesOptions(int $komoditasId, int $sourceId, int $tradeCodeId, int $year): Collection
    {
        $kuotaNasional = KuotaNasional::query()
            ->where('komoditas_id', $komoditasId)
            ->where('source_id', $sourceId)
            ->where('trade_code_id', $tradeCodeId)
            ->where('year', $year)
            ->get()
            ->filter(fn (KuotaNasional $item) => $this->sisaKuotaNasional($item) > 0);

        return Species::query()
            ->whereIn('id', $kuotaNasional->pluck('species_id'))
            ->orderBy('scientific_name')
            ->get();
    }

    /**
     * Cari baris kuota_nasional yang cocok dengan kombinasi lengkap yang dipilih di form.
     */
    public function resolveKuotaNasional(int $komoditasId, int $sourceId, int $tradeCodeId, int $speciesId, int $year): ?KuotaNasional
    {
        return KuotaNasional::query()
            ->where('komoditas_id', $komoditasId)
            ->where('source_id', $sourceId)
            ->where('trade_code_id', $tradeCodeId)
            ->where('species_id', $speciesId)
            ->where('year', $year)
            ->first();
    }
}
