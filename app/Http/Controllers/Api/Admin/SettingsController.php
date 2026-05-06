<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\ActivityLogService;
use App\Services\AppSettingsService;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AppSettingsService $settingsService,
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(): JsonResponse
    {
        return response()->json([
            'settings' => $this->settingsService->all(),
        ]);
    }

    public function update(UpdateSettingsRequest $request): JsonResponse
    {
        $settings = $this->settingsService->update($request->validated());

        $this->activityLogService->log($request->user(), 'admin.settings.updated', 'settings', $settings);

        return response()->json([
            'message' => 'Settings updated successfully.',
            'settings' => $settings,
        ]);
    }
}
