<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-background text-foreground antialiased">

<div class="flex min-h-screen"
     x-data="{
         collapsed: localStorage.getItem('sidebar_collapsed') === 'true',
         toggle() { this.collapsed = !this.collapsed; localStorage.setItem('sidebar_collapsed', this.collapsed); }
     }">

    {{-- ── Sidebar ─────────────────────────────────────────────── --}}
    <aside class="sidebar-shell"
           :style="collapsed ? 'width:4rem' : 'width:16rem'">

        {{-- Brand --}}
        <div class="flex items-center gap-3 px-3 py-4 border-b border-sidebar-border min-h-[65px] overflow-hidden">
            <div class="flex size-9 shrink-0 items-center justify-center rounded-lg"
                 style="background: #042e72;">
                <svg class="size-5" viewBox="0 0 20 20" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <rect x="3" y="3" width="6" height="6" rx="1" fill="rgba(255,255,255,0.9)"/>
                    <rect x="11" y="3" width="6" height="6" rx="1" fill="rgba(255,255,255,0.5)"/>
                    <rect x="3" y="11" width="6" height="6" rx="1" fill="rgba(255,255,255,0.5)"/>
                    <rect x="11" y="11" width="6" height="6" rx="1" fill="rgba(255,255,255,0.9)"/>
                </svg>
            </div>
            <div class="min-w-0 overflow-hidden" x-show="!collapsed"
                 x-transition:enter="transition-opacity duration-200 delay-75"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-100"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/40 leading-none whitespace-nowrap">Directorate Workflow</p>
                <h1 class="text-sm font-bold leading-tight mt-0.5 whitespace-nowrap">{{ config('app.name') }}</h1>
            </div>
        </div>

        @auth
        {{-- Nav --}}
        <nav class="flex-1 space-y-0.5 px-2 py-4 overflow-hidden">

            {{-- Dashboard --}}
            <a href="{{ route('dashboard') }}"
               title="Dashboard"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'sidebar-link--active' : '' }}"
               :class="collapsed ? 'justify-center' : ''">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                <span class="sidebar-label" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">Dashboard</span>
            </a>

            {{-- Follow-up items --}}
            <a href="{{ route('items.index') }}"
               title="Follow-up items"
               class="sidebar-link {{ request()->routeIs('items.index') || request()->routeIs('items.show') ? 'sidebar-link--active' : '' }}"
               :class="collapsed ? 'justify-center' : ''">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <span class="sidebar-label flex-1" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">Follow-up items</span>
                @php
                    $activeCount = \App\Models\FollowUpItem::whereNotIn('status', [\App\Enums\ItemStatus::Completed->value, \App\Enums\ItemStatus::Closed->value])
                        ->when(auth()->user()->isHeadOfSection(), fn($q) => $q->where(fn($i) => $i->where('current_owner_id', auth()->id())->orWhere('section_id', auth()->user()->section_id)))
                        ->count();
                @endphp
                @if($activeCount > 0)
                    <span x-show="!collapsed"
                          class="flex h-5 min-w-5 items-center justify-center rounded-full px-1.5 text-[11px] font-semibold shrink-0"
                          style="background: rgba(255,255,255,0.12); color: rgba(255,255,255,0.8)">{{ $activeCount }}</span>
                @endif
            </a>

            @if(auth()->user()->isSecretary())
            {{-- New item --}}
            <a href="{{ route('items.create') }}"
               title="New item"
               class="sidebar-link {{ request()->routeIs('items.create') ? 'sidebar-link--active' : '' }}"
               :class="collapsed ? 'justify-center' : ''">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                <span class="sidebar-label" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">New item</span>
            </a>

            {{-- Admin section divider --}}
            <div class="overflow-hidden" x-show="!collapsed"
                 x-transition:enter="transition-opacity duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 x-transition:leave="transition-opacity duration-75"
                 x-transition:leave-start="opacity-100"
                 x-transition:leave-end="opacity-0">
                <div class="pt-5 pb-1 px-1">
                    <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-white/40">Administration</p>
                </div>
            </div>
            <div x-show="collapsed" class="py-2 px-1">
                <div class="h-px" style="background: rgba(255,255,255,0.08)"></div>
            </div>

            {{-- Users --}}
            <a href="{{ route('admin.users.index') }}"
               title="Users"
               class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'sidebar-link--active' : '' }}"
               :class="collapsed ? 'justify-center' : ''">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                <span class="sidebar-label" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">Users</span>
            </a>

            {{-- Sections --}}
            <a href="{{ route('admin.sections.index') }}"
               title="Sections"
               class="sidebar-link {{ request()->routeIs('admin.sections.*') ? 'sidebar-link--active' : '' }}"
               :class="collapsed ? 'justify-center' : ''">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <span class="sidebar-label" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">Sections</span>
            </a>
            @endif

        </nav>

        {{-- Collapse toggle button --}}
        <div class="px-2 pb-2">
            <button @click="toggle()"
                    class="sidebar-toggle-btn"
                    :class="collapsed ? 'justify-center' : ''"
                    :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'">
                <svg class="size-4 shrink-0 transition-transform duration-300"
                     :class="collapsed ? 'rotate-180' : ''"
                     fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7"/>
                </svg>
                <span class="sidebar-label text-xs" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">Collapse</span>
            </button>
        </div>

        {{-- User card --}}
        <div class="border-t border-sidebar-border px-2 py-3">
            @php
                $authUser     = auth()->user();
                $nameParts    = explode(' ', $authUser->name);
                $initials     = collect($nameParts)->map(fn($p) => strtoupper(substr($p, 0, 1)))->take(2)->join('');
                $avatarColors = ['#2d6a4f','#1e3a5f','#7c3aed','#b45309','#0f766e'];
                $avatarColor  = $avatarColors[crc32($authUser->name) % count($avatarColors)];
            @endphp
            <div class="flex items-center gap-2 rounded-md py-2 overflow-hidden transition-all duration-300"
                 :class="collapsed ? 'justify-center px-0' : 'px-2'"
                 style="background: rgba(255,255,255,0.06);">
                <div class="db-avatar shrink-0" style="background: {{ $avatarColor }}">{{ $initials }}</div>
                <div class="min-w-0 flex-1 overflow-hidden" x-show="!collapsed"
                     x-transition:enter="transition-opacity duration-150"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="transition-opacity duration-75"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0">
                    <p class="text-sm font-semibold truncate leading-tight text-white whitespace-nowrap">{{ $authUser->name }}</p>
                    <p class="text-[11px] text-white/50 truncate whitespace-nowrap">{{ $authUser->role->label() }}@if($authUser->section) · {{ $authUser->section->name }}@endif</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0" x-show="!collapsed"
                      x-transition:enter="transition-opacity duration-150"
                      x-transition:enter-start="opacity-0"
                      x-transition:enter-end="opacity-100"
                      x-transition:leave="transition-opacity duration-75"
                      x-transition:leave-start="opacity-100"
                      x-transition:leave-end="opacity-0">
                    @csrf
                    <button type="submit" title="Sign out"
                            class="flex items-center justify-center size-7 rounded-md text-white/40 hover:text-white hover:bg-white/10 transition-colors">
                        <svg class="size-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </aside>

    {{-- ── Main content ─────────────────────────────────────────── --}}
    <div class="flex flex-1 flex-col transition-all duration-300"
         :style="collapsed ? 'margin-left:4rem' : 'margin-left:16rem'">
        <main class="flex-1 px-8 py-8 max-w-[1400px] w-full mx-auto">

            @if(session('status'))
                <div class="mb-6 flex items-center gap-3 rounded-lg border border-primary/20 bg-primary/5 px-4 py-3 text-sm text-primary">
                    <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ session('status') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 rounded-lg border border-destructive/20 bg-destructive/5 px-4 py-3 text-sm text-destructive">
                    <p class="font-semibold mb-1">Please review the highlighted inputs.</p>
                    <ul class="list-disc list-inside space-y-0.5 text-destructive/80">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

</body>
</html>
