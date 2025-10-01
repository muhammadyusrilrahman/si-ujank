<?php

namespace Database\Seeders;

use App\Models\Skpd;
use Illuminate\Database\Seeder;

class SkpdSeeder extends Seeder
{
    /**
     * Seed the SKPD master data.
     */
    public function run(): void
    {
        Skpd::updateOrCreate(
            ['name' => 'Badan Perencanaan Pembangunan Riset dan Inovasi Daerah Kabupaten Kapuas'],
            ['alias' => 'Bappedalitbang Kapuas']
        );
    }
}
