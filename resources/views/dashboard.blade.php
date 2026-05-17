@extends('layouts.app')

@section('content')

@php
    $hour     = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', $user->name)[0];

    if ($user->isSecretary()) {
        $contextLine = $stats['overdue'] > 0
            ? "{$stats['overdue']} overdue " . Str::plural('item', $stats['overdue']) . " and {$stats['stale']} awaiting an update."
            : "All {$stats['active']} active " . Str::plural('item', $stats['active']) . " are within schedule.";
    } elseif ($user->isDirector()) {
        $contextLine = $stats['at_director'] === 1
            ? '1 item is at the director desk awaiting review.'
            : "{$stats['at_director']} items are at the director desk awaiting review.";
    } else {
        $contextLine = $stats['my_items'] === 1
            ? '1 item is currently assigned to you.'
            : "{$stats['my_items']} items are currently assigned to you.";
    }
@endphp

{{-- ─── Greeting banner ──────────────────────────────────── --}}
<div class="dashboard-banner mb-8">
    <div class="dashboard-banner-glow"></div>
    <div class="relative flex items-center justify-between">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground mb-2">
                {{ now()->format('l, d F Y') }}
            </p>
            <h2 class="text-2xl font-bold tracking-tight">{{ $greeting }}, {{ $firstName }}</h2>
            <p class="text-sm text-muted-foreground mt-1 max-w-md">{{ $contextLine }}</p>
        </div>
        @if($user->isSecretary())
            <a href="{{ route('items.create') }}" class="btn-primary shrink-0">
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                Log new item
            </a>
        @endif
    </div>
</div>

