<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name' => Role::SUPER_ADMIN,
                'label' => 'Super Admin',
                'description' => 'Full system access. Cannot be disabled or deleted by anyone else.',
            ],
            [
                'name' => Role::ADMIN,
                'label' => 'Admin',
                'description' => 'Manage users, view audit logs, restore deleted messages.',
            ],
            [
                'name' => Role::USER,
                'label' => 'User',
                'description' => 'Standard chat user.',
            ],
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate(['name' => $role['name']], $role);
        }
    }
}
