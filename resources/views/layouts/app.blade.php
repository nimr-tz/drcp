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
<body class="h-full bg-background text-foreground antialiased" x-data>

<div class="flex min-h-screen">

    {{-- Sidebar --}}
    <aside class="fixed inset-y-0 left-0 z-50 flex w-64 flex-col bg-sidebar text-sidebar-foreground">

        {{-- Brand --}}
        <div class="flex flex-col gap-1 px-6 py-5 border-b border-sidebar-border">
            <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-sidebar-foreground/40">Directorate Workflow</p>
            <h1 class="text-base font-bold leading-tight">{{ config('app.name') }}</h1>
            <p class="text-[11px] text-sidebar-foreground/40 leading-snug">Follow-up control for secretary, director &amp; section heads.</p>
        </div>

        @auth
        {{-- Nav --}}
        <nav class="flex-1 space-y-0.5 px-3 py-4">
            <a href="{{ route('dashboard') }}"
               class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('dashboard') ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/60 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground' }}">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                Dashboard
            </a>
            <a href="{{ route('items.index') }}"
               class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('items.*') ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/60 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground' }}">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                Follow-up items
            </a>
            @if(auth()->user()->isSecretary())
            <a href="{{ route('items.create') }}"
               class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('items.create') ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/60 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground' }}">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                New item
            </a>

            <div class="pt-4 pb-1 px-3">
                <p class="text-[10px] font-semibold uppercase tracking-[0.16em] text-sidebar-foreground/30">Administration</p>
            </div>
            <a href="{{ route('admin.users.index') }}"
               class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.users.*') ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/60 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground' }}">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                Users
            </a>
            <a href="{{ route('admin.sections.index') }}"
               class="flex items-center gap-3 rounded-md px-3 py-2 text-sm font-medium transition-colors
                      {{ request()->routeIs('admin.sections.*') ? 'bg-sidebar-accent text-sidebar-foreground' : 'text-sidebar-foreground/60 hover:bg-sidebar-accent/60 hover:text-sidebar-foreground' }}">
                <svg class="size-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                Sections
            </a>
            @endif
        </nav>

        {{-- User card --}}
        <div class="border-t border-sidebar-border px-3 py-3">
            <div class="flex items-start justify-between gap-2 rounded-md px-3 py-2.5 bg-sidebar-accent/40">
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold truncate">{{ auth()->user()->name }}</p>
                    <p class="text-[11px] text-sidebar-foreground/50 truncate">{{ auth()->user()->role->label() }}</p>
                    @if(auth()->user()->section)
                        <p class="text-[11px] text-sidebar-foreground/50 truncate">{{ auth()->user()->section->name }}</p>
                    @endif
                </div>
                <form method="POST" action="{{ route('logout') }}" class="shrink-0">
                    @csrf
                    <button type="submit" class="text-[11px] text-sidebar-foreground/40 hover:text-sidebar-foreground transition-colors mt-0.5">
                        Sign out
                    </button>
                </form>
            </div>
        </div>
        @endauth
    </aside>

    {{-- Main content --}}
    <div class="flex flex-1 flex-col ml-64">
        <main class="flex-1 px-8 py-8 max-w-7xl w-full mx-auto">

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
