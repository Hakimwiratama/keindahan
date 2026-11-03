@extends('layouts.app')

@section('title', 'Buat folder')

@section('content')
    <section class="form-card">
        <a class="back-link" href="{{ route('albums.index') }}">← Kembali ke folder</a>
        <p class="eyebrow">FOLDER BARU</p>
        <h1>Buat folder kenangan</h1>
        <p class="muted">Beri nama folder sesuai ceritamu, misalnya “Sayang”, “Kuliah”, atau “Liburan”.</p>

        @if ($errors->any())
            <div class="notice notice-error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('albums.store') }}" class="form-stack">
            @csrf
            <label>Nama folder
                <input type="text" name="name" value="{{ old('name') }}" maxlength="80" placeholder="Contoh: Sayang" required autofocus>
            </label>
            <div class="form-actions">
                <a class="button button-quiet" href="{{ route('albums.index') }}">Batal</a>
                <button class="button" type="submit">Buat folder</button>
            </div>
        </form>
    </section>
@endsection
