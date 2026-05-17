<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Set New Password</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="shell auth-shell">
        <main class="content">
            @if ($errors->any())
                <div class="alert danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <section class="panel auth-panel">
                <div>
                    <p class="eyebrow">Password reset</p>
                    <h2>Set a new password</h2>
                    <p class="muted">Must be at least 10 characters, including uppercase, lowercase, and a number.</p>
                </div>

                <form method="POST" action="{{ route('password.reset.update') }}" class="stack">
                    @csrf
                    <input type="hidden" name="token" value="{{ $token }}">

                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email', $email) }}" required>
                    </label>

                    <label>
                        <span>New password</span>
                        <input type="password" name="password" required autocomplete="new-password">
                    </label>

                    <label>
                        <span>Confirm new password</span>
                        <input type="password" name="password_confirmation" required autocomplete="new-password">
                    </label>

                    <button class="primary-button" type="submit">Set new password</button>
                </form>
            </section>
        </main>
    </div>
</body>
</html>
