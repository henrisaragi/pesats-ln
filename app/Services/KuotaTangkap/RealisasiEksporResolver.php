<?php

declare(strict_types=1);

namespace App\Services\KuotaTangkap;

use App\Enums\SatsLnStatus;
use App\Models\KuotaTangkap\KuotaPeriod;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * E_i: realisasi ekspor (sats_ln_items.realisasi_on_port) per entitas untuk jenis & kode
 * perdagangan yang sama, tahun kuota sebelumnya (tahun - 1). Hanya SATS-LN Ekspor yang disetujui
 * dan tidak terhapus. Entitas tanpa realisasi tidak muncul (dianggap 0 oleh pemanggil).
 */
class RealisasiEksporResolver
{
    /**
     * @param  list<int>  $entityIds
     * @return array<int, string> entity_id => total realisasi (desimal 4 digit)
     */
    public function forPeriod(KuotaPeriod $period, array $entityIds): array
    {
        if ($entityIds === []) {
            return [];
        }

        return $this->baseQuery($period)
            ->whereIn('sats_ln.entity_id', $entityIds)
            ->groupBy('sats_ln.entity_id')
            ->selectRaw('sats_ln.entity_id, SUM(sats_ln_items.realisasi_on_port) as total')
            ->pluck('total', 'sats_ln.entity_id')
            ->mapWithKeys(fn ($total, $entityId): array => [(int) $entityId => bcadd((string) $total, '0', 4)])
            ->all();
    }

    /**
     * Entitas yang punya realisasi ekspor > 0 untuk periode ini (kandidat peserta otomatis).
     *
     * @return list<int>
     */
    public function entityIdsWithRealisasi(KuotaPeriod $period): array
    {
        return $this->baseQuery($period)
            ->where('sats_ln_items.realisasi_on_port', '>', 0)
            ->distinct()
            ->pluck('sats_ln.entity_id')
            ->map(fn ($id): int => (int) $id)
            ->all();
    }

    private function baseQuery(KuotaPeriod $period): Builder
    {
        return DB::table('sats_ln_items')
            ->join('sats_ln', 'sats_ln.id', '=', 'sats_ln_items.sats_ln_id')
            ->join('kuota_nasional', 'kuota_nasional.id', '=', 'sats_ln_items.kuota_nasional_id')
            ->whereNull('sats_ln.deleted_at')
            ->where('sats_ln.type_satsln', 'Export')
            ->where('sats_ln.status_satsln', SatsLnStatus::Disetujui->value)
            ->where('sats_ln.year', $period->year - 1)
            ->where('sats_ln_items.species_id', $period->species_id)
            ->where('kuota_nasional.trade_code_id', $period->trade_code_id);
    }
}
