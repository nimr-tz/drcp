@extends('layouts.app')

@section('content')

{{-- Header --}}
<div class="mb-8">
    <nav class="flex items-center gap-1.5 text-xs text-muted-foreground mb-3">
        <a href="{{ route('items.index') }}" class="hover:text-foreground transition-colors">Follow-up items</a>
        <svg class="size-3 text-muted-foreground/50" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
        <span class="text-foreground font-medium">New item</span>
    </nav>
    <h2 class="text-2xl font-bold tracking-tight">Log a follow-up item</h2>
    <p class="text-sm text-muted-foreground mt-1">Record a letter, call, meeting, visit, reminder, or internal task for director-level tracking.</p>
</div>

<form method="POST" action="{{ route('items.store') }}">
@csrf

<div class="flex gap-8 items-start">

    {{-- ── Main column ──────────────────────────────────── --}}
    <div class="flex-1 min-w-0 space-y-5">

        {{-- Step 1 · Item details --}}
        <div class="form-section">
            <div class="form-section-header">
                <span class="form-step-num">1</span>
                <div>
                    <h3 class="text-sm font-semibold">Item details</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Classify and describe the matter.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div class="space-y-1.5">
                        <label class="form-label" for="item_type">Item type <span class="text-destructive">*</span></label>
                        <select id="item_type" name="item_type" class="form-input @error('item_type') border-destructive @enderror" required>
                            @foreach($types as $type)
                                <option value="{{ $type }}" @selected(old('item_type') === $type)>{{ \Illuminate\Support\Str::headline($type) }}</option>
                            @endforeach
                        </select>
                        @error('item_type') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="status">Initial status <span class="text-destructive">*</span></label>
                        <select id="status" name="status" class="form-input @error('status') border-destructive @enderror" required>
                            @foreach($statuses as $status)
                                <option value="{{ $status }}" @selected(old('status', 'new') === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                            @endforeach
                        </select>
                        @error('status') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="form-label" for="title">Title / subject <span class="text-destructive">*</span></label>
                        <input id="title" class="form-input @error('title') border-destructive @enderror"
                               type="text" name="title" value="{{ old('title') }}"
                               placeholder="Brief, descriptive title of this matter" required>
                        @error('title') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="form-label" for="description">
                            Description
                            <span class="text-xs font-normal text-muted-foreground ml-1">optional</span>
                        </label>
                        <textarea id="description" name="description" rows="3"
                                  class="form-input h-auto py-2.5 @error('description') border-destructive @enderror"
                                  placeholder="Additional context, background, or notes on this matter…">{{ old('description') }}</textarea>
                        @error('description') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Step 2 · Reference & source --}}
        <div class="form-section">
            <div class="form-section-header">
                <span class="form-step-num">2</span>
                <div>
                    <h3 class="text-sm font-semibold">Reference &amp; source</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">When it arrived and where it came from.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div class="space-y-1.5">
                        <label class="form-label" for="received_at">Date received <span class="text-destructive">*</span></label>
                        <input id="received_at" class="form-input @error('received_at') border-destructive @enderror"
                               type="date" name="received_at" value="{{ old('received_at', now()->toDateString()) }}" required>
                        @error('received_at') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="priority">Priority <span class="text-destructive">*</span></label>
                        <select id="priority" name="priority" class="form-input @error('priority') border-destructive @enderror" required>
                            @foreach(['low' => 'Low — routine, no deadline pressure', 'medium' => 'Medium — scheduled follow-up needed', 'high' => 'High — urgent or director-flagged'] as $val => $label)
                                <option value="{{ $val }}" @selected(old('priority', 'medium') === $val)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('priority') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="source">
                            Source / origin
                            <span class="text-xs font-normal text-muted-foreground ml-1">optional</span>
                        </label>
                        <input id="source" class="form-input @error('source') border-destructive @enderror"
                               type="text" name="source" value="{{ old('source') }}"
                               placeholder="e.g. Ministry HQ, Internal">
                        @error('source') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="eoffice_reference">
                            e-Office reference
                            <span class="text-xs font-normal text-muted-foreground ml-1">optional</span>
                        </label>
                        <input id="eoffice_reference" class="form-input @error('eoffice_reference') border-destructive @enderror"
                               type="text" name="eoffice_reference" value="{{ old('eoffice_reference') }}"
                               placeholder="e.g. EO-DRCP-44321">
                        @error('eoffice_reference') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Step 3 · Assignment --}}
        <div class="form-section">
            <div class="form-section-header">
                <span class="form-step-num">3</span>
                <div>
                    <h3 class="text-sm font-semibold">Assignment</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">Who is responsible and which section.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div class="space-y-1.5">
                        <label class="form-label" for="current_owner_id">Current owner <span class="text-destructive">*</span></label>
                        <select id="current_owner_id" name="current_owner_id" class="form-input @error('current_owner_id') border-destructive @enderror" required>
                            <option value="">— Select a person —</option>
                            @foreach($owners as $owner)
                                <option value="{{ $owner->id }}" @selected((string) old('current_owner_id') === (string) $owner->id)>
                                    {{ $owner->name }} ({{ $owner->role->label() }})
                                </option>
                            @endforeach
                        </select>
                        @error('current_owner_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="section_id">
                            Section
                            <span class="text-xs font-normal text-muted-foreground ml-1">optional</span>
                        </label>
                        <select id="section_id" name="section_id" class="form-input @error('section_id') border-destructive @enderror">
                            <option value="">— No section —</option>
                            @foreach($sections as $section)
                                <option value="{{ $section->id }}" @selected((string) old('section_id') === (string) $section->id)>{{ $section->name }}</option>
                            @endforeach
                        </select>
                        @error('section_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                </div>
            </div>
        </div>

        {{-- Step 4 · Action plan --}}
        <div class="form-section">
            <div class="form-section-header">
                <span class="form-step-num">4</span>
                <div>
                    <h3 class="text-sm font-semibold">Action plan</h3>
                    <p class="text-xs text-muted-foreground mt-0.5">What happens next and when.</p>
                </div>
            </div>
            <div class="form-section-body">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="form-label" for="next_action">Next action <span class="text-destructive">*</span></label>
                        <input id="next_action" class="form-input @error('next_action') border-destructive @enderror"
                               type="text" name="next_action" value="{{ old('next_action') }}"
                               placeholder="What specifically needs to happen next?" required>
                        @error('next_action') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="next_follow_up_date">Next follow-up date <span class="text-destructive">*</span></label>
                        <input id="next_follow_up_date" class="form-input @error('next_follow_up_date') border-destructive @enderror"
                               type="date" name="next_follow_up_date"
                               value="{{ old('next_follow_up_date', now()->addDay()->toDateString()) }}" required>
                        @error('next_follow_up_date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5">
                        <label class="form-label" for="due_date">
                            Due date
                            <span class="text-xs font-normal text-muted-foreground ml-1">optional</span>
                        </label>
                        <input id="due_date" class="form-input @error('due_date') border-destructive @enderror"
                               type="date" name="due_date" value="{{ old('due_date') }}">
                        @error('due_date') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="space-y-1.5 sm:col-span-2">
                        <label class="form-label" for="history_notes">
                            Creation note
                            <span class="text-xs font-normal text-muted-foreground ml-1">optional</span>
                        </label>
                        <textarea id="history_notes" name="history_notes" rows="3"
                                  class="form-input h-auto py-2.5"
                                  placeholder="Any context to record in the audit history log…">{{ old('history_notes') }}</textarea>
                    </div>

                </div>
            </div>
        </div>

        {{-- Submit --}}
        <div class="flex items-center gap-3 py-2">
            <button type="submit" class="btn-primary px-6">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                Save follow-up item
            </button>
            <a href="{{ route('items.index') }}" class="btn-ghost text-sm">Cancel</a>
        </div>

    </div>

    {{-- ── Sidebar ──────────────────────────────────────── --}}
    <aside class="w-72 shrink-0 space-y-4 hidden lg:block">

        <div class="card p-5">
            <div class="flex items-center gap-2.5 mb-3">
                <div class="flex size-7 items-center justify-center rounded-md bg-primary/10 text-primary">
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h4 class="text-sm font-semibold">About this form</h4>
            </div>
            <p class="text-xs text-muted-foreground leading-relaxed">
                Each item logged here gets a unique <span class="font-mono font-medium text-foreground">DRCP-YYYY-####</span> reference and becomes part of the director-level follow-up register. Every change is recorded in the audit history.
            </p>
        </div>

        <div class="card p-5">
            <h4 class="text-sm font-semibold mb-3">Priority guide</h4>
            <div class="space-y-3">
                <div class="flex items-start gap-2.5">
                    <span class="badge-destructive mt-0.5">High</span>
                    <p class="text-xs text-muted-foreground leading-relaxed">Director-flagged, externally mandated, or time-critical. Escalate if missed.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="badge-warning mt-0.5">Medium</span>
                    <p class="text-xs text-muted-foreground leading-relaxed">Requires scheduled follow-up within the work week. Normal operational urgency.</p>
                </div>
                <div class="flex items-start gap-2.5">
                    <span class="badge-default mt-0.5">Low</span>
                    <p class="text-xs text-muted-foreground leading-relaxed">Routine. No immediate deadline. Can be addressed in the normal weekly review.</p>
                </div>
            </div>
        </div>

        <div class="card p-5">
            <h4 class="text-sm font-semibold mb-3">Item types</h4>
            <ul class="space-y-1.5 text-xs text-muted-foreground">
                <li class="flex gap-2"><span class="text-foreground font-medium w-20 shrink-0">Letter</span> Incoming/outgoing correspondence requiring a response.</li>
                <li class="flex gap-2"><span class="text-foreground font-medium w-20 shrink-0">Call</span> Phone follow-up or commitment made via call.</li>
                <li class="flex gap-2"><span class="text-foreground font-medium w-20 shrink-0">Meeting</span> Action points or decisions from a formal meeting.</li>
                <li class="flex gap-2"><span class="text-foreground font-medium w-20 shrink-0">Visit</span> Commitments arising from a field or stakeholder visit.</li>
                <li class="flex gap-2"><span class="text-foreground font-medium w-20 shrink-0">Task</span> Internal deliverable assigned within the directorate.</li>
                <li class="flex gap-2"><span class="text-foreground font-medium w-20 shrink-0">Reminder</span> A standing reminder with no single document source.</li>
            </ul>
        </div>

    </aside>

</div>
</form>

@endsection
