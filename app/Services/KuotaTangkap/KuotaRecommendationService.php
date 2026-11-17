<?php

declare(strict_types=1);

namespace App\Services\KuotaTangkap;

use App\Enums\AssesmentIsiStatus;
use App\Enums\KuotaPeriodStatus;
use App\Models\Assesment\AssesmentIsi;
use App\Models\KuotaTangkap\KuotaActivity;
use App\Models\KuotaTangkap\KuotaCalculationSnapshot;
use App\Models\KuotaTangkap\KuotaPeriod;
use App\Models\KuotaTangkap\KuotaSetting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rekomendasi pembagian kuota ekspor (docs/PRD-pembagian-kuota-tangkap.md §3):
 *   Q_b = floor(Q × (1 − c))
 *   Rekomendasi_i = w_E × (E_i/ΣE) × Q_b + w_N × (N_i/ΣN) × Q_b
 * Hasil hanya rekomendasi - kolom "ditetapkan" tidak pernah disentuh di sini.
 */
class KuotaRecommendationService
{
    private const SCALE = 10;

    public function __construct(
        private readonly RealisasiEksporResolver $realisasiEksporResolver,
        private readonly LargestRemainderRounder $rounder,
    ) {}

    public function calculate(KuotaPeriod $period, User $user): KuotaCalculationSnapshot
    {
        return DB::transaction(function () use ($period, $user): KuotaCalculationSnapshot {
            $period = KuotaPeriod::query()->lockForUpdate()->findOrFail($period->id);

            if (! $period->status->isEditable()) {
                throw ValidationException::withMessages(['period' => __('Periode dengan status :status tidak dapat dihitung ulang.', ['status' => $period->status->label()])]);
            }

            $allocations = $period->allocations()->with('entitas')->orderBy('id')->get();

            if ($allocations->isEmpty()) {
                throw ValidationException::withMessages(['period' => __('Tambahkan minimal satu perusahaan peserta sebelum menghitung.')]);
            }

            $setting = KuotaSetting::current();
            $entityIds = $allocations->pluck('entity_id')->map(fn ($id): int => (int) $id)->all();
            $realisasi = $this->realisasiEksporResolver->forPeriod($period, $entityIds);
            $assesments = $this->latestVerifiedAssesments($period, $entityIds);

            $inputs = [];
            $warnings = [];

            foreach ($allocations as $allocation) {
                $assesment = $assesments[$allocation->entity_id] ?? null;

                if ($assesment === null || $assesment->skor_total === null) {
                    $warnings[] = ['code' => 'no_assesment', 'entity' => $allocation->entitas?->name];
                }

                $inputs[$allocation->entity_id] = [
                    'e' => $realisasi[$allocation->entity_id] ?? '0.0000',
                    'n' => $assesment?->skor_total !== null ? (string) $assesment->skor_total : '0.00',
                ];
            }

            $result = $this->compute(
                $inputs,
                (string) $period->kuota_tangkap,
                (string) $setting->cadangan,
                (string) $setting->bobot_ekspor,
                (string) $setting->bobot_evaluasi,
            );

            $warnings = [...$result['warnings'], ...$warnings];

            $rows = [];

            foreach ($allocations as $allocation) {
                $computed = $result['rows'][$allocation->entity_id];
                $assesment = $assesments[$allocation->entity_id] ?? null;

                $values = [
                    'realisasi_ekspor' => $inputs[$allocation->entity_id]['e'],
                    'nilai_evaluasi' => $inputs[$allocation->entity_id]['n'],
                    'assesment_isi_id' => $assesment?->id,
                    'komponen_ekspor' => $computed['komponen_ekspor'],
                    'komponen_evaluasi' => $computed['komponen_evaluasi'],
                    'rekomendasi' => $computed['rekomendasi'],
                    'rekomendasi_bulat' => $computed['rekomendasi_bulat'],
                ];

                $allocation->update($values);

                $rows[] = [
                    'entity_id' => $allocation->entity_id,
                    'nama' => $allocation->entitas?->name,
                    'permohonan' => $allocation->permohonan !== null ? (string) $allocation->permohonan : null,
                    'assesment_verified_at' => $assesment?->verified_at?->toDateString(),
                    ...$values,
                ];
            }

            $snapshot = KuotaCalculationSnapshot::query()->create([
                'kuota_period_id' => $period->id,
                'version' => (int) $period->snapshots()->max('version') + 1,
                'kuota_tangkap' => (string) $period->kuota_tangkap,
                'cadangan' => (string) $setting->cadangan,
                'bobot_ekspor' => (string) $setting->bobot_ekspor,
                'bobot_evaluasi' => (string) $setting->bobot_evaluasi,
                'kuota_dibagi' => $result['kuota_dibagi'],
                'total_realisasi_ekspor' => $result['total_realisasi_ekspor'],
                'total_nilai_evaluasi' => $result['total_nilai_evaluasi'],
                'tahun_realisasi' => $period->year - 1,
                'rows' => $rows,
                'warnings' => $warnings,
                'calculated_by' => $user->id,
            ]);

            $period->update([
                'status' => KuotaPeriodStatus::Dihitung,
                'latest_snapshot_id' => $snapshot->id,
            ]);

            KuotaActivity::record($period, 'calculated', $user, null, [
                'version' => $snapshot->version,
                'kuota_dibagi' => $snapshot->kuota_dibagi,
                'total_realisasi_ekspor' => $result['total_realisasi_ekspor'],
                'total_nilai_evaluasi' => $result['total_nilai_evaluasi'],
                'peringatan' => count($warnings),
            ]);

            return $snapshot;
        });
    }

