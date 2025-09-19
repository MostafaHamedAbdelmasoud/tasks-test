<?php

namespace App\Services\Auth;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AuthService
{
    public function login(string $email, string $password): array
    {
        try {
            Log::info('Login attempt', ['email' => $email]);

            return DB::transaction(function () use ($email, $password): array {
                $user = User::where('email', $email)->first();

                if (!$user || !Hash::check($password, $user->password)) {
                    Log::warning('Failed login attempt', ['email' => $email]);
                    throw ValidationException::withMessages([
                        'email' => ['The provided credentials are incorrect.'],
                    ]);
                }

                // Revoke all existing tokens
                $user->tokens()->delete();

                $token = $user->createToken('auth-token')->plainTextToken;

                Log::info('Successful login', ['user_id' => $user->id, 'email' => $email]);

                return [
                    'user' => $user->fresh(),
                    'token' => $token,
                ];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('Login error', [
                'email' => $email,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Login failed. Please try again.');
        }
    }

    public function logout(User $user): void
    {
        try {
            Log::info('Logout attempt', ['user_id' => $user->id]);

            DB::transaction(function () use ($user): void {
                $currentToken = $user->currentAccessToken();
                if ($currentToken) {
                    $currentToken->delete();
                }
            });

            Log::info('Successful logout', ['user_id' => $user->id]);
        } catch (\Exception $e) {
            Log::error('Logout error', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \Exception('Logout failed. Please try again.');
        }
    }

    public function getProfile(User $user): User
    {
        try {
            Log::info('Profile accessed', ['user_id' => $user->id]);
            return $user->fresh();
        } catch (\Exception $e) {
            Log::error('Profile access error', [
                'user_id' => $user->id,
                'error' => $e->getMessage()
            ]);
            throw new \Exception('Failed to retrieve profile.');
        }
    }
}
