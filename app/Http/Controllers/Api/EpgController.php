<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\TvCatalogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EpgController extends Controller
{
    public function __invoke(Request $request, TvCatalogService $catalog): JsonResponse
    {
        $start = Carbon::parse($request->query('start', now()->subHour()->toIso8601String()));
        $end = Carbon::parse($request->query('end', now()->addHours(8)->toIso8601String()));
        $category = $request->string('category')->toString() ?: null;

        return response()->json([
            'data' => $catalog->epg($start, $end, $category)->map(fn (array $row) => [
                'channel' => [
                    'id' => $row['channel']->id,
                    'name' => $row['channel']->name,
                    'slug' => $row['channel']->slug,
                    'logo' => $row['channel']->logo ?: asset('brand/rifi-logo.png'),
                    'category' => $row['channel']->category?->name ?? $row['channel']->group_title ?? 'General',
                ],
                'programs' => $row['programs']->map(fn ($program) => [
                    'id' => $program->id,
                    'title' => $program->title,
                    'description' => $program->description,
                    'start_time' => $program->start_time->toIso8601String(),
                    'end_time' => $program->end_time->toIso8601String(),
                    'is_now' => $program->start_time <= now() && $program->end_time > now(),
                ])->values(),
            ])->values(),
            'meta' => [
                'start' => $start->toIso8601String(),
                'end' => $end->toIso8601String(),
            ],
        ]);
    }
}
