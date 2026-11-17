<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * Hanya mendaftarkan permission Pembagian Kuota Ekspor. Role dibuat dan diberi permission
 * secara manual oleh admin lewat menu Roles/Permissions - lihat docs/PRD-pembagian-kuota-tangkap.md §6.
 */
class KuotaTangkapPermissionSeeder extends Seeder
{
    public const PERMISSIONS = [
        'kuota.view',
        'kuota.create',
        'kuota.update',
        'kuota.delete',
        'kuota.calculate',
        'kuota.allocate',
        'kuota.submit',
        'kuota.approve',
        'kuota.realisasi',
        'kuota.export',
        'kuota.view-own',
        'kuota.settings',
    ];

    public function run(): void
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
