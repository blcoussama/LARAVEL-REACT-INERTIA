<?php

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->admin = User::factory()->admin()->create();
    $this->member = User::factory()->create();
    $this->victim = User::factory()->create(['email' => 'victim@example.com']);
});

test('a member cannot open any page of the users module', function () {
    $this->actingAs($this->member);

    $this->get('/user')->assertForbidden();
    $this->get('/user/create')->assertForbidden();
    $this->get("/user/{$this->victim->id}")->assertForbidden();
    $this->get("/user/{$this->victim->id}/edit")->assertForbidden();
});

test('a member cannot take over another account (e-mail and password change)', function () {
    $this->actingAs($this->member)
        ->put("/user/{$this->victim->id}", [
            'name' => 'pwned',
            'email' => 'attacker@example.com',
            'role' => 'admin',
            'password' => 'NewPass!word1',
            'password_confirmation' => 'NewPass!word1',
        ])
        ->assertForbidden();

    $this->victim->refresh();
    expect($this->victim->email)->toBe('victim@example.com')
        ->and($this->victim->name)->not->toBe('pwned')
        ->and($this->victim->role)->toBe(User::ROLE_MEMBER)
        ->and(Hash::check('NewPass!word1', $this->victim->password))->toBeFalse();
});

test('a member cannot delete another user', function () {
    $this->actingAs($this->member)
        ->delete("/user/{$this->victim->id}")
        ->assertForbidden();

    expect(User::find($this->victim->id))->not->toBeNull();
});

test('a member cannot create accounts', function () {
    $this->actingAs($this->member)
        ->post('/user', [
            'name' => 'Intruder',
            'email' => 'intruder@example.com',
            'role' => 'admin',
            'password' => 'Abcdefg!1',
            'password_confirmation' => 'Abcdefg!1',
        ])
        ->assertForbidden();

    expect(User::where('email', 'intruder@example.com')->exists())->toBeFalse();
});

test('the users link is only shared with administrators', function () {
    $this->actingAs($this->member)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.is_admin', false));

    $this->actingAs($this->admin)->get('/dashboard')
        ->assertInertia(fn (Assert $page) => $page->where('auth.user.is_admin', true));
});

test('an administrator can list, create, show and update users', function () {
    $this->actingAs($this->admin);

    $this->get('/user')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('User/Index')->has('users.data', 3));

    $this->post('/user', [
        'name' => 'New Member',
        'email' => 'new.member@example.com',
        'role' => 'member',
        'password' => 'Abcdefg!1',
        'password_confirmation' => 'Abcdefg!1',
    ])->assertRedirect('/user');

    $created = User::where('email', 'new.member@example.com')->firstOrFail();
    expect($created->role)->toBe(User::ROLE_MEMBER)
        ->and($created->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Abcdefg!1', $created->password))->toBeTrue();

    $project = Project::factory()->ownedBy($this->admin)->create();
    Task::factory()->count(2)->for($project)->assignedTo($created)->create();

    $this->get("/user/{$created->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('User/Show')
            ->where('user.email', 'new.member@example.com')
            ->where('user.assigned_tasks_count', 2)
            ->has('tasks.data', 2));

    $this->put("/user/{$created->id}", [
        'name' => 'Promoted Member',
        'email' => 'new.member@example.com',
        'role' => 'admin',
    ])->assertRedirect('/user');

    expect($created->fresh())
        ->name->toBe('Promoted Member')
        ->role->toBe(User::ROLE_ADMIN);
});

test('an administrator cannot change their own role nor delete themselves from the users module', function () {
    $this->actingAs($this->admin);

    $this->put("/user/{$this->admin->id}", [
        'name' => $this->admin->name,
        'email' => $this->admin->email,
        'role' => 'member',
    ])->assertRedirect('/user');

    expect($this->admin->fresh()->role)->toBe(User::ROLE_ADMIN);

    $this->delete("/user/{$this->admin->id}")->assertForbidden();
    expect(User::find($this->admin->id))->not->toBeNull();
});

test('the role cannot be mass assigned through registration or the profile form', function () {
    $this->post('/register', [
        'name' => 'Sneaky',
        'email' => 'sneaky@example.com',
        'role' => 'admin',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $registered = User::where('email', 'sneaky@example.com')->firstOrFail();
    expect($registered->role)->toBe(User::ROLE_MEMBER);

    $this->actingAs($this->member)->patch('/profile', [
        'name' => 'Member',
        'email' => $this->member->email,
        'role' => 'admin',
    ]);

    expect($this->member->fresh()->role)->toBe(User::ROLE_MEMBER);
});

test('the app:make-admin command promotes an existing account', function () {
    $this->artisan('app:make-admin', ['email' => $this->member->email])->assertExitCode(0);
    expect($this->member->fresh()->isAdmin())->toBeTrue();

    $this->artisan('app:make-admin', ['email' => 'nobody@example.com'])->assertExitCode(1);
});
