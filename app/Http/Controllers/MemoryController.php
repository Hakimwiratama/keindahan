<?php

namespace App\Http\Controllers;

use App\Models\Album;
use App\Models\Memory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MemoryController extends Controller
{
    public function create(Request $request, int $album): View
    {
        $album = $request->user()->albums()->findOrFail($album);
        $this->authorize('view', $album);

        return view('memories.create', compact('album'));
    }

    public function store(Request $request, int $album): RedirectResponse
    {
        $album = $request->user()->albums()->findOrFail($album);
        $this->authorize('view', $album);

        $type = $request->input('type');
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::in(['photo', 'video', 'note'])],
            'captured_at' => ['nullable', 'date'],
        ];

        if ($type === 'note') {
            $rules['note'] = ['required', 'string', 'max:20000'];
            $rules['files'] = ['prohibited'];
        } else {
            $fileRules = ['required', 'file', 'max:1048576'];

            if ($type === 'photo') {
                $fileRules[] = 'mimes:jpg,jpeg,png,gif,webp';
            } elseif ($type === 'video') {
                $fileRules[] = 'mimes:mp4,mov,avi,mkv,webm';
            }

            $rules['note'] = ['nullable', 'string', 'max:20000'];
            $rules['files'] = ['required', 'array', 'min:1'];
            $rules['files.*'] = $fileRules;
        }

        foreach ((array) $request->file('files', []) as $file) {
            if (! $file instanceof UploadedFile || $file->isValid()) {
                continue;
            }

            $error = $file->getError();

            Log::warning('PHP could not receive a memory upload.', [
                'user_id' => $request->user()->id,
                'album_id' => $album->id,
                'upload_error_code' => $error,
                'upload_max_filesize' => ini_get('upload_max_filesize'),
                'post_max_size' => ini_get('post_max_size'),
                'upload_tmp_dir' => ini_get('upload_tmp_dir') ?: sys_get_temp_dir(),
            ]);

            $message = match ($error) {
                UPLOAD_ERR_INI_SIZE => 'File melebihi batas unggah PHP yang aktif ('.ini_get('upload_max_filesize').').',
                UPLOAD_ERR_FORM_SIZE => 'File melebihi batas ukuran yang diterima formulir.',
                UPLOAD_ERR_PARTIAL => 'File hanya terkirim sebagian. Coba unggah lagi dan pastikan koneksi tetap aktif.',
                UPLOAD_ERR_NO_FILE => 'Pilih file yang ingin disimpan.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server tidak menemukan folder sementara untuk menerima file.',
                UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file unggahan sementara. Periksa ruang disk dan izin folder sementara.',
                UPLOAD_ERR_EXTENSION => 'Unggahan dihentikan oleh konfigurasi ekstensi PHP.',
                default => 'PHP gagal menerima file (kode '.$error.'). Periksa log aplikasi untuk detail.',
            };

            return back()->withInput()->withErrors(['files' => $message]);
        }

        $validated = $request->validate($rules);
        $capturedAt = $validated['captured_at'] ?? null;
        $userMemories = $request->user()->memories();

        if ($type === 'note') {
            $userMemories->create([
                'album_id' => $album->id,
                'title' => $validated['title'],
                'type' => 'note',
                'note' => $validated['note'],
                'captured_at' => $capturedAt,
            ]);

            return redirect()
                ->route('albums.show', $album)
                ->with('status', 'Catatan berhasil disimpan di folder '.$album->name.'.');
        }

        foreach ($request->file('files') as $file) {
            $filePath = $file->store('memories/'.$request->user()->id.'/'.$album->id, 'local');

            $userMemories->create([
                'album_id' => $album->id,
                'title' => $validated['title'],
                'type' => $type,
                'file_path' => $filePath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'file_size' => $file->getSize(),
                'captured_at' => $capturedAt,
            ]);
        }

        $fileCount = count($request->file('files'));

        return redirect()
            ->route('albums.show', $album)
            ->with('status', $fileCount.' file berhasil disimpan di folder '.$album->name.'.');
    }

    public function show(Request $request, int $album, int $memory): View
    {
        $memory = $this->ownedMemory($request, $album, $memory);
        $this->authorize('view', $memory);

        return view('memories.show', compact('memory'));
    }

    public function preview(Request $request, int $album, int $memory): BinaryFileResponse
    {
        $memory = $this->ownedMemory($request, $album, $memory);
        $this->authorize('view', $memory);

        abort_unless(
            in_array($memory->type, ['photo', 'video'], true)
            && $memory->file_path
            && Storage::disk('local')->exists($memory->file_path),
            404,
        );

        return response()->file(Storage::disk('local')->path($memory->file_path), [
            'Content-Type' => $memory->mime_type,
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; sandbox",
        ]);
    }

    public function download(Request $request, int $album, int $memory): StreamedResponse
    {
        $memory = $this->ownedMemory($request, $album, $memory);
        $this->authorize('view', $memory);

        abort_unless($memory->file_path, 404);
        abort_unless(Storage::disk('local')->exists($memory->file_path), 404);

        return Storage::disk('local')->download(
            $memory->file_path,
            $memory->original_filename ?? basename($memory->file_path),
        );
    }

    public function destroy(Request $request, int $album, int $memory): RedirectResponse
    {
        $memory = $this->ownedMemory($request, $album, $memory);
        $this->authorize('delete', $memory);

        if ($memory->file_path && Storage::disk('local')->exists($memory->file_path)) {
            Storage::disk('local')->delete($memory->file_path);
        }

        $albumId = $memory->album_id;
        $memory->delete();

        return redirect()
            ->route('albums.show', $albumId)
            ->with('status', 'Kenangan berhasil dihapus.');
    }

    private function ownedMemory(Request $request, int $albumId, int $memoryId): Memory
    {
        $album = $request->user()->albums()->findOrFail($albumId);
        $this->authorize('view', $album);

        return $album->memories()->findOrFail($memoryId);
    }
}
