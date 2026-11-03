<?php

namespace App\Http\Controllers;

use App\Models\Album;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AlbumController extends Controller
{
    public function index(Request $request): View
    {
        $albums = $request->user()
            ->albums()
            ->withCount('memories')
            ->latest()
            ->get();

        return view('albums.index', compact('albums'));
    }

    public function create(): View
    {
        return view('albums.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('albums', 'name')
                    ->where(fn ($query) => $query->where('user_id', $request->user()->id)),
            ],
        ]);

        $album = $request->user()->albums()->create($validated);

        return redirect()
            ->route('albums.show', $album)
            ->with('status', 'Folder '.$album->name.' berhasil dibuat.');
    }

    public function show(Request $request, int $album): View
    {
        $album = $request->user()->albums()->findOrFail($album);
        $this->authorize('view', $album);

        $memories = $album->memories()
            ->latest()
            ->paginate(12);

        return view('albums.show', compact('album', 'memories'));
    }

    public function destroy(Request $request, int $album): RedirectResponse
    {
        $album = $request->user()->albums()->findOrFail($album);
        $this->authorize('delete', $album);

        $memories = $album->memories()->get();

        foreach ($memories as $memory) {
            if (
                $memory->file_path
                && Storage::disk('local')->exists($memory->file_path)
                && ! Storage::disk('local')->delete($memory->file_path)
            ) {
                throw new \RuntimeException('Tidak dapat menghapus file kenangan: '.$memory->file_path);
            }
        }

        $albumName = $album->name;
        $memoryCount = $memories->count();

        DB::transaction(function () use ($album): void {
            $album->memories()->delete();
            $album->deleteOrFail();
        });

        return redirect()
            ->route('albums.index')
            ->with('status', 'Folder “'.$albumName.'” beserta '.$memoryCount.' kenangan berhasil dihapus.');
    }
}
