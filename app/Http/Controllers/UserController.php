<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Section;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function __construct()
    {
        abort_unless(Auth::user()?->isSecretary(), 403);
    }

    public function index()
    {
        return view('admin.users.index', [
            'users' => User::with('section')->orderBy('name')->get(),
        ]);
    }

    public function create()
    {
        return view('admin.users.form', [
            'user'     => new User,
            'sections' => Section::orderBy('name')->get(),
            'roles'    => UserRole::cases(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', 'unique:users,email'],
            'role'       => ['required', 'string', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
            'section_id' => ['nullable', 'exists:sections,id'],
            'password'   => ['required', Password::min(8)],
        ]);

        User::create([
            'name'       => $validated['name'],
            'email'      => $validated['email'],
            'role'       => $validated['role'],
            'section_id' => $validated['section_id'] ?? null,
            'password'   => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.users.index')
            ->with('status', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return view('admin.users.form', [
            'user'     => $user,
            'sections' => Section::orderBy('name')->get(),
            'roles'    => UserRole::cases(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'email'      => ['required', 'email', 'max:255', "unique:users,email,{$user->id}"],
            'role'       => ['required', 'string', 'in:' . implode(',', array_column(UserRole::cases(), 'value'))],
            'section_id' => ['nullable', 'exists:sections,id'],
            'password'   => ['nullable', Password::min(8)],
        ]);

        $user->name       = $validated['name'];
        $user->email      = $validated['email'];
        $user->role       = $validated['role'];
        $user->section_id = $validated['section_id'] ?? null;

        if (!empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()->route('admin.users.index')
            ->with('status', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->withErrors(['user' => 'You cannot delete your own account.']);
        }

        if ($user->assignedItems()->exists()) {
            return back()->withErrors(['user' => 'Cannot delete a user who owns follow-up items. Reassign their items first.']);
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('status', 'User deleted.');
    }
}
