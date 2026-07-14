<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final class RegisterController extends Controller
{
    /**
     * Register a new customer account.
     *
     * After registration the user is automatically logged in
     * and a Sanctum token is returned for immediate API access.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'                 => ['required', 'string', 'max:255'],
            'email'                => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'             => ['required', 'string', 'min:8', 'confirmed'],
            'phone'                => ['nullable', 'string', 'max:20', 'unique:users,phone'],
            'language_preference'  => ['nullable', 'string', 'in:en,bm'],
        ]);

        $user = User::create([
            'name'                 => $validated['name'],
            'email'                => $validated['email'],
            'password'             => Hash::make($validated['password']),
            'phone'                => $validated['phone'] ?? null,
            'language_preference'  => $validated['language_preference'] ?? 'en',
            'role'                 => 'customer',
            'is_active'            => true,
        ]);

        // Auto-login after successful registration
        Auth::login($user);

        /** @var \Laravel\Sanctum\NewAccessToken $tokenResult */
        $tokenResult = $user->createToken(
            name: 'registration-token',
            abilities: ['*'],
        );

        $token = $tokenResult->plainTextToken;

        return response()->json([
            'success' => true,
            'user'    => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'phone'               => $user->phone,
                'role'                => $user->role,
                'language_preference' => $user->language_preference,
                'is_active'           => $user->is_active,
            ],
            'token' => $token,
        ], JsonResponse::HTTP_CREATED);
    }
}
