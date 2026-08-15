<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Komoditas;
use App\Models\KuotaNasional;
use App\Models\SourceStatus;
use App\Models\Species;
use App\Models\TradeCode;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KuotaNasionalTest extends TestCase
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

        $user->givePermissionTo(['kuota-nasional.view']);

        return $user;
    }

    private function makeKuotaNasional(int $year): KuotaNasional
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
            'quota' => 100,
            'year' => $year,
        ]);
    }

    public function test_index_defaults_to_current_year_when_no_year_query_param_given(): void
    {
        $staff = $this->makeStaff();
        $currentYear = (int) now()->year;

        $thisYear = $this->makeKuotaNasional($currentYear);
        $otherYear = $this->makeKuotaNasional($currentYear - 1);

        $response = $this->actingAs($staff)->get(route('master.kuota-nasional.index'));

        $response->assertOk();
        $response->assertViewHas('activeYear', $currentYear);
        $ids = $response->viewData('kuotaNasional')->pluck('id');

        $this->assertTrue($ids->contains($thisYear->id));
        $this->assertFalse($ids->contains($otherYear->id));
    }

    public function test_index_filters_by_the_requested_year(): void
    {
        $staff = $this->makeStaff();

        $y2025 = $this->makeKuotaNasional(2025);
        $y2026 = $this->makeKuotaNasional(2026);

        $response = $this->actingAs($staff)->get(route('master.kuota-nasional.index', ['year' => 2025]));

        $response->assertOk();
        $response->assertViewHas('activeYear', 2025);
        $ids = $response->viewData('kuotaNasional')->pluck('id');

        $this->assertTrue($ids->contains($y2025->id));
        $this->assertFalse($ids->contains($y2026->id));
    }

    public function test_index_shows_unit_name_and_quota_formatted_per_trade_code_decimal_places(): void
    {
        $staff = $this->makeStaff();

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $source = SourceStatus::create(['code' => 'W-'.rand(1000, 9999), 'description' => 'Wild']);
        $tradeCode = TradeCode::create([
            'name' => 'Kulit',
            'code' => 'SKN-'.uniqid(),
            'unit' => 'KG',
            'unit_name' => 'Kilogram',
            'decimal_places' => 4,
            'rounding_enabled' => true,
        ]);

        KuotaNasional::create([
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'source_id' => $source->id,
            'trade_code_id' => $tradeCode->id,
            'quota' => 12.34567,
            'year' => (int) now()->year,
        ]);

        $response = $this->actingAs($staff)->get(route('master.kuota-nasional.index'));

        $response->assertOk();
        $response->assertSee('Kilogram');
        $response->assertSee('12,3457');
    }

    public function test_index_shows_whole_numbers_without_decimals_for_zero_decimal_trade_codes(): void
    {
        $staff = $this->makeStaff();

        $komoditas = Komoditas::create(['name' => 'Reptil', 'code' => 'RPT-'.uniqid()]);
        $species = Species::create(['komoditas_id' => $komoditas->id, 'scientific_name' => 'Varanus salvator', 'trade_status' => 'II']);
        $source = SourceStatus::create(['code' => 'W-'.rand(1000, 9999), 'description' => 'Wild']);
        $tradeCode = TradeCode::create([
            'name' => 'Individu Hidup',
            'code' => 'LIV-'.uniqid(),
            'unit' => 'LIV',
            'unit_name' => 'Ekor',
            'decimal_places' => 0,
            'rounding_enabled' => true,
        ]);

        KuotaNasional::create([
            'komoditas_id' => $komoditas->id,
            'species_id' => $species->id,
            'source_id' => $source->id,
            'trade_code_id' => $tradeCode->id,
            'quota' => 100,
            'year' => (int) now()->year,
        ]);

        $response = $this->actingAs($staff)->get(route('master.kuota-nasional.index'));

        $response->assertOk();
        $response->assertSee('Ekor');
        $response->assertDontSee('100,00');
    }
}
