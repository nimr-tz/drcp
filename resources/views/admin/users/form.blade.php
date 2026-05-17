@extends('layouts.app')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-1">Administration / Users</p>
        <h2 class="text-2xl font-bold tracking-tight">{{ $user->exists ? 'Edit user' : 'New user' }}</h2>
    </div>
    <a href="{{ route('admin.users.index') }}" class="btn-outline">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to users
    </a>
</div>

<div class="card max-w-xl">
    <div class="card-header">
        <h3 class="text-base font-semibold">User details</h3>
        <p class="text-sm text-muted-foreground">
            {{ $user->exists ? 'Update the user\'s information. Leave password blank to keep it unchanged.' : 'Fill in the details for the new user.' }}
        </p>
    </div>
    <div class="card-content">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-5">
            @csrf
            @if($user->exists) @method('PUT') @endif

            <div>
                <label for="name" class="form-label">Full name <span class="text-destructive">*</span></label>
                <input id="name" name="name" type="text" class="form-input @error('name') border-destructive @enderror"
                       value="{{ old('name', $user->name) }}" placeholder="e.g. John Doe" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="email" class="form-label">Email address <span class="text-destructive">*</span></label>
                <input id="email" name="email" type="email" class="form-input @error('email') border-destructive @enderror"
                       value="{{ old('email', $user->email) }}" placeholder="user@example.com" required>
                @error('email') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="role" class="form-label">Role <span class="text-destructive">*</span></label>
                <select id="role" name="role" class="form-input @error('role') border-destructive @enderror" required>
                    <option value="">— Select a role —</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->value }}" {{ old('role', $user->role?->value) === $role->value ? 'selected' : '' }}>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
                @error('role') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="section_id" class="form-label">Section</label>
                <select id="section_id" name="section_id" class="form-input @error('section_id') border-destructive @enderror">
                    <option value="">— None —</option>
                    @foreach($sections as $section)
                        <option value="{{ $section->id }}" {{ old('section_id', $user->section_id) == $section->id ? 'selected' : '' }}>
                            {{ $section->name }}
                        </option>
                    @endforeach
                </select>
                <p class="text-xs text-muted-foreground mt-1">Required for Head of Section; optional for others.</p>
                @error('section_id') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="form-label">
                    Password {{ $user->exists ? '' : '<span class="text-destructive">*</span>' }}
                </label>
                <input id="password" name="password" type="password"
                       class="form-input @error('password') border-destructive @enderror"
                       placeholder="{{ $user->exists ? 'Leave blank to keep current password' : 'Minimum 8 characters' }}"
                       {{ $user->exists ? '' : 'required' }}>
                @error('password') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit" class="btn-primary">
                    {{ $user->exists ? 'Save changes' : 'Create user' }}
                </button>
                <a href="{{ route('admin.users.index') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
