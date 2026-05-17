@extends('layouts.app')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-1">Administration</p>
        <h2 class="text-2xl font-bold tracking-tight">Sections</h2>
        <p class="text-sm text-muted-foreground mt-1">Manage the organisational sections users belong to.</p>
    </div>
    <a href="{{ route('admin.sections.create') }}" class="btn-primary">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
        New section
    </a>
</div>

<div class="card overflow-hidden">
    <table class="data-table">
        <thead class="bg-muted/40 border-b border-border">
            <tr>
                <th>Section name</th>
                <th>Description</th>
                <th class="w-28 text-center">Users</th>
                <th class="w-32"></th>
            </tr>
        </thead>
        <tbody>
        @forelse($sections as $section)
            <tr>
                <td class="font-medium">{{ $section->name }}</td>
                <td class="text-sm text-muted-foreground">{{ $section->description ?: '—' }}</td>
                <td class="text-center">
                    <span class="badge-default">{{ $section->users_count }}</span>
                </td>
                <td>
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.sections.edit', $section) }}" class="btn-outline text-xs px-3 py-1.5 h-auto">Edit</a>
                        <form method="POST" action="{{ route('admin.sections.destroy', $section) }}"
                              @submit="!confirm('Delete section {{ addslashes($section->name) }}? This cannot be undone.') && $event.preventDefault()">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn-destructive text-xs px-3 py-1.5 h-auto">Delete</button>
                        </form>
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="4" class="py-16 text-center">
                    <p class="text-sm font-semibold">No sections yet</p>
                    <p class="text-sm text-muted-foreground mt-1">
                        <a href="{{ route('admin.sections.create') }}" class="text-primary hover:underline">Create the first section</a> to get started.
                    </p>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
