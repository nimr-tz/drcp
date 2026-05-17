@extends('layouts.app')

@section('content')

{{-- Page header --}}
<div class="flex items-center justify-between mb-6">
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-muted-foreground mb-1">Register</p>
        <h2 class="text-2xl font-bold tracking-tight">Follow-up items</h2>
        <p class="text-sm text-muted-foreground mt-1">Search, filter, and track every follow-up in one place.</p>
    </div>
    @if(auth()->user()->isSecretary())
        <a href="{{ route('items.create') }}" class="btn-primary">
            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
            New item
        </a>
    @endif
</div>

{{-- Filters --}}
<div class="card mb-6">
    <form method="GET" class="p-4">
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-7">
            <div class="col-span-2 sm:col-span-1 xl:col-span-2">
                <input class="form-input" type="text" name="search" value="{{ request('search') }}" placeholder="Reference, title, e-office ref…">
            </div>
            <select class="form-input" name="item_type">
                <option value="">All types</option>
                @foreach($types as $type)
                    <option value="{{ $type }}" @selected(request('item_type') === $type)>{{ \Illuminate\Support\Str::headline($type) }}</option>
                @endforeach
            </select>
            <select class="form-input" name="status">
                <option value="">All statuses</option>
                @foreach($statuses as $status)
                    <option value="{{ $status }}" @selected(request('status') === $status)>{{ \Illuminate\Support\Str::headline($status) }}</option>
                @endforeach
            </select>
            <select class="form-input" name="section_id">
                <option value="">All sections</option>
                @foreach($sections as $section)
                    <option value="{{ $section->id }}" @selected((string) request('section_id') === (string) $section->id)>{{ $section->name }}</option>
                @endforeach
            </select>
            <select class="form-input" name="current_owner_id">
                <option value="">All owners</option>
                @foreach($owners as $owner)
                    <option value="{{ $owner->id }}" @selected((string) request('current_owner_id') === (string) $owner->id)>{{ $owner->name }}</option>
                @endforeach
            </select>
            <div class="flex items-center gap-2">
                <button type="submit" class="btn-primary h-10 px-4 text-sm">Filter</button>
                @if(request()->hasAny(['search','item_type','status','section_id','current_owner_id','date_from','date_to']))
                    <a href="{{ route('items.index') }}" class="btn-outline h-10 px-4 text-sm">Reset</a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- Table --}}
<div class="card overflow-hidden">
    <div class="overflow-x-auto">
        <table class="data-table">
            <thead class="bg-muted/40 border-b border-border">
                <tr>
                    <th class="w-36">Reference</th>
                    <th>Item</th>
                    <th class="w-44">Status</th>
                    <th class="w-36">Owner</th>
                    <th class="w-36">Section</th>
                    <th class="w-24">Priority</th>
                    <th class="w-32">Next follow up</th>
                </tr>
            </thead>
            <tbody>
            @forelse($items as $item)
                <tr>
                    <td>
                        <a href="{{ route('items.show', $item) }}" class="font-mono text-xs font-semibold text-primary hover:underline">
                            {{ $item->reference_code }}
                        </a>
                    </td>
                    <td>
                        <p class="font-medium">{{ $item->title }}</p>
                        <p class="text-xs text-muted-foreground mt-0.5">{{ \Illuminate\Support\Str::headline($item->item_type) }}
                            @if($item->eoffice_reference) · e-Office: {{ $item->eoffice_reference }}@endif
                        </p>
                        <div class="flex flex-wrap gap-1 mt-1.5">
                            @if(!$item->hasPrimaryDocument())
                                <span class="badge-destructive">No main doc</span>
                            @endif
                            @if($item->item_type === 'letter' && !$item->hasSignedResponse())
                                <span class="badge-warning">No signed response</span>
                            @endif
                        </div>
                    </td>
                    <td>
                        <span class="badge-default">{{ $item->status->label() }}</span>
                        @if($item->isOverdue())
                            <span class="badge-destructive ml-1">Overdue</span>
                        @elseif($item->needsAttention())
                            <span class="badge-warning ml-1">Awaiting update</span>
                        @endif
                    </td>
                    <td class="text-sm text-muted-foreground">{{ $item->currentOwner?->name ?? '—' }}</td>
                    <td class="text-sm text-muted-foreground">{{ $item->section?->name ?? '—' }}</td>
                    <td>
                        @php $p = $item->priority @endphp
                        <span @class([
                            'badge',
                            'badge-destructive' => $p === 'high',
                            'badge-warning' => $p === 'medium',
                            'badge-default' => $p === 'low',
                        ])>{{ ucfirst($p) }}</span>
                    </td>
                    <td class="text-sm text-muted-foreground tabular-nums">
                        {{ optional($item->next_follow_up_date)->format('d M Y') ?? '—' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="py-16 text-center">
                        @if(request()->hasAny(['search','item_type','status','section_id','current_owner_id','date_from','date_to']))
                            <p class="text-sm font-semibold">No items match your filters</p>
                            <p class="text-sm text-muted-foreground mt-1">
                                <a href="{{ route('items.index') }}" class="text-primary hover:underline">Clear all filters</a> to see the full register.
                            </p>
                        @else
                            <p class="text-sm font-semibold">No follow-up items yet</p>
                            @if(auth()->user()->isSecretary())
                                <p class="text-sm text-muted-foreground mt-1">
                                    <a href="{{ route('items.create') }}" class="text-primary hover:underline">Log the first follow-up item</a> to get started.
                                </p>
                            @else
                                <p class="text-sm text-muted-foreground mt-1">No items are assigned to you or your section yet.</p>
                            @endif
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if($items->hasPages())
        <div class="border-t border-border px-6 py-4">
            {{ $items->links() }}
        </div>
    @endif
</div>

@endsection
