<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Traits\ApiResponseTrait;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->validated('email'),
                $request->validated('password')
            );

            return $this->successResponse($result, 'Login successful');

        } catch (ValidationException $e) {
            Log::warning('Login validation failed', [
                'email' => $request->validated('email'),
                'errors' => $e->errors()
            ]);
            return $this->validationErrorResponse($e->errors());

        } catch (\Exception $e) {
            Log::error('Login controller error', [
                'email' => $request->validated('email'),
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Login failed. Please try again.', 500);
        }
    }

    public function logout(Request $request): JsonResponse
    {
        try {
            $this->authService->logout($request->user());
            return $this->successResponse(null, 'Logout successful');

        } catch (\Exception $e) {
            Log::error('Logout controller error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Logout failed. Please try again.', 500);
        }
    }

    public function me(Request $request): JsonResponse
    {
        try {
            $user = $this->authService->getProfile($request->user());
            return $this->successResponse(['user' => $user]);

        } catch (\Exception $e) {
            Log::error('Profile controller error', [
                'user_id' => $request->user()?->id,
                'error' => $e->getMessage()
            ]);
            return $this->errorResponse('Failed to retrieve profile.', 500);
        }
    }
}
