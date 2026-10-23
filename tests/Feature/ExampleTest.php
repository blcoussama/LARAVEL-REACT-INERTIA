<?php

use App\Models\User;

it('redirects the home page to the dashboard', function () {
    $this->get('/')->assertRedirect('/dashboard');
});

it('asks guests to log in before reaching the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

it('asks unverified users to verify their e-mail address', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')->assertRedirect('/verify-email');
});
