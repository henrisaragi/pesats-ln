<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\PermohonanStatus;
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
        'assesment',
        'template-surat-pernyataan',
        'video-panduan',
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
     * masa-berlaku toggles the expiry feature (status Expired, blocked re-use in Kuota
     * Perusahaan, expiry job, 3-months-left login alert) per role - a role WITHOUT this
     * permission is entirely exempt, its Verified komoditas never expire no matter how old
     * tanggal_berakhir gets (lihat claude/status-cek-task.md).
     */
    private const KOMODITAS_ENTITAS_PERMISSIONS = [
        'komoditas-entitas.view',
        'komoditas-entitas.view-all',
        'komoditas-entitas.create',
        'komoditas-entitas.verify',
        'komoditas-entitas.masa-berlaku',
    ];

    /**
     * Roles allowed to submit Komoditas Entitas registrations for their own entity.
     */
    private const KOMODITAS_ENTITAS_SUBMITTER_ROLES = ['perusahaan', 'penangkaran'];

    /**
     * Permohonan (SATS-LN Ekspor): self-service submit/manage own drafts (view/create/edit/delete)
     * plus view-all (cross-entity oversight, additive like documents.view-all/customer.view-all)
     * and verify for staff, reused for verify/reject and for marking individual SATS-LN tabs
     * printed/signed. disposisi/disposisi-cetak/print/update-status/siap-diambil/telah-diambil gate the dedicated
     * action buttons on the staff verifikasi detail page (permohonan-export/verifikasi/{id}) - not
     * auto-granted to any role below, assigned manually via the Roles/Permissions UI like verify.
     */
    private const PERMOHONAN_PERMISSIONS = [
        'permohonan-export.view',
        'permohonan-export.view-all',
        'permohonan-export.create',
        'permohonan-export.edit',
        'permohonan-export.delete',
        'permohonan-export.verify',
        'permohonan-export.disposisi',
        'permohonan-export.disposisi-cetak',
        'permohonan-export.print',
        'permohonan-export.update-status',
        'permohonan-export.siap-diambil',
        'permohonan-export.telah-diambil',
    ];

    /**
     * Gerbang akses baca halaman detail verifikasi (permohonan-export/verifikasi/{permohonan},
     * termasuk sats-ln.show) - sengaja dipisah dari permohonan-export.verify (yang sekarang cuma
     * menggerbang aksi tombol Verifikasi) supaya akses lihat detail satu Permohonan bisa diatur
     * independen dari izin melakukan aksi verifikasi. Nama permission sengaja pakai prefix
     * "permohonan." (bukan "permohonan-export.") sesuai permintaan eksplisit. Tidak auto-granted
     * ke role manapun, di-assign manual lewat UI Roles/Permissions seperti permohonan-export.verify.
     */
    private const PERMOHONAN_VERIFY_DETAIL_PERMISSION = 'permohonan.verify-detail';

    /**
     * Gerbang akses ke menu sidebar "Verifikasi Ekspor" dan route index
     * permohonan-export/verifikasi (daftar antrian) - dipisah dari permohonan-export.verify yang
     * sekarang khusus untuk aksi tombol Verifikasi, dan dari permohonan.verify-detail yang khusus
     * untuk halaman detail satu Permohonan. Tidak auto-granted ke role manapun, di-assign manual
     * lewat UI Roles/Permissions seperti dua permission verifikasi lainnya di atas.
     */
    private const PERMOHONAN_VERIFY_LIST_PERMISSION = 'permohonan.verify.view';

    /**
     * Filter status di halaman daftar verifikasi (permohonan-export/verifikasi): satu permission
     * per PermohonanStatus (permohonan.verify.filter.{status}) plus permohonan.verify.filter.all
     * untuk opsi "Semua Status". Status tanpa permission tidak muncul di dropdown dan request-nya
     * ditolak 403. Tidak auto-granted ke role manapun selain super-admin, di-assign manual lewat
     * UI Roles/Permissions.
     *
     * @return array<int, string>
     */
    private function permohonanVerifyFilterPermissions(): array
    {
        return [
            ...array_map(fn (PermohonanStatus $status): string => $status->verifikasiFilterPermission(), PermohonanStatus::cases()),
            PermohonanStatus::VERIFIKASI_FILTER_ALL_PERMISSION,
        ];
    }

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
     * Settings > Status Server: read-only monitoring page (queue workers/Reverb still running or
     * not) - super-admin only, no other role gets it (matches the whatsapp.manage precedent of
     * not being granted to any submitter role by default).
     */
    private const SETTINGS_PERMISSIONS = [
        'settings.whatsapp.manage',
        'settings.server-status.view',
    ];

    /**
     * Dokumen: without this, the "Perusahaan" field is locked to the user's own entity (both in
     * the create/edit form and the list) - same pattern as users.view-all in PermissionSeeder.
     */
    private const DOCUMENTS_PERMISSIONS = [
        'documents.view-all',
    ];

    /**
     * Assesment Isi: entitas mengisi kuisioner assesment.komoditas untuk salah satu komoditasnya
     * (lihat claude/fitur-assesment.md) - pola sama seperti komoditas-entitas. view-all untuk
     * staff yang perlu lihat lintas entitas, verify untuk verifikator mengoreksi jawaban dan
     * menerbitkan sertifikat - tidak di-auto-grant ke role manapun kecuali super-admin, di-assign
     * manual lewat UI Roles/Permissions seperti komoditas-entitas.verify.
     */
    private const ASSESMENT_ISI_PERMISSIONS = [
        'assesment-isi.view',
        'assesment-isi.view-all',
        'assesment-isi.create',
        'assesment-isi.verify',
    ];

    /**
     * Roles allowed to submit Assesment Isi for their own entity's komoditas - sama seperti
     * KOMODITAS_ENTITAS_SUBMITTER_ROLES karena assessment terikat komoditas yang dimiliki entitas.
     */
    private const ASSESMENT_ISI_SUBMITTER_ROLES = ['perusahaan', 'penangkaran'];

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

        array_push($permissions, ...self::KOMODITAS_ENTITAS_PERMISSIONS, ...self::CHAT_PERMISSIONS, ...self::PERMOHONAN_PERMISSIONS, ...[self::PERMOHONAN_VERIFY_DETAIL_PERMISSION, self::PERMOHONAN_VERIFY_LIST_PERMISSION], ...$this->permohonanVerifyFilterPermissions(), ...self::KUOTA_SAYA_PERMISSIONS, ...self::SETTINGS_PERMISSIONS, ...self::DOCUMENTS_PERMISSIONS, ...self::CUSTOMER_PERMISSIONS, ...self::ASSESMENT_ISI_PERMISSIONS);

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

            $role?->givePermissionTo(['komoditas-entitas.view', 'komoditas-entitas.create', 'komoditas-entitas.masa-berlaku']);
        }

        foreach (self::PERMOHONAN_SUBMITTER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo(['permohonan-export.view', 'permohonan-export.create', 'permohonan-export.edit', 'permohonan-export.delete']);
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

        foreach (self::ASSESMENT_ISI_SUBMITTER_ROLES as $roleName) {
            $role = Role::where('name', $roleName)->first();

            $role?->givePermissionTo(['assesment-isi.view', 'assesment-isi.create']);
        }
    }
}
