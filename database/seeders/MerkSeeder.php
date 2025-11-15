<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Jenis;
use App\Models\Merk;

class MerkSeeder extends Seeder
{
    public function run(): void
    {
        // Map jenis ke daftar merk sesuai contoh
        $map = [
            'Premium' => ['Fiberhome', 'ZTE'],
            'STB' => ['ZTE'],
            'Basic' => ['Huawei', 'Fiberhome'],
        ];

        foreach ($map as $jenisName => $merks) {
            $jenis = Jenis::firstOrCreate(['name' => $jenisName]);

            foreach ($merks as $merkName) {
                Merk::firstOrCreate([
                    'name' => $merkName,
                    'jenis_id' => $jenis->id,
                ]);
            }
        }
    }
}