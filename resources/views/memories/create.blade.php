@extends('layouts.app')

@section('title', 'Tambah kenangan · '.$album->name)

@section('content')
    <section class="form-card">
        <a class="back-link" href="{{ route('albums.show', $album) }}">← Kembali ke folder {{ $album->name }}</a>
        <p class="eyebrow">FOLDER {{ mb_strtoupper($album->name) }}</p>
        <h1>Tambah kenangan</h1>
        <p class="muted">Foto dan video disimpan di folder ini dan hanya bisa dilihat olehmu. Kamu bisa memilih beberapa file sekaligus.</p>

        @if ($errors->any())
            <div class="notice notice-error">
                <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('memories.store', $album) }}" enctype="multipart/form-data" class="form-stack" id="memory-form">
            @csrf
            <label>Judul kenangan <span class="field-hint">(dipakai untuk setiap file yang dipilih)</span>
                <input type="text" name="title" value="{{ old('title') }}" maxlength="255" required autofocus>
            </label>
            <label>Jenis kenangan
                <select name="type" id="memory-type" required>
                    <option value="photo" @selected(old('type', 'photo') === 'photo')>Foto</option>
                    <option value="video" @selected(old('type') === 'video')>Video</option>
                    <option value="note" @selected(old('type') === 'note')>Catatan</option>
                </select>
            </label>
            <label id="file-field">Pilih satu atau beberapa file <span class="field-hint">(maks. 1 GB / 1.024 MB per file)</span>
                <input type="file" name="files[]" id="memory-files" multiple accept="image/jpeg,image/png,image/gif,image/webp,video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm">
            </label>
            <label id="note-field">Catatan <span class="field-hint">(wajib untuk jenis catatan)</span>
                <textarea name="note" rows="6" maxlength="20000" placeholder="Tuliskan cerita di balik kenangan ini...">{{ old('note') }}</textarea>
            </label>
            <label>Tanggal kenangan
                <input type="date" name="captured_at" value="{{ old('captured_at') }}">
            </label>
            <div class="form-actions">
                <a class="button button-quiet" href="{{ route('albums.show', $album) }}">Batal</a>
                <button class="button" type="submit">Simpan ke folder</button>
            </div>
        </form>
    </section>
    <script>
        const typeSelect = document.getElementById('memory-type');
        const fileInput = document.getElementById('memory-files');
        const fileField = document.getElementById('file-field');
        const noteField = document.getElementById('note-field');
        const noteInput = noteField.querySelector('textarea');
        const updateFields = () => {
            const isNote = typeSelect.value === 'note';
            fileField.hidden = isNote;
            noteField.hidden = !isNote;
            fileInput.required = !isNote;
            noteInput.required = isNote;
            if (isNote) {
                fileInput.value = '';
            }
        };
        typeSelect.addEventListener('change', updateFields);
        updateFields();
    </script>
@endsection
