<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PenandatanganSeeder extends Seeder
{
    /**
     * Permission penandatangan SATS-LN: user yang memilikinya muncul di daftar Penandatangan pada
     * modal Print/Cetak (verifikasi/show). Hanya auto-granted ke super-admin, role/user lain
     * di-assign manual lewat UI Roles/Permissions.
     */
    public function run(): void
    {
        Permission::firstOrCreate([
            'name' => User::PENANDATANGAN_PERMISSION,
            'guard_name' => 'web',
        ]);

        Role::where('name', 'super-admin')->first()?->givePermissionTo(User::PENANDATANGAN_PERMISSION);
    }
}
