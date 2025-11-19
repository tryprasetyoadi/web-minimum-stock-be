<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\ActivityLog;
use App\Models\User;

class ActivityLogSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id')->all();
        if (empty($users)) {
            return; // Pastikan UserSeeder dijalankan terlebih dahulu
        }

        $activities = [
            'Login aplikasi',
            'Menambahkan shipment',
            'Mengupdate report',
            'Menghapus report',
            'Export data',
            'Upload data',
        ];

        for ($i = 0; $i < 50; $i++) {
            ActivityLog::create([
                'user_id' => $users[array_rand($users)],
                'activity' => $activities[array_rand($activities)],
                'timestamp' => Carbon::now()->subMinutes(rand(1, 300)),
            ]);
        }
    }
}