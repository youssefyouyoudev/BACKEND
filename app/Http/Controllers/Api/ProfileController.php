<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class ProfileController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function show(): UserResource
    {
        return new UserResource(request()->user());
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update($request->validated());

        $this->activityLogService->log($user, 'profile.updated', $user);

        return response()->json([
            'message' => 'Profile updated successfully.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = $request->user();

        $user->update([
            'password' => $request->validated('password'),
        ]);

        $user->tokens()->delete();

        $token = $user->createToken('web-app')->plainTextToken;

        $this->activityLogService->log($user, 'profile.password_updated', $user);

        return response()->json([
            'message' => 'Password updated successfully.',
            'token' => $token,
        ]);
    }
}
