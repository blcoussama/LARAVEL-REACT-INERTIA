<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:make-admin {email : E-mail address of an existing account}', function (string $email) {
    $user = User::where('email', $email)->first();

    if (! $user) {
        $this->error("No user found with the e-mail address \"$email\".");

        return 1;
    }

    $user->role = User::ROLE_ADMIN;
    $user->save();

    $this->info("\"$user->name\" is now an administrator.");

    return 0;
})->purpose('Give the administrator role to an existing user');
