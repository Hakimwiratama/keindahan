@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <section class="auth-card">
        <p class="eyebrow">SELAMAT DATANG KEMBALI</p>
        <h1>Masuk ke ruang kenanganmu</h1>
        <p class="muted">Semua momenmu tersimpan aman dan hanya dapat diakses oleh akunmu.</p>

        @if (session('login_session_expired'))
            <div class="notice notice-error" role="alert">
                Sesi login berakhir atau halaman sudah terlalu lama terbuka. Silakan kirim formulir sekali lagi.
            </div>
        @endif

        @if ($errors->any())
            <div class="notice notice-error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="form-stack">
            @csrf
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus>
            </label>
            <label>Kata sandi
                <input type="password" name="password" autocomplete="current-password" required>
            </label>
            <label class="checkbox-label">
                <input type="checkbox" name="remember" value="1"> Ingat saya
            </label>
            <button class="button button-full" type="submit">Masuk</button>
        </form>
        <p class="form-foot">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></p>
    </section>
@endsection
