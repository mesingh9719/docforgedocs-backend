<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SystemRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Create Permissions
        $permissions = [
            // Documents
            ['name' => 'document.view', 'label' => 'View Documents', 'group' => 'Documents'],
            ['name' => 'document.create', 'label' => 'Create Documents', 'group' => 'Documents'],
            ['name' => 'document.edit', 'label' => 'Edit Documents', 'group' => 'Documents'],
            ['name' => 'document.delete', 'label' => 'Delete Documents', 'group' => 'Documents'],
            ['name' => 'document.sign', 'label' => 'Sign Documents', 'group' => 'Documents'],

            // Team
            ['name' => 'team.view', 'label' => 'View Team', 'group' => 'Team'],
            ['name' => 'team.invite', 'label' => 'Invite Members', 'group' => 'Team'],
            ['name' => 'team.update', 'label' => 'Update Members', 'group' => 'Team'],
            ['name' => 'team.delete', 'label' => 'Remove Members', 'group' => 'Team'],
            ['name' => 'team.roles.manage', 'label' => 'Manage Roles', 'group' => 'Team'],

            // Templates
            ['name' => 'template.view', 'label' => 'View Templates', 'group' => 'Templates'],
            ['name' => 'template.create', 'label' => 'Create Templates', 'group' => 'Templates'],
            ['name' => 'template.edit', 'label' => 'Edit Templates', 'group' => 'Templates'],
            ['name' => 'template.delete', 'label' => 'Delete Templates', 'group' => 'Templates'],

            // Settings
            ['name' => 'settings.view', 'label' => 'View Settings', 'group' => 'Settings'],
            ['name' => 'settings.update', 'label' => 'Update Settings', 'group' => 'Settings'],
        ];

        foreach ($permissions as $perm) {
            \App\Models\Permission::firstOrCreate(
                ['name' => $perm['name']],
                $perm
            );
        }

        // 2. Create Roles and Assign Permissions
        $roles = [
            [
                'name' => 'admin',
                'label' => 'Admin',
                'description' => 'Full access to all business features.',
                'is_system' => true,
                'permissions' => ['*'] // Wildcard for all
            ],
            [
                'name' => 'editor',
                'label' => 'Editor',
                'description' => 'Can create and edit documents and templates.',
                'is_system' => true,
                'permissions' => [
                    'document.view',
                    'document.create',
                    'document.edit',
                    'document.delete',
                    'document.sign',
                    'template.view',
                    'template.create',
                    'template.edit',
                    'template.delete',
                    'team.view',
                    'settings.view'
                ]
            ],
            [
                'name' => 'member',
                'label' => 'Member',
                'description' => 'Can view and edit documents, but cannot manage team.',
                'is_system' => true,
                'permissions' => [
                    'document.view',
                    'document.edit',
                    'document.sign',
                    'template.view',
                    'team.view'
                ]
            ],
            [
                'name' => 'viewer',
                'label' => 'Viewer',
                'description' => 'Read-only access.',
                'is_system' => true,
                'permissions' => [
                    'document.view',
                    'template.view',
                    'team.view'
                ]
            ],
        ];

        foreach ($roles as $roleData) {
            $permissionsToSync = $roleData['permissions'];
            unset($roleData['permissions']);

            $role = \App\Models\Role::firstOrCreate(
                ['name' => $roleData['name'], 'is_system' => true],
                $roleData
            );

            // Sync permissions
            if (in_array('*', $permissionsToSync)) {
                // Assign all permissions for admin
                $allPermissionIds = \App\Models\Permission::pluck('id');
                $role->permissions()->sync($allPermissionIds);
            } else {
                $permissionIds = \App\Models\Permission::whereIn('name', $permissionsToSync)->pluck('id');
                $role->permissions()->sync($permissionIds);
            }
        }
    }
}
