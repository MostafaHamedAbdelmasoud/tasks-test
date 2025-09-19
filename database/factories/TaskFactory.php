<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class TaskFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['pending', 'completed', 'canceled']),
            'assigned_to' => User::factory(),
            'created_by' => User::factory()->manager(),
            'due_date' => $this->faker->dateTimeBetween('now', '+30 days'),
        ];
    }

    public function pending(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'pending',
            ];
        });
    }

    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
            ];
        });
    }

    public function canceled(): static
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'canceled',
            ];
        });
    }
}