<?php

declare(strict_types=1);

namespace App\Services\KuotaTangkap;

use App\Enums\KuotaPeriodStatus;
use App\Models\KuotaTangkap\KuotaActivity;
use App\Models\KuotaTangkap\KuotaAllocation;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/**
 * Penetapan manual dan transisi status periode (dihitung → diajukan → ditetapkan → ditutup).
 * Semua transisi mengunci baris periode dan memeriksa status asal di dalam transaksi.
 */
class KuotaPeriodWorkflowService
{
    /**
     * @param  array<int, array{ditetapkan: ?string, alasan_penyesuaian: ?string}>  $input  allocation_id => nilai
     */
    public function saveAllocations(KuotaPeriod $period, array $input, User $user): void
    {
        DB::transaction(function () use ($period, $input, $user): void {
            $period = $this->lockInStatus($period, KuotaPeriodStatus::Dihitung);
            $allocations = $period->allocations()->get()->keyBy('id');

            $proposed = $allocations->map(function (KuotaAllocation $allocation) use ($input): array {
                $row = $input[$allocation->id] ?? null;

                return $row === null
                    ? ['ditetapkan' => $this->nullableDecimal($allocation->ditetapkan), 'alasan_penyesuaian' => $allocation->alasan_penyesuaian]
                    : ['ditetapkan' => $this->nullableDecimal($row['ditetapkan']), 'alasan_penyesuaian' => $this->nullableText($row['alasan_penyesuaian'])];
            });

            $this->assertAllocationsValid($period, $allocations, $proposed, false);

            foreach ($allocations as $id => $allocation) {
                $new = $proposed[$id];
                $old = ['ditetapkan' => $this->nullableDecimal($allocation->ditetapkan), 'alasan_penyesuaian' => $allocation->alasan_penyesuaian];

                if ($old === $new) {
                    continue;
                }

                $allocation->update($new);
                KuotaActivity::record($period, 'allocated', $user, $old, $new, null, $allocation);
            }
        });
    }

    /**
     * Isi Ditetapkan = rekomendasi bulat untuk baris yang masih kosong.
     */
    public function copyRecommendation(KuotaPeriod $period, User $user): int
    {
        return DB::transaction(function () use ($period, $user): int {
            $period = $this->lockInStatus($period, KuotaPeriodStatus::Dihitung);
            $allocations = $period->allocations()->get()->keyBy('id');

            $proposed = $allocations->map(fn (KuotaAllocation $allocation): array => [
                'ditetapkan' => $allocation->ditetapkan === null && $allocation->rekomendasi_bulat !== null
                    ? bcadd((string) $allocation->rekomendasi_bulat, '0', 4)
                    : $this->nullableDecimal($allocation->ditetapkan),
                'alasan_penyesuaian' => $allocation->alasan_penyesuaian,
            ]);

            $this->assertAllocationsValid($period, $allocations, $proposed, false);

            $count = 0;

            foreach ($allocations as $id => $allocation) {
                if ($allocation->ditetapkan !== null || $proposed[$id]['ditetapkan'] === null) {
                    continue;
                }

                $allocation->update(['ditetapkan' => $proposed[$id]['ditetapkan']]);
                $count++;
            }

            KuotaActivity::record($period, 'recommendation_copied', $user, null, ['jumlah_baris' => $count]);

            return $count;
        });
    }

    public function submit(KuotaPeriod $period, User $user): void
    {
        DB::transaction(function () use ($period, $user): void {
            $period = $this->lockInStatus($period, KuotaPeriodStatus::Dihitung);
            $allocations = $period->allocations()->get()->keyBy('id');

            if ($period->latest_snapshot_id === null || $allocations->isEmpty()) {
                throw ValidationException::withMessages(['period' => __('Periode belum dihitung.')]);
            }

            $current = $allocations->map(fn (KuotaAllocation $allocation): array => [
                'ditetapkan' => $this->nullableDecimal($allocation->ditetapkan),
                'alasan_penyesuaian' => $allocation->alasan_penyesuaian,
            ]);

            $this->assertAllocationsValid($period, $allocations, $current, true);

            $period->update([
                'status' => KuotaPeriodStatus::Diajukan,
                'submitted_by' => $user->id,
                'submitted_at' => now(),
            ]);

            KuotaActivity::record($period, 'submitted', $user, null, ['total_ditetapkan' => $this->sumDitetapkan($current)]);
        });
    }

