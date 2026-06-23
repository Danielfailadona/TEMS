<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $accounts = [
            [
                'name' => 'Super Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('Admin@123'),
                'role' => Role::SuperAdmin->value,
                'is_active' => true,
            ],
            [
                'name' => 'Transportation Enforcer',
                'email' => 'enforcer@example.com',
                'password' => Hash::make('Enforcer@123'),
                'role' => Role::Enforcer->value,
                'is_active' => true,
            ],
            [
                'name' => 'Clamping Officer',
                'email' => 'clamp@example.com',
                'password' => Hash::make('Clamp@123'),
                'role' => Role::ClampingOfficer->value,
                'is_active' => true,
            ],
            [
                'name' => 'Cashier',
                'email' => 'cashier@example.com',
                'password' => Hash::make('Cashier@123'),
                'role' => Role::Cashier->value,
                'is_active' => true,
            ],
            [
                'name' => 'Vehicle Owner',
                'email' => 'owner@example.com',
                'password' => Hash::make('Owner@123'),
                'role' => Role::VehicleOwner->value,
                'is_active' => true,
            ],
        ];

        foreach ($accounts as $account) {
            User::updateOrCreate(
                ['email' => $account['email']],
                $account
            );
        }
    }
}
