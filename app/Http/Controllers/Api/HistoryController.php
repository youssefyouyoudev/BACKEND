<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreHistoryRequest;
use App\Http\Resources\WatchHistoryResource;
use App\Models\Channel;
use App\Models\WatchHistory;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HistoryController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request)
    {
        $history = $request->user()->watchHistories()
            ->with(['channel.playlist', 'channel.favoredByUsers' => fn ($query) => $query->where('user_id', $request->user()->id)])
            ->latest('watched_at')
            ->paginate(24);

        return WatchHistoryResource::collection($history);
    }

    public function store(StoreHistoryRequest $request): JsonResponse
    {
        $channel = Channel::query()->findOrFail($request->validated('channel_id'));
        $this->authorize('view', $channel);

        $history = WatchHistory::query()->updateOrCreate(
            [
                'user_id' => $request->user()->id,
                'channel_id' => $channel->id,
            ],
            [
                'watched_at' => now(),
                'duration' => $request->validated('duration'),
            ]
        );

        $this->activityLogService->log($request->user(), 'history.updated', $channel, [
            'duration' => $history->duration,
        ]);

        return response()->json([
            'message' => 'Watch history updated.',
            'history' => new WatchHistoryResource($history->load('channel.playlist')),
        ]);
    }
}
