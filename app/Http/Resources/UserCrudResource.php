<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Representation used by the administration screens (Users module).
 */
class UserCrudResource extends JsonResource
{
    public static $wrap = false;

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'email_verified' => $this->email_verified_at !== null,
            'created_at' => $this->created_at?->format('Y-m-d H:i'),
            'projects_count' => $this->whenCounted('projects'),
            'assigned_tasks_count' => $this->whenCounted('assignedTasks'),
            'can' => [
                'update' => $request->user()?->can('update', $this->resource) ?? false,
                'delete' => $request->user()?->can('delete', $this->resource) ?? false,
                'changeRole' => $request->user()?->can('changeRole', $this->resource) ?? false,
            ],
        ];
    }
}
