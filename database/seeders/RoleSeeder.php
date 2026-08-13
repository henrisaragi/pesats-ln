<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the application's roles.
     */
    public function run(): void
    {
        Role::firstOrCreate([
            'name' => 'super-admin',
            'guard_name' => 'web',
        ]);
    }
}
