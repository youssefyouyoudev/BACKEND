<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StorePlaylistRequest;
use App\Http\Requests\Web\Admin\UpdatePlaylistRequest;
use App\Models\IptvCategory;
use App\Models\IptvItem;
use App\Models\Playlist;
use App\Services\IptvChannelNormalizer;
use App\Services\PlaylistImporter;
use App\Services\PlaylistImportService;
use App\Services\UrlSafetyService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly UrlSafetyService $urlSafetyService,
        private readonly PlaylistImportService $playlistImportService,
        private readonly PlaylistImporter $iptvImporter,
    ) {}

    public function index(): View
    {
        $playlists = Playlist::query()
            ->withCount(['iptvItems', 'iptvCategories'])
            ->latest()
            ->paginate(15);

        return view('admin.playlists.index', [
            'playlists' => $playlists,
            'summary' => [
                'channels' => IptvItem::query()->where('type', IptvItem::TYPE_LIVE)->count(),
                'active' => IptvItem::query()->where('type', IptvItem::TYPE_LIVE)->where('is_active', true)->count(),
                'failed' => IptvItem::query()->where('health_status', 'offline')->count(),
                'categories' => IptvCategory::query()->count(),
            ],
        ]);
    }

    public function create(): View
    {
        return view('admin.playlists.create', [
            'playlist' => new Playlist(['input_type' => Playlist::INPUT_TYPE_M3U_URL, 'output' => 'mpegts']),
        ]);
    }

    public function edit(Playlist $playlist): View
    {
        return view('admin.playlists.edit', [
            'playlist' => $playlist,
        ]);
    }

    public function store(StorePlaylistRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $inputType = $this->normalizeInputType($validated['input_type']);
        $sourceUrl = $validated['m3u_url'] ?? null;
        $uploadedFile = $request->file('playlist_file');

        if ($sourceUrl) {
            $this->urlSafetyService->assertSafeForImport($sourceUrl);
        }

        $filePath = null;
        $sourceType = Playlist::SOURCE_TYPE_URL;
        $originalFilename = null;

        if ($inputType === Playlist::INPUT_TYPE_UPLOAD && $uploadedFile instanceof UploadedFile) {
            $sourceType = Playlist::SOURCE_TYPE_FILE;
            $filePath = $this->storeUploadedPlaylist($uploadedFile);
            $originalFilename = $uploadedFile->getClientOriginalName();
        }

        if ($inputType === Playlist::INPUT_TYPE_XTREAM) {
            $sourceUrl = null;
        }

        $playlist = Playlist::query()->create([
            'user_id' => $request->user()->id,
            'name' => $validated['name'],
            'input_type' => $inputType,
            'source_type' => $sourceType,
            'm3u_url' => $sourceUrl,
            'source_url' => $sourceUrl,
            'server_url' => $validated['server_url'] ?? null,
            'username' => $validated['username'] ?? null,
            'password' => $validated['password'] ?? null,
            'output' => $validated['output'] ?? 'mpegts',
            'file_path' => $filePath,
            'active_code' => $inputType === Playlist::INPUT_TYPE_ACTIVE_CODE ? ($validated['active_code'] ?? null) : null,
            'stored_path' => $filePath,
            'original_filename' => $originalFilename,
            'status' => $this->playlistHasImportableSource($inputType, $sourceUrl, $filePath) ? 'pending' : 'needs_url',
            'is_public' => true,
            'approved_by_admin' => $request->user()->id,
            'approved_at' => now(),
        ]);

        if (! $this->playlistHasImportableSource($inputType, $sourceUrl, $filePath)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', __('Playlist ":name" saved. Add an M3U URL or upload a file before parsing channels.', ['name' => $playlist->name]));
        }

        try {
            set_time_limit(300);
            $this->runImporters($playlist);

            return redirect()
                ->route('admin.playlists.index')
                ->with('status', __('Playlist ":name" imported successfully with :count IPTV items.', [
                    'name' => $playlist->name,
                    'count' => $playlist->iptvItems()->count(),
                ]));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => __('Import failed: :message', ['message' => $exception->getMessage()]),
                ]);
        }
    }

    public function parse(Playlist $playlist): RedirectResponse
    {
        return $this->reimport($playlist);
    }

    public function reimport(Playlist $playlist): RedirectResponse
    {
        if (! $this->playlistHasImportableSource($playlist->input_type, $playlist->m3u_url ?: $playlist->source_url, $playlist->resolved_file_path)) {
            $playlist->update(['status' => 'needs_url']);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => __('Add an M3U URL or upload a file before parsing this playlist.'),
                ]);
        }

        $playlist->update(['status' => 'processing']);

        try {
            set_time_limit(300);
            $this->runImporters($playlist);

            return redirect()
                ->back()
                ->with('status', __('Playlist ":name" parsed successfully.', ['name' => $playlist->name]));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => __('Parse failed: :message', ['message' => $exception->getMessage()]),
                ]);
        }
    }

    public function update(UpdatePlaylistRequest $request, Playlist $playlist): RedirectResponse
    {
        $validated = $request->validated();
        $inputType = $this->normalizeInputType($validated['input_type']);
        $sourceUrl = $validated['m3u_url'] ?? null;
        $uploadedFile = $request->file('playlist_file');
        $oldFilePath = $playlist->resolved_file_path;

        if ($sourceUrl) {
            $this->urlSafetyService->assertSafeForImport($sourceUrl);
        }

        $updates = [
            'name' => $validated['name'],
            'input_type' => $inputType,
            'active_code' => $inputType === Playlist::INPUT_TYPE_ACTIVE_CODE
                ? (($validated['active_code'] ?? null) ?: $playlist->active_code)
                : null,
            'server_url' => $inputType === Playlist::INPUT_TYPE_XTREAM ? ($validated['server_url'] ?? null) : null,
            'username' => $inputType === Playlist::INPUT_TYPE_XTREAM ? ($validated['username'] ?? null) : null,
            'password' => $inputType === Playlist::INPUT_TYPE_XTREAM ? (($validated['password'] ?? null) ?: $playlist->password) : null,
            'output' => $validated['output'] ?? $playlist->output ?? 'mpegts',
            'status' => 'pending',
        ];

        if ($inputType === Playlist::INPUT_TYPE_M3U_URL || $inputType === Playlist::INPUT_TYPE_ACTIVE_CODE || $inputType === Playlist::INPUT_TYPE_XTREAM) {
            $updates['source_type'] = Playlist::SOURCE_TYPE_URL;
            $updates['source_url'] = $sourceUrl;
            $updates['m3u_url'] = $sourceUrl;
            $updates['file_path'] = null;
            $updates['stored_path'] = null;
            $updates['original_filename'] = null;
        }

        if ($inputType === Playlist::INPUT_TYPE_UPLOAD) {
            $filePath = $uploadedFile instanceof UploadedFile
                ? $this->storeUploadedPlaylist($uploadedFile)
                : $oldFilePath;

            $updates['source_type'] = Playlist::SOURCE_TYPE_FILE;
            $updates['source_url'] = null;
            $updates['m3u_url'] = null;
            $updates['file_path'] = $filePath;
            $updates['stored_path'] = $filePath;
            $updates['original_filename'] = $uploadedFile instanceof UploadedFile
                ? $uploadedFile->getClientOriginalName()
                : $playlist->original_filename;
        }

        if (! $this->playlistHasImportableSource($inputType, $sourceUrl, $updates['file_path'] ?? null)) {
            $updates['status'] = 'needs_url';
        }

        $playlist->update($updates);

        if ($oldFilePath && $oldFilePath !== $playlist->resolved_file_path) {
            Storage::disk('playlists')->delete($oldFilePath);
        }

        if (! $this->playlistHasImportableSource($inputType, $sourceUrl, $playlist->resolved_file_path)) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', __('Playlist ":name" saved. Add an M3U URL or upload a file before parsing channels.', ['name' => $playlist->name]));
        }

        try {
            set_time_limit(300);
            $this->runImporters($playlist);

            return redirect()
                ->route('admin.playlists.index')
                ->with('status', __('Playlist ":name" updated and parsed successfully.', ['name' => $playlist->name]));
        } catch (Throwable $exception) {
            report($exception);

            return redirect()
                ->route('admin.dashboard')
                ->withErrors([
                    'playlist' => __('Update saved, but re-parse failed: :message', ['message' => $exception->getMessage()]),
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
            ->route('admin.playlists.index')
            ->with('status', __('Playlist ":name" deleted.', ['name' => $name]));
    }

    public function clearCache(): RedirectResponse
    {
        foreach ([
            'public-live:iptv-total-count',
            'public-live:iptv-category-counts',
            'public-live:iptv-initial-items',
            'api-tv:categories',
            'api-tv:curated-sports-categories-v1',
            'api-tv:live-categories-v2',
        ] as $key) {
            Cache::forget($key);
        }

        return back()->with('status', __('IPTV catalog cache cleared.'));
    }

    public function rebuildIndex(IptvChannelNormalizer $normalizer): RedirectResponse
    {
        IptvItem::query()
            ->select(['id', 'name', 'normalized_name'])
            ->chunkById(500, function ($items) use ($normalizer): void {
                foreach ($items as $item) {
                    $item->update(['normalized_name' => $normalizer->normalize($item->name)]);
                }
            });

        $this->clearCache();

        return back()->with('status', __('Channel index rebuilt.'));
    }

    public function mergeDuplicates(): RedirectResponse
    {
        Artisan::call('channels:merge-duplicates');

        return back()->with('status', __('Duplicate channel maintenance completed.'));
    }

    private function storeUploadedPlaylist(UploadedFile $uploadedFile): string
    {
        return $uploadedFile->storeAs(
            '',
            Str::uuid()->toString().'-'.preg_replace('/[^a-zA-Z0-9.\-_]/', '-', $uploadedFile->getClientOriginalName()),
            'playlists'
        );
    }

    private function playlistHasImportableSource(string $inputType, ?string $sourceUrl, ?string $filePath): bool
    {
        return match ($inputType) {
            Playlist::INPUT_TYPE_UPLOAD => filled($filePath),
            Playlist::INPUT_TYPE_ACTIVE_CODE,
            Playlist::INPUT_TYPE_M3U_URL => filled($sourceUrl),
            Playlist::INPUT_TYPE_XTREAM => true,
            default => false,
        };
    }

    private function normalizeInputType(string $inputType): string
    {
        return match ($inputType) {
            'remote_url' => Playlist::INPUT_TYPE_M3U_URL,
            'upload_file' => Playlist::INPUT_TYPE_UPLOAD,
            default => $inputType,
        };
    }

    private function runImporters(Playlist $playlist): void
    {
        if ($playlist->input_type !== Playlist::INPUT_TYPE_XTREAM) {
            $this->playlistImportService->process($playlist);
        }

        $this->iptvImporter->import($playlist->refresh());

        Cache::forget('public-live:iptv-total-count');
        Cache::forget('public-live:iptv-category-counts');
        Cache::forget('public-live:iptv-initial-items');
        Cache::forget('api-tv:categories');
    }
}
