@extends('layouts.app')

@section('title', 'Section Dashboard')

@section('content')
@php
    use Illuminate\Support\Str;
    $hour         = now()->hour;
    $greeting     = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    $firstName    = explode(' ', $user->name)[0];
    $sectionName  = $user->section?->name ?? 'Your section';
    $memberCount  = $officerWorkloads->count();

    $headline = $sectionOverdue === 0
        ? 'The section is <em>on schedule.</em>'
        : $sectionOverdue.' '.Str::plural('item', $sectionOverdue).' <em>need attention.</em>';

    $subline  = "{$sectionActive} active ".Str::plural('item', $sectionActive)." across {$memberCount} ".Str::plural('officer', $memberCount).'.';
    $subline .= $sectionOverdue === 0
        ? ' No overdue items in your section.'
        : " {$sectionOverdue} ".Str::plural('item', $sectionOverdue).' overdue.';

    $avatarPalette = ['#2d6a4f','#1e3a5f','#7c3aed','#b45309','#0f766e','#be185d'];

    $pipelineColors = [
        'assigned'                  => '#6366f1',
        'in_progress'               => '#10b981',
        'waiting_external_response' => '#f59e0b',
        'at_director'               => '#8b5cf6',
        'returned_for_action'       => '#f97316',
    ];
    $pipelineLabels = [
        'assigned'                  => 'Assigned',
        'in_progress'               => 'In progress',
        'waiting_external_response' => 'Waiting',
        'at_director'               => 'At Director',
        'returned_for_action'       => 'Returned',
    ];
@endphp

{{-- ── Page header ──────────────────────────────────────────────── --}}
<div class="flex items-start justify-between mb-6 gap-6">
    <div class="min-w-0">
        <p class="text-xs font-semibold uppercase tracking-[0.15em] text-muted-foreground mb-1">
            {{ $sectionName }} · {{ now()->format('l d F') }}
        </p>
        <h1 class="db-headline">{{ $greeting }}, <em>{{ $firstName }}.</em></h1>
        <p class="db-subline mt-1.5">{!! $headline !!} {{ $subline }}</p>
    </div>
    <div class="flex items-center gap-2 shrink-0 pt-1">
        <a href="{{ route('items.index', ['section_id' => $user->section_id]) }}" class="db-btn-secondary">
            <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35"/></svg>
            Search the section…
        </a>
        <a href="{{ route('items.index', ['section_id' => $user->section_id]) }}" class="db-btn-secondary">
            + Reassign workload
        </a>
    </div>
</div>

