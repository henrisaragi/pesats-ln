<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\Auth\AuthenticationService;
use Illuminate\Http\JsonResponse;

class AuthenticationController extends Controller
{
    public function __construct(private readonly AuthenticationService $authenticationService) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();

        $result = $this->authenticationService->issueApiToken(
            $credentials['login'],
            $credentials['password'],
        );

        if ($result === null) {
            return response()->json([
                'message' => __('Username/email atau password yang Anda masukkan salah.'),
            ], 422);
        }

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authenticationService->registerWithApiToken($request->validated());

        return response()->json([
            'user' => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }
}