{{-- ─── Primary stats ────────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-4">

    {{-- Active --}}
    <div class="stat-card">
        <div class="stat-icon bg-primary/10 text-primary">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
        </div>
        <div>
            <p class="stat-label">Active items</p>
            <p class="stat-value">{{ $stats['active'] }}</p>
        </div>
    </div>

    {{-- Overdue --}}
    <div class="stat-card {{ $stats['overdue'] > 0 ? 'stat-card-danger' : '' }}">
        <div class="stat-icon {{ $stats['overdue'] > 0 ? 'bg-destructive/10 text-destructive' : 'bg-muted text-muted-foreground' }}">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>
        <div>
            <p class="stat-label">Overdue</p>
            <p class="stat-value {{ $stats['overdue'] > 0 ? 'text-destructive' : '' }}">{{ $stats['overdue'] }}</p>
        </div>
    </div>

    {{-- Stale --}}
    <div class="stat-card {{ $stats['stale'] > 0 ? 'stat-card-warning' : '' }}">
        <div class="stat-icon {{ $stats['stale'] > 0 ? 'bg-warning/10 text-warning-foreground' : 'bg-muted text-muted-foreground' }}">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </div>
        <div>
            <p class="stat-label">No recent update</p>
            <p class="stat-value {{ $stats['stale'] > 0 ? 'text-warning-foreground' : '' }}">{{ $stats['stale'] }}</p>
        </div>
    </div>

    {{-- At director --}}
    <div class="stat-card">
        <div class="stat-icon bg-violet-500/10 text-violet-600">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        </div>
        <div>
            <p class="stat-label">At director</p>
            <p class="stat-value">{{ $stats['at_director'] }}</p>
        </div>
    </div>

</div>

{{-- ─── Secondary stats ──────────────────────────────────── --}}
<div class="grid grid-cols-2 gap-4 sm:grid-cols-4 mb-8">

    {{-- Assigned to me --}}
    <div class="stat-card">
        <div class="stat-icon bg-sky-500/10 text-sky-600">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        </div>
        <div>
            <p class="stat-label">Assigned to me</p>
            <p class="stat-value">{{ $stats['my_items'] }}</p>
        </div>
    </div>

    {{-- High priority --}}
    <div class="stat-card {{ $stats['high_priority'] > 0 ? 'stat-card-danger' : '' }}">
        <div class="stat-icon {{ $stats['high_priority'] > 0 ? 'bg-destructive/10 text-destructive' : 'bg-muted text-muted-foreground' }}">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
        </div>
        <div>
            <p class="stat-label">High priority</p>
            <p class="stat-value {{ $stats['high_priority'] > 0 ? 'text-destructive' : '' }}">{{ $stats['high_priority'] }}</p>
        </div>
    </div>

    {{-- Missing main doc --}}
    <div class="stat-card {{ $stats['missing_main_document'] > 0 ? 'stat-card-warning' : '' }}">
        <div class="stat-icon {{ $stats['missing_main_document'] > 0 ? 'bg-warning/10 text-warning-foreground' : 'bg-muted text-muted-foreground' }}">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 13h6M9 17h3m3-8l-4-4H7a2 2 0 00-2 2v14a2 2 0 002 2h10a2 2 0 002-2V9l-4-4z"/></svg>
        </div>
        <div>
            <p class="stat-label">Missing main doc</p>
            <p class="stat-value">{{ $stats['missing_main_document'] }}</p>
        </div>
    </div>

    {{-- No signed response --}}
    <div class="stat-card {{ $stats['missing_signed_response'] > 0 ? 'stat-card-warning' : '' }}">
        <div class="stat-icon {{ $stats['missing_signed_response'] > 0 ? 'bg-warning/10 text-warning-foreground' : 'bg-muted text-muted-foreground' }}">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
        </div>
        <div>
            <p class="stat-label">No signed response</p>
            <p class="stat-value">{{ $stats['missing_signed_response'] }}</p>
        </div>
    </div>

</div>

{{-- ─── Priority list ────────────────────────────────────── --}}
<div class="card overflow-hidden">
    <div class="flex items-center justify-between px-6 py-4 border-b border-border">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-0.5">Needs attention</p>
            <h3 class="text-base font-semibold">Priority follow-up list</h3>
        </div>
        <a href="{{ route('items.index') }}" class="btn-outline text-xs px-3 py-1.5 h-auto">View full register</a>
    </div>

    <table class="data-table">
        <thead class="bg-muted/40 border-b border-border">
            <tr>
                <th class="w-36">Reference</th>
                <th>Title</th>
                <th class="w-36">Status</th>
                <th class="w-32">Owner</th>
                <th class="w-36">Section</th>
                <th class="w-32">Follow up</th>
            </tr>
        </thead>
        <tbody>
        @forelse($highlightedItems as $item)
            @php $isOverdue = $item->isOverdue(); @endphp
            <tr class="{{ $isOverdue ? 'priority-row-overdue' : '' }}">
                <td>
                    <div class="flex items-center gap-2">
                        @if($isOverdue)
                            <span class="size-1.5 rounded-full bg-destructive shrink-0"></span>
                        @elseif($item->needsAttention())
                            <span class="size-1.5 rounded-full bg-warning shrink-0"></span>
                        @else
                            <span class="size-1.5 rounded-full bg-transparent shrink-0"></span>
                        @endif
                        <a href="{{ route('items.show', $item) }}"
                           class="font-mono text-xs font-semibold text-primary hover:underline">
                            {{ $item->reference_code }}
                        </a>
                    </div>
                </td>
                <td>
                    <p class="font-medium text-foreground leading-snug">{{ $item->title }}</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @if($isOverdue)
                            <span class="badge-destructive">Overdue</span>
                        @elseif($item->needsAttention())
                            <span class="badge-warning">Awaiting update</span>
                        @endif
                        @if(!$item->hasPrimaryDocument())
                            <span class="badge-warning">No main doc</span>
                        @endif
                        @if($item->item_type === 'letter' && !$item->hasSignedResponse())
                            <span class="badge-warning">No signed response</span>
                        @endif
                    </div>
                </td>
                <td><span class="badge-default">{{ $item->status->label() }}</span></td>
                <td class="text-sm text-muted-foreground">{{ $item->currentOwner?->name ?? '—' }}</td>
                <td class="text-sm text-muted-foreground">{{ $item->section?->name ?? '—' }}</td>
                <td class="text-sm tabular-nums {{ $isOverdue ? 'text-destructive font-medium' : 'text-muted-foreground' }}">
                    {{ optional($item->next_follow_up_date)->format('d M Y') ?? '—' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">
                    <div class="py-16 flex flex-col items-center text-center">
                        <div class="flex size-12 items-center justify-center rounded-full bg-primary/10 text-primary mb-4">
                            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        </div>
                        <p class="text-sm font-semibold text-foreground">Everything is up to date</p>
                        <p class="text-sm text-muted-foreground mt-1">
                            No items need attention right now.
                            @if($user->isSecretary())
                                <a href="{{ route('items.create') }}" class="text-primary hover:underline">Log a new follow-up item</a> to get started.
                            @else
                                <a href="{{ route('items.index') }}" class="text-primary hover:underline">Browse all items</a> or check back later.
                            @endif
                        </p>
                    </div>
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

@endsection
