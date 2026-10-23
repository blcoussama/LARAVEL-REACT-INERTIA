<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\FiltersAndSortsTables;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\TaskResource;
use App\Http\Resources\UserCrudResource;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;

/**
 * Administration of the accounts. The routes are also protected by the
 * "can:viewAny,App\Models\User" middleware (see routes/web.php).
 */
class UserController extends Controller
{
    use FiltersAndSortsTables;

    private const SORTABLE = ['id', 'name', 'email', 'role', 'created_at'];

    public function index()
    {
        Gate::authorize('viewAny', User::class);

        $query = User::query();

        $this->applyContainsFilter($query, 'name');
        $this->applyContainsFilter($query, 'email');
        $this->applySort($query, self::SORTABLE);

        $users = $query->paginate(10)->onEachSide(1)->withQueryString();

        return inertia('User/Index', [
            'users' => UserCrudResource::collection($users),
            'queryParams' => $this->tableQueryParams(),
            'success' => session('success'),
        ]);
    }

    public function create()
    {
        Gate::authorize('create', User::class);

        return inertia('User/Create', ['roles' => User::ROLES]);
    }

    /**
     * Accounts created by an administrator are considered verified.
     */
    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = new User([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        $user->role = $data['role'];
        $user->email_verified_at = now();
        $user->save();

        return to_route('user.index')
            ->with('success', 'User was created');
    }

    /**
     * Profile of a user with the tasks assigned to them.
     */
    public function show(User $user)
    {
        Gate::authorize('view', $user);

        $query = Task::query()
            ->where('assigned_user_id', $user->id)
            ->with(['project', 'assignedUser', 'createdBy', 'updatedBy']);

        $this->applyContainsFilter($query, 'name');
        $this->applyEnumFilter($query, 'status', Task::STATUSES);
        $this->applySort($query, TaskController::SORTABLE);

        $tasks = $query->paginate(10)->onEachSide(1)->withQueryString();

        return inertia('User/Show', [
            'user' => new UserCrudResource($user->loadCount(['projects', 'assignedTasks'])),
            'tasks' => TaskResource::collection($tasks),
            'queryParams' => $this->tableQueryParams(),
        ]);
    }

    public function edit(User $user)
    {
        Gate::authorize('update', $user);

        return inertia('User/Edit', [
            'user' => new UserCrudResource($user),
            'roles' => User::ROLES,
        ]);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $data = $request->validated();

        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
        ]);

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        if (isset($data['role']) && $request->user()->can('changeRole', $user)) {
            $user->role = $data['role'];
        }

        $user->save();

        return to_route('user.index')
            ->with('success', "User \"$user->name\" was updated");
    }

    public function destroy(User $user)
    {
        Gate::authorize('delete', $user);

        $name = $user->name;
        $user->delete();

        return to_route('user.index')
            ->with('success', "User \"$name\" was deleted");
    }
}
