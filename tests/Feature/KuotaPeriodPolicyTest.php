<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KuotaPeriodStatus;
use App\Models\Entitas\Entitas;
use Database\Seeders\KuotaTangkapPermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\KuotaTangkapFixtures;
use Tests\TestCase;

/**
 * Matriks ability × status KuotaPeriodPolicy untuk user yang memegang SEMUA permission kuota.*,
 * sehingga yang diuji murni aturan status, pemisahan pengaju, wilayah, dan kepemilikan.
 */
class KuotaPeriodPolicyTest extends TestCase
{
    use KuotaTangkapFixtures;
    use RefreshDatabase;

    /**
     * @return array<string, array{0: string, 1: list<KuotaPeriodStatus>}>
     */
    public static function abilityStatusMatrix(): array
    {
        return [
            'update' => ['update', [KuotaPeriodStatus::Draft, KuotaPeriodStatus::Dihitung]],
            'delete' => ['delete', [KuotaPeriodStatus::Draft]],
            'calculate' => ['calculate', [KuotaPeriodStatus::Draft, KuotaPeriodStatus::Dihitung]],
            'allocate' => ['allocate', [KuotaPeriodStatus::Dihitung]],
            'submit' => ['submit', [KuotaPeriodStatus::Dihitung]],
            'approve' => ['approve', [KuotaPeriodStatus::Diajukan]],
            'return' => ['return', [KuotaPeriodStatus::Diajukan]],
            'close' => ['close', [KuotaPeriodStatus::Ditetapkan]],
            'realisasi' => ['realisasi', [KuotaPeriodStatus::Ditetapkan]],
            'view' => ['view', KuotaPeriodStatus::cases()],
            'export' => ['export', KuotaPeriodStatus::cases()],
        ];
    }

    /**
     * @param  list<KuotaPeriodStatus>  $allowedStatuses
     */
    #[DataProvider('abilityStatusMatrix')]
    public function test_ability_is_allowed_only_in_expected_statuses(string $ability, array $allowedStatuses): void
    {
        $this->seed(KuotaTangkapPermissionSeeder::class);
        $user = $this->makeKuotaUser(KuotaTangkapPermissionSeeder::PERMISSIONS);
        $period = $this->makeKuotaPeriod();

        foreach (KuotaPeriodStatus::cases() as $status) {
            $period->status = $status;

            $this->assertSame(in_array($status, $allowedStatuses, true), $user->can($ability, $period), "{$ability} @ {$status->value}");
        }
    }

    public function test_permission_is_still_required(): void
    {
        $this->seed(KuotaTangkapPermissionSeeder::class);
        $user = $this->makeKuotaUser(['kuota.view']);
        $period = $this->makeKuotaPeriod(['status' => KuotaPeriodStatus::Dihitung]);

        $this->assertTrue($user->can('view', $period));
        $this->assertFalse($user->can('allocate', $period));
        $this->assertFalse($user->can('calculate', $period));
    }

    public function test_submitter_cannot_approve_or_return_but_another_approver_can(): void
    {
        $this->seed(KuotaTangkapPermissionSeeder::class);
        $submitter = $this->makeKuotaUser(KuotaTangkapPermissionSeeder::PERMISSIONS);
        $other = $this->makeKuotaUser(['kuota.view', 'kuota.approve']);
        $period = $this->makeKuotaPeriod(['status' => KuotaPeriodStatus::Diajukan, 'submitted_by' => $submitter->id]);

        $this->assertFalse($submitter->can('approve', $period));
        $this->assertFalse($submitter->can('return', $period));
        $this->assertTrue($other->can('approve', $period));
        $this->assertTrue($other->can('return', $period));
    }

    public function test_balai_user_is_limited_to_own_province(): void
    {
        $this->seed(KuotaTangkapPermissionSeeder::class);
        $jatim = $this->makeProvinsi('Jawa Timur');
        $balai = Entitas::create(['entity_type' => 'upt_ksda', 'name' => 'BBKSDA Jatim', 'provinsi_id' => $jatim->id]);
        $user = $this->makeKuotaUser(KuotaTangkapPermissionSeeder::PERMISSIONS, $balai);

        $this->assertTrue($user->can('view', $this->makeKuotaPeriod(['provinsi_id' => $jatim->id])));
        $this->assertFalse($user->can('view', $this->makeKuotaPeriod(['provinsi_id' => $this->makeProvinsi('Bali')->id])));
    }

    public function test_view_own_requires_participation_and_approved_status(): void
    {
        $this->seed(KuotaTangkapPermissionSeeder::class);
        $period = $this->makeKuotaPeriod(['status' => KuotaPeriodStatus::Diajukan]);
        $peserta = $this->makePerusahaan('PT Peserta');
        $bukanPeserta = $this->makePerusahaan('PT Lain');
        $this->addParticipant($period, $peserta);
        $pesertaUser = $this->makeKuotaUser(['kuota.view-own'], $peserta);
        $lainUser = $this->makeKuotaUser(['kuota.view-own'], $bukanPeserta);

        $this->assertFalse($pesertaUser->can('viewOwn', $period));

        $period->update(['status' => KuotaPeriodStatus::Ditetapkan]);

        $this->assertTrue($pesertaUser->can('viewOwn', $period));
        $this->assertFalse($lainUser->can('viewOwn', $period));
        $this->assertFalse($pesertaUser->can('view', $period));
    }
}
