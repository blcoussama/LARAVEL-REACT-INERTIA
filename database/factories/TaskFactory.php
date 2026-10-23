<?php

namespace Database\Factories;

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
     * By default the task is created by the owner of its project.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => rtrim(fake()->sentence(4), '.'),
            'description' => fake()->paragraph(),
            'due_date' => fake()->dateTimeBetween('now', '+6 months'),
            'status' => fake()->randomElement(Task::STATUSES),
            'priority' => fake()->randomElement(Task::PRIORITIES),
            'image_path' => null,
            'project_id' => Project::factory(),
            'assigned_user_id' => User::factory(),
            'created_by' => fn (array $attributes) => Project::find($attributes['project_id'])?->created_by,
            'updated_by' => fn (array $attributes) => $attributes['created_by'],
        ];
    }

    public function assignedTo(User $user): static
    {
        return $this->state(fn (array $attributes) => [
            'assigned_user_id' => $user->id,
        ]);
    }
}
