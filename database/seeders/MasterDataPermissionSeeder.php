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
        'purpose-of-transactions',
        'kuota-nasional',
        'entitas',
        'entity-profiles',
    ];

    private const ACTIONS = ['view', 'create', 'update', 'delete'];

    /**
     * Entities that support syncing region data from INSW.
     */
    private const SYNCABLE_ENTITIES = [
        'countries',
        'provinsi',
        'kota',
        'kecamatan',
        'kelurahan',
        'ports',
    ];

    /**
     * Komoditas Entitas only needs view/create (self-service submission by the entity)
     * plus verify (approve/reject) - not the generic CRUD sweep, since no UI uses
     * update/delete for it.
     */
    private const KOMODITAS_ENTITAS_PERMISSIONS = [
        'komoditas-entitas.view',
        'komoditas-entitas.create',
        'komoditas-entitas.verify',
    ];

    /**
     * Roles allowed to submit Komoditas Entitas registrations for their own entity.
     */
    private const KOMODITAS_ENTITAS_SUBMITTER_ROLES = ['perusahaan', 'penangkaran'];

    /**
     * Chat: "start" lets an entity user open a new conversation, "respond" puts a user in the
     * staff pool that sees the unclaimed queue and can reply/claim - not a CRUD sweep, this
     * feature has no update/delete actions.
     */
    private const CHAT_PERMISSIONS = [
        'chat.start',
        'chat.respond',
    ];

    /**
     * Roles allowed to start a new chat conversation.
     */
    private const CHAT_STARTER_ROLES = ['perusahaan', 'penangkaran', 'perorangan'];

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

        foreach (self::SYNCABLE_ENTITIES as $entity) {
            $permissions[] = "{$entity}.sync";
        }

        array_push($permissions, ...self::KOMODITAS_ENTITAS_PERMISSIONS, ...self::CHAT_PERMISSIONS);

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

        foreach (self::KOMODITAS_ENTITAS_SUBMITTER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo(['komoditas-entitas.view', 'komoditas-entitas.create']);
        }

        foreach (self::CHAT_STARTER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo('chat.start');
        }
    }
}
