<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Memory Vault') · Memory Vault</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
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
