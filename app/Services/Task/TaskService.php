<?php

namespace App\Services\Task;

use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class TaskService
{
    public function getAllTasks(User $user, array $filters = []): LengthAwarePaginator
    {
        try {
            Log::info('Fetching tasks', ['user_id' => $user->id, 'filters' => $filters]);

            $query = Task::with(['assignedUser', 'creator', 'dependencies']);

            // Apply user-based filtering
            if ($user->can('view own tasks') && !$user->can('view tasks')) {
                $query->where('assigned_to', $user->id);
            }

            // Apply Laravel filters using the filter system
            if (!empty($filters)) {
                $request = request();
                $request->merge($filters);
                $taskFilter = new \App\Filters\TaskFilter($request);
                $query = $query->filter($taskFilter);
            }

            // Manager-only filters
            if ($user->can('view tasks')) {
                if (!empty($filters['assigned_to'])) {
                    $query->byAssignedUser((int) $filters['assigned_to']);
                }
                if (!empty($filters['created_by'])) {
                    $query->byCreatedBy((int) $filters['created_by']);
                }
            }

            $tasks = $query->paginate($filters['per_page'] ?? 15);

            Log::info('Tasks fetched successfully', [
                'user_id' => $user->id,
                'total' => $tasks->total()
            ]);

            return $tasks;
        } catch (\Exception $e) {
            Log::error('Error fetching tasks', [
                'user_id' => $user->id,
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to fetch tasks. Please try again.');
        }
    }

    public function createTask(User $user, array $data): Task
    {
        try {
            Log::info('Creating task', ['user_id' => $user->id, 'data' => $data]);

            return DB::transaction(function () use ($user, $data): Task {
                $data['created_by'] = $user->id;

                $task = Task::create($data);

                if (!empty($data['dependencies'])) {
                    $this->attachDependencies($task, $data['dependencies']);
                }

                $task->load(['assignedUser', 'creator', 'dependencies']);

                Log::info('Task created successfully', [
                    'task_id' => $task->id,
                    'created_by' => $user->id
                ]);

                return $task;
            });
        } catch (\Exception $e) {
            Log::error('Error creating task', [
                'user_id' => $user->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to create task. Please try again.');
        }
    }

    public function getTaskById(int $taskId): Task
    {
        try {
            Log::info('Fetching task by ID', ['task_id' => $taskId]);

            $task = Task::with(['assignedUser', 'creator', 'dependencies', 'dependentTasks'])
                ->findOrFail($taskId);

            Log::info('Task fetched successfully', ['task_id' => $taskId]);

            return $task;
        } catch (\Exception $e) {
            Log::error('Error fetching task', [
                'task_id' => $taskId,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Task not found or access denied.');
        }
    }

    public function updateTask(Task $task, array $data): Task
    {
        try {
            Log::info('Updating task', ['task_id' => $task->id, 'data' => $data]);

            return DB::transaction(function () use ($task, $data): Task {
                if (isset($data['status']) && $data['status'] === 'completed') {
                    if (!$this->canTaskBeCompleted($task)) {
                        throw new \Exception('Cannot complete task. Some dependencies are not completed yet.');
                    }
                }

                $task->update($data);

                if (isset($data['dependencies'])) {
                    $this->syncDependencies($task, $data['dependencies']);
                }

                $task->load(['assignedUser', 'creator', 'dependencies']);

                Log::info('Task updated successfully', ['task_id' => $task->id]);

                return $task;
            });
        } catch (\Exception $e) {
            Log::error('Error updating task', [
                'task_id' => $task->id,
                'data' => $data,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function updateTaskStatus(Task $task, string $status): Task
    {
        try {
            Log::info('Updating task status', ['task_id' => $task->id, 'status' => $status]);

            return DB::transaction(function () use ($task, $status): Task {
                if ($status === 'completed' && !$this->canTaskBeCompleted($task)) {
                    throw new \Exception('Cannot complete task. Some dependencies are not completed yet.');
                }

                $task->update(['status' => $status]);
                $task->load(['assignedUser', 'creator', 'dependencies']);

                Log::info('Task status updated successfully', [
                    'task_id' => $task->id,
                    'status' => $status
                ]);

                return $task;
            });
        } catch (\Exception $e) {
            Log::error('Error updating task status', [
                'task_id' => $task->id,
                'status' => $status,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    public function deleteTask(Task $task): void
    {
        try {
            Log::info('Deleting task', ['task_id' => $task->id]);

            DB::transaction(function () use ($task): void {
                $task->dependencies()->detach();
                $task->dependentTasks()->detach();
                $task->delete();
            });

            Log::info('Task deleted successfully', ['task_id' => $task->id]);
        } catch (\Exception $e) {
            Log::error('Error deleting task', [
                'task_id' => $task->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Failed to delete task. Please try again.');
        }
    }


    private function attachDependencies(Task $task, array $dependencyIds): void
    {
        try {
            // Validate dependencies exist
            $validDependencies = Task::whereIn('id', $dependencyIds)->pluck('id')->toArray();

            if (count($validDependencies) !== count($dependencyIds)) {
                throw new \Exception('Some dependency tasks do not exist.');
            }

            $task->dependencies()->attach($validDependencies);

            Log::info('Dependencies attached', [
                'task_id' => $task->id,
                'dependencies' => $validDependencies
            ]);
        } catch (\Exception $e) {
            Log::error('Error attaching dependencies', [
                'task_id' => $task->id,
                'dependencies' => $dependencyIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function syncDependencies(Task $task, array $dependencyIds): void
    {
        try {
            // Validate dependencies exist
            $validDependencies = Task::whereIn('id', $dependencyIds)->pluck('id')->toArray();

            if (count($validDependencies) !== count($dependencyIds)) {
                throw new \Exception('Some dependency tasks do not exist.');
            }

            $task->dependencies()->sync($validDependencies);

            Log::info('Dependencies synced', [
                'task_id' => $task->id,
                'dependencies' => $validDependencies
            ]);
        } catch (\Exception $e) {
            Log::error('Error syncing dependencies', [
                'task_id' => $task->id,
                'dependencies' => $dependencyIds,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function canTaskBeCompleted(Task $task): bool
    {
        try {
            $incompleteCount = $task->dependencies()
                ->where('status', '!=', 'completed')
                ->count();

            return $incompleteCount === 0;
        } catch (\Exception $e) {
            Log::error('Error checking task completion status', [
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}