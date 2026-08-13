<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seed the application's users.
     */
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'henrisaragi@icloud.com'],
            [
                'name' => 'Henri Saragi',
                'username' => 'henrisaragi',
                'password' => Hash::make('password'),
                'is_active' => true,
            ]
        );

        $user->assignRole('super-admin');
    }
}
