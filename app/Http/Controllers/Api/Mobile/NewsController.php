<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\ArticleResource;
use App\Services\MobileCatalogService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NewsController extends Controller
{
    public function index(Request $request, MobileCatalogService $catalog): AnonymousResourceCollection
    {
        return ArticleResource::collection($catalog->news($request->query()));
    }

    public function show(string $slug, MobileCatalogService $catalog): ArticleResource
    {
        $article = $catalog->article($slug);

        abort_unless($article, 404, 'Article not found.');

        return ArticleResource::make($article);
    }
}
