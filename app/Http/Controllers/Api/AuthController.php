<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ForgotPasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(
        private readonly ActivityLogService $activityLogService,
    ) {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $user = User::query()->create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'role' => User::ROLE_USER,
            'is_active' => true,
        ]);

        $token = $user->createToken($request->string('device_name', 'web-app')->toString())->plainTextToken;

        $this->activityLogService->log($user, 'auth.registered', $user);

        return response()->json([
            'message' => 'Registration completed successfully.',
            'token' => $token,
            'user' => new UserResource($user),
        ], JsonResponse::HTTP_CREATED);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::query()->where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        if (! $user->is_active) {
            abort(JsonResponse::HTTP_FORBIDDEN, 'Your account is currently inactive.');
        }

        $token = $user->createToken($request->string('device_name', 'web-app')->toString())->plainTextToken;

        $this->activityLogService->log($user, 'auth.logged_in', $user);

        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => new UserResource($user),
        ]);
    }

    public function logout(): JsonResponse
    {
        $user = request()->user();

        $user?->currentAccessToken()?->delete();

        $this->activityLogService->log($user, 'auth.logged_out', $user);

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    public function me(): UserResource
    {
        return new UserResource(request()->user());
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'message' => 'If an account exists for that email address, a reset link has been queued.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::reset(
            $request->validated(),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                event(new PasswordReset($user));
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        return response()->json([
            'message' => __($status),
        ]);
    }
}
