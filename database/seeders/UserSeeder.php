<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = Role::whereIn('name', ['Admin', 'Operator', 'User'])
            ->get()
            ->keyBy('name');

        $data = [
            [
                'first_name' => 'System',
                'last_name' => 'Admin',
                'address' => 'HQ Office',
                'phone' => '0800000001',
                'email' => 'admin@example.com',
                'password' => 'secret123',
                'role_name' => 'Admin',
            ],
            [
                'first_name' => 'Main',
                'last_name' => 'Operator',
                'address' => 'Warehouse A',
                'phone' => '0800000002',
                'email' => 'operator@example.com',
                'password' => 'secret123',
                'role_name' => 'Operator',
            ],
            [
                'first_name' => 'Regular',
                'last_name' => 'User',
                'address' => 'Branch 1',
                'phone' => '0800000003',
                'email' => 'user@example.com',
                'password' => 'secret123',
                'role_name' => 'User',
            ],
        ];

        foreach ($data as $item) {
            $role = $roles[$item['role_name']] ?? null;
            if (! $role) {
                // Skip if role missing
                continue;
            }

            User::updateOrCreate(
                ['email' => $item['email']],
                [
                    'name' => $item['first_name'].' '.$item['last_name'],
                    'first_name' => $item['first_name'],
                    'last_name' => $item['last_name'],
                    'address' => $item['address'],
                    'phone' => $item['phone'],
                    'role_id' => $role->role_id,
                    'password' => Hash::make($item['password']),
                    'is_deleted' => false,
                ]
            );
        }
    }
}
