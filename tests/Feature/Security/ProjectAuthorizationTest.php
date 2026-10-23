<?php

use App\Models\Project;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->owner = User::factory()->create();
    $this->other = User::factory()->create();
    $this->admin = User::factory()->admin()->create();
    $this->project = Project::factory()->ownedBy($this->owner)->create(['name' => 'Original name']);
});

function projectPayload(array $overrides = []): array
{
    return array_merge(['name' => 'Hacked', 'status' => 'completed'], $overrides);
}

test('every member can browse projects and create their own', function () {
    $this->actingAs($this->other);

    $this->get('/project')->assertOk();
    $this->get("/project/{$this->project->id}")->assertOk();
    $this->get('/project/create')->assertOk();

    $this->post('/project', ['name' => 'My project', 'status' => 'pending'])
        ->assertRedirect('/project');

    expect(Project::where('name', 'My project')->first())
        ->created_by->toBe($this->other->id);
});

test('a member cannot edit, update or delete a project they do not own', function () {
    $this->actingAs($this->other);

    $this->get("/project/{$this->project->id}/edit")->assertForbidden();
    $this->put("/project/{$this->project->id}", projectPayload())->assertForbidden();
    $this->delete("/project/{$this->project->id}")->assertForbidden();

    expect($this->project->fresh())->not->toBeNull()
        ->name->toBe('Original name');
});

test('the owner can update and delete their project', function () {
    $this->actingAs($this->owner);

    $this->put("/project/{$this->project->id}", projectPayload(['name' => 'Renamed']))
        ->assertRedirect('/project');
    expect($this->project->fresh()->name)->toBe('Renamed');

    $this->delete("/project/{$this->project->id}")->assertRedirect('/project');
    expect(Project::find($this->project->id))->toBeNull();
});

test('an administrator can update and delete any project', function () {
    $this->actingAs($this->admin);

    $this->put("/project/{$this->project->id}", projectPayload(['name' => 'Moderated']))
        ->assertRedirect('/project');
    expect($this->project->fresh())
        ->name->toBe('Moderated')
        ->updated_by->toBe($this->admin->id)
        ->created_by->toBe($this->owner->id);

    $this->delete("/project/{$this->project->id}")->assertRedirect('/project');
    expect(Project::find($this->project->id))->toBeNull();
});

test('the permissions sent to the page match the policy', function () {
    $this->actingAs($this->other)->get('/project')
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.data.0.can.update', false)
            ->where('projects.data.0.can.delete', false));

    $this->actingAs($this->owner)->get("/project/{$this->project->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('project.can.update', true)
            ->where('project.can.addTask', true));
});

test('the author of a project is shared without their e-mail address', function () {
    $this->actingAs($this->other)->get("/project/{$this->project->id}")
        ->assertInertia(fn (Assert $page) => $page
            ->where('project.createdBy', ['id' => $this->owner->id, 'name' => $this->owner->name]));
});
