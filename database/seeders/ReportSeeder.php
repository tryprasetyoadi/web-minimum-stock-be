<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use App\Models\Report;

class ReportSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            'WH TR TREG 1', 'WH TR TREG 2', 'WH TR TREG 3',
            'WH TR TREG 4', 'WH TR TREG 5', 'WH TR TREG 6',
        ];

        $jenisList = ['AP', 'NodeB', 'ONT', 'ONTEnterprhise'];

        foreach ($jenisList as $jenis) {
            for ($i = 0; $i < 30; $i++) {
                $warehouse = $warehouses[array_rand($warehouses)];
                $qty = rand(10, 100);
                $type = match ($jenis) {
                    'AP' => 'ACCESS_POINT',
                    'NodeB' => 'NODE_B',
                    'ONT' => 'ONT_DEVICE',
                    'ONTEnterprhise' => 'ONT_ENTERPRISE',
                    default => 'UNKNOWN',
                };

                $tanggalPengiriman = Carbon::now()->subDays(rand(1, 7));
                // Sebagian data belum tiba (on delivery)
                $tanggalSampai = rand(0, 1) === 1 ? null : Carbon::now()->subDays(rand(0, 3));

                Report::create([
                    'jenis' => $jenis,
                    'type' => $type,
                    'qty' => $qty,
                    'warehouse' => $warehouse,
                    'sender_alamat' => 'WH FH',
                    'sender_pic' => 'FIBERHOME',
                    'receiver_alamat' => 'WH TA',
                    'receiver_warehouse' => $warehouse,
                    'receiver_pic' => 'WH TR TREG1',
                    'tanggal_pengiriman' => $tanggalPengiriman,
                    'tanggal_sampai' => $tanggalSampai,
                    'batch' => 'Batch ' . ($i + 1),
                ]);
            }
        }
    }
}