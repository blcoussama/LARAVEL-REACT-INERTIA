<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTaskRequest extends FormRequest
{
    /**
     * The user must be allowed to update the task and, when the task is moved
     * to another project, to create tasks in that target project.
     */
    public function authorize(): bool
    {
        /** @var Task $task */
        $task = $this->route('task');

        if (! $this->user()->can('update', $task)) {
            return false;
        }

        $targetId = $this->integer('project_id');
        if ($targetId === 0 || $targetId === $task->project_id) {
            return true;
        }

        $target = Project::find($targetId);

        return $target === null || $this->user()->can('create', [Task::class, $target]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'description' => ['nullable', 'string', 'max:5000'],
            'due_date' => ['nullable', 'date'],
            'project_id' => ['required', 'integer', 'exists:projects,id'],
            'assigned_user_id' => ['required', 'integer', 'exists:users,id'],
            'status' => ['required', Rule::in(Task::STATUSES)],
            'priority' => ['required', Rule::in(Task::PRIORITIES)],
        ];
    }
}
