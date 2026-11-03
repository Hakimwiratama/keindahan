@extends('layouts.app')

@section('title', $memory->title)

@section('content')
    <section class="detail-card">
        <a class="button button-quiet detail-back-button" href="{{ route('albums.show', $memory->album) }}">← Kembali ke folder</a>
        <div class="detail-header">
            <div>
                <p class="eyebrow">{{ ['photo' => 'FOTO', 'video' => 'VIDEO', 'note' => 'CATATAN'][$memory->type] }}</p>
                <h1>{{ $memory->title }}</h1>
                <p class="muted">{{ $memory->captured_at?->translatedFormat('d F Y') ?? 'Disimpan '.$memory->created_at->translatedFormat('d F Y') }}</p>
            </div>
        </div>

        @if ($memory->note)
            <div class="note-content">{{ $memory->note }}</div>
        @endif

        @if ($memory->file_path)
            @if ($memory->type === 'photo')
                <img class="detail-preview" src="{{ route('memories.preview', [$memory->album, $memory]) }}" alt="{{ $memory->title }}">
            @elseif ($memory->type === 'video')
                <video class="detail-preview" controls preload="metadata">
                    <source src="{{ route('memories.preview', [$memory->album, $memory]) }}" type="{{ $memory->mime_type }}">
                    Browser tidak mendukung pemutaran video.
                </video>
            @endif
            <div class="file-panel">
                <span class="file-icon">{{ $memory->type === 'video' ? '▷' : '▧' }}</span>
                <div>
                    <strong>{{ $memory->original_filename }}</strong>
                    <p class="muted">{{ $memory->file_size ? number_format($memory->file_size / 1048576, 2).' MB' : 'File kenangan' }}</p>
                </div>
                <a class="button" href="{{ route('memories.download', [$memory->album, $memory]) }}">Unduh file</a>
            </div>
        @endif

        <form method="POST" action="{{ route('memories.destroy', [$memory->album, $memory]) }}" onsubmit="return confirm('Hapus kenangan ini secara permanen?')">
            @csrf
            @method('DELETE')
            <button class="button button-danger" type="submit">Hapus kenangan</button>
        </form>
    </section>
@endsection
