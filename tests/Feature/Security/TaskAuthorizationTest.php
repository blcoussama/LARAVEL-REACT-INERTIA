<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->assignee = User::factory()->create();
    $this->stranger = User::factory()->create();
    $this->admin = User::factory()->admin()->create();

    $this->project = Project::factory()->ownedBy($this->owner)->create();
    $this->task = Task::factory()->for($this->project)->assignedTo($this->assignee)->create([
        'name' => 'Original task',
        'status' => 'pending',
    ]);
});

function taskPayload(Task $task, array $overrides = []): array
{
    return array_merge([
        'name' => $task->name,
        'status' => $task->status,
        'priority' => $task->priority,
        'project_id' => $task->project_id,
        'assigned_user_id' => $task->assigned_user_id,
    ], $overrides);
}

test('a member cannot create a task in a project they do not own', function () {
    $this->actingAs($this->stranger)
        ->post('/task', taskPayload($this->task, ['name' => 'Injected task']))
        ->assertForbidden();

    expect(Task::where('name', 'Injected task')->exists())->toBeFalse();
});

test('the project owner and administrators can create tasks in it', function () {
    $this->actingAs($this->owner)
        ->post('/task', taskPayload($this->task, ['name' => 'Planned by owner']))
        ->assertRedirect('/task');

    $this->actingAs($this->admin)
        ->post('/task', taskPayload($this->task, ['name' => 'Planned by admin']))
        ->assertRedirect('/task');

    expect(Task::where('name', 'Planned by owner')->first()->created_by)->toBe($this->owner->id)
        ->and(Task::where('name', 'Planned by admin')->exists())->toBeTrue();
});

test('the task form only offers the projects the user can plan', function () {
    $mine = Project::factory()->ownedBy($this->stranger)->create();

    $this->actingAs($this->stranger)->get('/task/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('projects.data', 1)
            ->where('projects.data.0.id', $mine->id));
});

test('a stranger cannot edit, update or delete the task', function () {
    $this->actingAs($this->stranger);

    $this->get("/task/{$this->task->id}/edit")->assertForbidden();
    $this->put("/task/{$this->task->id}", taskPayload($this->task, ['name' => 'Hacked']))->assertForbidden();
    $this->delete("/task/{$this->task->id}")->assertForbidden();

    expect($this->task->fresh())->not->toBeNull()
        ->name->toBe('Original task');
});

test('the assignee can update the task but not delete it', function () {
    $this->actingAs($this->assignee);

    $this->put("/task/{$this->task->id}", taskPayload($this->task, ['status' => 'in_progress']))
        ->assertRedirect('/task');
    expect($this->task->fresh()->status)->toBe('in_progress');

    $this->delete("/task/{$this->task->id}")->assertForbidden();
    expect(Task::find($this->task->id))->not->toBeNull();
});

test('the assignee cannot move the task into a project they do not own', function () {
    $foreign = Project::factory()->ownedBy($this->stranger)->create();

    $this->actingAs($this->assignee)
        ->put("/task/{$this->task->id}", taskPayload($this->task, ['project_id' => $foreign->id]))
        ->assertForbidden();

    expect($this->task->fresh()->project_id)->toBe($this->project->id);
});

test('the project owner can delete a task created by someone else in the project', function () {
    $byAdmin = Task::factory()->for($this->project)->create(['created_by' => $this->admin->id]);

    $this->actingAs($this->owner)->delete("/task/{$byAdmin->id}")->assertRedirect('/task');

    expect(Task::find($byAdmin->id))->toBeNull();
});

test('an administrator can update and delete any task', function () {
    $this->actingAs($this->admin);

    $this->put("/task/{$this->task->id}", taskPayload($this->task, ['name' => 'Fixed by admin']))
        ->assertRedirect('/task');
    expect($this->task->fresh()->name)->toBe('Fixed by admin');

    $this->delete("/task/{$this->task->id}")->assertRedirect('/task');
    expect(Task::find($this->task->id))->toBeNull();
});

test('the permissions sent to the task list match the policy', function () {
    $this->actingAs($this->assignee)->get('/task')
        ->assertInertia(fn (Assert $page) => $page
            ->where('tasks.data.0.can.update', true)
            ->where('tasks.data.0.can.delete', false));

    $this->actingAs($this->stranger)->get('/task')
        ->assertInertia(fn (Assert $page) => $page
            ->where('tasks.data.0.can.update', false)
            ->where('tasks.data.0.can.delete', false));
});
