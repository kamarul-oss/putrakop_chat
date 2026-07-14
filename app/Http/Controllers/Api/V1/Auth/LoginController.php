<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

final class LoginController extends Controller
{
    /**
     * Authenticate a user with email/password and device fingerprint.
     *
     * The device fingerprint is checked against existing device records.
     * If the device is not trusted, the response flags `requires_verification`
     * so the client can trigger an additional verification step (e.g. OTP).
     */
    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email'              => ['required', 'email', 'max:255'],
            'password'           => ['required', 'string', 'max:255'],
            'device_fingerprint' => ['required', 'string', 'max:255'],
            'device_name'        => ['nullable', 'string', 'max:255'],
        ]);

        // ── Find user by email ──────────────────────────────────
        $user = User::where('email', $validated['email'])->first();

        if ($user === null || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        // ── Check account status ────────────────────────────────
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Your account has been deactivated. Please contact support.'],
            ]);
        }

        // ── Check / create device record ────────────────────────
        $hashedFingerprint = UserDevice::hashFingerprint($validated['device_fingerprint']);

        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $hashedFingerprint)
            ->first();

        $requiresVerification = false;

        if ($device === null) {
            // First time on this device — create record, not trusted
            $device = UserDevice::create([
                'user_id'           => $user->id,
                'device_fingerprint' => $hashedFingerprint,
                'device_name'       => $validated['device_name'] ?? null,
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
                'is_trusted'        => false,
                'last_active_at'    => now(),
            ]);

            $requiresVerification = true;
        } else {
            // Existing device — update activity
            $device->update([
                'ip_address'      => $request->ip(),
                'user_agent'      => $request->userAgent(),
                'last_active_at'  => now(),
            ]);

            if (! $device->is_trusted) {
                $requiresVerification = true;
            }
        }

        // ── Create session & token ──────────────────────────────
        Auth::login($user);

        $user->update(['last_login_at' => now()]);

        /** @var \Laravel\Sanctum\NewAccessToken $tokenResult */
        $tokenResult = $user->createToken(
            name: $validated['device_name'] ?? 'auth-token',
            abilities: ['*'],
        );

        $token = $tokenResult->plainTextToken;

        return response()->json([
            'success'               => true,
            'user'                  => [
                'id'         => $user->id,
                'name'       => $user->name,
                'email'      => $user->email,
                'role'       => $user->role,
                'avatar'     => $user->avatar,
                'department' => $user->department,
            ],
            'device'                => [
                'id'               => $device->id,
                'device_name'      => $device->device_name,
                'is_trusted'       => $device->is_trusted,
                'last_active_at'   => $device->last_active_at?->toISOString(),
            ],
            'requires_verification' => $requiresVerification,
            'token'                 => $token,
        ]);
    }

    /**
     * Logout the authenticated user — invalidate session and revoke the
     * current Sanctum token.
     */
    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        // Revoke the current access token
        $request->user()->currentAccessToken()->delete();

        // Log the user out of the session
        Auth::logout();

        // Regenerate the session ID to prevent fixation
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    /**
     * Return the authenticated user's profile with department relationship.
     */
    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->load('department');

        return response()->json([
            'success' => true,
            'user'    => [
                'id'                  => $user->id,
                'name'                => $user->name,
                'email'               => $user->email,
                'phone'               => $user->phone,
                'role'                => $user->role,
                'avatar'              => $user->avatar,
                'language_preference' => $user->language_preference,
                'is_active'           => $user->is_active,
                'last_login_at'       => $user->last_login_at?->toISOString(),
                'department'          => $user->department !== null
                    ? [
                        'id'   => $user->department->id,
                        'name' => $user->department->name,
                    ]
                    : null,
            ],
        ]);
    }
}
