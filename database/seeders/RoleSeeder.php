<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Roles auto-assigned at the end of the "lengkapi data entitas" onboarding wizard
     * (see App\Services\Onboarding\OnboardingService) - seeded here so they exist on
     * any fresh environment, not just where they were created manually via the Role UI.
     */
    private const ENTITY_TYPE_ROLES = ['perusahaan', 'penangkaran', 'perorangan', 'upt-ksda'];

    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);

        foreach (self::ENTITY_TYPE_ROLES as $role) {
            Role::firstOrCreate([
                'name' => $role,
                'guard_name' => 'web',
            ]);
        }
    }
}
