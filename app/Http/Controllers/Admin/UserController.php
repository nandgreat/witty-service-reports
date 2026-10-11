<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index()
    {
        return Inertia::render('Admin/Users', [
            'users' => User::orderBy('role')->orderBy('name')->get(['id', 'name', 'email', 'role', 'is_active']),
        ]);
    }

    public function store(Request $request)
    {
        User::create($this->validated($request));

        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $data = $this->validated($request, $user);
        if (empty($data['password'])) {
            unset($data['password']);
        }
        // never lock the last admin out
        if ($user->is($request->user())) {
            $data['role'] = User::ROLE_ADMIN;
            $data['is_active'] = true;
        }
        $user->update($data);

        return back()->with('success', 'User updated.');
    }

    /** Admin sets a new password for a user and signs them out everywhere. */
    public function password(Request $request, User $user)
    {
        $data = $request->validate(['password' => ['required', 'string', 'min:8', 'confirmed']]);
        $user->forceFill(['password' => $data['password'], 'remember_token' => null])->save();
        if (! $user->is($request->user())) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return back()->with('success', "Password changed for {$user->name}.");
    }

    public function toggle(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'You cannot disable your own account.');
        $user->update(['is_active' => ! $user->is_active]);
        if (! $user->is_active) {
            DB::table('sessions')->where('user_id', $user->id)->delete();
        }

        return back()->with('success', $user->is_active ? 'User enabled.' : 'User disabled and signed out.');
    }

    public function destroy(Request $request, User $user)
    {
        abort_if($user->is($request->user()), 422, 'You cannot delete your own account.');
        $user->delete();

        return back()->with('success', 'User deleted.');
    }

    private function validated(Request $request, ?User $user = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users')->ignore($user)],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_COORDINATOR])],
            'is_active' => ['boolean'],
        ]);
    }
}
