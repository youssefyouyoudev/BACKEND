<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StorePlaylistRequest;
use App\Http\Requests\Web\Admin\UpdatePlaylistRequest;
use App\Models\Playlist;
use App\Services\PlaylistImportService;
use App\Services\UrlSafetyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function update(UpdatePlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $validated = $request->validated();
        $sourceUrl = $validated['m3u_url'] ?? null;
        $uploadedFile = $request->file('playlist_file');
        $oldFilePath = $playlist->resolved_file_path;

        if ($sourceUrl) {
            $this->urlSafetyService->assertSafeForImport($sourceUrl);
        }

        $updates = [
            'name' => $validated['name'],
            'status' => 'pending',
        ];

        if ($sourceUrl) {
            $updates['source_type'] = Playlist::SOURCE_TYPE_URL;
            $updates['source_url'] = $sourceUrl;
            $updates['file_path'] = null;
            $updates['stored_path'] = null;
            $updates['original_filename'] = null;
        } elseif ($uploadedFile instanceof UploadedFile) {
            $filePath = $this->storeUploadedPlaylist($uploadedFile);

            $updates['source_type'] = Playlist::SOURCE_TYPE_FILE;
            $updates['source_url'] = null;
            $updates['file_path'] = $filePath;
            $updates['stored_path'] = $filePath;
            $updates['original_filename'] = $uploadedFile->getClientOriginalName();
        }

        $playlist->update($updates);

        if ($oldFilePath && $oldFilePath !== $playlist->resolved_file_path) {
            Storage::disk('playlists')->delete($oldFilePath);
        }

        try {
            set_time_limit(300);
            $this->playlistImportService->process($playlist);

            return redirect()
                ->route('admin.dashboard')
                ->with('status', "Playlist \"{$playlist->name}\" updated and parsed successfully.");
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => 'Update saved, but re-parse failed: '.$exception->getMessage(),
                ]);
        }
    }

    public function destroy(Playlist $playlist): RedirectResponse
    {
        $filePath = $playlist->resolved_file_path;
        $name = $playlist->name;

        $playlist->delete();

        if ($filePath) {
            Storage::disk('playlists')->delete($filePath);
        }

        return redirect()
            ->route('admin.dashboard')
            ->with('status', "Playlist \"{$name}\" deleted.");
    }

    private function storeUploadedPlaylist(UploadedFile $uploadedFile): string
    {
        return $uploadedFile->storeAs(
            '',
            Str::uuid()->toString().'-'.preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $uploadedFile->getClientOriginalName()),
            'playlists'
        );
    }
}
