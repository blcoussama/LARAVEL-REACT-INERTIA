<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersAndSortsTables;
use App\Http\Requests\StoreTaskRequest;
use App\Http\Requests\UpdateTaskRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserResource;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Support\ImageStorage;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class TaskController extends Controller
{
    use FiltersAndSortsTables;

    public const SORTABLE = ['id', 'name', 'status', 'priority', 'created_at', 'due_date'];

    private const RELATIONS = ['project', 'assignedUser', 'createdBy', 'updatedBy'];

    public function index()
    {
        Gate::authorize('viewAny', Task::class);

        return $this->renderIndex(Task::query(), myTasks: false);
    }

    public function myTasks()
    {
        Gate::authorize('viewAny', Task::class);

        return $this->renderIndex(
            Task::query()->where('assigned_user_id', Auth::id()),
            myTasks: true,
        );
    }

    public function create()
    {
        $projects = $this->plannableProjects();

        return inertia('Task/Create', [
            'projects' => ProjectResource::collection($projects),
            'users' => UserResource::collection(User::query()->orderBy('name')->get()),
            'selectedProjectId' => request()->integer('project_id') ?: null,
        ]);
    }

    public function store(StoreTaskRequest $request)
    {
        $data = $request->safe()->except('image');
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageStorage::store($request->file('image'), 'task');
        }

        Task::create($data);

        return to_route('task.index')
            ->with('success', 'Task was created');
    }

    public function show(Task $task)
    {
        Gate::authorize('view', $task);

        return inertia('Task/Show', [
            'task' => new TaskResource($task->load(self::RELATIONS)),
        ]);
    }

    public function edit(Task $task)
    {
        Gate::authorize('update', $task);

        // The current project is always listed, even for an assignee who
        // cannot move the task to another project.
        $projects = $this->plannableProjects()
            ->push($task->project)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return inertia('Task/Edit', [
            'task' => new TaskResource($task->load(self::RELATIONS)),
            'projects' => ProjectResource::collection($projects),
            'users' => UserResource::collection(User::query()->orderBy('name')->get()),
        ]);
    }

    public function update(UpdateTaskRequest $request, Task $task)
    {
        $data = $request->safe()->except('image');
        $data['updated_by'] = Auth::id();

        if ($request->hasFile('image')) {
            ImageStorage::delete($task->image_path);
            $data['image_path'] = ImageStorage::store($request->file('image'), 'task');
        }

        $task->update($data);

        return to_route('task.index')
            ->with('success', "Task \"$task->name\" was updated");
    }

    public function destroy(Task $task)
    {
        Gate::authorize('delete', $task);

        $name = $task->name;
        $task->delete();
        ImageStorage::delete($task->image_path);

        return to_route('task.index')
            ->with('success', "Task \"$name\" was deleted");
    }

    private function renderIndex(Builder $query, bool $myTasks)
    {
        $query->with(self::RELATIONS);

        $this->applyContainsFilter($query, 'name');
        $this->applyEnumFilter($query, 'status', Task::STATUSES);
        $this->applySort($query, self::SORTABLE);

        $tasks = $query->paginate(10)->onEachSide(1)->withQueryString();

        return inertia('Task/Index', [
            'tasks' => TaskResource::collection($tasks),
            'queryParams' => $this->tableQueryParams(),
            'success' => session('success'),
            'myTasks' => $myTasks,
        ]);
    }

    /**
     * Projects in which the current user may create tasks.
     */
    private function plannableProjects()
    {
        /** @var User $user */
        $user = Auth::user();

        return Project::query()
            ->with(['createdBy', 'updatedBy'])
            ->unless($user->isAdmin(), fn ($query) => $query->where('created_by', $user->id))
            ->orderBy('name')
            ->get();
    }
}
