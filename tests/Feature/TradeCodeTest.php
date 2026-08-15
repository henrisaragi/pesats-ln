<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\TradeCode;
use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradeCodeTest extends TestCase
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

        $user->givePermissionTo(['trade-codes.view', 'trade-codes.create', 'trade-codes.update']);

        return $user;
    }

    public function test_staff_can_create_trade_code_with_unit_name_and_decimal_settings(): void
    {
        $staff = $this->makeStaff();

        $response = $this->actingAs($staff)->post(route('master.trade-codes.store'), [
            'name' => 'Kulit',
            'code' => 'SKN-'.uniqid(),
            'unit' => 'KG',
            'unit_name' => 'Kilogram',
            'decimal_places' => '4',
            'rounding_enabled' => '1',
            'description' => 'Kulit mentah',
        ]);

        $response->assertRedirect(route('master.trade-codes.index'));
        $this->assertDatabaseHas('trade_codes', [
            'unit_name' => 'Kilogram',
            'decimal_places' => 4,
            'rounding_enabled' => true,
        ]);
    }

    public function test_rounding_enabled_defaults_to_false_when_checkbox_not_submitted(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('master.trade-codes.store'), [
            'name' => 'Kulit',
            'code' => 'SKN-'.uniqid(),
            'decimal_places' => '2',
        ])->assertRedirect(route('master.trade-codes.index'));

        $this->assertDatabaseHas('trade_codes', ['rounding_enabled' => false]);
    }

    public function test_decimal_places_must_be_between_0_and_4(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('master.trade-codes.store'), [
            'name' => 'Kulit',
            'code' => 'SKN-'.uniqid(),
            'decimal_places' => '5',
        ])->assertSessionHasErrors('decimal_places');
    }

    public function test_zero_decimal_places_is_allowed_for_whole_number_units(): void
    {
        $staff = $this->makeStaff();

        $this->actingAs($staff)->post(route('master.trade-codes.store'), [
            'name' => 'Individu Hidup',
            'code' => 'LIV-'.uniqid(),
            'unit' => 'LIV',
            'unit_name' => 'Ekor',
            'decimal_places' => '0',
            'rounding_enabled' => '1',
        ])->assertRedirect(route('master.trade-codes.index'));

        $this->assertDatabaseHas('trade_codes', ['unit' => 'LIV', 'decimal_places' => 0]);
    }

    public function test_staff_can_update_unit_name_and_decimal_settings(): void
    {
        $staff = $this->makeStaff();

        $tradeCode = TradeCode::create([
            'name' => 'Kulit',
            'code' => 'SKN-'.uniqid(),
            'decimal_places' => 2,
            'rounding_enabled' => true,
        ]);

        $this->actingAs($staff)->put(route('master.trade-codes.update', $tradeCode), [
            'name' => 'Kulit',
            'code' => $tradeCode->code,
            'unit_name' => 'Kilogram',
            'decimal_places' => '3',
            'rounding_enabled' => '0',
        ])->assertRedirect(route('master.trade-codes.index'));

        $tradeCode->refresh();
        $this->assertSame('Kilogram', $tradeCode->unit_name);
        $this->assertSame(3, $tradeCode->decimal_places);
        $this->assertFalse($tradeCode->rounding_enabled);
    }
}
