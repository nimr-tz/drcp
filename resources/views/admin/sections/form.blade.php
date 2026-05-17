@extends('layouts.app')

@section('content')

<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-1">Administration / Sections</p>
        <h2 class="text-2xl font-bold tracking-tight">{{ $section->exists ? 'Edit section' : 'New section' }}</h2>
    </div>
    <a href="{{ route('admin.sections.index') }}" class="btn-outline">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
        Back to sections
    </a>
</div>

<div class="card max-w-xl">
    <div class="card-header">
        <h3 class="text-base font-semibold">Section details</h3>
        <p class="text-sm text-muted-foreground">{{ $section->exists ? 'Update the section information below.' : 'Fill in the details for the new section.' }}</p>
    </div>
    <div class="card-content">
        <form method="POST" action="{{ $section->exists ? route('admin.sections.update', $section) : route('admin.sections.store') }}" class="space-y-5">
            @csrf
            @if($section->exists) @method('PUT') @endif

            <div>
                <label for="name" class="form-label">Section name <span class="text-destructive">*</span></label>
                <input id="name" name="name" type="text" class="form-input @error('name') border-destructive @enderror"
                       value="{{ old('name', $section->name) }}" placeholder="e.g. Coordination" required>
                @error('name') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="description" class="form-label">Description</label>
                <textarea id="description" name="description" rows="3"
                          class="form-input @error('description') border-destructive @enderror"
                          placeholder="Optional — briefly describe what this section handles.">{{ old('description', $section->description) }}</textarea>
                @error('description') <p class="form-error">{{ $message }}</p> @enderror
            </div>

            <div class="flex items-center gap-3 pt-1">
                <button type="submit" class="btn-primary">
                    {{ $section->exists ? 'Save changes' : 'Create section' }}
                </button>
                <a href="{{ route('admin.sections.index') }}" class="btn-ghost">Cancel</a>
            </div>
        </form>
    </div>
</div>

@endsection
