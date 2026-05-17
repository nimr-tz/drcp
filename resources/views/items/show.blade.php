@extends('layouts.app')

@section('content')

@php
    $canAct = auth()->user()->isSecretary() || auth()->id() === $item->current_owner_id;
    $isClosed = in_array($item->status, ['completed', 'closed']);
    $activeTab = old('_form', 'update');
@endphp

{{-- Page header --}}
<div class="flex items-start justify-between mb-6">
    <div>
        <nav class="flex items-center gap-1.5 text-xs text-muted-foreground mb-2">
            <a href="{{ route('items.index') }}" class="hover:text-foreground transition-colors">Follow-up items</a>
            <span>/</span>
            <span>{{ $item->reference_code }}</span>
        </nav>
        <h2 class="text-2xl font-bold tracking-tight">{{ $item->title }}</h2>
        <p class="text-sm text-muted-foreground mt-1">{{ $item->description ?: 'No extra description provided.' }}</p>
    </div>
    <div class="flex items-center gap-2 shrink-0 ml-4">
        <span class="badge-default">{{ $item->status->label() }}</span>
        @if($item->isOverdue())
            <span class="badge-destructive">Overdue</span>
        @elseif($item->needsAttention())
            <span class="badge-warning">Awaiting update</span>
        @endif
    </div>
</div>

