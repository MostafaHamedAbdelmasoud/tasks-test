<?php

namespace Database\Seeders;

use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TaskSeeder extends Seeder
{
    public function run(): void
    {
        // Get existing users created by UserSeeder with eager loading of roles
        $managers = User::role('manager')->with('roles')->get();
        $users = User::role('user')->with('roles')->get();

        // Ensure we have enough users
        if ($managers->isEmpty() || $users->count() < 3) {
            $this->command->warn('Not enough users found. Make sure UserSeeder runs first.');
            return;
        }

        // Cache user IDs for better performance
        $managerIds = $managers->pluck('id')->toArray();
        $userIds = $users->pluck('id')->toArray();
        $primaryManager = $managers->first();

        // Prepare task data for bulk creation
        $specificTasks = [
            [
                'title' => 'Setup Development Environment',
                'description' => 'Setup the development environment for the new project',
                'status' => 'completed',
                'assigned_to' => $userIds[0],
                'created_by' => $primaryManager->id,
                'due_date' => now()->addDays(3),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Database Design',
                'description' => 'Create database schema and ERD for the application',
                'status' => 'completed',
                'assigned_to' => $userIds[1],
                'created_by' => $primaryManager->id,
                'due_date' => now()->addDays(5),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'API Development',
                'description' => 'Develop RESTful API endpoints for the application',
                'status' => 'pending',
                'assigned_to' => $userIds[0],
                'created_by' => $primaryManager->id,
                'due_date' => now()->addDays(10),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Frontend Implementation',
                'description' => 'Implement frontend components and user interface',
                'status' => 'pending',
                'assigned_to' => $userIds[1],
                'created_by' => $primaryManager->id,
                'due_date' => now()->addDays(15),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'Testing and QA',
                'description' => 'Conduct thorough testing and quality assurance',
                'status' => 'pending',
                'assigned_to' => $userIds[2],
                'created_by' => $primaryManager->id,
                'due_date' => now()->addDays(20),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Bulk insert specific tasks
        $insertedTaskIds = [];
        foreach ($specificTasks as $taskData) {
            $task = Task::create($taskData);
            $insertedTaskIds[] = $task->id;
        }

        // Generate additional random tasks data for bulk insert
        $randomTasksData = [];
        for ($i = 0; $i < 10; $i++) {
            $randomTasksData[] = [
                'title' => fake()->sentence(3),
                'description' => fake()->paragraph(),
                'status' => fake()->randomElement(['pending', 'completed', 'canceled']),
                'assigned_to' => $userIds[array_rand($userIds)],
                'created_by' => $managerIds[array_rand($managerIds)],
                'due_date' => fake()->dateTimeBetween('now', '+3 months')->format('Y-m-d'),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        // Bulk insert random tasks
        Task::insert($randomTasksData);

        // Create task dependencies using efficient bulk operations
        $dependencyData = [
            // Task 3 (API Development) depends on tasks 1 and 2
            [
                'task_id' => $insertedTaskIds[2],
                'depends_on_task_id' => $insertedTaskIds[0],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'task_id' => $insertedTaskIds[2],
                'depends_on_task_id' => $insertedTaskIds[1],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Task 4 (Frontend) depends on task 3
            [
                'task_id' => $insertedTaskIds[3],
                'depends_on_task_id' => $insertedTaskIds[2],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            // Task 5 (Testing) depends on task 4
            [
                'task_id' => $insertedTaskIds[4],
                'depends_on_task_id' => $insertedTaskIds[3],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        // Bulk insert dependencies
        \DB::table('task_dependencies')->insert($dependencyData);

        $this->command->info('Created ' . (count($specificTasks) + count($randomTasksData)) . ' tasks with optimized queries');
    }
}
