<?php

namespace App\Http\Requests;

use App\Models\Project;
use App\Models\Task;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTaskRequest extends FormRequest
{
    /**
     * A task can only be created in a project the user is allowed to plan
     * (its owner, or an administrator). An unknown project is left to the
     * "exists" validation rule (422).
     */
    public function authorize(): bool
    {
        $project = Project::find($this->integer('project_id'));

        return $project === null || $this->user()->can('create', [Task::class, $project]);
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
