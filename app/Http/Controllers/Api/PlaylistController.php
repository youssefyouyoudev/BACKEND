<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Playlist\StorePlaylistUploadRequest;
use App\Http\Requests\Playlist\StorePlaylistUrlRequest;
use App\Http\Resources\ChannelResource;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Services\ActivityLogService;
use App\Services\AppSettingsService;
use App\Services\PlaylistImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly PlaylistImportService $playlistImportService,
        private readonly ActivityLogService $activityLogService,
        private readonly AppSettingsService $settingsService,
    ) {
    }

    public function index(Request $request)
    {
        $playlists = Playlist::query()
            ->where('user_id', $request->user()->id)
            ->withCount('channels')
            ->latest()
            ->paginate(12);

        return PlaylistResource::collection($playlists);
    }

    public function storeFromUrl(StorePlaylistUrlRequest $request): JsonResponse
    {
        $this->authorize('create', Playlist::class);

        if (! $this->settingsService->all()['allow_url_imports']) {
            abort(JsonResponse::HTTP_FORBIDDEN, 'Playlist URL imports are currently disabled by the administrator.');
        }

        $playlist = $this->playlistImportService->importFromUrl($request->user(), $request->validated());

        return response()->json([
            'message' => 'Playlist imported successfully.',
            'playlist' => new PlaylistResource($playlist->load('channels')),
        ], JsonResponse::HTTP_CREATED);
    }

    public function storeFromUpload(StorePlaylistUploadRequest $request): JsonResponse
    {
        $this->authorize('create', Playlist::class);

        $playlist = $this->playlistImportService->importFromUpload(
            $request->user(),
            $request->validated(),
            $request->file('playlist_file')
        );

        return response()->json([
            'message' => 'Playlist uploaded successfully.',
            'playlist' => new PlaylistResource($playlist->load('channels')),
        ], JsonResponse::HTTP_CREATED);
    }

    public function show(Request $request, Playlist $playlist): PlaylistResource
    {
        $this->authorize('view', $playlist);

        $playlist->load('user')->loadCount('channels');

        return new PlaylistResource($playlist);
    }

    public function channels(Request $request, Playlist $playlist)
    {
        $this->authorize('view', $playlist);

        $channels = $playlist->channels()
            ->when($request->filled('group'), fn ($q) => $q->where('group_title', $request->string('group')->toString()))
            ->when($request->filled('search'), fn ($q) => $q->where('name', 'like', '%'.$request->string('search')->toString().'%'))
            ->where('is_active', true)
            ->orderBy('group_title')
            ->orderBy('sort_order')
            ->paginate(min(100, max(1, $request->integer('per_page', 50))));

        return ChannelResource::collection($channels);
    }

    public function refresh(Playlist $playlist): JsonResponse
    {
        $this->authorize('refresh', $playlist);

        $refreshed = $this->playlistImportService->refresh($playlist);

        return response()->json([
            'message' => 'Playlist refreshed successfully.',
            'playlist' => new PlaylistResource($refreshed->load('channels')),
        ]);
    }

    public function destroy(Playlist $playlist): JsonResponse
    {
        $this->authorize('delete', $playlist);

        $user = request()->user();

        $this->activityLogService->log($user, 'playlist.deleted', $playlist);
        $playlist->delete();

        return response()->json([
            'message' => 'Playlist deleted successfully.',
        ]);
    }
}
