<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\CategoryResource;
use App\Http\Resources\Mobile\ChannelResource;
use App\Services\MobileCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ChannelController extends Controller
{
    public function index(Request $request, MobileCatalogService $catalog): AnonymousResourceCollection
    {
        return ChannelResource::collection($catalog->channels($request->query()));
    }

    public function featured(Request $request, MobileCatalogService $catalog): AnonymousResourceCollection
    {
        return ChannelResource::collection($catalog->channels([
            ...$request->query(),
            'featured' => true,
            'per_page' => $request->integer('per_page', 12),
        ]));
    }

    public function categories(MobileCatalogService $catalog): AnonymousResourceCollection
    {
        return CategoryResource::collection($catalog->categories());
    }

    public function show(int $id, MobileCatalogService $catalog): ChannelResource
    {
        $channel = $catalog->channel($id);

        abort_unless($channel, 404, 'Channel not found.');

        return ChannelResource::make($channel);
    }
}
