<?php

namespace App\Http\Controllers;

use App\Filters\TaskFilter;
use App\Http\Requests\CreateTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Requests\UpdateTaskStatusRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Models\Task;
use App\Services\Task\TaskService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TaskController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly TaskService $taskService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $filters = $request->only([
                'status', 'assigned_to', 'created_by', 'due_date_from',
                'due_date_to', 'title', 'description', 'per_page'
            ]);

            $tasks = $this->taskService->getAllTasks($request->user(), $filters);

            return $this->successResponse($tasks, 'Tasks retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Task index controller error', [
                'user_id' => $request->user()?->id,
                'filters' => $request->all(),
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Failed to retrieve tasks. Please try again.', 500);
        }
    }

    public function store(CreateTaskRequest $request): JsonResponse
    {
        try {
            $task = $this->taskService->createTask($request->user(), $request->validated());
            return $this->createdResponse($task, 'Task created successfully');

        } catch (\Exception $e) {
            Log::error('Task store controller error', [
                'user_id' => $request->user()?->id,
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function show(Request $request, Task $task): JsonResponse
    {
        try {
            $this->authorize('view', $task);
            $taskDetails = $this->taskService->getTaskById($task->id);
            return $this->successResponse($taskDetails, 'Task retrieved successfully');

        } catch (\Exception $e) {
            Log::error('Task show controller error', [
                'user_id' => $request->user()?->id,
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Task not found or access denied.', 404);
        }
    }

    public function update(UpdateTaskRequest $request, Task $task): JsonResponse
    {
        try {
            $updatedTask = $this->taskService->updateTask($task, $request->validated());
            return $this->updatedResponse($updatedTask, 'Task updated successfully');

        } catch (\Exception $e) {
            Log::error('Task update controller error', [
                'user_id' => $request->user()?->id,
                'task_id' => $task->id,
                'data' => $request->validated(),
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): JsonResponse
    {
        try {
            $updatedTask = $this->taskService->updateTaskStatus($task, $request->validated()['status']);
            return $this->updatedResponse($updatedTask, 'Task status updated successfully');

        } catch (\Exception $e) {
            Log::error('Task status update controller error', [
                'user_id' => $request->user()?->id,
                'task_id' => $task->id,
                'status' => $request->validated()['status'] ?? null,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function destroy(Request $request, Task $task): JsonResponse
    {
        try {
            $this->authorize('destroy', $task);
            $this->taskService->deleteTask($task);
            return $this->deletedResponse('Task deleted successfully');

        } catch (\Illuminate\Auth\Access\AuthorizationException $e) {
            return $this->errorResponse('You are not authorized to delete this task.', 403);
        } catch (\Exception $e) {
            Log::error('Task delete controller error', [
                'user_id' => $request->user()?->id,
                'task_id' => $task->id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Failed to delete task. Please try again.', 500);
        }
    }
}
