<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateTask', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'due_date' => 'nullable|date|after_or_equal:today',
            'status' => 'sometimes|required|in:pending,completed,canceled',
            'dependencies' => 'nullable|array',
            'dependencies.*' => 'exists:tasks,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'Task title is required',
            'assigned_to.exists' => 'Selected user does not exist',
            'due_date.after_or_equal' => 'Due date must be today or a future date',
            'status.in' => 'Status must be one of: pending, completed, canceled',
            'dependencies.*.exists' => 'One or more dependency tasks do not exist',
        ];
    }
}
