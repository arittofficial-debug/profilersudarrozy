<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users.view')->only(['index', 'edit']);
        $this->middleware('permission:users.update')->only(['update']);
        $this->middleware('permission:users.delete')->only(['destroy']);
    }

    public function index()
    {
        $users = User::with('roles')->orderByDesc('last_login_at')->paginate(15);

        return view('admin.users.index', compact('users'));
    }

    public function edit(User $user)
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.users.edit', compact('user', 'roles'));
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
            'username' => ['nullable', 'string', 'max:255', 'unique:users,username,' . $user->id],
            'is_active' => ['nullable', 'boolean'],
            'roles' => ['array'],
            'roles.*' => ['exists:roles,id'],
        ]);

        $isSelf = $user->id === Auth::id();
        $active = (bool) ($validated['is_active'] ?? false);

        if ($isSelf && ! $active) {
            return back()->with('error', 'Akun sendiri tidak dapat dinonaktifkan.');
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'] ?? $user->username,
            'is_active' => $active,
        ])->save();

        $roleIds = array_map('intval', $validated['roles'] ?? []);

        if ($user->hasRole('super_admin')) {
            $superAdminId = Role::where('slug', 'super_admin')->value('id');
            if ($superAdminId) {
                $roleIds[] = (int) $superAdminId;
            }
        }

        $roleIds = array_values(array_unique($roleIds));

        if (! $roleIds) {
            $user->is_active = false;
            $user->save();
        }

        $user->roles()->sync($roleIds);

        if ($isSelf && ! $user->hasRole('super_admin')) {
            return back()->with('error', 'Role super admin sendiri tidak dapat dicabut.');
        }

        return redirect()->route('admin.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'Akun sendiri tidak dapat dihapus.');
        }

        $activeSuperAdmins = User::where('is_active', true)
            ->whereHas('roles', fn ($query) => $query->where('roles.slug', 'super_admin'))
            ->count();

        if ($user->hasRole('super_admin') && $user->is_active && $activeSuperAdmins <= 1) {
            return back()->with('error', 'Super admin terakhir tidak dapat dihapus.');
        }

        $user->roles()->detach();
        $user->delete();

        return redirect()->route('admin.users.index')->with('success', 'User berhasil dihapus.');
    }
}
