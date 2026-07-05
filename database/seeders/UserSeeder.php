<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name' => 'Administrator',
                'email' => 'admin@gmail.com',
                'phone' => '+37369000001',
                'idnp' => '2000000000001',
                'driver_license' => 'ADM001',
                'password' => '123456789k',
                'role' => 'admin',
            ],
            [
                'name' => 'Andrei Popescu',
                'email' => 'andrei.popescu@example.com',
                'phone' => '+37369000002',
                'idnp' => '2000000000002',
                'driver_license' => 'MD-A10001',
                'password' => 'user123456',
                'role' => 'user',
            ],
            [
                'name' => 'Elena Rusu',
                'email' => 'elena.rusu@example.com',
                'phone' => '+37369000003',
                'idnp' => '2000000000003',
                'driver_license' => 'MD-E20002',
                'password' => 'user123456',
                'role' => 'user',
            ],
            [
                'name' => 'Victor Munteanu',
                'email' => 'victor.munteanu@example.com',
                'phone' => '+37369000004',
                'idnp' => '2000000000004',
                'driver_license' => 'MD-V30003',
                'password' => 'user123456',
                'role' => 'user',
            ],
        ];

        foreach ($users as $data) {
            $password = $data['password'];
            unset($data['password']);

            User::updateOrCreate(
                ['email' => $data['email']],
                [...$data, 'password' => Hash::make($password), 'email_verified_at' => now()]
            );
        }
    }
}
