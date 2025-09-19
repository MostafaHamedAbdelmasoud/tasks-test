<?php

namespace App\Filters;

use Illuminate\Database\Eloquent\Builder;

class TaskFilter extends QueryFilter
{
    public function filters(): array
    {
        return [
            'status',
            'assigned_to',
            'created_by',
            'due_date_from',
            'due_date_to',
            'title',
            'description',
        ];
    }

    public function status(string $status): Builder
    {
        return $this->builder->where('status', $status);
    }

    public function assignedTo(int $userId): Builder
    {
        return $this->builder->where('assigned_to', $userId);
    }

    public function createdBy(int $userId): Builder
    {
        return $this->builder->where('created_by', $userId);
    }

    public function dueDateFrom(string $date): Builder
    {
        return $this->builder->where('due_date', '>=', $date);
    }

    public function dueDateTo(string $date): Builder
    {
        return $this->builder->where('due_date', '<=', $date);
    }

    public function title(string $title): Builder
    {
        return $this->builder->where('title', 'like', "%{$title}%");
    }

    public function description(string $description): Builder
    {
        return $this->builder->where('description', 'like', "%{$description}%");
    }
}