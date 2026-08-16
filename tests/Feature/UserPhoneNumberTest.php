<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\MasterDataPermissionSeeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserPhoneNumberTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->seed(PermissionSeeder::class);
        $this->seed(MasterDataPermissionSeeder::class);
    }

    private function makeSuperAdmin(): User
    {
        $user = User::create([
            'name' => 'Admin Test',
            'email' => uniqid('admin', true).'@example.test',
            'username' => uniqid('admin', true),
            'password' => bcrypt('password'),
            'is_active' => true,
            'onboarding_completed' => true,
        ]);

        $user->assignRole('super-admin');

        return $user;
    }

    public function test_phone_number_is_saved_when_creating_a_user(): void
    {
        $admin = $this->makeSuperAdmin();
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'New Staff',
            'username' => 'newstaff'.uniqid(),
            'email' => uniqid('new', true).'@example.test',
            'password' => 'password123',
            'phone_number' => '15551234567',
            'role' => 'staff',
        ])->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['name' => 'New Staff', 'phone_number' => '15551234567']);
    }

    public function test_phone_number_can_be_updated(): void
    {
        $admin = $this->makeSuperAdmin();
        Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'web']);

        $target = User::create([
            'name' => 'Existing Staff',
            'email' => uniqid('existing', true).'@example.test',
            'username' => uniqid('existing', true),
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);
        $target->assignRole('staff');

        $this->actingAs($admin)->put(route('users.update', $target), [
            'name' => $target->name,
            'username' => $target->username,
            'email' => $target->email,
            'phone_number' => '15559876543',
            'role' => 'staff',
        ])->assertRedirect(route('users.index'));

        $this->assertSame('15559876543', $target->fresh()->phone_number);
    }
}
