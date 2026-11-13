<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Memory Vault') · Memory Vault</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body @class([
    'dark-dashboard' => auth()->check() || request()->routeIs('login'),
    'login-transition' => session('login_transition'),
])>
    @if (session('login_transition'))
        <div class="login-transition-overlay" aria-hidden="true">
            <span class="login-light login-light-top-left"><span class="login-orb"></span></span>
            <span class="login-light login-light-top-right"><span class="login-orb"></span></span>
            <span class="login-light login-light-bottom-right"><span class="login-orb"></span></span>
            <span class="login-light login-light-bottom-left"><span class="login-orb"></span></span>
            <span class="login-light-core"></span>
        </div>
    @endif
    @if (auth()->check() || request()->routeIs('login'))
        <div class="snowfall" aria-hidden="true">
            @for ($flake = 0; $flake < 48; $flake++)
                <span style="--flake-left: {{ ($flake * 37) % 100 }}%; --flake-size: {{ 10 + ($flake % 4) * 5 }}px; --flake-duration: {{ 12 + ($flake % 4) * 4 }}s; --flake-delay: -{{ (($flake * 7) % 24) + 1 }}s; --flake-opacity: {{ .45 + ($flake % 4) * .12 }}; --flake-drift: {{ (($flake * 17) % 61) - 30 }}px; --flake-sway: {{ (($flake * 19) % 41) - 20 }}px; --flake-sway-back: {{ 20 - (($flake * 19) % 41) }}px;">❄</span>
            @endfor
        </div>
    @endif
    <header class="topbar">
        <a class="brand" href="{{ auth()->check() ? route('albums.index') : route('login') }}">
            <span class="brand-mark">M</span> Memory Vault
        </a>
        <nav class="navigation">
            @auth
                <span class="welcome">Halo, {{ auth()->user()->name }}</span>
                <a href="{{ route('albums.index') }}">Folder</a>
                <a class="button button-small" href="{{ route('albums.create') }}">+ Folder</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="link-button" type="submit">Keluar</button>
                </form>
            @else
                <a href="{{ route('login') }}">Masuk</a>
                <a class="button button-small" href="{{ route('register') }}">Buat akun</a>
            @endauth
        </nav>
    </header>

    <main class="container">
        @if (session('status'))
            <div class="notice notice-success">{{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <footer class="footer">Ruang pribadi untuk hal-hal yang ingin selalu diingat.</footer>
</body>
</html>
