<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use App\Models\Shipment;
use App\Models\User;
use App\Models\Role;

class ShipmentSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::where('name', 'Admin')->first();
        $operatorRole = Role::where('name', 'Operator')->first();

        // Helper untuk membuat/ambil user berdasarkan nama dan peran
        $getUser = function (string $name, string $roleName) use ($adminRole, $operatorRole) {
            $role = $roleName === 'Admin' ? $adminRole : $operatorRole;
            if (! $role) {
                // Jika role belum ada, buat default ke Operator
                $role = Role::firstOrCreate(['name' => $roleName]);
            }

            $email = strtolower(str_replace(' ', '', $name)) . '@example.com';

            return User::firstOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'first_name' => $name,
                    'last_name' => $roleName,
                    'address' => 'Auto-generated',
                    'phone' => '0800009999',
                    'role_id' => $role->role_id,
                    'password' => Hash::make('secret123'),
                    'is_deleted' => false,
                ]
            );
        };

        $rows = [
            [
                'type' => 'ONT_FIBERHOME_HG6245N',
                'jenis' => 'Premium',
                'merk' => 'Fiberhome',
                'qty' => 10,
                'delivery_by' => 'Udara',
                'alamat_tujuan' => 'TA WITEL CCAN LAMPUNG (BANDAR LAMPUNG) WH',
                'pic' => ['name' => 'Admin', 'role' => 'Admin'],
                'approved_by' => ['name' => 'Admin', 'role' => 'Admin'],
                'time_added' => '2023-11-27 10:43:53',
                'status' => 'On Going',
            ],
            [
                'type' => 'STB_ZTE_B860H_V5.0',
                'jenis' => 'STB',
                'merk' => 'ZTE',
                'qty' => 5,
                'delivery_by' => 'Darat',
                'alamat_tujuan' => 'TA WITEL JAKARTA SELATAN',
                'pic' => ['name' => 'Rina', 'role' => 'Operator'],
                'approved_by' => ['name' => 'Andi', 'role' => 'Admin'],
                'time_added' => '2023-11-28 09:12:15',
                'status' => 'Submitted',
            ],
            [
                'type' => 'ONT_HUAWEI_HG8245H',
                'jenis' => 'Basic',
                'merk' => 'Huawei',
                'qty' => 15,
                'delivery_by' => 'Udara',
                'alamat_tujuan' => 'TA WITEL BANDUNG',
                'pic' => ['name' => 'Admin', 'role' => 'Admin'],
                'approved_by' => ['name' => 'Admin', 'role' => 'Admin'],
                'time_added' => '2023-11-29 11:20:33',
                'status' => 'On Going',
            ],
            [
                'type' => 'ONT_ZTE_F660',
                'jenis' => 'Premium',
                'merk' => 'ZTE',
                'qty' => 8,
                'delivery_by' => 'Darat',
                'alamat_tujuan' => 'TA WITEL SURABAYA',
                'pic' => ['name' => 'Budi', 'role' => 'Operator'],
                'approved_by' => ['name' => 'Siti', 'role' => 'Admin'],
                'time_added' => '2023-11-30 14:45:27',
                'status' => 'Approved',
            ],
            [
                'type' => 'ONT_FIBERHOME_AN5506-04-FG',
                'jenis' => 'Basic',
                'merk' => 'Fiberhome',
                'qty' => 12,
                'delivery_by' => 'Udara',
                'alamat_tujuan' => 'TA WITEL MEDAN',
                'pic' => ['name' => 'Agus', 'role' => 'Operator'],
                'approved_by' => ['name' => 'Tono', 'role' => 'Admin'],
                'time_added' => '2023-12-01 08:32:40',
                'status' => 'Approved',
            ],
        ];

        foreach ($rows as $row) {
            $pic = $getUser($row['pic']['name'], $row['pic']['role']);
            $approver = $getUser($row['approved_by']['name'], $row['approved_by']['role']);

            Shipment::updateOrCreate(
                [
                    'type' => $row['type'],
                    'alamat_tujuan' => $row['alamat_tujuan'],
                ],
                [
                    'jenis' => $row['jenis'],
                    'merk' => $row['merk'],
                    'qty' => $row['qty'],
                    'delivery_by' => $row['delivery_by'],
                    'pic_user_id' => $pic->id,
                    'approved_by_user_id' => $approver->id,
                    'status' => $row['status'],
                    'created_at' => Carbon::parse($row['time_added']),
                    'updated_at' => Carbon::parse($row['time_added']),
                ]
            );
        }
    }
}