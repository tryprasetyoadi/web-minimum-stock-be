<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\JenisSeeder;
use Database\Seeders\MerkSeeder;
use Database\Seeders\ShipmentSeeder;
use Database\Seeders\ReportSeeder;
use Database\Seeders\ActivityLogSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
            JenisSeeder::class,
            MerkSeeder::class,
            ShipmentSeeder::class,
            ReportSeeder::class,
            ActivityLogSeeder::class,
        ]);
    }
}
