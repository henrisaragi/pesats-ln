<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServerStatusTest extends TestCase
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
            $user->givePermissionTo('settings.server-status.view');
        }

        return $user;
    }

    public function test_user_without_permission_cannot_view_the_page(): void
    {
        $user = $this->makeUser(withPermission: false);

        $this->actingAs($user)->get(route('settings.server-status.index'))->assertForbidden();
    }

    public function test_user_with_permission_can_view_the_page_with_all_three_cards(): void
    {
        $user = $this->makeUser(withPermission: true);

        $response = $this->actingAs($user)->get(route('settings.server-status.index'));

        $response->assertOk();
        $response->assertSee('php artisan queue:work', false);
        $response->assertSee('php artisan queue:work --queue=notifications --tries=3 --sleep=1', false);
        $response->assertSee('php artisan reverb:start', false);
        $response->assertSee(__('Queue Worker (Default)'));
        $response->assertSee(__('Queue Worker (Notifications)'));
        $response->assertSee(__('Reverb Server'));
    }

    public function test_super_admin_has_the_permission_by_default(): void
    {
        $superAdmin = User::create([
            'name' => 'Super Admin Test',
            'email' => uniqid('superadmin', true).'@example.test',
            'username' => uniqid('superadmin', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);
        $superAdmin->assignRole('super-admin');

        $this->assertTrue($superAdmin->can('settings.server-status.view'));
        $this->actingAs($superAdmin)->get(route('settings.server-status.index'))->assertOk();
    }
}
