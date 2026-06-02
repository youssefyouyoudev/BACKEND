<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ChannelResource;
use App\Services\MobileCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CategoryChannelController extends Controller
{
    public function __invoke(string $slug, Request $request, MobileCatalogService $catalog): AnonymousResourceCollection
    {
        abort_unless($catalog->category($slug), 404, 'Category not found.');

        return ChannelResource::collection($catalog->channels([
            ...$request->query(),
            'category' => $slug,
        ]));
    }
}
