<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

/**
 * API de autenticación para la app móvil (Sanctum personal access tokens).
 * Devuelve un token Bearer que la app guarda y envía en cada request.
 */
class AuthController extends Controller
{
    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'is_admin' => (bool) $user->is_admin,
            'avatar_url' => $user->avatar_url,
        ];
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:255',
        ]);

        // Rate limit por email+IP (anti fuerza bruta)
        $key = 'mobile-login:' . strtolower($data['email']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            $seconds = RateLimiter::availableIn($key);
            throw ValidationException::withMessages([
                'email' => "Demasiados intentos. Probá de nuevo en " . ceil($seconds / 60) . " minutos.",
            ]);
        }

        $user = User::where('email', strtolower($data['email']))->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);
            throw ValidationException::withMessages([
                'email' => 'Credenciales incorrectas.',
            ]);
        }

        RateLimiter::clear($key);

        $device = $data['device_name'] ?? 'mobile';
        $token = $user->createToken($device, ['agent:access']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->userPayload($user),
        ]);
    }

    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|lowercase|unique:users,email',
            'password' => ['required', 'confirmed', Password::defaults()],
            'device_name' => 'nullable|string|max:255',
        ]);

        $user = User::create([
            'name' => $data['name'],
            'email' => strtolower($data['email']),
            'password' => $data['password'], // el cast 'hashed' lo hashea
            'email_verified_at' => now(), // registro móvil queda verificado
        ]);

        $token = $user->createToken($data['device_name'] ?? 'mobile', ['agent:access']);

        return response()->json([
            'token' => $token->plainTextToken,
            'user' => $this->userPayload($user),
        ], 201);
    }

    public function me(Request $request)
    {
        return response()->json(['user' => $this->userPayload($request->user())]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        return response()->json(['success' => true]);
    }
}
