@extends('layouts.app')

@section('title', $album->name)

@section('content')
    <section class="page-heading">
        <div>
            <a class="back-link" href="{{ route('albums.index') }}">← Semua folder</a>
            <p class="eyebrow">FOLDER KENANGAN</p>
            <h1>{{ $album->name }}</h1>
            <p class="muted">{{ $memories->total() }} kenangan tersimpan di folder ini.</p>
        </div>
        <div class="album-actions">
            <a class="button" href="{{ route('memories.create', $album) }}">+ Tambah kenangan</a>
            <form method="POST" action="{{ route('albums.destroy', $album) }}" onsubmit="return confirm('Hapus folder beserta seluruh foto, video, dan catatan di dalamnya secara permanen? Tindakan ini tidak dapat dibatalkan.')">
                @csrf
                @method('DELETE')
                <button class="button button-danger" type="submit">Hapus folder</button>
            </form>
        </div>
    </section>

    @if ($memories->isEmpty())
        <section class="empty-state">
            <div class="empty-icon">✦</div>
            <h2>Folder ini masih kosong</h2>
            <p class="muted">Unggah satu atau beberapa foto/video sekaligus, atau simpan catatan di sini.</p>
            <a class="button" href="{{ route('memories.create', $album) }}">Tambah kenangan pertama</a>
        </section>
    @else
        <section class="memory-grid">
            @foreach ($memories as $memory)
                <a class="memory-card" href="{{ route('memories.show', [$album, $memory]) }}">
                    @if ($memory->type === 'photo')
                        <div class="memory-media-frame">
                            <img class="memory-preview" src="{{ route('memories.preview', [$album, $memory]) }}" alt="{{ $memory->title }}" loading="lazy">
                        </div>
                    @elseif ($memory->type === 'video')
                        <div class="memory-media-frame">
                            <video class="memory-preview" preload="metadata" aria-label="{{ $memory->title }}">
                                <source src="{{ route('memories.preview', [$album, $memory]) }}" type="{{ $memory->mime_type }}">
                                Browser tidak mendukung pemutaran video.
                            </video>
                        </div>
                    @else
                        <div class="memory-art memory-art-note">
                            <span>✎</span>
                            <small>CATATAN</small>
                        </div>
                    @endif
                    <div class="memory-card-body">
                        <h2>{{ $memory->title }}</h2>
                        <p>{{ $memory->captured_at?->translatedFormat('d F Y') ?? $memory->created_at->translatedFormat('d F Y') }}</p>
                        @if ($memory->note)
                            <p class="card-note">{{ \Illuminate\Support\Str::limit($memory->note, 105) }}</p>
                        @elseif ($memory->original_filename)
                            <p class="card-note">{{ $memory->original_filename }}</p>
                        @endif
                    </div>
                </a>
            @endforeach
        </section>
        <div class="pagination">{{ $memories->links() }}</div>
    @endif
@endsection
