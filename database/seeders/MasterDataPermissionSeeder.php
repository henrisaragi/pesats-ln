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
        'kuota-perusahaan',
        'entitas',
        'entity-profiles',
        'documents',
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
     * update/delete for it. view-all widens the index() scope from "own entity only" to
     * every entity (same additive pattern as documents.view-all) - staff roles need both
     * .view and .view-all granted via the Roles/Permissions UI to see cross-entity data.
     */
    private const KOMODITAS_ENTITAS_PERMISSIONS = [
        'komoditas-entitas.view',
        'komoditas-entitas.view-all',
        'komoditas-entitas.create',
        'komoditas-entitas.verify',
    ];

    /**
     * Roles allowed to submit Komoditas Entitas registrations for their own entity.
     */
    private const KOMODITAS_ENTITAS_SUBMITTER_ROLES = ['perusahaan', 'penangkaran'];

    /**
     * Permohonan (SATS-LN Ekspor): self-service submit by the entity (view/create own drafts) plus
     * a single "verify" permission for staff, reused for verify/reject and for marking individual
     * SATS-LN tabs printed/signed (all on the same staff-facing screen, no need to split further).
     */
    private const PERMOHONAN_PERMISSIONS = [
        'permohonan.view',
        'permohonan.create',
        'permohonan.verify',
    ];

    /**
     * Roles allowed to submit Permohonan SATS-LN Ekspor for their own entity.
     */
    private const PERMOHONAN_SUBMITTER_ROLES = ['perusahaan'];

    /**
     * Kuota Saya: read-only self-service view of the kuota_nasional an entity has been
     * allocated (via its own kuota_perusahaan rows) - view only, no create/update/delete.
     */
    private const KUOTA_SAYA_PERMISSIONS = [
        'kuota-saya.view',
    ];

    /**
     * Roles allowed to view their own allocated Kuota Nasional.
     */
    private const KUOTA_SAYA_VIEWER_ROLES = ['perusahaan'];

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
     * Settings > Whatsapp: a single on/off toggle page, no separate view/update split since
     * there's no scenario where a user should see the toggle but not be allowed to change it.
     */
    private const SETTINGS_PERMISSIONS = [
        'settings.whatsapp.manage',
    ];

    /**
     * Dokumen: without this, the "Perusahaan" field is locked to the user's own entity (both in
     * the create/edit form and the list) - same pattern as users.view-all in PermissionSeeder.
     */
    private const DOCUMENTS_PERMISSIONS = [
        'documents.view-all',
    ];

    /**
     * Customer Entitas: data customer per entitas harus ketat (lihat claude/customer-task.md) -
     * "edit" sengaja dipakai untuk create DAN update sekaligus (pola sama seperti
     * komoditas-entitas.create yang juga dipakai untuk store() dan update()), tidak ada
     * customer.create/update terpisah. customer.view-all hanya untuk melihat lintas entitas -
     * ubah/hapus tetap selalu dibatasi ke entitas sendiri di controller, terlepas dari permission.
     */
    private const CUSTOMER_PERMISSIONS = [
        'customer.view',
        'customer.view-all',
        'customer.edit',
        'customer.delete',
    ];

    /**
     * Roles allowed to manage their own entity's customer records (customer.view-all excluded -
     * self-service only ever sees/edits their own entity's data anyway).
     */
    private const CUSTOMER_SELF_SERVICE_ROLES = ['perusahaan'];

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

        array_push($permissions, ...self::KOMODITAS_ENTITAS_PERMISSIONS, ...self::CHAT_PERMISSIONS, ...self::PERMOHONAN_PERMISSIONS, ...self::KUOTA_SAYA_PERMISSIONS, ...self::SETTINGS_PERMISSIONS, ...self::DOCUMENTS_PERMISSIONS, ...self::CUSTOMER_PERMISSIONS);

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

        foreach (self::PERMOHONAN_SUBMITTER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo(['permohonan.view', 'permohonan.create']);
        }

        foreach (self::CHAT_STARTER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo('chat.start');
        }

        foreach (self::KUOTA_SAYA_VIEWER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo('kuota-saya.view');
        }

        foreach (self::CUSTOMER_SELF_SERVICE_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo(['customer.view', 'customer.edit', 'customer.delete']);
        }
    }
}
