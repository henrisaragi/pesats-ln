<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KuotaNasional;
use App\Models\KuotaPerusahaan;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KuotaSayaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeUser(string $entityType): User
    {
        $entity = Entitas::create([
            'entity_type' => $entityType,
            'name' => 'Entitas Test',
            'address' => 'Jl. Test',
        ]);

        $user = User::create([
            'name' => 'User Test',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'entity_id' => $entity->id,
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->assignRole($entityType);

        return $user;
    }

    private function makeKuotaPerusahaan(Entitas $entitas, int $year, float $quota = 100): KuotaPerusahaan
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $source = SourceStatus::create(['code' => 'W-'.rand(1000, 9999), 'description' => 'Wild']);
        $tradeCode = TradeCode::create(['name' => 'Kulit', 'code' => 'SKN-'.uniqid(), 'unit_name' => 'Kilogram']);

        $kuotaNasional = KuotaNasional::create([
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'source_id' => $source->id,
            'trade_code_id' => $tradeCode->id,
            'quota' => $quota + 900,
            'year' => $year,
        ]);

        return KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => $quota,
            'year' => $year,
        ]);
    }

    public function test_perusahaan_user_only_sees_their_own_entitys_kuota(): void
    {
        $currentYear = (int) now()->year;
        $owner = $this->makeUser('perusahaan');
        $stranger = $this->makeUser('perusahaan');

        $ownRow = $this->makeKuotaPerusahaan($owner->entity, $currentYear);
        $strangerRow = $this->makeKuotaPerusahaan($stranger->entity, $currentYear);

        $response = $this->actingAs($owner)->get(route('kuota-saya.index'));

        $response->assertOk();
        $ids = $response->viewData('kuotaPerusahaan')->pluck('id');

        $this->assertTrue($ids->contains($ownRow->id));
        $this->assertFalse($ids->contains($strangerRow->id));
    }

    public function test_index_filters_by_active_year(): void
    {
        $currentYear = (int) now()->year;
        $user = $this->makeUser('perusahaan');

        $thisYear = $this->makeKuotaPerusahaan($user->entity, $currentYear);
        $lastYear = $this->makeKuotaPerusahaan($user->entity, $currentYear - 1);

        $response = $this->actingAs($user)->get(route('kuota-saya.index'));
        $ids = $response->viewData('kuotaPerusahaan')->pluck('id');

        $this->assertTrue($ids->contains($thisYear->id));
        $this->assertFalse($ids->contains($lastYear->id));
    }

    public function test_perorangan_user_cannot_view_kuota_saya(): void
    {
        $user = $this->makeUser('perorangan');

        $this->actingAs($user)->get(route('kuota-saya.index'))->assertForbidden();
    }

    public function test_index_shows_own_quota_over_national_quota(): void
    {
        $currentYear = (int) now()->year;
        $user = $this->makeUser('perusahaan');
        $this->makeKuotaPerusahaan($user->entity, $currentYear, 100);

        $response = $this->actingAs($user)->get(route('kuota-saya.index'));

        $response->assertOk();
        $response->assertSee('Kilogram');
        $response->assertSee('100,00/1.000,00');
    }
}
