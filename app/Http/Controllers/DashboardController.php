<?php

namespace App\Http\Controllers;

use App\Http\Resources\TaskResource;
use App\Models\Task;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        $total = Task::query()
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $mine = Task::query()
            ->where('assigned_user_id', $userId)
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $activeTasks = Task::query()
            ->with(['project', 'assignedUser', 'createdBy', 'updatedBy'])
            ->whereIn('status', ['pending', 'in_progress'])
            ->where('assigned_user_id', $userId)
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->limit(10)
            ->get();

        return inertia('Dashboard', [
            'totalPendingTasks' => (int) ($total['pending'] ?? 0),
            'myPendingTasks' => (int) ($mine['pending'] ?? 0),
            'totalProgressTasks' => (int) ($total['in_progress'] ?? 0),
            'myProgressTasks' => (int) ($mine['in_progress'] ?? 0),
            'totalCompletedTasks' => (int) ($total['completed'] ?? 0),
            'myCompletedTasks' => (int) ($mine['completed'] ?? 0),
            'activeTasks' => TaskResource::collection($activeTasks),
        ]);
    }
}
