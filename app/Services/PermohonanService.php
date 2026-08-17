<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\PermohonanStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KuotaNasional;
use App\Models\KuotaPerusahaan;
use App\Models\Permohonan;
use App\Models\SatsLnItem;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PermohonanService
{
    /**
     * Sisa kuota perusahaan yang belum dipakai di SATS-LN manapun. Item di Permohonan yang masih
     * Draft TETAP dihitung terpakai (begitu qty diisi di sebuah item, langsung dianggap
     * "dipesan"/reserved) - kalau Draft dikecualikan, dua SatsLn draft berbeda bisa sama-sama
     * lolos validasi memakai kuota yang sama lebih dari sekali sebelum salah satunya di-submit.
     * Cuma Ditolak yang dikecualikan (permohonan yang ditolak verifier memang harus melepas
     * kuotanya).
     */
    public function sisaKuotaPerusahaan(KuotaPerusahaan $kuotaPerusahaan, ?int $exceptSatsLnItemId = null): float
    {
        $terpakai = (float) $kuotaPerusahaan->satsLnItems()
            ->whereHas('satsLn.permohonan', fn ($query) => $query->where('status', '!=', PermohonanStatus::Ditolak->value))
            ->when($exceptSatsLnItemId !== null, fn ($query) => $query->where('id', '!=', $exceptSatsLnItemId))
            ->sum('qty');

        return (float) $kuotaPerusahaan->quota - $terpakai;
    }

    /**
     * Realisasi kuota nasional: total qty SatsLnItem dari SEMUA entitas yang memakai baris
     * kuota_nasional ini (bukan cuma entitas yang sedang login) - konsepnya sama seperti mutasi
     * rekening bersama, setiap entitas yang mengajukan spesies dari kuota nasional yang sama
     * menambah "terpakai"-nya, termasuk yang masih Draft (lihat catatan di sisaKuotaPerusahaan()).
     * Cuma Ditolak yang dikecualikan, dan SATS-LN/Permohonan yang sudah dihapus (soft delete)
     * otomatis tidak ikut lewat whereHas di bawah - jadi kuota otomatis "kembali" begitu SATS-LN
     * dibatalkan/dihapus.
     */
    public function realisasiKuotaNasional(KuotaNasional $kuotaNasional, ?int $exceptSatsLnItemId = null): float
    {
        return (float) $kuotaNasional->satsLnItems()
            ->whereHas('satsLn.permohonan', fn ($query) => $query->where('status', '!=', PermohonanStatus::Ditolak->value))
            ->when($exceptSatsLnItemId !== null, fn ($query) => $query->where('id', '!=', $exceptSatsLnItemId))
            ->sum('qty');
    }

    /**
     * Sisa kuota nasional yang belum dipakai entitas manapun.
     */
    public function sisaKuotaNasional(KuotaNasional $kuotaNasional, ?int $exceptSatsLnItemId = null): float
    {
        return (float) $kuotaNasional->quota - $this->realisasiKuotaNasional($kuotaNasional, $exceptSatsLnItemId);
    }

    /**
     * Realisasi kuota nasional per BARIS SatsLnItem, seperti saldo berjalan di mutasi rekening -
     * bukan satu total yang sama diulang di semua baris. Item diurutkan kronologis (berdasarkan
     * id, lintas SEMUA entitas yang memakai kuota_nasional yang sama) dan tiap baris menampilkan
     * akumulasi SAMPAI DENGAN dirinya sendiri: SatsLn pertama qty 1 -> tampil 1, SatsLn kedua qty
     * 2 -> tampil 3 (1+2), SatsLn ketiga qty 10 -> tampil 13 (1+2+10), lalu entitas lain qty 5 ->
     * tampil 18 (1+2+10+5), dst. Satu query pakai window function (SUM ... OVER PARTITION BY
     * kuota_nasional_id ORDER BY id), bukan N query per baris.
     *
     * @param  Collection<int, SatsLnItem>  $items
     * @return array<int, float> sats_ln_item.id => realisasi berjalan sampai baris itu
     */
    public function realisasiKuotaNasionalRunningTotalForItems(Collection $items): array
    {
        $kuotaNasionalIds = $items->pluck('kuota_nasional_id')->filter()->unique()->values();

        if ($kuotaNasionalIds->isEmpty()) {
            return [];
        }

        return SatsLnItem::query()
            ->whereIn('sats_ln_items.kuota_nasional_id', $kuotaNasionalIds)
            ->whereHas('satsLn.permohonan', fn ($query) => $query->where('status', '!=', PermohonanStatus::Ditolak->value))
            ->selectRaw('sats_ln_items.id, SUM(sats_ln_items.qty) OVER (PARTITION BY sats_ln_items.kuota_nasional_id ORDER BY sats_ln_items.id) as running_total')
            ->get()
            ->mapWithKeys(fn ($row) => [(int) $row->id => (float) $row->running_total])
            ->all();
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
     * Komoditas yang tampil hanya dari kuota_perusahaan milik entitas untuk Status Asal terpilih -
     * dipakai jenjang kedua modal "Tambah Spesies" (Source -> Komoditas -> Trade Code -> Species).
     *
     * @return Collection<int, Komoditas>
     */
    public function komoditasOptionsForEntitas(Entitas $entitas, int $sourceId): Collection
    {
        $komoditasIds = KuotaPerusahaan::query()
            ->join('kuota_nasional', 'kuota_nasional.id', '=', 'kuota_perusahaan.kuota_nasional_id')
            ->where('kuota_perusahaan.entity_id', $entitas->id)
            ->where('kuota_nasional.source_id', $sourceId)
            ->distinct()
            ->pluck('kuota_nasional.komoditas_id');

        return Komoditas::query()->whereIn('id', $komoditasIds)->orderBy('name')->get();
    }

    /**
     * Trade Code yang tampil hanya dari kuota_perusahaan milik entitas untuk Status Asal + Komoditas
     * terpilih - jenjang ketiga modal "Tambah Spesies".
     *
     * @return Collection<int, TradeCode>
     */
    public function tradeCodeOptionsForEntitas(Entitas $entitas, int $sourceId, int $komoditasId): Collection
    {
        $tradeCodeIds = KuotaPerusahaan::query()
            ->join('kuota_nasional', 'kuota_nasional.id', '=', 'kuota_perusahaan.kuota_nasional_id')
            ->where('kuota_perusahaan.entity_id', $entitas->id)
            ->where('kuota_nasional.source_id', $sourceId)
            ->where('kuota_nasional.komoditas_id', $komoditasId)
            ->distinct()
            ->pluck('kuota_nasional.trade_code_id');

        return TradeCode::query()->whereIn('id', $tradeCodeIds)->orderBy('code')->get();
    }

    /**
     * Species yang tampil hanya dari kuota_perusahaan milik entitas untuk Status Asal + Komoditas +
     * Trade Code terpilih - jenjang terakhir modal "Tambah Spesies".
     *
     * @return Collection<int, Species>
     */
    public function speciesOptionsForEntitas(Entitas $entitas, int $sourceId, int $komoditasId, int $tradeCodeId): Collection
    {
        $speciesIds = KuotaPerusahaan::query()
            ->join('kuota_nasional', 'kuota_nasional.id', '=', 'kuota_perusahaan.kuota_nasional_id')
            ->where('kuota_perusahaan.entity_id', $entitas->id)
            ->where('kuota_nasional.source_id', $sourceId)
            ->where('kuota_nasional.komoditas_id', $komoditasId)
            ->where('kuota_nasional.trade_code_id', $tradeCodeId)
            ->distinct()
            ->pluck('kuota_nasional.species_id');

        return Species::query()->whereIn('id', $speciesIds)->orderBy('scientific_name')->get();
    }

    /**
     * Resolve baris kuota_perusahaan milik entitas yang cocok dengan kombinasi 4 field modal
     * "Tambah Spesies" - null kalau kombinasi itu tidak dimiliki entitas ini sama sekali.
     */
    public function resolveKuotaPerusahaanForEntitas(
        Entitas $entitas,
        int $sourceId,
        int $komoditasId,
        int $tradeCodeId,
        int $speciesId,
    ): ?KuotaPerusahaan {
        return KuotaPerusahaan::query()
            ->with('kuotaNasional')
            ->where('entity_id', $entitas->id)
            ->whereHas('kuotaNasional', fn ($query) => $query
                ->where('source_id', $sourceId)
                ->where('komoditas_id', $komoditasId)
                ->where('trade_code_id', $tradeCodeId)
                ->where('species_id', $speciesId))
            ->orderByDesc('year')
            ->first();
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

    private const ROMAN_MONTHS = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    public function generateNomorPermohonan(): string
    {
        $year = now()->year;

        $sequence = DB::transaction(function () use ($year): int {
            // Postgres rejects "FOR UPDATE" combined with an aggregate (count()), so lock the
            // matching rows via a plain select and count the resulting collection in PHP instead.
            return Permohonan::query()
                ->withTrashed()
                ->whereYear('created_at', $year)
                ->lockForUpdate()
                ->pluck('id')
                ->count() + 1;
        });

        return $this->formatNomorPermohonan($sequence, now()->month, $year);
    }

    /**
     * Preview nomor yang AKAN dipakai kalau Permohonan dibuat sekarang - hanya untuk ditampilkan
     * di modal create, bukan authoritative (tidak lockForUpdate). Nilai final tetap dihitung
     * ulang oleh generateNomorPermohonan() di dalam transaction saat store() dipanggil.
     */
    public function nextNomorPermohonanPreview(): string
    {
        $year = now()->year;

        $sequence = Permohonan::query()
            ->withTrashed()
            ->whereYear('created_at', $year)
            ->count() + 1;

        return $this->formatNomorPermohonan($sequence, now()->month, $year);
    }

    private function formatNomorPermohonan(int $sequence, int $month, int $year): string
    {
        return sprintf('%06d/EKS/%s/%d', $sequence, self::ROMAN_MONTHS[$month - 1], $year);
    }
}
