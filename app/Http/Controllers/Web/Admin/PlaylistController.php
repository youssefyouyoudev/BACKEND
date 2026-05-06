<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StorePlaylistRequest;
use App\Jobs\ParsePlaylistJob;
use App\Models\Playlist;
use App\Services\PlaylistImportService;
use App\Services\UrlSafetyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Throwable;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly UrlSafetyService $urlSafetyService,
        private readonly PlaylistImportService $playlistImportService,
    ) {
    }

    public function store(StorePlaylistRequest $request): RedirectResponse
    {
        $validated    = $request->validated();
        $sourceUrl    = $validated['m3u_url'] ?? null;
        $uploadedFile = $request->file('playlist_file');

        if ($sourceUrl) {
            $this->urlSafetyService->assertSafeForImport($sourceUrl);
        }

        $filePath         = null;
        $sourceType       = Playlist::SOURCE_TYPE_URL;
        $originalFilename = null;

        if ($uploadedFile !== null) {
            $sourceType = Playlist::SOURCE_TYPE_FILE;
            $filePath   = $uploadedFile->storeAs(
                '',
                Str::uuid()->toString().'-'.preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $uploadedFile->getClientOriginalName()),
                'playlists'
            );
            $originalFilename = $uploadedFile->getClientOriginalName();
        }

        $playlist = Playlist::query()->create([
            'user_id'           => $request->user()->id,
            'name'              => $validated['name'],
            'source_type'       => $sourceType,
            'source_url'        => $sourceUrl,
            'file_path'         => $filePath,
            'stored_path'       => $filePath,
            'original_filename' => $originalFilename,
            'status'            => 'pending',
            'is_public'         => true,
            'approved_by_admin' => $request->user()->id,
            'approved_at'       => now(),
        ]);

        // Parse immediately after saving — no separate "Parse Playlist" step needed.
        try {
            set_time_limit(300);
            $this->playlistImportService->process($playlist);

            return redirect()
                ->route('admin.dashboard')
                ->with('status', "Playlist \"{$playlist->name}\" imported successfully with {$playlist->channels()->count()} channels.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => 'Import failed: '.$exception->getMessage(),
                ]);
        }
    }

    public function parse(Playlist $playlist): RedirectResponse
    {
        $playlist->update(['status' => 'processing']);

        // Always run synchronously — no queue worker dependency.
        try {
            set_time_limit(300);
            $this->playlistImportService->process($playlist);

            return redirect()
                ->route('admin.dashboard')
                ->with('status', "Playlist \"{$playlist->name}\" parsed successfully.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => 'Parse failed: '.$exception->getMessage(),
                ]);
        }
    }
}