{{-- ── KPI cards ─────────────────────────────────────────────────── --}}
<div class="grid grid-cols-4 gap-4 mb-5">

    {{-- Section active --}}
    <div class="db-kpi-card">
        <p class="db-kpi-label">Section Active</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">{{ $sectionActive }}</p>
            <div class="flex -space-x-1.5 pb-1">
                @foreach($officerWorkloads->take(4) as $officer)
                    @php
                        $ini   = collect(explode(' ', $officer['user']->name))->filter()->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                        $color = $avatarPalette[crc32($officer['user']->name) % count($avatarPalette)];
                    @endphp
                    <div class="db-avatar db-avatar--sm border-2 border-white" style="background:{{ $color }}">{{ $ini }}</div>
                @endforeach
            </div>
        </div>
        <p class="db-kpi-sub mt-1.5">
            {{ $memberCount }} {{ Str::plural('officer', $memberCount) }} ·
            avg load {{ ($sectionActive > 0 && $memberCount > 0) ? min(100, round($sectionActive / $memberCount / 15 * 100)) : 0 }}%
        </p>
    </div>

    {{-- Assigned to me --}}
    <div class="db-kpi-card {{ $myOverdue > 0 ? 'db-kpi-card--danger' : '' }}">
        <p class="db-kpi-label">Assigned to Me</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value {{ $myOverdue > 0 ? 'text-destructive' : '' }}">{{ $myItemCount }}</p>
            <div class="flex flex-col items-end gap-1 pb-1">
                @if($myOverdue > 0)
                    <span class="db-priority-tag db-priority-tag--high">{{ $myOverdue }} overdue</span>
                @endif
                @if($myStale > 0)
                    <span class="db-priority-tag db-priority-tag--warning">{{ $myStale }} awaiting update</span>
                @endif
            </div>
        </div>
        <p class="db-kpi-sub mt-1.5">Highest load in the section</p>
    </div>

    {{-- Completed this month --}}
    <div class="db-kpi-card">
        <p class="db-kpi-label">Completed ({{ now()->format('M') }})</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value">{{ $completedThisMonth }}</p>
            <svg viewBox="0 0 80 32" fill="none" class="w-20 h-8 db-sparkline">
                <polyline points="0,28 16,24 32,20 48,18 64,14 80,10" stroke="var(--primary)" stroke-width="1.5" fill="none" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <p class="db-kpi-sub mt-1.5">
            @if($completedChange > 0)
                <span class="text-primary font-medium">↑ {{ $completedChange }}%</span> vs. {{ now()->subMonth()->format('M') }}
            @elseif($completedChange < 0)
                <span class="text-destructive font-medium">↓ {{ abs($completedChange) }}%</span> vs. {{ now()->subMonth()->format('M') }}
            @else
                Same as {{ now()->subMonth()->format('M') }}
            @endif
        </p>
    </div>

    {{-- Avg closure time --}}
    <div class="db-kpi-card {{ $avgClosureDays > 3 ? 'db-kpi-card--warning' : '' }}">
        <p class="db-kpi-label">Avg. Closure Time</p>
        <div class="flex items-end justify-between mt-2">
            <p class="db-kpi-value {{ $avgClosureDays > 3 ? 'text-warning-foreground' : '' }}">
                {{ $avgClosureDays > 0 ? $avgClosureDays : '—' }}<span class="text-xl font-semibold text-muted-foreground ml-1">{{ $avgClosureDays > 0 ? 'd' : '' }}</span>
            </p>
        </div>
        <p class="db-kpi-sub mt-1.5">Target ≤ 3.0 d</p>
        @if($avgClosureDays > 0)
            <p class="db-kpi-sub font-medium {{ $avgClosureDays <= 3 ? 'text-primary' : 'text-warning-foreground' }}">
                {{ $avgClosureDays <= 3 ? '↓ On target' : '↑ Above target' }}
            </p>
        @endif
    </div>

</div>

