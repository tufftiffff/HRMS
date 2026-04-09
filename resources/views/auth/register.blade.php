<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register – HRMS</title>
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-header">
        <h1 class="auth-title">Create your account</h1>
        <p class="auth-subtitle">Register to access the HRMS portal.</p>
    </div>

    @if ($errors->any())
        <div class="auth-alert auth-alert-error">
            {{ $errors->first() }}
        </div>
    @endif

    <form class="auth-form" method="POST" action="{{ route('register.submit') }}">
        @csrf

        <div class="form-group">
            <label class="form-label" for="name">Full name</label>
            <input
                id="name"
                type="text"
                name="name"
                class="form-input"
                value="{{ old('name') }}"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email address</label>
            <input
                id="email"
                type="email"
                name="email"
                class="form-input"
                value="{{ old('email') }}"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Password</label>
            <input
                id="password"
                type="password"
                name="password"
                class="form-input"
                required
            >
        </div>

        <div class="form-group">
            <label class="form-label" for="password_confirmation">Confirm password</label>
            <input
                id="password_confirmation"
                type="password"
                name="password_confirmation"
                class="form-input"
                required
            >
        </div>

        <button type="submit" class="auth-button">
            Create account
        </button>
    </form>

    <div class="auth-footer">
        <span>Already registered? <a href="{{ route('login') }}">Sign in</a></span>
    </div>
</div>

</body>
</html>
