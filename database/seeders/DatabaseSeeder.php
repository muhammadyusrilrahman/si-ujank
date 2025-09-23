<?php

namespace Database\Seeders;

use App\Models\Skpd;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $skpd = Skpd::firstOrCreate(
            ['name' => 'Badan Perencanaan Pembangunan Riset dan Inovasi Daerah Kabupaten Kapuas'],
            ['alias' => 'Bappedalitbang Kapuas']
        );

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('admin'),
                'skpd_id' => $skpd->id,
                'role' => User::ROLE_SUPER_ADMIN,
            ]
        );
    }
}
