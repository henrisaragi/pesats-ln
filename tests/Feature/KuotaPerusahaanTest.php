<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\KomoditasEntitasStatus;
use App\Models\Entitas\Entitas;
use App\Models\Komoditas;
use App\Models\KomoditasEntitas;
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

class KuotaPerusahaanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeStaff(): User
    {
        $user = User::create([
            'name' => 'Staff Test',
            'email' => uniqid('staff', true).'@example.test',
            'username' => uniqid('staff', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->givePermissionTo([
            'kuota-perusahaan.view',
            'kuota-perusahaan.create',
            'kuota-perusahaan.update',
            'kuota-perusahaan.delete',
        ]);

        return $user;
    }

    private function makePerusahaan(): Entitas
    {
        return Entitas::create([
            'entity_type' => 'perusahaan',
            'name' => 'PT Test',
            'address' => 'Jl. Test',
        ]);
    }

    private function makeKuotaNasional(float $quota = 100, int $year = 2026): KuotaNasional
    {
        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $source = SourceStatus::create(['code' => 'W-'.rand(1000, 9999), 'description' => 'Wild']);
        $tradeCode = TradeCode::create(['name' => 'Kulit', 'code' => 'SKN-'.uniqid(), 'unit' => 'pcs']);

        return KuotaNasional::create([
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'source_id' => $source->id,
            'trade_code_id' => $tradeCode->id,
            'quota' => $quota,
            'year' => $year,
        ]);
    }

    /**
     * Cast every value to string, mirroring a real application/x-www-form-urlencoded submission
     * from the browser - PHP's test client otherwise keeps values as native ints/floats, which
     * masks bugs where a controller passes $request->validated() straight into a strictly-typed
     * (int $x) method instead of using $request->integer().
     *
     * @return array<string, mixed>
     */
    private function payload(Entitas $entitas, KuotaNasional $kuotaNasional, float $quota): array
    {
        return [
            'entity_id' => (string) $entitas->id,
            'komoditas_id' => (string) $kuotaNasional->komoditas_id,
            'source_id' => (string) $kuotaNasional->source_id,
            'trade_code_id' => (string) $kuotaNasional->trade_code_id,
            'species_id' => (string) $kuotaNasional->species_id,
            'year' => (string) $kuotaNasional->year,
            'quota' => (string) $quota,
        ];
    }

    private function verifyKomoditasFor(Entitas $entitas, int $komoditasId): void
    {
        KomoditasEntitas::create([
            'komoditas_id' => $komoditasId,
            'entitas_id' => $entitas->id,
            'file_path' => 'dummy.pdf',
            'tanggal_terbit' => '2026-01-01',
            'tanggal_berakhir' => '2031-01-01',
            'status' => KomoditasEntitasStatus::Verified,
        ]);
    }

    public function test_staff_can_view_index_with_existing_rows(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $kuotaNasional = $this->makeKuotaNasional(50);

        KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => 20,
            'year' => 2026,
        ]);

        $this->actingAs($staff)->get(route('kuota-perusahaan.index'))->assertOk();
    }

    public function test_index_only_lists_rows_for_the_active_year(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();

        $currentYear = (int) now()->year;
        $kuotaNasionalThisYear = $this->makeKuotaNasional(50, $currentYear);
        $kuotaNasionalLastYear = $this->makeKuotaNasional(50, $currentYear - 1);

        $thisYear = KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasionalThisYear->id,
            'quota' => 20,
            'year' => $currentYear,
        ]);

        $lastYear = KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasionalLastYear->id,
            'quota' => 20,
            'year' => $currentYear - 1,
        ]);

        $response = $this->actingAs($staff)->get(route('kuota-perusahaan.index'));

        $response->assertOk();
        $response->assertViewHas('activeYear', $currentYear);
        $ids = $response->viewData('kuotaPerusahaan')->pluck('id');

        $this->assertTrue($ids->contains($thisYear->id));
        $this->assertFalse($ids->contains($lastYear->id));

        $response = $this->actingAs($staff)->get(route('kuota-perusahaan.index', ['year' => $currentYear - 1]));
        $ids = $response->viewData('kuotaPerusahaan')->pluck('id');

        $this->assertTrue($ids->contains($lastYear->id));
        $this->assertFalse($ids->contains($thisYear->id));
    }

    public function test_index_can_be_filtered_by_komoditas(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $currentYear = (int) now()->year;

        $kuotaNasionalA = $this->makeKuotaNasional(50, $currentYear);
        $kuotaNasionalB = $this->makeKuotaNasional(50, $currentYear);

        $rowA = KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasionalA->id,
            'quota' => 20,
            'year' => $currentYear,
        ]);

        $rowB = KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasionalB->id,
            'quota' => 20,
            'year' => $currentYear,
        ]);

        $response = $this->actingAs($staff)->get(route('kuota-perusahaan.index', [
            'komoditas_id' => $kuotaNasionalA->komoditas_id,
        ]));

        $response->assertOk();
        $response->assertViewHas('activeKomoditasId', $kuotaNasionalA->komoditas_id);
        $ids = $response->viewData('kuotaPerusahaan')->pluck('id');

        $this->assertTrue($ids->contains($rowA->id));
        $this->assertFalse($ids->contains($rowB->id));
    }

    public function test_staff_can_create_kuota_perusahaan_when_komoditas_verified_and_quota_available(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $kuotaNasional = $this->makeKuotaNasional(100);
        $this->verifyKomoditasFor($entitas, $kuotaNasional->komoditas_id);

        $response = $this->actingAs($staff)->post(route('kuota-perusahaan.store'), $this->payload($entitas, $kuotaNasional, 40));

        $response->assertRedirect(route('kuota-perusahaan.index'));
        $this->assertDatabaseHas('kuota_perusahaan', [
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => 40,
            'year' => 2026,
        ]);
    }

    public function test_cannot_create_when_komoditas_not_verified_for_entity(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $kuotaNasional = $this->makeKuotaNasional(100);

        $response = $this->actingAs($staff)->post(route('kuota-perusahaan.store'), $this->payload($entitas, $kuotaNasional, 40));

        $response->assertSessionHasErrors('komoditas_id');
        $this->assertDatabaseMissing('kuota_perusahaan', ['entity_id' => $entitas->id]);
    }

    public function test_cannot_create_when_quota_exceeds_sisa_kuota_nasional(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $kuotaNasional = $this->makeKuotaNasional(50);
        $this->verifyKomoditasFor($entitas, $kuotaNasional->komoditas_id);

        $response = $this->actingAs($staff)->post(route('kuota-perusahaan.store'), $this->payload($entitas, $kuotaNasional, 60));

        $response->assertSessionHasErrors('quota');
        $this->assertDatabaseMissing('kuota_perusahaan', ['entity_id' => $entitas->id]);
    }

    public function test_update_can_keep_same_allocation_without_double_counting_itself(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $kuotaNasional = $this->makeKuotaNasional(50);
        $this->verifyKomoditasFor($entitas, $kuotaNasional->komoditas_id);

        $kuotaPerusahaan = KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => 50,
            'year' => 2026,
        ]);

        $response = $this->actingAs($staff)->put(route('kuota-perusahaan.update', $kuotaPerusahaan), $this->payload($entitas, $kuotaNasional, 50));

        $response->assertRedirect(route('kuota-perusahaan.index'));
        $this->assertDatabaseHas('kuota_perusahaan', ['id' => $kuotaPerusahaan->id, 'quota' => 50]);
    }

    public function test_staff_can_delete_kuota_perusahaan(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $kuotaNasional = $this->makeKuotaNasional(50);

        $kuotaPerusahaan = KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => 20,
            'year' => 2026,
        ]);

        $this->actingAs($staff)->delete(route('kuota-perusahaan.destroy', $kuotaPerusahaan))
            ->assertRedirect(route('kuota-perusahaan.index'));

        $this->assertDatabaseMissing('kuota_perusahaan', ['id' => $kuotaPerusahaan->id]);
    }

    public function test_index_shows_unit_name_and_quota_formatted_per_trade_code_decimal_places(): void
    {
        $staff = $this->makeStaff();
        $entitas = $this->makePerusahaan();
        $currentYear = (int) now()->year;

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $source = SourceStatus::create(['code' => 'W-'.rand(1000, 9999), 'description' => 'Wild']);
        $tradeCode = TradeCode::create([
            'name' => 'Kulit',
            'code' => 'SKN-'.uniqid(),
            'unit' => 'KG',
            'unit_name' => 'Kilogram',
            'decimal_places' => 3,
            'rounding_enabled' => false,
        ]);

        $kuotaNasional = KuotaNasional::create([
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'source_id' => $source->id,
            'trade_code_id' => $tradeCode->id,
            'quota' => 1000,
            'year' => $currentYear,
        ]);

        KuotaPerusahaan::create([
            'entity_id' => $entitas->id,
            'kuota_nasional_id' => $kuotaNasional->id,
            'quota' => 12.3459,
            'year' => $currentYear,
        ]);

        $response = $this->actingAs($staff)->get(route('kuota-perusahaan.index'));

        $response->assertOk();
        $response->assertSee('Kilogram');
        $response->assertSee('12,345/1.000');
    }
}
