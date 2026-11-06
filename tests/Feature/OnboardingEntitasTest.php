<?php

declare(strict_types=1);

namespace Tests\Feature;

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
}
