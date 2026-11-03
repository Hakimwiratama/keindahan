@extends('layouts.app')

@section('title', 'Folder kenangan')

@section('content')
    <section class="page-heading">
        <div>
            <p class="eyebrow">ARSIP PRIBADIMU</p>
            <h1>Folder kenangan</h1>
            <p class="muted">Buat folder sendiri sesuai cerita, misalnya Sayang, Kuliah, atau Liburan.</p>
        </div>
        <a class="button" href="{{ route('albums.create') }}">+ Buat folder</a>
    </section>

    @if ($albums->isEmpty())
        <section class="empty-state">
            <div class="empty-icon">▰</div>
            <h2>Mulai dengan membuat folder</h2>
            <p class="muted">Buat folder seperti “Sayang” atau “Kuliah”, lalu simpan banyak foto, video, dan catatan di dalamnya.</p>
            <a class="button" href="{{ route('albums.create') }}">Buat folder pertama</a>
        </section>
    @else
        <section class="album-grid">
            @foreach ($albums as $album)
                <a class="album-card" href="{{ route('albums.show', $album) }}">
                    <div class="album-art">
                        <span class="album-art-icon">▰</span>
                        <span class="album-art-count">{{ $album->memories_count }} kenangan</span>
                    </div>
                    <div class="album-card-body">
                        <h2>{{ $album->name }}</h2>
                        <p>Dibuat {{ $album->created_at->translatedFormat('d F Y') }}</p>
                    </div>
                </a>
            @endforeach
        </section>
    @endif
@endsection
