@extends('layouts.app')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-1">Administration</p>
        <h2 class="text-2xl font-bold tracking-tight">Users</h2>
        <p class="text-sm text-muted-foreground mt-1">Manage who has access to DRCP and what they can do.</p>
    </div>
    <a href="{{ route('admin.users.create') }}" class="btn-primary">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New user
    </a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead class="bg-muted/40 border-b border-border">
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Section</th>
                <th class="w-32"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($users as $user)
            <tr>
                <td class="font-medium">{{ $user->name }}</td>
                <td class="text-sm text-muted-foreground">{{ $user->email }}</td>
                <td>
                    <span class="badge-default">{{ $user->role->label() }}</span>
                </td>
                <td class="text-sm text-muted-foreground">{{ $user->section?->name ?? '—' }}</td>
                <td>
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn-outline text-xs px-3 py-1.5 h-auto">Edit</a>
                        @if($user->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.destroy', $user) }}"
                              @submit="!confirm('Delete {{ addslashes($user->name) }}? This cannot be undone.') && $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-destructive text-xs px-3 py-1.5 h-auto">Delete</button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="py-16 text-center">
                    <p class="text-sm font-semibold">No users yet</p>
                    <p class="text-sm text-muted-foreground mt-1">
                        <a href="{{ route('admin.users.create') }}" class="text-primary hover:underline">Create the first user</a> to get started.
                    </p>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
