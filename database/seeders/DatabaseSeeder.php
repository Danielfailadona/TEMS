<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\User;
use App\Models\ViolationType;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            ['name' => 'Super Administrator', 'email' => 'admin@example.com', 'role' => Role::SuperAdmin, 'password' => 'Admin@123'],
            ['name' => 'Transportation Enforcer', 'email' => 'enforcer@example.com', 'role' => Role::Enforcer, 'password' => 'Enforcer@123'],
            ['name' => 'Clamping Officer', 'email' => 'clamp@example.com', 'role' => Role::ClampingOfficer, 'password' => 'Clamp@123'],
            ['name' => 'Cashier', 'email' => 'cashier@example.com', 'role' => Role::Cashier, 'password' => 'Cashier@123'],
            ['name' => 'Vehicle Owner', 'email' => 'owner@example.com', 'role' => Role::VehicleOwner, 'password' => 'Owner@123'],
        ];

        foreach ($users as $data) {
            User::updateOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'role' => $data['role'],
                    'password' => Hash::make($data['password']),
                    'is_active' => true,
                ]
            );
        }

        $violations = [
            ['code' => 'NO-LICENSE', 'name' => 'Driving Without Valid License', 'description' => 'Operating a motor vehicle without a valid driver\'s license', 'penalty_amount' => 3000, 'is_impoundable' => true],
            ['code' => 'EXP-LICENSE-1', 'name' => 'Expired License (1-30 days)', 'description' => 'Driving with an expired license for 1 to 30 days', 'penalty_amount' => 3000, 'is_impoundable' => false],
            ['code' => 'EXP-LICENSE-2', 'name' => 'Expired License (31+ days)', 'description' => 'Driving with an expired license for 31 days or more', 'penalty_amount' => 10000, 'is_impoundable' => true],
            ['code' => 'RECKLESS', 'name' => 'Reckless Driving', 'description' => 'Driving in a manner that endangers persons or property', 'penalty_amount' => 2000, 'is_impoundable' => true],
            ['code' => 'DUI', 'name' => 'Driving Under Influence', 'description' => 'Driving under the influence of alcohol or drugs', 'penalty_amount' => 10000, 'is_impoundable' => true],
            ['code' => 'NO-SEATBELT', 'name' => 'No Seat Belt', 'description' => 'Failure to wear a seat belt while driving', 'penalty_amount' => 1000, 'is_impoundable' => false],
            ['code' => 'NO-HELMET', 'name' => 'No Motorcycle Helmet', 'description' => 'Riding without a standard protective helmet', 'penalty_amount' => 1500, 'is_impoundable' => false],
            ['code' => 'NO-PARK', 'name' => 'Illegal Parking', 'description' => 'Parking in a no-parking zone or obstruction', 'penalty_amount' => 1000, 'is_impoundable' => true],
            ['code' => 'RED-LIGHT', 'name' => 'Red Light Violation', 'description' => 'Running a red traffic light', 'penalty_amount' => 1500, 'is_impoundable' => false],
            ['code' => 'OBSTRUCTION', 'name' => 'Obstruction of Traffic', 'description' => 'Obstructing the free flow of traffic', 'penalty_amount' => 1000, 'is_impoundable' => true],
            ['code' => 'COLORUM', 'name' => 'Colorum Vehicle', 'description' => 'Operating a public utility vehicle without a franchise', 'penalty_amount' => 5000, 'is_impoundable' => true],
            ['code' => 'UNMOD', 'name' => 'Unauthorized Modification', 'description' => 'Unauthorized vehicle modification not conforming to standards', 'penalty_amount' => 2000, 'is_impoundable' => true],
            ['code' => 'SMOKE-EMIT', 'name' => 'Excessive Smoke Emission', 'description' => 'No or wasteful emission of smoke or exhaust', 'penalty_amount' => 1000, 'is_impoundable' => false],
            ['code' => 'OVERLOAD', 'name' => 'Overloading', 'description' => 'Vehicle exceeding authorized passenger or cargo limit', 'penalty_amount' => 1000, 'is_impoundable' => false],
            ['code' => 'NO-REG', 'name' => 'Unregistered Vehicle', 'description' => 'Operating an unregistered motor vehicle', 'penalty_amount' => 2000, 'is_impoundable' => true],
        ];

        foreach ($violations as $violation) {
            ViolationType::updateOrCreate(['code' => $violation['code']], $violation);
        }
    }
}
