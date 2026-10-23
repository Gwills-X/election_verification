<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\Election;
use App\Models\User;
use App\Services\AuditLogService;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users/Index', [
            'users' => User::with('assignedElections:id,name')->orderBy('role')->orderBy('name')->paginate(15),
        ]);
    }

    public function create()
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => null,
            'roles' => [User::ROLE_ELECTION_OFFICER, User::ROLE_OBSERVER],
            'elections' => Election::select('id', 'name')->orderByDesc('year')->get(),
        ]);
    }

    public function store(StoreUserRequest $request, AuditLogService $audit)
    {
        $data = $request->validated();
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active', true),
        ]);
        $user->assignedElections()->sync($data['election_ids'] ?? []);
        $audit->log($request->user()->id, 'USER_CREATED', "Created {$user->role} account for '{$user->email}'.");
        return redirect()->route('admin.users.index')->with('success', 'User created.');
    }

    public function edit(User $user)
    {
        return Inertia::render('Admin/Users/Form', [
            'user' => $user->load('assignedElections:id'),
            'roles' => [User::ROLE_ELECTION_OFFICER, User::ROLE_OBSERVER],
            'elections' => Election::select('id', 'name')->orderByDesc('year')->get(),
        ]);
    }

    public function update(UpdateUserRequest $request, User $user, AuditLogService $audit)
    {
        $data = $request->validated();
        $user->fill([
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'is_active' => $request->boolean('is_active'),
        ]);
        if (!empty($data['password'])) $user->password = $data['password'];
        $user->save();
        $user->assignedElections()->sync($data['election_ids'] ?? []);
        $audit->log($request->user()->id, 'USER_UPDATED', "Updated account for '{$user->email}'.");
        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function destroy(User $user, AuditLogService $audit)
    {
        if ($user->id === request()->user()->id || $user->isAdmin()) {
            return back()->with('error', 'This account cannot be deleted.');
        }
        $audit->log(request()->user()->id, 'USER_DELETED', "Deleted account '{$user->email}'.");
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }
}