{{-- Details + Timeline --}}
<div class="grid grid-cols-1 gap-6 lg:grid-cols-2 mb-6">

    {{-- Item details --}}
    <div class="card">
        <div class="card-header border-b border-border pb-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-0.5">Current state</p>
            <h3 class="text-base font-semibold">Item details</h3>
        </div>
        <div class="card-content">
            <dl class="grid grid-cols-2 gap-x-6 gap-y-4 text-sm">
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Type</dt>
                    <dd>{{ \Illuminate\Support\Str::headline($item->item_type) }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Priority</dt>
                    <dd>
                        @php $p = $item->priority @endphp
                        <span @class(['badge', 'badge-destructive' => $p==='high', 'badge-warning' => $p==='medium', 'badge-default' => $p==='low'])>{{ ucfirst($p) }}</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Current owner</dt>
                    <dd class="font-medium">{{ $item->currentOwner?->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Section</dt>
                    <dd>{{ $item->section?->name ?? 'Unassigned' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Received</dt>
                    <dd class="tabular-nums">{{ optional($item->received_at)->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Next follow up</dt>
                    <dd class="tabular-nums">{{ optional($item->next_follow_up_date)->format('d M Y') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Due date</dt>
                    <dd class="tabular-nums">{{ optional($item->due_date)->format('d M Y') ?? 'Not set' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Last update</dt>
                    <dd class="tabular-nums">{{ optional($item->last_updated_at)->format('d M Y H:i') ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Source</dt>
                    <dd>{{ $item->source ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">e-Office ref</dt>
                    <dd>{{ $item->eoffice_reference ?? '—' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Next action</dt>
                    <dd class="font-medium">{{ $item->next_action }}</dd>
                </div>
                @if($item->closure_note)
                <div class="col-span-2">
                    <dt class="text-xs text-muted-foreground font-medium uppercase tracking-wide mb-1">Closure note</dt>
                    <dd>{{ $item->closure_note }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Handoff + Timeline --}}
    <div class="card flex flex-col">
        <div class="card-header border-b border-border pb-4">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-0.5">Handoff view</p>
            <h3 class="text-base font-semibold">Who has it and what they need to do</h3>
        </div>
        <div class="card-content">
            {{-- Handoff cards --}}
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="rounded-lg border border-border bg-muted/30 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-1">Current owner</p>
                    <p class="font-semibold">{{ $item->currentOwner?->name ?? 'Unassigned' }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">{{ $item->section?->name ?? 'No section' }}</p>
                </div>
                <div class="rounded-lg border border-border bg-muted/30 p-4">
                    <p class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground mb-1">Task to do now</p>
                    <p class="font-semibold text-sm">{{ $item->next_action }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">Follow up by {{ optional($item->next_follow_up_date)->format('d M Y') }}</p>
                </div>
            </div>

            {{-- Timeline --}}
            <div class="space-y-3 max-h-72 overflow-y-auto pr-1">
                @forelse($item->histories as $history)
                    <div @class([
                        'rounded-md border-l-4 border border-border bg-background p-3 text-sm',
                        'border-l-primary' => $history->event_type === 'created',
                        'border-l-blue-500' => $history->event_type === 'transfer',
                        'border-l-muted-foreground' => $history->event_type === 'updated',
                        'border-l-destructive' => $history->event_type === 'closed',
                        'border-l-warning-foreground' => in_array($history->event_type, ['document_added','document_marked_primary']),
                    ])>
                        <div class="flex items-start justify-between gap-2">
                            <div>
                                <span class="text-[10px] font-semibold uppercase tracking-widest text-muted-foreground">
                                    {{ \Illuminate\Support\Str::headline(str_replace('_',' ',$history->event_type)) }}
                                </span>
                                <p class="font-medium text-foreground">{{ $history->action_summary }}</p>
                                <p class="text-xs text-muted-foreground">{{ $history->actor?->name ?? 'System' }}</p>
                                @if($history->fromUser || $history->toUser)
                                    <p class="text-xs text-muted-foreground">{{ $history->fromUser?->name ?? '—' }} → {{ $history->toUser?->name ?? '—' }}</p>
                                @endif
                                @if($history->notes)
                                    <p class="text-xs text-foreground/70 mt-1">{{ $history->notes }}</p>
                                @endif
                            </div>
                            <span class="text-[11px] text-muted-foreground tabular-nums whitespace-nowrap shrink-0">
                                {{ $history->created_at->format('d M Y H:i') }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-muted-foreground">No history entries yet.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

{{-- Documents --}}
<div class="card mb-6">
    <div class="card-header border-b border-border pb-4">
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-0.5">Documents</p>
        <h3 class="text-base font-semibold">Files attached to this item</h3>
    </div>
    <div class="card-content">

        @php($primaryDocument = $item->documents->firstWhere('is_primary', true))
        @if($primaryDocument)
            <div class="rounded-lg border border-primary/20 bg-primary/5 p-4 mb-6">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-primary/70 mb-1">Main document</p>
                <p class="font-semibold text-sm">{{ $primaryDocument->document_label }} <span class="text-xs font-normal text-muted-foreground">v{{ $primaryDocument->version_number }}</span></p>
                <p class="text-xs text-muted-foreground mt-0.5">{{ \Illuminate\Support\Str::headline($primaryDocument->category) }} · {{ $primaryDocument->original_name }}</p>
                @if(str_contains((string) $primaryDocument->mime_type, 'pdf') || str_starts_with((string) $primaryDocument->mime_type, 'image/'))
                    <div class="mt-3">
                        <a href="{{ route('items.documents.preview', [$item, $primaryDocument]) }}" target="_blank" class="btn-primary text-xs px-3 py-1.5 h-auto">Open preview</a>
                    </div>
                    <div class="mt-4 rounded-md overflow-hidden border border-border">
                        <iframe src="{{ route('items.documents.preview', [$item, $primaryDocument]) }}" class="w-full h-96 border-0" title="Document preview"></iframe>
                    </div>
                @endif
            </div>
        @else
            <div class="rounded-lg border border-destructive/20 bg-destructive/5 px-4 py-3 text-sm text-destructive mb-6">
                <strong>No main document has been set for this item yet.</strong>
            </div>
        @endif

        @if($documentGroups->isEmpty())
            <div class="py-8 text-center">
                <p class="text-sm font-semibold text-foreground">No documents yet</p>
                <p class="text-sm text-muted-foreground mt-1">Upload files using the panel below to keep everything in one place.</p>
            </div>
        @else
            <div class="divide-y divide-border">
                @foreach($documentGroups as $group)
                    @php($latest = $group['latest'])
                    <div class="py-4 first:pt-0 last:pb-0">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('items.documents.download', [$item, $latest]) }}" class="text-sm font-semibold hover:underline">{{ $latest->document_label }}</a>
                                    <span class="badge-outline">v{{ $latest->version_number }}</span>
                                    @if($latest->is_primary) <span class="badge-primary">Main</span> @endif
                                </div>
                                <p class="text-xs text-muted-foreground mt-1">
                                    {{ \Illuminate\Support\Str::headline($latest->category) }} · {{ $latest->original_name }}
                                    · {{ $latest->uploadedBy?->name ?? 'Unknown' }}
                                    · {{ $latest->created_at->format('d M Y H:i') }}
                                    · {{ number_format($latest->size_bytes / 1024, 1) }} KB
                                </p>
                                @if($latest->description) <p class="text-xs text-foreground/70 mt-1">{{ $latest->description }}</p> @endif
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('items.documents.download', [$item, $latest]) }}" class="btn-primary text-xs px-3 py-1.5 h-auto">Download</a>
                                @if(str_contains((string) $latest->mime_type, 'pdf') || str_starts_with((string) $latest->mime_type, 'image/'))
                                    <a href="{{ route('items.documents.preview', [$item, $latest]) }}" target="_blank" class="btn-outline text-xs px-3 py-1.5 h-auto">Preview</a>
                                @endif
                                @if((auth()->user()->isSecretary() || auth()->id() === $item->current_owner_id) && !$latest->is_primary)
                                    <form method="POST" action="{{ route('items.documents.primary', [$item, $latest]) }}">
                                        @csrf
                                        <button type="submit" class="btn-outline text-xs px-3 py-1.5 h-auto">Set main</button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        @if($group['versions']->count() > 1)
                            <details class="mt-2">
                                <summary class="text-xs text-primary cursor-pointer hover:underline">View all {{ $group['versions']->count() }} versions</summary>
                                <div class="mt-2 space-y-2 pl-4 border-l-2 border-border">
                                    @foreach($group['versions'] as $doc)
                                        <div class="flex items-center justify-between gap-4">
                                            <div>
                                                <div class="flex items-center gap-2">
                                                    <a href="{{ route('items.documents.download', [$item, $doc]) }}" class="text-xs font-semibold hover:underline">v{{ $doc->version_number }}</a>
                                                    @if($doc->is_primary) <span class="badge-primary">Main</span> @endif
                                                </div>
                                                <p class="text-[11px] text-muted-foreground">{{ $doc->original_name }} · {{ $doc->created_at->format('d M Y H:i') }}</p>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <a href="{{ route('items.documents.download', [$item, $doc]) }}" class="btn-outline text-xs px-2.5 py-1 h-auto">Download</a>
                                                @if((auth()->user()->isSecretary() || auth()->id() === $item->current_owner_id) && !$doc->is_primary)
                                                    <form method="POST" action="{{ route('items.documents.primary', [$item, $doc]) }}">
                                                        @csrf
                                                        <button type="submit" class="btn-outline text-xs px-2.5 py-1 h-auto">Set main</button>
                                                    </form>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

{{-- Action panel (tabs) --}}
@if($canAct)
<div class="card" x-data="{ tab: @js($activeTab) }">
    <div class="card-header border-b border-border pb-0">
        <div class="flex items-end gap-1 mb-0">
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mr-4 pb-3">Actions</p>
            @if(!$isClosed)
                <button @click="tab = 'update'"
                    :class="tab === 'update' ? 'tab-active' : 'tab-inactive'"
                    type="button">Update</button>
                <button @click="tab = 'transfer'"
                    :class="tab === 'transfer' ? 'tab-active' : 'tab-inactive'"
                    type="button">Transfer</button>
                <button @click="tab = 'document'"
                    :class="tab === 'document' ? 'tab-active' : 'tab-inactive'"
                    type="button">Add document</button>
                <button @click="tab = 'close'"
                    :class="tab === 'close' ? 'tab-active' : 'tab-inactive'"
                    type="button">Close out</button>
            @endif
        </div>
    </div>
    <div class="card-content pt-6">

        @if($isClosed)
            <div class="flex items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                This item is <strong class="ml-1">{{ $item->status }}</strong>. No further updates are possible. Create a new item if more action is required.
            </div>
        @else

            {{-- Update --}}
            <div x-show="tab === 'update'" x-cloak>
                <form method="POST" action="{{ route('items.update', $item) }}">
                    @csrf @method('PUT')
                    <input type="hidden" name="_form" value="update">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-input @error('status') border-destructive @enderror" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($item->status === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Section</label>
                            <select name="section_id" class="form-input">
                                <option value="">No section</option>
                                @foreach($sections as $section)
                                    <option value="{{ $section->id }}" @selected($item->section_id === $section->id)>{{ $section->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="form-label">Next action</label>
                            <input class="form-input @error('next_action') border-destructive @enderror"
                                   type="text" name="next_action" value="{{ old('next_action', $item->next_action) }}" required>
                            @error('next_action') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Next follow-up date</label>
                            <input class="form-input @error('next_follow_up_date') border-destructive @enderror"
                                   type="date" name="next_follow_up_date" value="{{ old('next_follow_up_date', optional($item->next_follow_up_date)->toDateString()) }}" required>
                            @error('next_follow_up_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Due date <span class="text-xs font-normal text-muted-foreground">(optional)</span></label>
                            <input class="form-input" type="date" name="due_date" value="{{ old('due_date', optional($item->due_date)->toDateString()) }}">
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="form-label">Update note <span class="text-xs font-normal text-muted-foreground">(optional)</span></label>
                            <textarea name="notes" rows="3" class="form-input h-auto py-2">{{ old('notes') }}</textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn-primary">Save update</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Transfer --}}
            <div x-show="tab === 'transfer'" x-cloak>
                <form method="POST" action="{{ route('items.transfer', $item) }}"
                      @submit="!confirm('Transfer ownership of this item? The new owner will be notified.') && $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="_form" value="transfer">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="form-label">New owner</label>
                            <select name="current_owner_id" class="form-input @error('current_owner_id') border-destructive @enderror" required>
                                @foreach($owners as $owner)
                                    <option value="{{ $owner->id }}" @selected($item->current_owner_id === $owner->id)>{{ $owner->name }}</option>
                                @endforeach
                            </select>
                            @error('current_owner_id') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">New status</label>
                            <select name="status" class="form-input @error('status') border-destructive @enderror" required>
                                @foreach($statuses as $status)
                                    <option value="{{ $status }}" @selected($item->status === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                                @endforeach
                            </select>
                            @error('status') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="form-label">Task for new owner</label>
                            <input class="form-input @error('next_action') border-destructive @enderror"
                                   type="text" name="next_action" value="{{ $item->next_action }}" required>
                            @error('next_action') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Next follow-up date</label>
                            <input class="form-input @error('next_follow_up_date') border-destructive @enderror"
                                   type="date" name="next_follow_up_date" value="{{ optional($item->next_follow_up_date)->toDateString() }}" required>
                            @error('next_follow_up_date') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="form-label">Transfer note <span class="text-xs font-normal text-muted-foreground">(optional)</span></label>
                            <textarea name="notes" rows="3" class="form-input h-auto py-2" placeholder="Anything the next person should know?"></textarea>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn-primary">Transfer ownership</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Add document --}}
            <div x-show="tab === 'document'" x-cloak>
                <form method="POST" action="{{ route('items.documents.store', $item) }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="_form" value="document">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="form-label">File</label>
                            <input class="form-input h-auto py-1.5 @error('document') border-destructive @enderror"
                                   type="file" name="document" required>
                            @error('document') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Category</label>
                            <select name="category" class="form-input @error('category') border-destructive @enderror" required>
                                @foreach($documentCategories as $category)
                                    <option value="{{ $category }}" @selected(old('category') === $category)>{{ \Illuminate\Support\Str::headline($category) }}</option>
                                @endforeach
                            </select>
                            @error('category') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Document label <span class="text-xs font-normal text-muted-foreground">(optional)</span></label>
                            <input class="form-input @error('document_label') border-destructive @enderror"
                                   type="text" name="document_label" value="{{ old('document_label') }}" placeholder="e.g. Draft response">
                            @error('document_label') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5">
                            <label class="form-label">Description <span class="text-xs font-normal text-muted-foreground">(optional)</span></label>
                            <input class="form-input @error('description') border-destructive @enderror"
                                   type="text" name="description" value="{{ old('description') }}" placeholder="What is this document for?">
                            @error('description') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="flex items-center gap-2 sm:col-span-2">
                            <input id="is_primary" type="checkbox" name="is_primary" value="1" @checked(old('is_primary'))
                                   class="size-4 rounded border-input accent-primary cursor-pointer">
                            <label for="is_primary" class="text-sm cursor-pointer select-none">Mark as main document for this item</label>
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn-primary">Upload document</button>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Close out --}}
            <div x-show="tab === 'close'" x-cloak>
                <p class="text-sm text-muted-foreground mb-5">Closing an item is permanent. It will be locked and no further updates will be possible.</p>
                <form method="POST" action="{{ route('items.close', $item) }}"
                      @submit="!confirm('Close this item permanently? It will be locked and cannot be updated.') && $event.preventDefault()">
                    @csrf
                    <input type="hidden" name="_form" value="close">
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="space-y-1.5">
                            <label class="form-label">Final status</label>
                            <select name="status" class="form-input @error('status') border-destructive @enderror" required>
                                <option value="completed" @selected(old('status') === 'completed')>Completed</option>
                                <option value="closed" @selected(old('status') === 'closed')>Closed</option>
                            </select>
                            @error('status') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="space-y-1.5 sm:col-span-2">
                            <label class="form-label">Closure note / outcome</label>
                            <textarea name="closure_note" rows="4" required
                                      class="form-input h-auto py-2 @error('closure_note') border-destructive @enderror"
                                      placeholder="Describe the outcome or reason for closing…">{{ old('closure_note') }}</textarea>
                            @error('closure_note') <p class="form-error">{{ $message }}</p> @enderror
                        </div>
                        <div class="sm:col-span-2">
                            <button type="submit" class="btn-destructive">Close item</button>
                        </div>
                    </div>
                </form>
            </div>
        @endif
    </div>
</div>
@endif

@endsection
