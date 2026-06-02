<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ArticleResource;
use App\Http\Resources\Mobile\ChannelResource;
use App\Http\Resources\Mobile\LeagueResource;
use App\Services\MobileCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    public function __invoke(Request $request, MobileCatalogService $catalog): JsonResponse
    {
        $query = Str::of($request->string('q')->toString())->squish()->limit(80, '')->toString();
        $results = $catalog->search($query);

        return response()->json([
            'data' => [
                'query' => $query,
                'channels' => ChannelResource::collection($results['channels'])->resolve($request),
                'news' => ArticleResource::collection($results['news'])->resolve($request),
                'leagues' => LeagueResource::collection($results['leagues'])->resolve($request),
            ],
            'meta' => [
                'total' => $results['channels']->count() + $results['news']->count() + $results['leagues']->count(),
            ],
        ]);
    }
}
