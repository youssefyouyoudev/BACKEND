<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserManagementController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('search'), function ($query) use ($request): void {
                $search = $request->string('search')->toString();
                $query->where(function ($builder) use ($search): void {
                    $builder->where('name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%');
                });
            })
            ->when($request->filled('role'), fn ($query) => $query->where('role', $request->string('role')->toString()))
            ->when($request->filled('is_active'), fn ($query) => $query->where('is_active', $request->boolean('is_active')))
            ->latest()
            ->paginate(20);

        return UserResource::collection($users);
    }

    public function toggle(User $user): JsonResponse
    {
        if ($user->is(request()->user())) {
            abort(JsonResponse::HTTP_UNPROCESSABLE_ENTITY, 'You cannot deactivate your own account.');
        }

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        $this->activityLogService->log(request()->user(), 'admin.user.toggled', $user, [
            'is_active' => $user->is_active,
        ]);

        return response()->json([
            'message' => 'User status updated successfully.',
            'user' => new UserResource($user),
        ]);
    }
}
