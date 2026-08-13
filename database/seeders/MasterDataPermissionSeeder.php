<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class MasterDataPermissionSeeder extends Seeder
{
    private const ENTITIES = [
        'countries',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'ports',
        'trade-codes',
        'komoditas',
        'source-statuses',
        'species',
    ];

    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Seed the master data permissions.
     */
    public function run(): void
    {
        $permissions = [];

        foreach (self::ENTITIES as $entity) {
            foreach (self::ACTIONS as $action) {
                $permissions[] = "{$entity}.{$action}";
            }
        }

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        $superAdmin = Role::where('name', 'super-admin')->first();

        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }
    }
}
