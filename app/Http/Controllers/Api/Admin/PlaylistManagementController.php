<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ApprovePlaylistRequest;
use App\Http\Resources\PlaylistResource;
use App\Models\Playlist;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlaylistManagementController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request)
    {
        $playlists = Playlist::query()
            ->with(['user', 'approver'])
            ->withCount('channels')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('is_public'), fn ($query) => $query->where('is_public', $request->boolean('is_public')))
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('email', 'like', '%'.$search.'%'));
                });
            })
            ->latest()
            ->paginate(20);

        return PlaylistResource::collection($playlists);
    }

    public function approve(ApprovePlaylistRequest $request, Playlist $playlist): JsonResponse
    {
        $approved = $request->boolean('approved');

        $playlist->update([
            'approved_by_admin' => $approved ? $request->user()->id : null,
            'approved_at' => $approved ? now() : null,
            'status' => $approved ? 'ready' : 'rejected',
        ]);

        $this->activityLogService->log($request->user(), 'admin.playlist.reviewed', $playlist, [
            'approved' => $approved,
        ]);

        return response()->json([
            'message' => $approved ? 'Playlist approved successfully.' : 'Playlist rejected successfully.',
            'playlist' => new PlaylistResource($playlist->fresh(['user', 'channels'])),
        ]);
    }
}