    public function approve(KuotaPeriod $period, User $user): void
    {
        DB::transaction(function () use ($period, $user): void {
            $period = $this->lockInStatus($period, KuotaPeriodStatus::Diajukan);

            if ($period->submitted_by === $user->id) {
                throw ValidationException::withMessages(['period' => __('Pengaju tidak dapat menyetujui periode yang diajukannya sendiri.')]);
            }

            $period->update([
                'status' => KuotaPeriodStatus::Ditetapkan,
                'approved_by' => $user->id,
                'approved_at' => now(),
                'catatan_pengembalian' => null,
            ]);

            KuotaActivity::record($period, 'approved', $user);
        });
    }

    public function return(KuotaPeriod $period, string $catatan, User $user): void
    {
        DB::transaction(function () use ($period, $catatan, $user): void {
            $period = $this->lockInStatus($period, KuotaPeriodStatus::Diajukan);

            $period->update([
                'status' => KuotaPeriodStatus::Dihitung,
                'catatan_pengembalian' => $catatan,
                'submitted_by' => null,
                'submitted_at' => null,
            ]);

            KuotaActivity::record($period, 'returned', $user, null, null, $catatan);
        });
    }

    public function close(KuotaPeriod $period, User $user): void
    {
        DB::transaction(function () use ($period, $user): void {
            $period = $this->lockInStatus($period, KuotaPeriodStatus::Ditetapkan);

            $period->update([
                'status' => KuotaPeriodStatus::Ditutup,
                'closed_by' => $user->id,
                'closed_at' => now(),
            ]);

            KuotaActivity::record($period, 'closed', $user);
        });
    }

    /**
     * @param  array{realisasi_tangkap: ?string, sk_tangkap_nomor: ?string, sk_tangkap_tanggal: ?string}  $data
     */
    public function updateRealisasi(KuotaAllocation $allocation, array $data, ?UploadedFile $skFile, User $user): void
    {
        DB::transaction(function () use ($allocation, $data, $skFile, $user): void {
            $period = $this->lockInStatus($allocation->period, KuotaPeriodStatus::Ditetapkan);
            $old = $this->realisasiSnapshot($allocation);

            $values = [
                'realisasi_tangkap' => $this->nullableDecimal($data['realisasi_tangkap'] ?? null),
                'sk_tangkap_nomor' => $this->nullableText($data['sk_tangkap_nomor'] ?? null),
                'sk_tangkap_tanggal' => $this->nullableText($data['sk_tangkap_tanggal'] ?? null),
            ];

            if ($skFile !== null) {
                $path = sprintf('kuota-tangkap/%d/%d/sk-kuota-ekspor-%d-%s.pdf', $period->year, $period->id, $allocation->entity_id, now()->format('YmdHis'));
                Storage::disk('s3')->put($path, (string) file_get_contents($skFile->getRealPath()));

                if ($allocation->sk_tangkap_path !== null) {
                    Storage::disk('s3')->delete($allocation->sk_tangkap_path);
                }

                $values['sk_tangkap_path'] = $path;
            }

            $allocation->update($values);

            KuotaActivity::record($period, 'realisasi_updated', $user, $old, $this->realisasiSnapshot($allocation->refresh()), null, $allocation);
        });
    }

