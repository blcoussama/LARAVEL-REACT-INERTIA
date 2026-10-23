<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('deleting a project also deletes its tasks', function () {
    $owner = User::factory()->create();
    $project = Project::factory()->ownedBy($owner)->create();
    Task::factory()->count(3)->for($project)->create();

    $this->actingAs($owner)->delete("/project/{$project->id}")->assertRedirect('/project');

    expect(Project::find($project->id))->toBeNull()
        ->and(Task::where('project_id', $project->id)->count())->toBe(0);
});

test('deleting a project removes its image and the images of its tasks', function () {
    Storage::fake('public');
    $owner = User::factory()->create();

    $this->actingAs($owner)->post('/project', [
        'name' => 'With image',
        'status' => 'pending',
        'image' => UploadedFile::fake()->image('cover.png', 200, 100),
    ]);
    $project = Project::where('name', 'With image')->firstOrFail();

    $this->post('/task', [
        'name' => 'Task with image',
        'status' => 'pending',
        'priority' => 'low',
        'project_id' => $project->id,
        'assigned_user_id' => $owner->id,
        'image' => UploadedFile::fake()->image('task.png', 50, 50),
    ]);
    $task = Task::where('name', 'Task with image')->firstOrFail();

    Storage::disk('public')->assertExists([$project->image_path, $task->image_path]);

    $this->delete("/project/{$project->id}")->assertRedirect('/project');

    Storage::disk('public')->assertMissing([$project->image_path, $task->image_path]);
});

test('deleting a user keeps their projects and tasks, with an empty author / assignee', function () {
    $admin = User::factory()->admin()->create();
    $leaver = User::factory()->create();
    $project = Project::factory()->ownedBy($leaver)->create();
    $task = Task::factory()->for($project)->assignedTo($leaver)->create(['created_by' => $leaver->id]);

    $this->actingAs($admin)->delete("/user/{$leaver->id}")->assertRedirect('/user');

    expect(User::find($leaver->id))->toBeNull()
        ->and($project->fresh())->created_by->toBeNull()->updated_by->toBeNull()
        ->and($task->fresh())->assigned_user_id->toBeNull()->created_by->toBeNull();

    // The pages still render without an author or an assignee.
    $this->get('/project')->assertOk();
    $this->get("/project/{$project->id}")->assertOk();
    $this->get("/task/{$task->id}")->assertOk();
    $this->get('/dashboard')->assertOk();
});

test('a user who created projects can delete their own account', function () {
    $user = User::factory()->create();
    Project::factory()->ownedBy($user)->has(Task::factory()->count(2))->create();

    $this->actingAs($user)
        ->delete('/profile', ['password' => 'password'])
        ->assertRedirect('/');

    expect(User::find($user->id))->toBeNull()
        ->and(Project::count())->toBe(1);
});
