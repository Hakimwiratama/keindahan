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

        @if ($memory->file_path && in_array($memory->type, ['photo', 'video'], true))
            <div
                class="media-viewer"
                @if ($mediaCount > 1)
                    data-media-viewer
                    data-previous-url="{{ $previousMemoryId ? route('memories.show', [$memory->album_id, $previousMemoryId]) : '' }}"
                    data-next-url="{{ $nextMemoryId ? route('memories.show', [$memory->album_id, $nextMemoryId]) : '' }}"
                @endif
            >
                @if ($previousMemoryId)
                    <a class="media-nav-arrow media-nav-previous" data-media-navigation rel="prev" href="{{ route('memories.show', [$memory->album_id, $previousMemoryId]) }}" aria-label="Media sebelumnya"><span aria-hidden="true">‹</span></a>
                @endif
                @if ($mediaCount > 1)
                    <span class="media-position" aria-label="Media {{ $mediaPosition }} dari {{ $mediaCount }}">{{ $mediaPosition }} / {{ $mediaCount }}</span>
                @endif
                @if ($nextMemoryId)
                    <a class="media-nav-arrow media-nav-next" data-media-navigation rel="next" href="{{ route('memories.show', [$memory->album_id, $nextMemoryId]) }}" aria-label="Media berikutnya"><span aria-hidden="true">›</span></a>
                @endif
                @if ($memory->type === 'photo')
                    <img class="detail-preview" src="{{ route('memories.preview', [$memory->album, $memory]) }}" alt="{{ $memory->title }}">
                @elseif ($memory->type === 'video')
                    <video class="detail-preview" controls preload="metadata">
                        <source src="{{ route('memories.preview', [$memory->album, $memory]) }}" type="{{ $memory->mime_type }}">
                        Browser tidak mendukung pemutaran video.
                    </video>
                @endif
            </div>
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

    @if ($mediaCount > 1)
        <script>
            (() => {
                let navigationPending = false;

                const getViewer = () => document.querySelector('[data-media-viewer]');

                const initializeSwipe = (viewer) => {
                    if (!viewer || viewer.dataset.swipeBound) return;
                    viewer.dataset.swipeBound = 'true';
                    let touchStart = null;

                    viewer.addEventListener('touchstart', (event) => {
                        if (event.touches.length !== 1) return;
                        touchStart = { x: event.touches[0].clientX, y: event.touches[0].clientY };
                    }, { passive: true });

                    viewer.addEventListener('touchend', (event) => {
                        if (!touchStart || event.changedTouches.length !== 1) return;
                        const deltaX = event.changedTouches[0].clientX - touchStart.x;
                        const deltaY = event.changedTouches[0].clientY - touchStart.y;
                        touchStart = null;

                        if (Math.abs(deltaX) < 55 || Math.abs(deltaX) < Math.abs(deltaY) * 1.25) return;
                        const url = deltaX > 0 ? viewer.dataset.previousUrl : viewer.dataset.nextUrl;
                        if (url) swapMedia(url);
                    }, { passive: true });
                };

                const swapMedia = async (url, addHistory = true) => {
                    if (navigationPending) return;
                    const currentCard = document.querySelector('.detail-card');
                    const currentViewer = getViewer();
                    if (!currentCard || !currentViewer) {
                        window.location.assign(url);
                        return;
                    }

                    navigationPending = true;
                    const viewerTop = currentViewer.getBoundingClientRect().top;

                    try {
                        const response = await fetch(url, {
                            headers: { Accept: 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                        });
                        if (!response.ok) {
                            window.location.assign(url);
                            return;
                        }

                        const documentResponse = new DOMParser().parseFromString(await response.text(), 'text/html');
                        const nextCard = documentResponse.querySelector('.detail-card');
                        if (!nextCard) {
                            window.location.assign(url);
                            return;
                        }

                        currentCard.replaceWith(nextCard);
                        document.title = documentResponse.title;
                        if (addHistory) history.pushState({ mediaViewer: true }, '', url);
                        initializeSwipe(getViewer());

                        const restoreViewerPosition = () => {
                            const viewer = getViewer();
                            if (viewer) {
                                window.scrollTo(0, window.scrollY + viewer.getBoundingClientRect().top - viewerTop);
                            }
                        };
                        requestAnimationFrame(restoreViewerPosition);

                        const media = nextCard.querySelector('.media-viewer img, .media-viewer video');
                        if (media instanceof HTMLImageElement && !media.complete) {
                            media.addEventListener('load', restoreViewerPosition, { once: true });
                        } else if (media instanceof HTMLVideoElement && media.readyState === 0) {
                            media.addEventListener('loadedmetadata', restoreViewerPosition, { once: true });
                        }
                    } catch (error) {
                        console.error('Gagal mengganti media tanpa memuat ulang halaman.', error);
                        window.location.assign(url);
                    } finally {
                        navigationPending = false;
                    }
                };

                initializeSwipe(getViewer());

                document.addEventListener('click', (event) => {
                    const link = event.target instanceof Element
                        ? event.target.closest('a[data-media-navigation]')
                        : null;
                    if (!link || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                    if (link.origin !== window.location.origin) return;

                    event.preventDefault();
                    swapMedia(link.href);
                });

                document.addEventListener('keydown', (event) => {
                    if (event.altKey || event.ctrlKey || event.metaKey || event.defaultPrevented) return;
                    if (event.target instanceof Element && event.target.closest('input, textarea, select, button, a, video, audio, [contenteditable="true"]')) return;

                    const viewer = getViewer();
                    if (!viewer) return;
                    const url = event.key === 'ArrowLeft'
                        ? viewer.dataset.previousUrl
                        : event.key === 'ArrowRight'
                            ? viewer.dataset.nextUrl
                            : null;
                    if (!url) return;

                    event.preventDefault();
                    swapMedia(url);
                });

                window.addEventListener('popstate', () => swapMedia(window.location.href, false));
            })();
        </script>
    @endif
@endsection