    /**
     * Cadangan aktual = Q − Σ ditetapkan.
     */
    public function cadanganAktual(KuotaPeriod $period, ?Collection $allocations = null): string
    {
        $allocations ??= $period->allocations;
        $sum = $allocations->reduce(fn (string $carry, KuotaAllocation $allocation): string => bcadd($carry, (string) ($allocation->ditetapkan ?? '0'), 4), '0');

        return bcsub((string) $period->kuota_tangkap, $sum, 4);
    }

    /**
     * Peringatan non-blocking: cadangan aktual < persentase cadangan (snapshot terakhir) × Q.
     */
    public function cadanganBelowMinimum(KuotaPeriod $period, ?Collection $allocations = null): bool
    {
        $cadangan = (string) ($period->latestSnapshot?->cadangan ?? '0');
        $minimum = bcmul((string) $period->kuota_tangkap, $cadangan, 4);

        return bccomp($this->cadanganAktual($period, $allocations), $minimum, 4) < 0;
    }

    /**
     * @param  Collection<int, KuotaAllocation>  $allocations
     * @param  Collection<int, array{ditetapkan: ?string, alasan_penyesuaian: ?string}>  $proposed
     */
    private function assertAllocationsValid(KuotaPeriod $period, Collection $allocations, Collection $proposed, bool $requireAll): void
    {
        $errors = [];

        foreach ($allocations as $id => $allocation) {
            $row = $proposed[$id];

            if ($requireAll && $row['ditetapkan'] === null) {
                $errors["allocations.{$id}.ditetapkan"] = __('Nilai ditetapkan untuk :entitas belum diisi.', ['entitas' => $allocation->entitas?->name]);

                continue;
            }

            if (KuotaAllocation::requiresReason($row['ditetapkan'], $allocation->rekomendasi_bulat) && $row['alasan_penyesuaian'] === null) {
                $errors["allocations.{$id}.alasan_penyesuaian"] = __('Alasan wajib diisi untuk :entitas karena nilai ditetapkan berbeda dari rekomendasi.', ['entitas' => $allocation->entitas?->name]);
            }
        }

        $sum = $this->sumDitetapkan($proposed);

        if (bccomp($sum, (string) $period->kuota_tangkap, 4) > 0) {
            $errors['allocations'] = __('Total ditetapkan (:total) melebihi kuota ekspor (:kuota).', [
                'total' => number_format((float) $sum, 0, ',', '.'),
                'kuota' => number_format((float) $period->kuota_tangkap, 0, ',', '.'),
            ]);
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @param  Collection<int, array{ditetapkan: ?string, alasan_penyesuaian: ?string}>  $rows
     */
    private function sumDitetapkan(Collection $rows): string
    {
        return $rows->reduce(fn (string $carry, array $row): string => bcadd($carry, $row['ditetapkan'] ?? '0', 4), '0');
    }

    private function lockInStatus(KuotaPeriod $period, KuotaPeriodStatus $expected): KuotaPeriod
    {
        $locked = KuotaPeriod::query()->lockForUpdate()->findOrFail($period->id);

        if ($locked->status !== $expected) {
            throw ValidationException::withMessages(['period' => __('Aksi tidak dapat dilakukan pada periode berstatus :status.', ['status' => $locked->status->label()])]);
        }

        return $locked;
    }

    /**
     * @return array<string, mixed>
     */
    private function realisasiSnapshot(KuotaAllocation $allocation): array
    {
        return [
            'realisasi_tangkap' => $this->nullableDecimal($allocation->realisasi_tangkap),
            'sk_tangkap_nomor' => $allocation->sk_tangkap_nomor,
            'sk_tangkap_tanggal' => $allocation->sk_tangkap_tanggal?->toDateString(),
            'sk_tangkap_path' => $allocation->sk_tangkap_path,
        ];
    }

    private function nullableDecimal(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return bcadd((string) $value, '0', 4);
    }

    private function nullableText(mixed $value): ?string
    {
        $value = is_string($value) ? trim($value) : $value;

        return $value === null || $value === '' ? null : (string) $value;
    }
}
