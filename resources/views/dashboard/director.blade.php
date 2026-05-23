@extends('layouts.app')

@section('title', "Director's Desk")

@section('content')
@php
    use Illuminate\Support\Str;
    $firstName  = explode(' ', $user->name)[0];
    $firstItem  = $queueItems->first();
    $countWords = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten'];
    $countWord  = ($atDeskCount >= 1 && $atDeskCount <= 10) ? $countWords[$atDeskCount] : $atDeskCount;
@endphp

{{-- ── Page header ──────────────────────────────────────────────── --}}
<div class="flex items-start justify-between mb-6 gap-6">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground mb-1">
            Director's Desk · {{ now()->format('l d F') }}
        </p>
        <h1 class="db-headline">
            @if($atDeskCount === 0)
                Your desk is <em>clear.</em>
            @elseif($atDeskCount === 1)
                One item <em>awaits your review.</em>
            @else
                {{ $countWord }} items <em>await your review.</em>
            @endif
        </h1>
        <p class="db-subline mt-1.5">
            Routed up through the Secretary. Decisions taken here close the loop or return the item to the originating section.
        </p>
    </div>
    <div class="flex items-center gap-2 shrink-0 pt-1">
        <a href="{{ route('items.index', ['status' => 'at_director']) }}" class="db-btn-secondary">
            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7h18M3 12h18M3 17h18"/></svg>
            Open full register
        </a>
        @if($firstItem)
        <a href="{{ route('items.show', $firstItem) }}" class="db-btn-primary">
            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m9 12 2 2 4-4M3 12a9 9 0 1 0 18 0 9 9 0 0 0-18 0"/></svg>
            Review first item
        </a>
        @endif
    </div>
</div>

