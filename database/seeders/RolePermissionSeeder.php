<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $timestamp = now();

        // Define permissions data for bulk creation
        $permissionsData = [
            ['name' => 'view tasks', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'view own tasks', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'create tasks', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'update tasks', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'update own task status', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'delete tasks', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];

        // Bulk insert permissions (ignore duplicates)
        foreach ($permissionsData as $permissionData) {
            Permission::firstOrCreate(
                ['name' => $permissionData['name'], 'guard_name' => $permissionData['guard_name']],
                $permissionData
            );
        }

        // Define roles data
        $rolesData = [
            ['name' => 'manager', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
            ['name' => 'user', 'guard_name' => 'web', 'created_at' => $timestamp, 'updated_at' => $timestamp],
        ];

        // Create roles
        $createdRoles = [];
        foreach ($rolesData as $roleData) {
            $createdRoles[$roleData['name']] = Role::firstOrCreate(
                ['name' => $roleData['name'], 'guard_name' => $roleData['guard_name']],
                $roleData
            );
        }

        // Get all permissions for efficient assignment
        $allPermissions = Permission::whereIn('name', array_column($permissionsData, 'name'))->get()->keyBy('name');

        // Define role-permission mappings
        $rolePermissions = [
            'manager' => ['view tasks', 'create tasks', 'update tasks', 'delete tasks'],
            'user' => ['view own tasks', 'update own task status'],
        ];

        // Efficiently assign permissions to roles
        foreach ($rolePermissions as $roleName => $permissionNames) {
            $role = $createdRoles[$roleName];
            $permissions = $allPermissions->only($permissionNames);

            // Use syncPermissions for efficient bulk assignment
            $role->syncPermissions($permissions->values());
        }

        $this->command->info('Created ' . count($permissionsData) . ' permissions and ' . count($rolesData) . ' roles with optimized assignments');
    }
}
