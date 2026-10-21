<?php

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Enums\TaskType;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'assignee_id' => User::factory(),
            'creator_id' => User::factory(),
            'type' => TaskType::Build,
            'status' => TaskStatus::Backlog,
            'position' => 0,
            'difficulty' => fake()->numberBetween(1, 5),
            'priority' => TaskPriority::Normal,
            'due_date' => null,
            'title' => fake()->sentence(),
            'description' => fake()->optional()->paragraph(),
        ];
    }
}