{{-- ── Workload table + Pipeline + Hygiene ─────────────────────── --}}
<div class="grid grid-cols-3 gap-4 mb-5">

    {{-- Workload by officer --}}
    <div class="db-card col-span-2 overflow-hidden">
        <div class="flex items-center justify-between px-5 py-4 border-b border-border">
            <div>
                <p class="db-section-label">{{ $sectionName }}</p>
                <h3 class="db-section-title mt-0.5">Workload by officer</h3>
            </div>
            <span class="text-xs text-muted-foreground">As of {{ now()->format('H:i') }}</span>
        </div>

        <div class="px-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-border">
                        <th class="text-left py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide">Officer</th>
                        <th class="text-center py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-16">Active</th>
                        <th class="text-center py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-20">Overdue</th>
                        <th class="text-center py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-24">Done ({{ now()->format('M') }})</th>
                        <th class="text-right py-3 text-xs font-semibold text-muted-foreground uppercase tracking-wide w-36">Load</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border">
                    @forelse($officerWorkloads as $row)
                        @php
                            $ini       = collect(explode(' ', $row['user']->name))->filter()->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                            $color     = $avatarPalette[crc32($row['user']->name) % count($avatarPalette)];
                            $loadColor = $row['load'] >= 80 ? '#dc2626' : ($row['load'] >= 60 ? '#f59e0b' : '#10b981');
                        @endphp
                        <tr class="hover:bg-muted/30 transition-colors">
                            <td class="py-3">
                                <div class="flex items-center gap-3">
                                    <div class="db-avatar" style="background:{{ $color }}">{{ $ini }}</div>
                                    <div>
                                        <p class="font-semibold text-foreground text-sm leading-tight">{{ $row['user']->name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ $row['user']->role->label() }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="py-3 text-center font-semibold tabular-nums">{{ $row['active'] }}</td>
                            <td class="py-3 text-center">
                                @if($row['overdue'] > 0)
                                    <span class="font-semibold text-destructive tabular-nums">{{ $row['overdue'] }}</span>
                                @else
                                    <span class="text-muted-foreground">—</span>
                                @endif
                            </td>
                            <td class="py-3 text-center font-semibold tabular-nums">{{ $row['done'] }}</td>
                            <td class="py-3">
                                <div class="flex items-center justify-end gap-2">
                                    <div class="db-load-bar">
                                        <div class="db-load-fill" style="width:{{ $row['load'] }}%; background:{{ $loadColor }}"></div>
                                    </div>
                                    <span class="text-xs font-semibold tabular-nums w-8 text-right" style="color:{{ $loadColor }}">{{ $row['load'] }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-sm text-muted-foreground">No officers assigned to this section.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Right column: Pipeline + Document hygiene --}}
    <div class="flex flex-col gap-4">

        {{-- Section pipeline --}}
        <div class="db-card p-5 flex-1">
            <p class="db-section-label">Section Pipeline</p>
            <h3 class="db-section-title mt-0.5 mb-4">Status distribution</h3>

            <div class="space-y-2.5">
                @foreach($sectionPipeline as $status => $count)
                    <div class="flex items-center gap-3">
                        <div class="w-0.5 h-4 rounded-full shrink-0" style="background:{{ $pipelineColors[$status] ?? '#9ca3af' }}"></div>
                        <span class="text-sm flex-1 text-foreground">{{ $pipelineLabels[$status] ?? $status }}</span>
                        <span class="text-sm font-semibold tabular-nums">{{ $count }}</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Document hygiene --}}
        <div class="db-card p-5">
            <p class="db-section-label">Health</p>
            <h3 class="db-section-title mt-0.5 mb-4">Document hygiene</h3>

            <div class="space-y-2.5">
                <div class="flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Items without main document</span>
                    <span class="text-sm font-semibold tabular-nums {{ $sectionMissingDoc > 0 ? 'text-warning-foreground' : 'text-muted-foreground' }}">{{ $sectionMissingDoc }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Letters without signed response</span>
                    <span class="text-sm font-semibold tabular-nums {{ $sectionMissingResponse > 0 ? 'text-warning-foreground' : 'text-muted-foreground' }}">{{ $sectionMissingResponse }}</span>
                </div>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-muted-foreground">Stale (no update 3+ days)</span>
                    <span class="text-sm font-semibold tabular-nums {{ $sectionStale > 0 ? 'text-warning-foreground' : 'text-muted-foreground' }}">{{ $sectionStale }}</span>
                </div>
            </div>
        </div>

    </div>

</div>

{{-- ── Section work in flight ─────────────────────────────────────── --}}
<div class="db-card overflow-hidden">
    <div class="flex items-center justify-between px-5 py-4 border-b border-border">
        <h3 class="db-section-title">Section work in flight</h3>
        <a href="{{ route('items.index', ['section_id' => $user->section_id]) }}"
           class="text-xs text-muted-foreground hover:text-foreground transition-colors font-medium">
            Open section register →
        </a>
    </div>

    {{-- Table header --}}
    <div class="db-register-header db-register-header--section">
        <div>Reference</div>
        <div>Item</div>
        <div>Status</div>
        <div>Owner</div>
        <div>Priority</div>
        <div>Next Follow-up</div>
    </div>

    <div class="divide-y divide-border">
    @forelse($sectionItems as $item)
        @php
            $isOverdue   = $item->isOverdue();
            $isStale     = !$isOverdue && $item->needsAttention();
            $priColor    = match($item->priority) { 'high' => '#dc2626', 'medium' => '#f59e0b', default => '#9ca3af' };
            $ownerName   = $item->currentOwner?->name ?? '';
            $ini         = collect(explode(' ', $ownerName))->filter()->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
            $color       = $avatarPalette[crc32($ownerName) % count($avatarPalette)];
            $statusClass = match($item->status->value) {
                'assigned'                  => 'status-assigned',
                'in_progress'               => 'status-in-progress',
                'waiting_external_response' => 'status-waiting',
                'at_director'               => 'status-at-director',
                'returned_for_action'       => 'status-returned',
                default                     => 'status-new',
            };
            $statusShort = match($item->status->value) {
                'assigned'                  => 'Assigned',
                'in_progress'               => 'In Progress',
                'waiting_external_response' => 'Waiting Response',
                'at_director'               => 'At Director',
                'returned_for_action'       => 'Returned',
                default                     => $item->status->label(),
            };
        @endphp
        <div class="db-register-row db-register-row--section {{ $isOverdue ? 'db-register-row--overdue' : '' }}">

            {{-- Reference --}}
            <div>
                <a href="{{ route('items.show', $item) }}"
                   class="font-mono text-xs font-semibold text-primary hover:underline">{{ $item->reference_code }}</a>
                <p class="text-[11px] text-muted-foreground capitalize mt-0.5">{{ str_replace('_', ' ', $item->item_type) }}</p>
                <div class="flex flex-wrap gap-1 mt-1">
                    @if($isStale)
                        <span class="db-flag db-flag--warning">No update {{ now()->diffInDays($item->last_updated_at ?? $item->created_at) }}d</span>
                    @endif
                    @if($item->item_type === 'letter' && !$item->hasSignedResponse())
                        <span class="db-flag db-flag--warning">No signed response</span>
                    @endif
                </div>
            </div>

            {{-- Title --}}
            <div class="min-w-0">
                <p class="text-sm font-medium text-foreground leading-snug">{{ $item->title }}</p>
            </div>

            {{-- Status --}}
            <div>
                <span class="status-badge {{ $statusClass }}">
                    <span class="status-dot"></span>{{ $statusShort }}
                </span>
            </div>

            {{-- Owner --}}
            <div class="flex items-center gap-2">
                @if($item->currentOwner)
                    <div class="db-avatar" style="background:{{ $color }}">{{ $ini }}</div>
                    <span class="text-xs font-medium text-foreground truncate">{{ $item->currentOwner->name }}</span>
                @else
                    <span class="text-xs text-muted-foreground">—</span>
                @endif
            </div>

            {{-- Priority --}}
            <div>
                <span class="text-xs font-semibold" style="color:{{ $priColor }}">· {{ ucfirst($item->priority) }}</span>
            </div>

            {{-- Next follow-up --}}
            <div>
                @if($item->next_follow_up_date)
                    <span class="text-xs tabular-nums {{ $isOverdue ? 'text-destructive font-semibold' : 'text-muted-foreground' }}">
                        {{ $item->next_follow_up_date->format('d M') }}
                    </span>
                @else
                    <span class="text-xs text-muted-foreground">—</span>
                @endif
            </div>

        </div>
    @empty
        <div class="py-12 flex flex-col items-center text-center">
            <div class="size-10 rounded-full bg-primary/10 text-primary flex items-center justify-center mb-3">
                <svg class="size-5" fill="none" stroke="currentColor" stroke-width="1.75" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p class="text-sm font-semibold">No active items in your section</p>
        </div>
    @endforelse
    </div>

    <div class="px-5 py-3 border-t border-border" style="background:color-mix(in oklch, var(--muted) 40%, transparent)">
        <p class="text-[11px] text-muted-foreground">
            {{ $sectionName }} view · {{ $memberCount }} {{ Str::plural('officer', $memberCount) }} · {{ $sectionActive }} active {{ Str::plural('item', $sectionActive) }}
            · v{{ config('app.version', '2.4') }} · live · {{ now()->format('H:i') }}
        </p>
    </div>
</div>

@endsection
