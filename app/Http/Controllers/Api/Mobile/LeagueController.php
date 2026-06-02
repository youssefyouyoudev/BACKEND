<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Resources\Mobile\LeagueResource;
use App\Services\MobileCatalogService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeagueController extends Controller
{
    public function __invoke(MobileCatalogService $catalog): AnonymousResourceCollection
    {
        return LeagueResource::collection($catalog->leagues());
    }
}
