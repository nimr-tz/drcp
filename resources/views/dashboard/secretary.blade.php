@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
    $hour      = now()->hour;
    $greeting  = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName = explode(' ', $user->name)[0];

    $pipelineColors = [
        'new'                       => '#9ca3af',
        'assigned'                  => '#6366f1',
        'in_progress'               => '#10b981',
        'waiting_external_response' => '#f59e0b',
        'at_director'               => '#8b5cf6',
        'returned_for_action'       => '#f97316',
    ];
    $pipelineLabels = [
        'new'                       => 'New',
        'assigned'                  => 'Assigned',
        'in_progress'               => 'In Progress',
        'waiting_external_response' => 'Waiting',
        'at_director'               => 'At Director',
        'returned_for_action'       => 'Returned',
    ];
    $avatarPalette = ['#2d6a4f','#1e3a5f','#7c3aed','#b45309','#0f766e','#be185d'];
@endphp

{{-- ── Page header ──────────────────────────────────────────────── --}}
<div class="flex items-start justify-between mb-6 gap-6">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground mb-1">
            {{ now()->format('l · d F Y') }}
        </p>
        <h1 class="db-headline">{{ $greeting }}, <em>{{ $firstName }}.</em></h1>
        <p class="db-subline mt-1.5">
            @if($overdueCount > 0)
                <span class="text-destructive font-semibold">{{ $overdueCount }} overdue</span>
                &nbsp;·&nbsp; {{ $staleCount }} {{ Str::plural('item', $staleCount) }} awaiting an update
                &nbsp;·&nbsp; the directorate has {{ $totalActive }} active follow-ups.
            @else
                All {{ $totalActive }} active {{ Str::plural('item', $totalActive) }} are within schedule.
                @if($staleCount > 0) &nbsp;·&nbsp; {{ $staleCount }} awaiting an update.@endif
            @endif
        </p>
    </div>
    <div class="flex items-center gap-2 shrink-0 pt-1">
        <a href="{{ route('items.index') }}" class="db-btn-secondary">
            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35"/></svg>
            Search items
        </a>
        <a href="{{ route('items.create') }}" class="db-btn-secondary">
            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            Log new item
        </a>
        <a href="{{ route('items.index', ['status' => 'at_director']) }}" class="db-btn-primary">
            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0"/></svg>
            Route to Director
        </a>
    </div>
</div>

{{-- ── Alert banner ─────────────────────────────────────────────── --}}
@if($overdueCount > 0)
<div class="db-alert-banner mb-5">
    <div class="db-alert-icon">
        <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M12 2a10 10 0 1 0 0 20A10 10 0 0 0 12 2z"/></svg>
    </div>
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-foreground">
            {{ $overdueCount }} {{ Str::plural('item', $overdueCount) }} overdue and need re-routing today
        </p>
        <p class="text-xs text-muted-foreground mt-0.5 truncate">
            @foreach($criticalItems as $ci)
                <a href="{{ route('items.show', $ci) }}" class="text-primary font-mono font-semibold hover:underline">{{ $ci->reference_code }}</a>
                @if($ci->status->value === 'at_director') at Director
                @elseif($ci->status->value === 'returned_for_action') returned for action
                @endif
                {{ $ci->last_updated_at ? $ci->last_updated_at->diffForHumans() : '' }}
                @if(!$loop->last) &nbsp;·&nbsp; @endif
            @endforeach
        </p>
    </div>
    <a href="{{ route('items.index') }}" class="db-btn-outline-sm shrink-0">Review now →</a>
</div>
@endif

