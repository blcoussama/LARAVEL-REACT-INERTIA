<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;

/**
 * Projects live in a shared team workspace:
 * - every verified member can browse projects and create new ones;
 * - only the owner (creator) of a project or an administrator can edit it,
 *   delete it or add tasks to it.
 */
class ProjectPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Project $project): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function delete(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    /**
     * Planning work (creating tasks) in a project belongs to its owner.
     */
    public function addTask(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }
}
