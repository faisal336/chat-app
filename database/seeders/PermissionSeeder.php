<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            // Users
            ['users.view', 'View users', 'users'],
            ['users.create', 'Create users', 'users'],
            ['users.update', 'Edit users', 'users'],
            ['users.disable', 'Disable / enable users', 'users'],
            ['users.archive', 'Archive users', 'users'],
            ['users.delete', 'Delete users', 'users'],
            ['users.reset_pin', 'Reset user PIN', 'users'],
            ['users.manage_roles', 'Assign roles', 'users'],

            // Messages / chat
            ['messages.view_any', 'View any conversation', 'chat'],
            ['messages.view_deleted', 'View deleted messages', 'chat'],
            ['messages.restore', 'Restore deleted messages', 'chat'],
            ['attachments.view_any', 'View any attachment', 'chat'],

            // Audit
            ['audit.view', 'View audit logs', 'audit'],
            ['audit.export', 'Export audit logs', 'audit'],

            // System
            ['system.settings', 'Manage system settings', 'system'],
        ];

        $created = [];
        foreach ($permissions as [$name, $label, $group]) {
            $created[$name] = Permission::updateOrCreate(
                ['name' => $name],
                ['label' => $label, 'group' => $group]
            );
        }

        $superAdmin = Role::where('name', Role::SUPER_ADMIN)->first();
        $admin = Role::where('name', Role::ADMIN)->first();
        $user = Role::where('name', Role::USER)->first();

        $superAdmin?->permissions()->sync(collect($created)->pluck('id')->all());

        $admin?->permissions()->sync(collect($created)
            ->except(['users.manage_roles', 'system.settings'])
            ->pluck('id')->all());

        $user?->permissions()->sync([]);
    }
}
