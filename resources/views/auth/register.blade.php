@extends('layouts.app')

@section('title', 'Buat akun')

@section('content')
    <section class="auth-card">
        <p class="eyebrow">MULAI MENYIMPAN CERITA</p>
        <h1>Buat ruang pribadimu</h1>
        <p class="muted">Daftar untuk menyimpan foto, video, dan catatan yang berarti bagimu.</p>

        @if (session('registration_session_expired'))
            <div class="notice notice-error" role="alert">
                Sesi formulir berakhir atau halaman sudah terlalu lama terbuka. Nama dan email tetap tersimpan; silakan kirim formulir ini sekali lagi.
            </div>
        @endif

        @if ($errors->any())
            <div class="notice notice-error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.store') }}" class="form-stack">
            @csrf
            <label>Nama
                <input type="text" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>
            </label>
            <label>Email
                <input type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>
            </label>
            <label>Kata sandi
                <input type="password" name="password" autocomplete="new-password" required>
            </label>
            <label>Ulangi kata sandi
                <input type="password" name="password_confirmation" autocomplete="new-password" required>
            </label>
            <button class="button button-full" type="submit">Buat akun</button>
        </form>
        <p class="form-foot">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></p>
    </section>
@endsection