    /**
     * Perhitungan murni (tanpa DB). Semua angka berupa string desimal agar presisi terjaga.
     *
     * @param  array<int|string, array{e: string, n: string}>  $inputs
     * @return array{
     *     kuota_dibagi: int,
     *     total_realisasi_ekspor: string,
     *     total_nilai_evaluasi: string,
     *     rows: array<int|string, array{komponen_ekspor: string, komponen_evaluasi: string, rekomendasi: string, rekomendasi_bulat: int}>,
     *     warnings: list<array{code: string}>
     * }
     */
    public function compute(array $inputs, string $kuotaTangkap, string $cadangan, string $bobotEkspor, string $bobotEvaluasi): array
    {
        $kuotaDibagi = bcadd(bcmul($kuotaTangkap, bcsub('1', $cadangan, self::SCALE), self::SCALE), '0', 0);

        $sumE = '0';
        $sumN = '0';

        foreach ($inputs as $input) {
            $sumE = bcadd($sumE, $input['e'], self::SCALE);
            $sumN = bcadd($sumN, $input['n'], self::SCALE);
        }

        $warnings = [];
        $hasE = bccomp($sumE, '0', self::SCALE) > 0;
        $hasN = bccomp($sumN, '0', self::SCALE) > 0;

        if (! $hasE) {
            $warnings[] = ['code' => 'sum_e_zero'];
        }

        if (! $hasN) {
            $warnings[] = ['code' => 'sum_n_zero'];
        }

        $rows = [];
        $exact = [];
        $total = '0';

        foreach ($inputs as $key => $input) {
            $komponenEkspor = $hasE
                ? bcdiv(bcmul(bcmul($bobotEkspor, $input['e'], self::SCALE), $kuotaDibagi, self::SCALE), $sumE, self::SCALE)
                : '0';
            $komponenEvaluasi = $hasN
                ? bcdiv(bcmul(bcmul($bobotEvaluasi, $input['n'], self::SCALE), $kuotaDibagi, self::SCALE), $sumN, self::SCALE)
                : '0';
            $rekomendasi = bcadd($komponenEkspor, $komponenEvaluasi, self::SCALE);
            $total = bcadd($total, $rekomendasi, self::SCALE);
            $exact[$key] = $this->roundHalfUp($rekomendasi, 4);

            $rows[$key] = [
                'komponen_ekspor' => $this->roundHalfUp($komponenEkspor, 4),
                'komponen_evaluasi' => $this->roundHalfUp($komponenEvaluasi, 4),
                'rekomendasi' => $exact[$key],
            ];
        }

        $target = (int) $this->roundHalfUp($total, 0);
        $rounded = $this->rounder->round($exact, $target);

        foreach ($rounded as $key => $value) {
            $rows[$key]['rekomendasi_bulat'] = $value;
        }

        return [
            'kuota_dibagi' => (int) $kuotaDibagi,
            'total_realisasi_ekspor' => $this->roundHalfUp($sumE, 4),
            'total_nilai_evaluasi' => $this->roundHalfUp($sumN, 2),
            'rows' => $rows,
            'warnings' => $warnings,
        ];
    }

    /**
     * @param  list<int>  $entityIds
     * @return array<int, AssesmentIsi> entitas_id => assesment terverifikasi terbaru untuk komoditas periode
     */
    private function latestVerifiedAssesments(KuotaPeriod $period, array $entityIds): array
    {
        return AssesmentIsi::query()
            ->where('komoditas_id', $period->komoditas_id)
            ->where('status', AssesmentIsiStatus::Verified->value)
            ->whereIn('entitas_id', $entityIds)
            ->orderByDesc('verified_at')
            ->orderByDesc('id')
            ->get()
            ->unique('entitas_id')
            ->keyBy('entitas_id')
            ->all();
    }

    private function roundHalfUp(string $value, int $scale): string
    {
        $half = $scale === 0 ? '0.5' : '0.'.str_repeat('0', $scale).'5';

        return bcadd($value, $half, $scale);
    }
}
