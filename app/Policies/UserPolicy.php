<?php

namespace App\Policies;

use App\Models\User;

/**
 * User management (the "Users" module) is reserved to administrators.
 * Members manage their own account from the Profile page only.
 */
class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function view(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, User $model): bool
    {
        return $user->isAdmin();
    }

    /**
     * An administrator cannot delete their own account from the Users module
     * (the Profile page, which asks for the password, is the place for that).
     */
    public function delete(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }

    /**
     * Changing one's own role from the Users module is refused, so that an
     * administrator cannot lock themselves out by mistake.
     */
    public function changeRole(User $user, User $model): bool
    {
        return $user->isAdmin() && ! $user->is($model);
    }
}
