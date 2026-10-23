<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

test('only the fields needed by the layout are shared about the current user', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page
            ->has('auth.user', fn (Assert $u) => $u
                ->where('id', $user->id)
                ->where('name', $user->name)
                ->where('email', $user->email)
                ->has('email_verified_at')
                ->where('is_admin', false)));
});

test('the dashboard counts all tasks and the tasks assigned to me', function () {
    $me = User::factory()->create();
    $project = Project::factory()->create();
    Task::factory()->for($project)->assignedTo($me)->create(['status' => 'pending']);
    Task::factory()->for($project)->assignedTo($me)->create(['status' => 'in_progress']);
    Task::factory()->for($project)->create(['status' => 'pending']);
    Task::factory()->for($project)->create(['status' => 'completed']);

    $this->actingAs($me)->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('totalPendingTasks', 2)
            ->where('myPendingTasks', 1)
            ->where('totalProgressTasks', 1)
            ->where('myProgressTasks', 1)
            ->where('totalCompletedTasks', 1)
            ->where('myCompletedTasks', 0)
            ->has('activeTasks.data', 2));
});

test('"My Tasks" only lists the tasks assigned to me', function () {
    $me = User::factory()->create();
    Task::factory()->count(2)->assignedTo($me)->create();
    Task::factory()->count(3)->create();

    $this->actingAs($me)->get('/task/my-tasks')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Task/Index')
            ->where('myTasks', true)
            ->has('tasks.data', 2));
});

test('project and task pages render', function () {
    $user = User::factory()->create();
    $task = Task::factory()->create();

    $this->actingAs($user);
    $this->get("/project/{$task->project_id}")
        ->assertInertia(fn (Assert $page) => $page->component('Project/Show')->has('tasks.data', 1));
    $this->get("/task/{$task->id}")
        ->assertInertia(fn (Assert $page) => $page->component('Task/Show')->where('task.id', $task->id));
    $this->get('/task')
        ->assertInertia(fn (Assert $page) => $page->component('Task/Index')->where('myTasks', false));
});

test('the demo seeder creates fictitious accounts and data', function () {
    $this->seed(DatabaseSeeder::class);

    $admin = User::where('email', 'admin@example.com')->firstOrFail();

    expect($admin->isAdmin())->toBeTrue()
        ->and(User::count())->toBe(5)
        ->and(User::where('role', User::ROLE_ADMIN)->count())->toBe(1)
        ->and(User::whereNull('email_verified_at')->count())->toBe(0)
        ->and(User::where('email', 'not like', '%@example.com')->count())->toBe(0)
        ->and(Hash::check(DatabaseSeeder::DEMO_PASSWORD, $admin->password))->toBeTrue()
        ->and(Project::count())->toBe(12)
        ->and(Task::count())->toBeGreaterThan(40);

    // Running it twice does not fail on the unique e-mail constraint.
    $this->seed(DatabaseSeeder::class);
    expect(User::count())->toBe(5);
});