{{-- ── KPI cards ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-4 gap-4 mb-5">

    {{-- Active follow-ups --}}
    <div class="db-kpi-card">
        <p class="db-kpi-label">Active Follow-ups</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">{{ $totalActive }}</p>
            <svg viewBox="0 0 80 32" fill="none" class="w-20 h-8 db-sparkline">
                <polyline points="0,28 16,22 32,18 48,24 64,14 80,8" stroke="var(--primary)" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="db-kpi-sub mt-1.5"><span class="text-primary font-medium">↑ 12%</span> vs. last week</p>
    </div>

    {{-- Overdue --}}
    <div class="db-kpi-card {{ $overdueCount > 0 ? 'db-kpi-card--danger' : '' }}">
        <p class="db-kpi-label">Overdue</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value {{ $overdueCount > 0 ? 'text-destructive' : '' }}">{{ $overdueCount }}</p>
            <div class="flex flex-col items-end gap-1 pb-1">
                @if($overdueHigh > 0)
                    <span class="db-priority-tag db-priority-tag--high">{{ $overdueHigh }} high</span>
                @endif
                @if($overdueMedium > 0)
                    <span class="db-priority-tag db-priority-tag--medium">{{ $overdueMedium }} medium</span>
                @endif
            </div>
        </div>
        @if($overdueCount > 0)
            <p class="db-kpi-sub mt-1.5 text-destructive">Oldest: {{ $oldestOverdueDays }} {{ Str::plural('day', $oldestOverdueDays) }} late</p>
        @else
            <p class="db-kpi-sub mt-1.5">All items within schedule</p>
        @endif
    </div>

    {{-- Awaiting update --}}
    <div class="db-kpi-card {{ $staleCount > 0 ? 'db-kpi-card--warning' : '' }}">
        <p class="db-kpi-label">Awaiting Update</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value {{ $staleCount > 0 ? 'text-warning-foreground' : '' }}">{{ $staleCount }}</p>
        </div>
        @if($staleCount > 0)
            <p class="db-kpi-sub mt-1.5">No movement in <span class="font-medium text-warning-foreground">3+ days</span></p>
            @if($staleSections)<p class="db-kpi-sub">Across {{ $staleSections }}</p>@endif
        @else
            <p class="db-kpi-sub mt-1.5">All items are active</p>
        @endif
    </div>

    {{-- At Director's Desk --}}
    <div class="db-kpi-card {{ $atDirectorCount > 0 ? 'db-kpi-card--violet' : '' }}">
        <p class="db-kpi-label">At Director's Desk</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">{{ $atDirectorCount }}</p>
        </div>
        @if($atDirectorCount > 0)
            <p class="db-kpi-sub mt-1.5">Longest wait: <span class="{{ $longestAtDirectorDays >= 3 ? 'text-destructive' : 'text-primary' }} font-medium">{{ $longestAtDirectorDays }}d</span></p>
            <p class="db-kpi-sub">Awaiting decision or signature</p>
        @else
            <p class="db-kpi-sub mt-1.5">No items pending</p>
        @endif
    </div>

</div>

{{-- ── Pipeline + Completion rate ──────────────────────────────── --}}
<div class="grid grid-cols-3 gap-4 mb-5">

    {{-- Pipeline bar --}}
    <div class="db-card col-span-2 p-5">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="db-section-label">Pipeline · Live</p>
                <h3 class="db-section-title mt-0.5">Active workflow distribution</h3>
            </div>
            <span class="text-sm font-semibold tabular-nums text-muted-foreground">{{ $totalActive }} active</span>
        </div>

        {{-- Proportional bar --}}
        <div class="flex h-2 rounded-full overflow-hidden gap-px mb-5">
            @foreach($pipeline as $status => $count)
                @if($count > 0)
                    @php $pct = $totalActive > 0 ? round($count / $totalActive * 100, 1) : 0; @endphp
                    <div class="h-full transition-all" style="width:{{ $pct }}%; background:{{ $pipelineColors[$status] ?? '#9ca3af' }}"
                         title="{{ $pipelineLabels[$status] ?? $status }}: {{ $count }}"></div>
                @endif
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="grid grid-cols-3 gap-x-6 gap-y-2.5">
            @foreach($pipeline as $status => $count)
                @php $pct = $totalActive > 0 ? round($count / $totalActive * 100) : 0; @endphp
                <div class="flex items-center gap-2 min-w-0">
                    <span class="size-2 rounded-full shrink-0" style="background:{{ $pipelineColors[$status] ?? '#9ca3af' }}"></span>
                    <span class="text-xs font-semibold text-muted-foreground uppercase tracking-wide truncate">{{ $pipelineLabels[$status] ?? $status }}</span>
                    <span class="ml-auto text-sm font-bold tabular-nums">{{ $count }}</span>
                    <span class="text-xs text-muted-foreground">/{{ $pct }}%</span>
                </div>
            @endforeach
        </div>
    </div>

    {{-- Completion rate donut --}}
    <div class="db-card p-5" style="background: #f0faf4; border-color: #c6e8d4;">
        <div class="flex items-center justify-between mb-4">
            <div>
                <p class="db-section-label">This week</p>
                <h3 class="db-section-title mt-0.5">Completion rate</h3>
            </div>
            @if($completionRate > 0)
                <span class="text-xs font-semibold text-primary">↑ {{ $completionRate }}%</span>
            @endif
        </div>

        <div class="flex items-center gap-4">
            {{-- Donut SVG --}}
            @php
                $r      = 34;
                $circum = 2 * M_PI * $r;
                $filled = ($completionRate / 100) * $circum;
                $empty  = $circum - $filled;
            @endphp
            <div class="relative size-24 shrink-0">
                <svg viewBox="0 0 80 80" class="size-24 -rotate-90">
                    <circle cx="40" cy="40" r="{{ $r }}" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                    <circle cx="40" cy="40" r="{{ $r }}" fill="none" stroke="#05499c" stroke-width="8"
                        stroke-dasharray="{{ $filled }} {{ $empty }}" stroke-linecap="round"/>
                </svg>
                <div class="absolute inset-0 flex items-center justify-center">
                    <span class="text-lg font-bold tabular-nums">{{ $completionRate }}%</span>
                </div>
            </div>

            <div class="flex-1 space-y-2.5">
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-muted-foreground">Closed</span>
                    <span class="text-sm font-semibold tabular-nums">{{ $completedThisWeek }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-muted-foreground">Avg. handling time</span>
                    <span class="text-sm font-semibold tabular-nums">{{ $avgHandlingDays > 0 ? $avgHandlingDays.' d' : '—' }}</span>
                </div>
                <div class="flex items-center justify-between gap-2">
                    <span class="text-xs text-muted-foreground">On-time rate</span>
                    <span class="text-sm font-semibold tabular-nums {{ $onTimeRate >= 90 ? 'text-primary' : ($onTimeRate >= 70 ? 'text-warning-foreground' : 'text-destructive') }}">{{ $onTimeRate }}%</span>
                </div>
            </div>
        </div>
    </div>

</div>

{{-- ── Items register + Activity feed ───────────────────────────── --}}
<div class="grid grid-cols-3 gap-4">

    {{-- Needs attention table --}}
    <div class="db-card col-span-2 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border">
            <h3 class="db-section-title">Needs your attention</h3>
            <a href="{{ route('items.index') }}" class="text-xs text-muted-foreground hover:text-foreground transition-colors font-medium">View full register →</a>
        </div>

        <div class="db-register-header">
            <div>Reference</div>
            <div>Item</div>
            <div>Status</div>
            <div>Owner</div>
            <div>Due</div>
        </div>

        <div class="divide-y divide-border">
        @forelse($highlightedItems as $item)
            @php
                $isOverdue   = $item->isOverdue();
                $isStale     = $item->needsAttention();
                $accentColor = $isOverdue ? '#dc2626' : ($item->status->value === 'at_director' ? '#8b5cf6' : ($isStale ? '#f59e0b' : 'transparent'));
                $ownerName   = $item->currentOwner?->name ?? '';
                $initials    = collect(explode(' ', $ownerName))->filter()->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                $avatarColor = $avatarPalette[crc32($ownerName) % count($avatarPalette)];
                $statusClass = match($item->status->value) {
                    'new'                       => 'status-new',
                    'assigned'                  => 'status-assigned',
                    'in_progress'               => 'status-in-progress',
                    'waiting_external_response' => 'status-waiting',
                    'at_director'               => 'status-at-director',
                    'returned_for_action'       => 'status-returned',
                    default                     => '',
                };
                $statusShort = match($item->status->value) {
                    'new'                       => 'New',
                    'assigned'                  => 'Assigned',
                    'in_progress'               => 'In Progress',
                    'waiting_external_response' => 'Waiting',
                    'at_director'               => 'At Director',
                    'returned_for_action'       => 'Returned',
                    default                     => $item->status->label(),
                };
            @endphp
            <div class="db-register-row {{ $isOverdue ? 'db-register-row--overdue' : '' }}">

                {{-- Reference + left accent --}}
                <div class="relative pl-3.5">
                    <div class="absolute left-0 top-0 bottom-0 w-0.5 rounded-full" style="background:{{ $accentColor }}"></div>
                    <a href="{{ route('items.show', $item) }}"
                       class="font-mono text-xs font-semibold text-primary hover:underline leading-tight block">{{ $item->reference_code }}</a>
                    <p class="text-[11px] text-muted-foreground capitalize mt-0.5">{{ str_replace('_', ' ', $item->item_type) }}</p>
                </div>

                {{-- Title + flags --}}
                <div class="min-w-0">
                    <p class="text-sm font-medium text-foreground leading-snug truncate">{{ $item->title }}</p>
                    <div class="flex flex-wrap gap-1 mt-1">
                        @if($isOverdue)<span class="db-flag db-flag--danger">Overdue</span>@endif
                        @if(!$isOverdue && $isStale)<span class="db-flag db-flag--warning">Stale</span>@endif
                        @if(!$item->hasPrimaryDocument())<span class="db-flag db-flag--warning">No main doc</span>@endif
                        @if($item->item_type === 'letter' && !$item->hasSignedResponse())<span class="db-flag db-flag--warning">No signed response</span>@endif
                        @if($item->section)<span class="db-flag db-flag--neutral">{{ $item->section->name }}</span>@endif
                    </div>
                </div>

                {{-- Status badge --}}
                <div>
                    <span class="status-badge {{ $statusClass }}">
                        <span class="status-dot"></span>{{ $statusShort }}
                    </span>
                </div>

                {{-- Owner avatar --}}
                <div class="flex items-center gap-2">
                    @if($item->currentOwner)
                        <div class="db-avatar" style="background:{{ $avatarColor }}">{{ $initials }}</div>
                        <span class="text-xs text-muted-foreground truncate">{{ collect(explode(' ', $ownerName))->last() }}</span>
                    @else
                        <span class="text-xs text-muted-foreground">—</span>
                    @endif
                </div>

                {{-- Due date --}}
                <div>
                    @if($item->next_follow_up_date)
                        @if($item->next_follow_up_date->isToday())
                            <span class="text-xs font-semibold text-destructive">Today</span>
                        @elseif($item->next_follow_up_date->isTomorrow())
                            <span class="text-xs font-semibold text-warning-foreground">Tomorrow</span>
                        @elseif($item->next_follow_up_date->isPast())
                            <span class="text-xs font-semibold text-destructive">{{ $item->next_follow_up_date->format('d M') }}</span>
                        @else
                            <span class="text-xs text-muted-foreground tabular-nums">{{ $item->next_follow_up_date->format('d M') }}</span>
                        @endif
                        <p class="text-[11px] text-muted-foreground mt-0.5">upd. {{ $item->last_updated_at ? $item->last_updated_at->diffForHumans(null, true, true) : 'never' }}</p>
                    @else
                        <span class="text-xs text-muted-foreground">—</span>
                    @endif
                </div>
            </div>
        @empty
            <div class="py-14 flex flex-col items-center text-center">
                <div class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
                    <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <p class="text-sm font-semibold">Everything is up to date</p>
                <a href="{{ route('items.create') }}" class="text-sm text-primary hover:underline mt-1">Log a new follow-up item</a>
            </div>
        @endforelse
        </div>
    </div>

    {{-- Right column: Activity + Document hygiene --}}
    <div class="flex flex-col gap-4">

        {{-- Live activity feed --}}
        <div class="db-card p-5 flex-1 min-h-0" style="background: #f3f4ff; border-color: #d4d7ff;">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="db-section-label">Live</p>
                    <h3 class="db-section-title mt-0.5">Activity</h3>
                </div>
                <span class="flex items-center gap-1.5 text-[11px] text-muted-foreground">
                    <span class="size-1.5 rounded-full bg-primary animate-pulse"></span>
                    updated {{ now()->format('H:i') }}
                </span>
            </div>

            <div class="space-y-3 overflow-y-auto" style="max-height: 320px;">
                @forelse($activityFeed->take(10) as $entry)
                    @php
                        $actorName = $entry->actor?->name ?? 'System';
                        $parts     = explode(' ', trim($actorName));
                        $shortName = count($parts) > 1
                            ? strtoupper(substr($parts[0], 0, 1)) . '. ' . end($parts)
                            : $actorName;
                        $action = match(true) {
                            $entry->event_type === 'created'                                => 'logged new item',
                            ($entry->new_status ?? '') === 'at_director'                    => 'routed to Director',
                            ($entry->new_status ?? '') === 'returned_for_action'            => 'returned for action',
                            ($entry->new_status ?? '') === 'waiting_external_response'      => 'marked waiting response',
                            ($entry->new_status ?? '') === 'in_progress'                    => 'marked in progress',
                            ($entry->new_status ?? '') === 'completed'                      => 'completed item',
                            ($entry->new_status ?? '') === 'closed'                         => 'closed item',
                            str_contains($entry->action_summary ?? '', 'document')          => 'uploaded document',
                            default                                                         => $entry->action_summary ?? 'updated',
                        };
                    @endphp
                    <div class="flex gap-3 items-start">
                        <span class="text-[11px] text-muted-foreground tabular-nums w-9 shrink-0 pt-0.5">
                            {{ $entry->created_at->isToday() ? $entry->created_at->format('H:i') : 'Yest.' }}
                        </span>
                        <p class="text-xs text-foreground leading-relaxed">
                            <span class="font-semibold">{{ $shortName }}</span>
                            {{ $action }}
                            @if($entry->item)
                                <a href="{{ route('items.show', $entry->item) }}"
                                   class="font-mono font-semibold text-primary hover:underline ml-0.5">{{ $entry->item->reference_code }}</a>
                            @endif
                        </p>
                    </div>
                @empty
                    <p class="text-xs text-muted-foreground">No recent activity.</p>
                @endforelse
            </div>
        </div>

        {{-- Document hygiene --}}
        <div class="db-card p-5" style="background: #fffbf0; border-color: #fde8a0;">
            <p class="db-section-label">Document Hygiene</p>
            <h3 class="db-section-title mt-0.5 mb-4">Missing attachments</h3>

            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Items without a main document</span>
                    <span class="text-sm font-semibold tabular-nums {{ $missingMainDoc > 0 ? 'text-warning-foreground' : 'text-muted-foreground' }}">{{ $missingMainDoc }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Letters without signed response</span>
                    <span class="text-sm font-semibold tabular-nums {{ $missingSignedResponse > 0 ? 'text-warning-foreground' : 'text-muted-foreground' }}">{{ $missingSignedResponse }}</span>
                </div>
                <div class="flex items-center justify-between border-t border-border pt-2.5 mt-2.5">
                    <span class="text-xs text-muted-foreground">Total documents on file</span>
                    <span class="text-sm font-semibold tabular-nums">{{ $totalDocuments }}</span>
                </div>
            </div>

            <a href="{{ route('items.index') }}" class="db-btn-outline-sm w-full mt-4">
                Open document register →
            </a>
        </div>

    </div>
</div>

@endsection
