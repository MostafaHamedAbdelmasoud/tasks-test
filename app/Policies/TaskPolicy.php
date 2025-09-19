<?php

namespace App\Policies;

use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TaskPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        if ($user->isManager()) {
            return true;
        }

        return $task->assigned_to === $user->id;
    }

    public function createTask(User $user): bool
    {
        return $user->can('create tasks');
    }

    public function updateTask(User $user, Task $task): bool
    {
        return $user->can('update tasks');
    }

    public function updateTaskStatus(User $user, Task $task): bool
    {
        if ($user->can('update tasks')) {
            return true;
        }

        return $user->can('update own task status') && $task->assigned_to === $user->id;
    }

    public function delete(User $user, Task $task): bool
    {
        return $user->can('delete tasks');
    }

    public function destroy(User $user, Task $task): bool
    {
        return $user->can('delete tasks');
    }
}
