<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppSetting;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppSettingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeUser(bool $withPermission): User
    {
        $user = User::create([
            'name' => 'User Test',
            'email' => uniqid('user', true).'@example.test',
            'username' => uniqid('user', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        if ($withPermission) {
            $user->givePermissionTo('settings.whatsapp.manage');
        }

        return $user;
    }

    public function test_user_without_permission_cannot_view_the_page(): void
    {
        $user = $this->makeUser(withPermission: false);

        $this->actingAs($user)->get(route('settings.whatsapp.index'))->assertForbidden();
    }

    public function test_user_with_permission_can_view_and_toggle_the_setting(): void
    {
        $user = $this->makeUser(withPermission: true);

        $this->actingAs($user)->get(route('settings.whatsapp.index'))->assertOk();

        $this->actingAs($user)->put(route('settings.whatsapp.update'), ['is_enabled' => '1'])
            ->assertRedirect(route('settings.whatsapp.index'));

        $this->assertTrue(WhatsAppSetting::isEnabled());

        $this->actingAs($user)->put(route('settings.whatsapp.update'), [])
            ->assertRedirect(route('settings.whatsapp.index'));

        $this->assertFalse(WhatsAppSetting::isEnabled());
    }

    public function test_setting_defaults_to_disabled_when_no_row_exists_yet(): void
    {
        $this->assertFalse(WhatsAppSetting::isEnabled());
    }
}
