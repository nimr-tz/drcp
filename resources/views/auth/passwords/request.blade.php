<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reset Password</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="shell auth-shell">
        <main class="content">
            @if (session('status'))
                <div class="alert success">{{ session('status') }}</div>
            @endif

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
                    <h2>Forgot your password?</h2>
                    <p class="muted">Enter your email address and we will send you a reset link.</p>
                </div>

                <form method="POST" action="{{ route('password.request.send') }}" class="stack">
                    @csrf
                    <label>
                        <span>Email</span>
                        <input type="email" name="email" value="{{ old('email') }}" required autofocus>
                    </label>

                    <button class="primary-button" type="submit">Send reset link</button>
                </form>

                <p class="muted"><a href="{{ route('login') }}">Back to sign in</a></p>
            </section>
        </main>
    </div>
</body>
</html>
