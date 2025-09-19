<?php

namespace Tests\Feature;

use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TaskTest extends TestCase
{
    use RefreshDatabase;

    private User $manager;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Create roles and permissions
        $this->createRolesAndPermissions();

        $this->manager = User::factory()->manager()->create();
        $this->manager->assignRole('manager');

        $this->user = User::factory()->create();
        $this->user->assignRole('user');
    }

    private function createRolesAndPermissions(): void
    {
        // Create permissions
        $permissions = [
            'view tasks',
            'view own tasks',
            'create tasks',
            'update tasks',
            'update own task status',
            'delete tasks',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        $managerRole = Role::create(['name' => 'manager']);
        $userRole = Role::create(['name' => 'user']);

        // Manager permissions
        $managerRole->givePermissionTo([
            'view tasks',
            'create tasks',
            'update tasks',
            'delete tasks',
        ]);

        // User permissions
        $userRole->givePermissionTo([
            'view own tasks',
            'update own task status',
        ]);
    }

    public function test_manager_can_create_task(): void
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test task description',
            'assigned_to' => $this->user->id,
            'due_date' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->postJson('/api/tasks', $taskData);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'title',
                    'description',
                    'assigned_to',
                    'created_by',
                    'due_date',
                ],
            ]);

        $this->assertDatabaseHas('tasks', [
            'title' => 'Test Task',
            'created_by' => $this->manager->id,
            'assigned_to' => $this->user->id,
        ]);
    }

    public function test_user_cannot_create_task(): void
    {
        $taskData = [
            'title' => 'Test Task',
            'description' => 'Test task description',
        ];

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tasks', $taskData);

        $response->assertStatus(403);
    }

    public function test_manager_can_view_all_tasks(): void
    {
        Task::factory()->count(3)->create([
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'title',
                            'status',
                            'assigned_user',
                            'creator',
                        ],
                    ],
                ],
            ]);
    }

    public function test_user_can_only_view_assigned_tasks(): void
    {
        // Create tasks assigned to this user
        Task::factory()->count(2)->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
        ]);

        // Create tasks assigned to other users
        Task::factory()->count(3)->create([
            'assigned_to' => User::factory()->create()->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tasks');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(2, $data);
    }

    public function test_tasks_can_be_filtered_by_status(): void
    {
        Task::factory()->create([
            'status' => 'pending',
            'created_by' => $this->manager->id,
        ]);
        Task::factory()->create([
            'status' => 'completed',
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->getJson('/api/tasks?status=pending');

        $response->assertStatus(200);
        $data = $response->json('data.data');
        $this->assertCount(1, $data);
        $this->assertEquals('pending', $data[0]['status']);
    }

    public function test_manager_can_update_task(): void
    {
        $task = Task::factory()->create([
            'created_by' => $this->manager->id,
            'title' => 'Original Title',
        ]);

        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated description',
        ];

        $response = $this->actingAs($this->manager, 'sanctum')
            ->putJson("/api/tasks/{$task->id}", $updateData);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_user_can_update_task_status(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_task_with_incomplete_dependencies_cannot_be_completed(): void
    {
        $dependency = Task::factory()->create([
            'status' => 'pending',
            'created_by' => $this->manager->id,
        ]);

        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
            'status' => 'pending',
        ]);

        $task->dependencies()->attach($dependency->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(422);
    }

    public function test_task_with_completed_dependencies_can_be_completed(): void
    {
        $dependency = Task::factory()->create([
            'status' => 'completed',
            'created_by' => $this->manager->id,
        ]);

        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
            'status' => 'pending',
        ]);

        $task->dependencies()->attach($dependency->id);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/tasks/{$task->id}/status", [
                'status' => 'completed',
            ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('tasks', [
            'id' => $task->id,
            'status' => 'completed',
        ]);
    }

    public function test_manager_can_delete_task(): void
    {
        $task = Task::factory()->create([
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->manager, 'sanctum')
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('tasks', [
            'id' => $task->id,
        ]);
    }

    public function test_user_cannot_delete_task(): void
    {
        $task = Task::factory()->create([
            'assigned_to' => $this->user->id,
            'created_by' => $this->manager->id,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->deleteJson("/api/tasks/{$task->id}");

        $response->assertStatus(403);
    }
}
