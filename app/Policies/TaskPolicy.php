<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;

/**
 * - Everybody can read tasks (shared workspace).
 * - A task can be created only in a project the user owns (or by an admin).
 * - The task creator, the owner of its project and the assignee can update
 *   it (the assignee typically moves the status forward).
 * - Only the task creator, the project owner or an admin can delete it.
 */
class TaskPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        return $user->isAdmin() ? true : null;
    }

    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Task $task): bool
    {
        return true;
    }

    public function create(User $user, Project $project): bool
    {
        return $project->isOwnedBy($user);
    }

    public function update(User $user, Task $task): bool
    {
        return $task->isCreatedBy($user)
            || $task->isAssignedTo($user)
            || $task->project->isOwnedBy($user);
    }

    public function delete(User $user, Task $task): bool
    {
        return $task->isCreatedBy($user)
            || $task->project->isOwnedBy($user);
    }
}
