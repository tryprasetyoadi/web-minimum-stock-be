<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jenis;

class JenisSeeder extends Seeder
{
    public function run(): void
    {
        $names = ['Premium', 'STB', 'Basic'];

        foreach ($names as $name) {
            Jenis::firstOrCreate(['name' => $name]);
        }
    }
}