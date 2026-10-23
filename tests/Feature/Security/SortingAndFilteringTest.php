<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Returns the ORDER BY clauses of the queries run while calling $callback.
 */
function orderByClauses(callable $callback): string
{
    DB::flushQueryLog();
    DB::enableQueryLog();
    $callback();
    $sql = collect(DB::getQueryLog())->pluck('query')
        ->filter(fn (string $q) => str_contains($q, 'order by'))
        ->implode("\n");
    DB::disableQueryLog();

    return $sql;
}

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
});

test('users cannot be sorted on the password column', function () {
    $sql = orderByClauses(fn () => $this->actingAs($this->admin)
        ->get('/user?sort_field=password&sort_direction=asc')
        ->assertOk());

    // The column falls back to the default one; the (valid) direction is kept.
    expect($sql)->not->toContain('password')
        ->and($sql)->toMatch('/order by [`"]created_at[`"] asc/');
});

test('an unknown column or direction falls back to the default order instead of a 500', function (string $uri) {
    Project::factory()->ownedBy($this->member)->create();
    Task::factory()->create();

    $this->actingAs($this->admin)->get($uri)->assertOk();
})->with([
    'unknown user column' => '/user?sort_field=doesnotexist',
    'invalid user direction' => '/user?sort_field=id&sort_direction=foo',
    'SQL in the direction' => '/project?sort_field=name&sort_direction=asc,(select 1)',
    'unknown project column' => '/project?sort_field=created_by',
    'unknown task column' => '/task?sort_field=assigned_user_id',
    'my tasks bad direction' => '/task/my-tasks?sort_direction=sideways',
    'array parameters' => '/task?name[]=x&status[]=y&sort_field[]=id',
]);

test('the project page sorts its tasks with the same allow-list', function () {
    $project = Project::factory()->ownedBy($this->member)->create();
    Task::factory()->for($project)->create();

    $sql = orderByClauses(fn () => $this->actingAs($this->member)
        ->get("/project/{$project->id}?sort_field=created_by&sort_direction=asc")
        ->assertOk());

    expect($sql)->not->toMatch('/order by [`"]created_by[`"]/');
});

test('allowed columns are sorted as requested', function () {
    Project::factory()->ownedBy($this->member)->create(['name' => 'Bravo']);
    Project::factory()->ownedBy($this->member)->create(['name' => 'Alpha']);
    Project::factory()->ownedBy($this->member)->create(['name' => 'Charlie']);

    $this->actingAs($this->member)->get('/project?sort_field=name&sort_direction=asc')
        ->assertInertia(fn (Assert $page) => $page
            ->where('projects.data.0.name', 'Alpha')
            ->where('projects.data.2.name', 'Charlie'));
});

test('filters only accept known statuses', function () {
    Project::factory()->ownedBy($this->member)->create(['status' => 'pending']);
    Project::factory()->ownedBy($this->member)->create(['status' => 'completed']);

    $this->actingAs($this->member)->get('/project?status=completed')
        ->assertInertia(fn (Assert $page) => $page->has('projects.data', 1));

    $this->actingAs($this->member)->get('/project?status=unknown')
        ->assertInertia(fn (Assert $page) => $page->has('projects.data', 2));
});
