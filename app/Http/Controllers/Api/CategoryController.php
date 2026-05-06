<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TvCatalogService;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function __invoke(TvCatalogService $catalog): JsonResponse
    {
        return response()->json([
            'data' => $catalog->categories()->map(fn ($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'color' => $category->color,
                'icon' => $category->icon,
                'channels_count' => $category->channels_count,
            ])->values(),
        ]);
    }
}
