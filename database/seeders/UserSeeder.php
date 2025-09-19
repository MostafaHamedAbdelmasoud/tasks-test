<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Pre-fetch roles to avoid multiple database queries
        $managerRole = Role::where('name', 'manager')->first();
        $userRole = Role::where('name', 'user')->first();

        // Pre-hash password once for reuse
        $hashedPassword = Hash::make('password123');
        $timestamp = now();

        // Define user data
        $usersData = [
            // Managers
            [
                'name' => 'Manager One',
                'email' => 'manager1@example.com',
                'password' => $hashedPassword,
                'email_verified_at' => $timestamp,
                'role' => 'manager',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Manager Two',
                'email' => 'manager2@example.com',
                'password' => $hashedPassword,
                'email_verified_at' => $timestamp,
                'role' => 'manager',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            // Regular users
            [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'password' => $hashedPassword,
                'email_verified_at' => $timestamp,
                'role' => 'user',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Jane Smith',
                'email' => 'jane@example.com',
                'password' => $hashedPassword,
                'email_verified_at' => $timestamp,
                'role' => 'user',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
            [
                'name' => 'Bob Wilson',
                'email' => 'bob@example.com',
                'password' => $hashedPassword,
                'email_verified_at' => $timestamp,
                'role' => 'user',
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ],
        ];

        // Create users and assign roles efficiently
        $createdUsers = [];
        foreach ($usersData as $userData) {
            $role = $userData['role'];
            unset($userData['role']); // Remove role from user data

            $user = User::create($userData);
            $createdUsers[] = [
                'user' => $user,
                'role' => $role,
            ];
        }

        // Bulk assign roles to reduce database queries
        $managerUserIds = [];
        $regularUserIds = [];

        foreach ($createdUsers as $userData) {
            if ($userData['role'] === 'manager') {
                $managerUserIds[] = $userData['user']->id;
            } else {
                $regularUserIds[] = $userData['user']->id;
            }
        }

        // Bulk assign roles using direct database insert
        if (!empty($managerUserIds) && $managerRole) {
            $roleAssignments = [];
            foreach ($managerUserIds as $userId) {
                $roleAssignments[] = [
                    'role_id' => $managerRole->id,
                    'model_type' => User::class,
                    'model_id' => $userId,
                ];
            }
            \DB::table('model_has_roles')->insert($roleAssignments);
        }

        if (!empty($regularUserIds) && $userRole) {
            $roleAssignments = [];
            foreach ($regularUserIds as $userId) {
                $roleAssignments[] = [
                    'role_id' => $userRole->id,
                    'model_type' => User::class,
                    'model_id' => $userId,
                ];
            }
            \DB::table('model_has_roles')->insert($roleAssignments);
        }

        $this->command->info('Created ' . count($usersData) . ' users with optimized role assignments');
    }
}
