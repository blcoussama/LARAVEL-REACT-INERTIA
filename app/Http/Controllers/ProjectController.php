<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersAndSortsTables;
use App\Http\Requests\StoreProjectRequest;
use App\Http\Requests\UpdateProjectRequest;
use App\Http\Resources\ProjectResource;
use App\Http\Resources\TaskResource;
use App\Models\Project;
use App\Models\Task;
use App\Support\ImageStorage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ProjectController extends Controller
{
    use FiltersAndSortsTables;

    private const SORTABLE = ['id', 'name', 'status', 'created_at', 'due_date'];

    public function index()
    {
        Gate::authorize('viewAny', Project::class);

        $query = Project::query()->with(['createdBy', 'updatedBy']);

        $this->applyContainsFilter($query, 'name');
        $this->applyEnumFilter($query, 'status', Project::STATUSES);
        $this->applySort($query, self::SORTABLE);

        $projects = $query->paginate(10)->onEachSide(1)->withQueryString();

        return inertia('Project/Index', [
            'projects' => ProjectResource::collection($projects),
            'queryParams' => $this->tableQueryParams(),
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', Project::class);

        return inertia('Project/Create');
    }

    public function store(StoreProjectRequest $request)
    {
        $data = $request->safe()->except('image');
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        if ($request->hasFile('image')) {
            $data['image_path'] = ImageStorage::store($request->file('image'), 'project');
        }

        Project::create($data);

        return to_route('project.index')
            ->with('success', 'Project was created');
    }

    public function show(Project $project)
    {
        Gate::authorize('view', $project);

        $project->load(['createdBy', 'updatedBy']);

        $query = $project->tasks()->with(['project', 'assignedUser', 'createdBy', 'updatedBy']);

        $this->applyContainsFilter($query, 'name');
        $this->applyEnumFilter($query, 'status', Task::STATUSES);
        $this->applySort($query, TaskController::SORTABLE);

        $tasks = $query->paginate(10)->onEachSide(1)->withQueryString();

        return inertia('Project/Show', [
            'project' => new ProjectResource($project),
            'tasks' => TaskResource::collection($tasks),
            'queryParams' => $this->tableQueryParams(),
            'success' => session('success'),
        ]);
    }

    public function edit(Project $project)
    {
        Gate::authorize('update', $project);

        return inertia('Project/Edit', [
            'project' => new ProjectResource($project->load(['createdBy', 'updatedBy'])),
        ]);
    }

    public function update(UpdateProjectRequest $request, Project $project)
    {
        $data = $request->safe()->except('image');
        $data['updated_by'] = Auth::id();

        if ($request->hasFile('image')) {
            ImageStorage::delete($project->image_path);
            $data['image_path'] = ImageStorage::store($request->file('image'), 'project');
        }

        $project->update($data);

        return to_route('project.index')
            ->with('success', "Project \"$project->name\" was updated");
    }

    /**
     * The tasks of the project are deleted by the database (ON DELETE
     * CASCADE); their images are removed from the disk here.
     */
    public function destroy(Project $project)
    {
        Gate::authorize('delete', $project);

        $name = $project->name;
        $images = $project->tasks()->whereNotNull('image_path')->pluck('image_path')
            ->push($project->image_path);

        DB::transaction(fn () => $project->delete());

        $images->each(fn (?string $path) => ImageStorage::delete($path));

        return to_route('project.index')
            ->with('success', "Project \"$name\" was deleted");
    }
}
