<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SampleUsersSeeder extends Seeder
{
    public function run(): void
    {
        $addressId = $this->resolveAddressId();

        $roleDefinitions = [
            [
                'role' => 'admin',
                'account_code' => 'ADM-0001',
                'first_name' => 'System',
                'middle_name' => 'Core',
                'last_name' => 'Administrator',
                'contact_number' => 9171001001,
                'email' => 'admin.sample@example.com',
            ],
            [
                'role' => 'provincial admin',
                'account_code' => 'PAD-0001',
                'first_name' => 'Paula',
                'middle_name' => 'Ramos',
                'last_name' => 'Provincial',
                'contact_number' => 9171001002,
                'email' => 'provincial.admin.sample@example.com',
            ],
            [
                'role' => 'chief',
                'account_code' => 'CHF-0001',
                'first_name' => 'Carlos',
                'middle_name' => 'Dela',
                'last_name' => 'Cruz',
                'contact_number' => 9171001003,
                'email' => 'chief.sample@example.com',
            ],
            [
                'role' => 'firefighter',
                'account_code' => 'FIR-0001',
                'first_name' => 'Marco',
                'middle_name' => 'Reyes',
                'last_name' => 'Santos',
                'contact_number' => 9171001004,
                'email' => 'firefighter.sample@example.com',
            ],
            [
                'role' => 'citizen',
                'account_code' => 'CIT-0001',
                'first_name' => 'Ana',
                'middle_name' => 'Lopez',
                'last_name' => 'Garcia',
                'contact_number' => 9171001005,
                'email' => 'citizen.sample@example.com',
            ],
            [
                'role' => 'barangay',
                'account_code' => 'BRG-0001',
                'first_name' => 'Benito',
                'middle_name' => 'Torres',
                'last_name' => 'Barangay',
                'contact_number' => 9171001006,
                'email' => 'barangay.sample@example.com',
            ],
        ];

        foreach ($roleDefinitions as $definition) {
            $roleId = DB::table('roles')->where('role', $definition['role'])->value('role_id');

            if (! $roleId) {
                continue;
            }

            DB::table('users')->updateOrInsert(
                ['account_code' => $definition['account_code']],
                [
                    'first_name' => $definition['first_name'],
                    'middle_name' => $definition['middle_name'],
                    'last_name' => $definition['last_name'],
                    'role_id' => $roleId,
                    'address_id' => $addressId,
                    'contact_number' => $definition['contact_number'],
                    'email' => $definition['email'],
                    'password' => Hash::make('password123'),
                    'email_verified_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
    }

    private function resolveAddressId(): ?int
    {
        $existingAddressId = DB::table('full_addresses')->value('address_id');

        if ($existingAddressId) {
            return (int) $existingAddressId;
        }

        $barangayId = DB::table('barangays')->value('barangay_id');

        if (! $barangayId) {
            return null;
        }

        return (int) DB::table('full_addresses')->insertGetId([
            'street' => 'Sample Seeder Street',
            'barangay_id' => $barangayId,
            'created_at' => now(),
            'updated_at' => now(),
        ], 'address_id');
    }
}
