<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full antialiased" style="font-family: var(--font-sans)">

<div class="flex min-h-screen">

    {{-- ── Left panel · Branding ─────────────────────────────────── --}}
    <div class="login-brand-panel hidden lg:flex lg:w-5/12 xl:w-[42%]">

        {{-- Decorative blobs --}}
        <div class="login-blob login-blob-1"></div>
        <div class="login-blob login-blob-2"></div>
        <div class="login-blob login-blob-3"></div>

        {{-- Content --}}
        <div class="relative z-10 flex flex-col justify-between h-full p-12">

            {{-- Top: wordmark --}}
            <div>
                <p class="text-[10px] font-bold uppercase tracking-[0.22em] text-sidebar-foreground/40 mb-1">
                    Directorate Workflow
                </p>
                <h1 class="text-2xl font-bold text-sidebar-foreground tracking-tight">
                    {{ config('app.name') }}
                </h1>
            </div>

            {{-- Middle: hero text + features --}}
            <div class="space-y-10">
                <div>
                    <h2 class="text-3xl font-bold text-sidebar-foreground leading-tight mb-4">
                        Follow-up control<br>for every matter.
                    </h2>
                    <p class="text-sm text-sidebar-foreground/55 leading-relaxed max-w-xs">
                        One register for letters, calls, meetings, and internal tasks — tracked from assignment through director-level sign-off.
                    </p>
                </div>

                <ul class="space-y-5">
                    <li class="flex items-start gap-4">
                        <div class="login-feature-icon">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-sidebar-foreground">Complete audit trail</p>
                            <p class="text-xs text-sidebar-foreground/50 mt-0.5 leading-relaxed">Every transfer, update, and closure logged with timestamp and actor.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <div class="login-feature-icon">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-sidebar-foreground">Role-based access</p>
                            <p class="text-xs text-sidebar-foreground/50 mt-0.5 leading-relaxed">Secretary, director, and section heads each see exactly what they need.</p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <div class="login-feature-icon">
                            <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-sidebar-foreground">Document management</p>
                            <p class="text-xs text-sidebar-foreground/50 mt-0.5 leading-relaxed">Attach, version, and flag primary documents and signed responses.</p>
                        </div>
                    </li>
                </ul>
            </div>

            {{-- Bottom: footer --}}
            <p class="text-[11px] text-sidebar-foreground/25 tracking-wide">
                For authorised personnel only · {{ config('app.name') }}
            </p>

        </div>
    </div>

    {{-- ── Right panel · Sign-in form ───────────────────────────── --}}
    <div class="flex flex-1 flex-col items-center justify-center bg-background px-6 py-12">

        {{-- Mobile wordmark (hidden on large screens) --}}
        <div class="mb-8 text-center lg:hidden">
            <p class="text-[10px] font-bold uppercase tracking-[0.2em] text-muted-foreground mb-1">Directorate Workflow</p>
            <h1 class="text-xl font-bold text-foreground">{{ config('app.name') }}</h1>
        </div>

        <div class="w-full max-w-sm">

            {{-- Form header --}}
            <div class="mb-8">
                <h2 class="text-2xl font-bold tracking-tight text-foreground">Welcome back</h2>
                <p class="text-sm text-muted-foreground mt-1.5">Sign in to access the follow-up register.</p>
            </div>

            {{-- Error alert --}}
            @if($errors->any())
                <div class="mb-6 flex items-start gap-3 rounded-lg border border-destructive/20 bg-destructive/5 px-4 py-3">
                    <svg class="size-4 text-destructive shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <p class="text-sm text-destructive">{{ $errors->first() }}</p>
                </div>
            @endif

            {{-- Form --}}
            <form method="POST" action="{{ route('login.store') }}" class="space-y-5">
                @csrf

                <div class="space-y-1.5">
                    <label class="form-label" for="email">Email address</label>
                    <input id="email"
                           class="form-input login-input @error('email') border-destructive @enderror"
                           type="email" name="email" value="{{ old('email') }}"
                           placeholder="you@example.com"
                           required autofocus autocomplete="email">
                </div>

                <div class="space-y-1.5">
                    <div class="flex items-center justify-between">
                        <label class="form-label" for="password">Password</label>
                        @if(Route::has('password.request'))
                            <a href="{{ route('password.request') }}"
                               class="text-xs text-muted-foreground hover:text-primary transition-colors">
                                Forgot password?
                            </a>
                        @endif
                    </div>
                    <input id="password"
                           class="form-input login-input"
                           type="password" name="password"
                           placeholder="••••••••"
                           required autocomplete="current-password">
                </div>

                <div class="flex items-center gap-2.5">
                    <input id="remember" type="checkbox" name="remember" value="1"
                           class="size-4 rounded border-input accent-primary cursor-pointer">
                    <label for="remember" class="text-sm text-muted-foreground cursor-pointer select-none">
                        Keep me signed in
                    </label>
                </div>

                <button type="submit" class="btn-primary w-full justify-center h-11 text-sm">
                    Sign in
                    <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7l5 5m0 0l-5 5m5-5H6"/></svg>
                </button>

            </form>

            {{-- Footer --}}
            <p class="mt-8 text-center text-xs text-muted-foreground/60">
                Access is restricted to authorised staff only.
            </p>

        </div>
    </div>

</div>

</body>
</html>
