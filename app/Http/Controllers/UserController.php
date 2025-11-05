<?php

namespace App\Http\Controllers;

use App\Models\Skpd;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('q');
        $currentUser = $request->user();

        $users = User::with('skpd')
            ->when($search, function ($query) use ($search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('username', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(! $currentUser->isSuperAdmin(), function ($query) use ($currentUser) {
                $query->where('skpd_id', $currentUser->skpd_id);
            })
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'search' => $search,
        ]);
    }

    public function create(Request $request): View
    {
        $currentUser = $request->user();

        if ($currentUser->isUserRegular()) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambahkan pengguna.');
        }

        $skpds = $currentUser->isSuperAdmin()
            ? Skpd::cachedOptions()
            : Skpd::cachedOptions()->where('id', $currentUser->skpd_id)->values();

        $roleOptions = $currentUser->isSuperAdmin()
            ? [
                User::ROLE_SUPER_ADMIN => 'Super Admin',
                User::ROLE_ADMIN_UNIT => 'Admin Unit',
                User::ROLE_USER_REGULAR => 'User Reguler',
            ]
            : [
                User::ROLE_ADMIN_UNIT => 'Admin Unit',
                User::ROLE_USER_REGULAR => 'User Reguler',
            ];

        return view('users.create', [
            'skpds' => $skpds,
            'roleOptions' => $roleOptions,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->isUserRegular()) {
            abort(403, 'Anda tidak memiliki hak akses untuk menambahkan pengguna.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'username' => ['required', 'string', 'max:255', 'unique:users,username'],
            'skpd_id' => ['nullable', 'exists:skpds,id'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:' . implode(',', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN_UNIT, User::ROLE_USER_REGULAR])],
        ]);

        if ($currentUser->isSuperAdmin()) {
            if (in_array($validated['role'], [User::ROLE_ADMIN_UNIT, User::ROLE_USER_REGULAR], true) && empty($validated['skpd_id'])) {
                return back()
                    ->withErrors(['skpd_id' => 'SKPD wajib dipilih untuk peran tersebut.'])
                    ->withInput($request->except('password'));
            }
        } else {
            $validated['skpd_id'] = $currentUser->skpd_id;
            if ($validated['role'] === User::ROLE_SUPER_ADMIN) {
                abort(403, 'Anda tidak dapat menetapkan peran Super Admin.');
            }
        }

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'skpd_id' => $validated['skpd_id'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(Request $request, User $user): View
    {
        $currentUser = $request->user();

        if ($currentUser->isUserRegular()) {
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui pengguna.');
        }

        if (! $currentUser->isSuperAdmin()) {
            if ($user->isSuperAdmin() || $user->skpd_id !== $currentUser->skpd_id) {
                abort(403, 'Anda tidak dapat mengelola pengguna dari SKPD lain.');
            }
        }

        if ($user->isSuperAdmin() && ! $currentUser->is($user)) {
            abort(403, 'Akun Super Admin hanya dapat dikelola oleh pemiliknya.');
        }

        $skpds = $currentUser->isSuperAdmin()
            ? Skpd::cachedOptions()
            : Skpd::cachedOptions()->where('id', $currentUser->skpd_id)->values();

        $roleOptions = $currentUser->isSuperAdmin()
            ? [
                User::ROLE_SUPER_ADMIN => 'Super Admin',
                User::ROLE_ADMIN_UNIT => 'Admin Unit',
                User::ROLE_USER_REGULAR => 'User Reguler',
            ]
            : [
                User::ROLE_ADMIN_UNIT => 'Admin Unit',
                User::ROLE_USER_REGULAR => 'User Reguler',
            ];

        return view('users.edit', [
            'user' => $user,
            'skpds' => $skpds,
            'roleOptions' => $roleOptions,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->isUserRegular()) {
            abort(403, 'Anda tidak memiliki hak akses untuk memperbarui pengguna.');
        }

        if (! $currentUser->isSuperAdmin()) {
            if ($user->isSuperAdmin() || $user->skpd_id !== $currentUser->skpd_id) {
                abort(403, 'Anda tidak dapat mengelola pengguna dari SKPD lain.');
            }
        }

        if ($user->isSuperAdmin() && ! $currentUser->is($user)) {
            abort(403, 'Akun Super Admin hanya dapat dikelola oleh pemiliknya.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'username' => ['required', 'string', 'max:255', "unique:users,username,{$user->id}"],
            'skpd_id' => ['nullable', 'exists:skpds,id'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'role' => ['required', 'in:' . implode(',', [User::ROLE_SUPER_ADMIN, User::ROLE_ADMIN_UNIT, User::ROLE_USER_REGULAR])],
        ]);

        if ($currentUser->isSuperAdmin()) {
            if (in_array($validated['role'], [User::ROLE_ADMIN_UNIT, User::ROLE_USER_REGULAR], true) && empty($validated['skpd_id'])) {
                return back()
                    ->withErrors(['skpd_id' => 'SKPD wajib dipilih untuk peran tersebut.'])
                    ->withInput($request->except(['password', 'password_confirmation']));
            }
        } else {
            $validated['skpd_id'] = $currentUser->skpd_id;
            if ($validated['role'] === User::ROLE_SUPER_ADMIN) {
                abort(403, 'Anda tidak dapat menetapkan peran Super Admin.');
            }
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'username' => $validated['username'],
            'skpd_id' => $validated['skpd_id'],
            'role' => $validated['role'],
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $currentUser = $request->user();

        if ($currentUser->isUserRegular()) {
            abort(403, 'Anda tidak memiliki hak akses untuk menghapus pengguna.');
        }

        if (! $currentUser->isSuperAdmin()) {
            if ($user->isSuperAdmin() || $user->skpd_id !== $currentUser->skpd_id) {
                abort(403, 'Anda tidak dapat menghapus pengguna dari SKPD lain.');
            }
        }

        if ($user->isSuperAdmin() && ! $currentUser->is($user)) {
            abort(403, 'Akun Super Admin hanya dapat dihapus oleh pemiliknya.');
        }

        if ($currentUser->is($user)) {
            return redirect()->route('users.index')->withErrors([
                'user' => 'Anda tidak dapat menghapus akun yang sedang digunakan.',
            ]);
        }

        $user->delete();

        return redirect()->route('users.index')->with('status', 'Pengguna berhasil dihapus.');
    }
}
