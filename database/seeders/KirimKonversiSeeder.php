<?php

namespace Database\Seeders;

use App\Models\KirimKonversi;
use Illuminate\Database\Seeder;

class KirimKonversiSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        KirimKonversi::factory()->count(100)->create();
    }
}
