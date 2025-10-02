<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required','string','max:255'],
            'email' => ['required','email','max:255','unique:users,email'],
            'password' => ['required','confirmed', Password::min(8)],
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
        // Create personal access token
        $tokenResult = $user->createToken('api-token');
        $token = $tokenResult->accessToken;
        $expiresAt = null;
        if (isset($tokenResult->token) && method_exists($tokenResult->token, 'getAttribute')) {
            $expiresAt = $tokenResult->token->expires_at ?? null;
        }

        $payload = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt ? now()->diffInSeconds($expiresAt) : null,
            'client_id' => env('PASSPORT_CLIENT_ID') ?: env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_CLIENT_SECRET') ?: env('PASSPORT_PASSWORD_CLIENT_SECRET'),
        ];

        return response()->json([
            'message' => 'User registered',
            'data' => $payload,
            'timestamp' => now()->toISOString(),
            'success' => true,
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required'],
        ]);
        // Attempt to authenticate
        if (! Auth::attempt(['email' => $data['email'], 'password' => $data['password']])) {
            return response()->json([
                'message' => 'Invalid credentials',
                'data' => null,
                'timestamp' => now()->toISOString(),
                'success' => false,
            ], 401);
        }

        $user = Auth::user();
        $tokenResult = $user->createToken('api-token');
        $token = $tokenResult->accessToken;
        $expiresAt = null;
        if (isset($tokenResult->token)) {
            $expiresAt = $tokenResult->token->expires_at ?? null;
        }

        $payload = [
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => $expiresAt ? now()->diffInSeconds($expiresAt) : null,
            'client_id' => env('PASSPORT_CLIENT_ID') ?: env('PASSPORT_PASSWORD_CLIENT_ID'),
            'client_secret' => env('PASSPORT_CLIENT_SECRET') ?: env('PASSPORT_PASSWORD_CLIENT_SECRET'),
        ];

        return response()->json([
            'message' => 'Login successful',
            'data' => $payload,
            'timestamp' => now()->toISOString(),
            'success' => true,
        ], 200);
    }

    protected function getPasswordGrantToken(string $username, string $password)
    {
        // legacy: no longer used when creating personal access tokens
        return null;
    }
}
