<?php

declare(strict_types=1);

namespace App\Services\KuotaTangkap;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KuotaPeriodStatus;
use App\Models\Assesment\AssesmentIsi;
use App\Models\Entitas\Entitas;
use App\Models\KuotaTangkap\KuotaActivity;
use App\Models\KuotaTangkap\KuotaAllocation;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Header periode & daftar peserta. Perubahan pada periode yang sudah dihitung mengembalikan
 * status ke draft, karena rekomendasi lama tidak lagi sesuai dengan inputnya.
 */
class KuotaPeriodService
{
    private const HEADER_FIELDS = ['provinsi_id', 'komoditas_id', 'species_id', 'trade_code_id', 'year', 'kuota_tangkap', 'catatan'];

    public function __construct(private readonly RealisasiEksporResolver $realisasiEksporResolver) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $user): KuotaPeriod
    {
        return DB::transaction(function () use ($data, $user): KuotaPeriod {
            $period = KuotaPeriod::query()->create([
                ...array_intersect_key($data, array_flip(self::HEADER_FIELDS)),
                'status' => KuotaPeriodStatus::Draft,
                'created_by' => $user->id,
            ]);

            KuotaActivity::record($period, 'created', $user, null, $period->only(self::HEADER_FIELDS));

            return $period;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(KuotaPeriod $period, array $data, User $user): KuotaPeriod
    {
        return DB::transaction(function () use ($period, $data, $user): KuotaPeriod {
            $period = KuotaPeriod::query()->lockForUpdate()->findOrFail($period->id);
            $old = $period->only(self::HEADER_FIELDS);

            $period->fill(array_intersect_key($data, array_flip(self::HEADER_FIELDS)));

            if (! $period->isDirty()) {
                return $period;
            }

            $changed = array_keys($period->getDirty());
            $period->save();
            $this->markStale($period, array_diff($changed, ['catatan']) !== []);

            KuotaActivity::record($period, 'updated', $user, array_intersect_key($old, array_flip($changed)), $period->only($changed));

            return $period;
        });
    }

    public function delete(KuotaPeriod $period, User $user): void
    {
        DB::transaction(function () use ($period, $user): void {
            KuotaActivity::record($period, 'deleted', $user, $period->only(self::HEADER_FIELDS));
            $period->delete();
        });
    }

    public function addParticipant(KuotaPeriod $period, int $entityId, ?string $permohonan, User $user): KuotaAllocation
    {
        return DB::transaction(function () use ($period, $entityId, $permohonan, $user): KuotaAllocation {
            $allocation = $period->allocations()->create([
                'entity_id' => $entityId,
                'permohonan' => $permohonan,
            ]);

            $this->markStale($period, true);
            KuotaActivity::record($period, 'participant_added', $user, null, ['entity_id' => $entityId, 'permohonan' => $permohonan], null, $allocation);

            return $allocation;
        });
    }

    public function updatePermohonan(KuotaAllocation $allocation, ?string $permohonan, User $user): void
    {
        DB::transaction(function () use ($allocation, $permohonan, $user): void {
            $old = $allocation->permohonan !== null ? (string) $allocation->permohonan : null;
            $allocation->update(['permohonan' => $permohonan]);

            KuotaActivity::record($allocation->period, 'updated', $user, ['permohonan' => $old], ['permohonan' => $permohonan], null, $allocation);
        });
    }

    public function removeParticipant(KuotaAllocation $allocation, User $user): void
    {
        DB::transaction(function () use ($allocation, $user): void {
            $period = $allocation->period;
            KuotaActivity::record($period, 'participant_removed', $user, ['entity_id' => $allocation->entity_id, 'permohonan' => $allocation->permohonan]);
            $allocation->delete();
            $this->markStale($period, true);
        });
    }

    /**
     * Kandidat otomatis: perusahaan dengan assessment terverifikasi untuk komoditas periode
     * ATAU realisasi ekspor tahun - 1 untuk jenis & kode perdagangan periode. Tidak dibatasi
     * provinsi domisili perusahaan (perusahaan luar provinsi bisa memegang kuota ekspor).
     *
     * @return int jumlah peserta baru
     */
    public function addCandidates(KuotaPeriod $period, User $user): int
    {
        $assesmentEntityIds = AssesmentIsi::query()
            ->where('komoditas_id', $period->komoditas_id)
            ->where('status', AssesmentIsiStatus::Verified->value)
            ->pluck('entitas_id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        $candidateIds = array_unique([...$assesmentEntityIds, ...$this->realisasiEksporResolver->entityIdsWithRealisasi($period)]);
        $existing = $period->allocations()->pluck('entity_id')->map(fn ($id): int => (int) $id)->all();

        $newIds = Entitas::query()
            ->where('entity_type', 'perusahaan')
            ->whereIn('id', array_diff($candidateIds, $existing))
            ->orderBy('name')
            ->pluck('id');

        foreach ($newIds as $entityId) {
            $this->addParticipant($period, (int) $entityId, null, $user);
        }

        return $newIds->count();
    }

    private function markStale(KuotaPeriod $period, bool $inputsChanged): void
    {
        if ($inputsChanged && $period->status === KuotaPeriodStatus::Dihitung) {
            $period->update(['status' => KuotaPeriodStatus::Draft]);
        }
    }
}
