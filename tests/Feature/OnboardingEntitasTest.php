<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Kota;
use App\Models\Provinsi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class OnboardingEntitasTest extends TestCase
{
    use RefreshDatabase;

    private function makeOnboardingUser(): User
    {
        return User::create([
            'name' => 'Budi Santoso',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => false,
        ]);
    }

    public function test_choosing_type_does_not_copy_user_name_into_entity_name(): void
    {
        $user = $this->makeOnboardingUser();

        $this->actingAs($user)
            ->post(route('onboarding.type.store'), ['entity_type' => 'perusahaan'])
            ->assertRedirect(route('onboarding.entitas.show'));

        $this->assertSame('', $user->fresh()->entity->name);

        $this->actingAs($user->fresh())
            ->get(route('onboarding.entitas.show'))
            ->assertOk()
            ->assertDontSee('value="Budi Santoso"', false);
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function entityNameLabels(): array
    {
        return [
            'perusahaan' => ['perusahaan', 'Nama Perusahaan'],
            'penangkaran' => ['penangkaran', 'Nama Unit Penangkaran'],
            'perorangan' => ['perorangan', 'Nama Lengkap Sesuai Identitas Paspor/KTP'],
            'upt_ksda' => ['upt_ksda', 'Nama Lengkap Sesuai Identitas'],
        ];
    }

    #[DataProvider('entityNameLabels')]
    public function test_entity_name_label_follows_entity_type(string $entityType, string $label): void
    {
        $user = $this->makeOnboardingUser();

        $this->actingAs($user)->post(route('onboarding.type.store'), ['entity_type' => $entityType])->assertRedirect(route('onboarding.entitas.show'));

        $this->actingAs($user->fresh())
            ->get(route('onboarding.entitas.show'))
            ->assertOk()
            ->assertSee($label);
    }

    /**
     * @return array<string, int|string>
     */
    private function entitasPayload(): array
    {
        $provinsi = Provinsi::create(['code' => '31', 'name' => 'DKI Jakarta']);
        $kota = Kota::create(['provinsi_id' => $provinsi->id, 'code' => '31.71', 'name' => 'Jakarta Pusat']);
        $kecamatan = Kecamatan::create(['kota_id' => $kota->id, 'code' => '31.71.01', 'name' => 'Gambir']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'code' => '31.71.01.1001', 'name' => 'Gambir']);

        return [
            'name' => 'PT Satwa Lestari',
            'address' => 'Jl. Merdeka No. 1',
            'provinsi_id' => $provinsi->id,
            'kota_id' => $kota->id,
            'kecamatan_id' => $kecamatan->id,
            'kelurahan_id' => $kelurahan->id,
            'latitude' => '-6.1753924',
            'longitude' => '106.8271528',
        ];
    }

    public function test_entitas_step_saves_coordinates(): void
    {
        $user = $this->makeOnboardingUser();
        $this->actingAs($user)->post(route('onboarding.type.store'), ['entity_type' => 'perusahaan']);

        $this->actingAs($user->fresh())
            ->post(route('onboarding.entitas.store'), $this->entitasPayload())
            ->assertRedirect(route('onboarding.detail.show'));

        $entity = $user->fresh()->entity;
        $this->assertSame('-6.1753924', $entity->latitude);
        $this->assertSame('106.8271528', $entity->longitude);
    }

    public function test_entitas_step_rejects_missing_or_out_of_range_coordinates(): void
    {
        $user = $this->makeOnboardingUser();
        $this->actingAs($user)->post(route('onboarding.type.store'), ['entity_type' => 'perusahaan']);

        $this->actingAs($user->fresh())
            ->post(route('onboarding.entitas.store'), [
                ...$this->entitasPayload(),
                'latitude' => '',
                'longitude' => '200',
            ])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertNull($user->fresh()->entity->latitude);
    }
}