{{-- ── KPI cards ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-3 gap-4 mb-5">

    {{-- At your desk --}}
    <div class="db-kpi-card">
        <p class="db-kpi-label">At Your Desk</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">{{ $atDeskCount }}</p>
            <div class="flex flex-col items-end gap-1 pb-1">
                @if($highPriority > 0)
                    <span class="db-priority-tag db-priority-tag--high">{{ $highPriority }} high priority</span>
                @endif
                @if($mediumPriority > 0)
                    <span class="db-priority-tag db-priority-tag--medium">{{ $mediumPriority }} medium</span>
                @endif
            </div>
        </div>
        <p class="db-kpi-sub mt-1.5">Routed by Secretary · awaiting your decision</p>
    </div>

    {{-- Avg wait time --}}
    <div class="db-kpi-card">
        <p class="db-kpi-label">Avg. Wait Time</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">
                {{ $avgWaitDays > 0 ? $avgWaitDays : '—' }}<span class="text-xl font-semibold text-muted-foreground ml-1">{{ $avgWaitDays > 0 ? 'days' : '' }}</span>
            </p>
            <svg viewBox="0 0 80 32" fill="none" class="w-20 h-8 db-sparkline">
                <polyline points="0,26 16,22 32,24 48,16 64,18 80,13" stroke="#8b5cf6" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="db-kpi-sub mt-1.5">
            @if($avgWaitDays > 2)
                <span class="text-destructive font-medium">↑ {{ round($avgWaitDays - 2, 1) }} d</span> vs. last week (target ≤ 2.0 d)
            @else
                Within target ≤ 2.0 d
            @endif
        </p>
    </div>

    {{-- Decisions this month --}}
    <div class="db-kpi-card">
        <p class="db-kpi-label">Decisions This Month</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">{{ $totalDecisions }}</p>
            <div class="flex flex-col items-end gap-1 pb-1">
                @if($approvedCount > 0)
                    <span class="db-priority-tag db-priority-tag--success">{{ $approvedCount }} approved</span>
                @endif
                @if($returnedCount > 0)
                    <span class="db-priority-tag db-priority-tag--warning">{{ $returnedCount }} returned</span>
                @endif
            </div>
        </div>
        <p class="db-kpi-sub mt-1.5">
            {{ $approvalRate }}% approved @if($totalDecisions > 0)· steady rate @endif
        </p>
    </div>

</div>

{{-- ── Queue ──────────────────────────────────────────────────────── --}}
<div class="db-card mb-5 overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-border">
        <div>
            <p class="db-section-label">The Queue</p>
            <h3 class="db-section-title mt-0.5">Awaiting your decision</h3>
        </div>
        <div class="flex items-center gap-3">
            <span class="text-xs bg-muted px-2.5 py-1 rounded-full font-semibold text-muted-foreground">
                {{ $atDeskCount }} {{ Str::plural('item', $atDeskCount) }}
            </span>
            <span class="text-xs text-muted-foreground font-medium">Sort by wait ↓</span>
        </div>
    </div>

    @forelse($queueItems as $item)
        @php
            $days        = (int) ($item->days_waiting ?? 0);
            $routedAt    = $item->routed_at ? \Carbon\Carbon::parse($item->routed_at) : null;
            $hours       = $routedAt ? now()->diffInHours($routedAt) : 0;
            $waitLabel   = $days >= 1 ? $days.'d' : ($hours > 0 ? $hours.'h' : '—');
            $urgency     = $days >= 3 ? '#dc2626' : ($days >= 2 ? '#f59e0b' : '#6366f1');
            $barPct      = min(100, round($days / 4 * 100));
            $barColor    = $days >= 3 ? '#dc2626' : ($days >= 2 ? '#f59e0b' : '#6366f1');
            $priColor    = match($item->priority) { 'high' => '#dc2626', 'medium' => '#f59e0b', default => '#9ca3af' };
        @endphp
        <div class="px-5 py-4 border-b border-border last:border-0 hover:bg-muted/30 transition-colors">
            <div class="flex items-center gap-4">

                {{-- Wait badge --}}
                <div class="db-wait-badge" style="border-color: {{ $urgency }}30">
                    <span class="db-wait-value" style="color:{{ $urgency }}">{{ $waitLabel }}</span>
                    <span class="db-wait-label">Waiting</span>
                </div>

                {{-- Item info --}}
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1 flex-wrap">
                        <a href="{{ route('items.show', $item) }}"
                           class="font-mono text-xs font-semibold text-primary hover:underline">{{ $item->reference_code }}</a>
                        <span class="db-flag db-flag--neutral capitalize">{{ str_replace('_', ' ', $item->item_type) }}</span>
                        <span class="flex items-center gap-1 text-xs font-semibold" style="color:{{ $priColor }}">
                            <span class="size-1.5 rounded-full" style="background:{{ $priColor }}"></span>
                            {{ ucfirst($item->priority) }} priority
                        </span>
                    </div>
                    <p class="text-sm font-semibold text-foreground leading-snug">{{ $item->title }}</p>
                    <p class="text-xs text-muted-foreground mt-0.5">
                        Routed {{ $routedAt ? $routedAt->diffForHumans() : 'recently' }}
                        @if($item->routed_by_name) · by {{ $item->routed_by_name }}@endif
                        @if($item->section) · {{ $item->section->name }}@endif
                    </p>

                    {{-- Progress bar: 0 → target 2d → max 4d --}}
                    <div class="mt-2.5">
                        <div class="h-0.5 rounded-full bg-border overflow-hidden w-full">
                            <div class="h-full rounded-full" style="width:{{ $barPct }}%; background:{{ $barColor }}"></div>
                        </div>
                        <div class="flex justify-between text-[10px] text-muted-foreground mt-0.5 select-none">
                            <span>0</span><span>target 2d</span><span>4d</span>
                        </div>
                    </div>
                </div>

                {{-- Actions --}}
                <div class="flex flex-col items-end gap-1.5 shrink-0">
                    <a href="{{ route('items.show', $item) }}" class="db-btn-primary text-xs px-4 py-1.5">Review →</a>
                    <a href="{{ route('items.show', $item) }}" class="text-xs text-muted-foreground font-medium hover:text-foreground transition-colors">Return for action</a>
                </div>
            </div>
        </div>
    @empty
        <div class="py-14 flex flex-col items-center text-center">
            <div class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-sm font-semibold">Your desk is clear</p>
            <p class="text-sm text-muted-foreground mt-1">No items are waiting for your review.</p>
        </div>
    @endforelse
</div>

{{-- ── Decision rhythm ────────────────────────────────────────────── --}}
<div class="mb-1">
    <p class="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground mb-4">Your decision rhythm</p>
</div>
<div class="grid grid-cols-3 gap-4">

    {{-- Bar chart --}}
    <div class="db-card col-span-2 p-5">
        <p class="db-section-label mb-0.5">Last 14 days</p>
        <h3 class="db-section-title mb-5">Decisions per day</h3>

        @php $maxBar = $decisionsByDay->map(fn($d) => $d['approved'] + $d['returned'])->max() ?: 1; @endphp
        <div class="flex items-end gap-1" style="height: 96px;">
            @foreach($decisionsByDay as $day)
                @php
                    $appPct = $maxBar > 0 ? round($day['approved'] / $maxBar * 100) : 0;
                    $retPct = $maxBar > 0 ? round($day['returned'] / $maxBar * 100) : 0;
                    $total  = $day['approved'] + $day['returned'];
                @endphp
                <div class="flex-1 flex flex-col justify-end items-stretch gap-0" style="height: 96px;">
                    @if($day['returned'] > 0)
                        <div class="rounded-t-sm mx-0.5" style="height:{{ $retPct }}%; min-height:3px; background:#e5c99a;"></div>
                    @endif
                    @if($day['approved'] > 0)
                        <div class="{{ $day['returned'] === 0 ? 'rounded-t-sm' : '' }} mx-0.5" style="height:{{ $appPct }}%; min-height:3px; background:#05499c;"></div>
                    @endif
                    @if($total === 0)
                        <div class="mx-0.5 rounded-sm" style="height:3px; background:#e5e7eb;"></div>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- X-axis labels: show every 3rd --}}
        <div class="flex gap-1 mt-1">
            @foreach($decisionsByDay as $i => $day)
                <div class="flex-1 text-center">
                    @if($i % 3 === 0)
                        <span class="text-[9px] text-muted-foreground">{{ \Carbon\Carbon::parse($day['date'])->format('d M') }}</span>
                    @endif
                </div>
            @endforeach
        </div>

        <div class="flex gap-5 mt-3 pt-3 border-t border-border">
            <div class="flex items-center gap-1.5">
                <div class="size-3 rounded-sm" style="background:#05499c"></div>
                <span class="text-xs text-muted-foreground">Approved / signed</span>
            </div>
            <div class="flex items-center gap-1.5">
                <div class="size-3 rounded-sm" style="background:#e5c99a"></div>
                <span class="text-xs text-muted-foreground">Returned for action</span>
            </div>
        </div>
    </div>

    {{-- By originating section --}}
    <div class="db-card p-5">
        <p class="db-section-label mb-0.5">Where Work Comes From</p>
        <h3 class="db-section-title mb-5">By originating section</h3>

        @if($bySection->isEmpty())
            <p class="text-xs text-muted-foreground">No items in queue.</p>
        @else
            @php
                $maxSec   = $bySection->max('count') ?: 1;
                $totalSec = $bySection->sum('count');
            @endphp
            <div class="space-y-4">
                @foreach($bySection as $sec)
                    @php $pct = $totalSec > 0 ? round($sec['count'] / $totalSec * 100) : 0; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <span class="text-sm font-medium text-foreground">{{ $sec['name'] }}</span>
                            <span class="text-xs text-muted-foreground tabular-nums">{{ $sec['count'] }} · {{ $pct }}%</span>
                        </div>
                        <div class="h-1 rounded-full bg-border overflow-hidden">
                            <div class="h-full rounded-full bg-primary" style="width:{{ round($sec['count'] / $maxSec * 100) }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

</div>

@endsection
