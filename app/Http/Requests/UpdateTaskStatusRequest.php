<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaskStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('updateTaskStatus', $this->route('task'));
    }

    public function rules(): array
    {
        return [
            'status' => 'required|in:pending,completed,canceled',
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Status is required',
            'status.in' => 'Status must be one of: pending, completed, canceled',
        ];
    }
}
