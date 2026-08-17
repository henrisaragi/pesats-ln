<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class MarkExpiredKomoditasEntitasCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeEntitasWithUser(bool $subjectToMasaBerlaku = true): Entitas
    {
        $entitas = Entitas::create(['entity_type' => 'perusahaan', 'name' => 'PT Test', 'address' => 'Jl. Test']);

        $user = User::create([
            'name' => 'Entity User',
            'email' => uniqid('entity', true).'@example.test',
            'username' => uniqid('entity', true),
            'password' => bcrypt('password'),
            'entity_id' => $entitas->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
        $user->assignRole('perusahaan');

        if (! $subjectToMasaBerlaku) {
            Role::findByName('perusahaan', 'web')->revokePermissionTo('komoditas-entitas.masa-berlaku');
        }

        return $entitas;
    }

    private function makeKomoditasEntitas(Entitas $entitas, KomoditasEntitasStatus $status, string $tanggalBerakhir): KomoditasEntitas
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);

        return KomoditasEntitas::create([
            'komoditas_id' => $komoditas->id,
            'entitas_id' => $entitas->id,
            'file_path' => 'dummy.pdf',
            'tanggal_terbit' => '2020-01-01',
            'tanggal_berakhir' => $tanggalBerakhir,
            'status' => $status,
        ]);
    }

    public function test_marks_verified_rows_past_tanggal_berakhir_as_expired(): void
    {
        $entitas = $this->makeEntitasWithUser();
        $overdue = $this->makeKomoditasEntitas($entitas, KomoditasEntitasStatus::Verified, now()->subDay()->toDateString());
        $dueToday = $this->makeKomoditasEntitas($entitas, KomoditasEntitasStatus::Verified, now()->toDateString());
        $stillValid = $this->makeKomoditasEntitas($entitas, KomoditasEntitasStatus::Verified, now()->addYear()->toDateString());

        $this->artisan('komoditas-entitas:mark-expired')->assertExitCode(0);

        $this->assertSame(KomoditasEntitasStatus::Expired, $overdue->fresh()->status);
        $this->assertSame(KomoditasEntitasStatus::Expired, $dueToday->fresh()->status);
        $this->assertSame(KomoditasEntitasStatus::Verified, $stillValid->fresh()->status);
    }

    public function test_does_not_touch_pending_or_already_expired_rows(): void
    {
        $entitas = $this->makeEntitasWithUser();
        $pending = $this->makeKomoditasEntitas($entitas, KomoditasEntitasStatus::Pending, now()->subDay()->toDateString());
        $alreadyExpired = $this->makeKomoditasEntitas($entitas, KomoditasEntitasStatus::Expired, now()->subDay()->toDateString());

        $this->artisan('komoditas-entitas:mark-expired');

        $this->assertSame(KomoditasEntitasStatus::Pending, $pending->fresh()->status);
        $this->assertSame(KomoditasEntitasStatus::Expired, $alreadyExpired->fresh()->status);
    }

    public function test_skips_entities_exempt_from_masa_berlaku(): void
    {
        $entitas = $this->makeEntitasWithUser(subjectToMasaBerlaku: false);
        $overdue = $this->makeKomoditasEntitas($entitas, KomoditasEntitasStatus::Verified, now()->subYears(2)->toDateString());

        $this->artisan('komoditas-entitas:mark-expired');

        $this->assertSame(KomoditasEntitasStatus::Verified, $overdue->fresh()->status);
    }
}
