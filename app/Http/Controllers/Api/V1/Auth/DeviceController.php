<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

final class DeviceController extends Controller
{
    /**
     * Register a new device or update an existing one.
     *
     * The device fingerprint is hashed with SHA-256 before storage
     * to prevent raw fingerprint persistence.
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => ['required', 'string', 'max:255'],
            'device_name'        => ['nullable', 'string', 'max:255'],
            'device_type'        => ['nullable', 'string', 'max:50'],
            'browser'            => ['nullable', 'string', 'max:100'],
            'operating_system'   => ['nullable', 'string', 'max:100'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $hashedFingerprint = UserDevice::hashFingerprint($validated['device_fingerprint']);

        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $hashedFingerprint)
            ->first();

        $isNew = false;

        if ($device === null) {
            $device = UserDevice::create([
                'user_id'           => $user->id,
                'device_fingerprint' => $hashedFingerprint,
                'device_name'       => $validated['device_name'] ?? null,
                'device_type'       => $validated['device_type'] ?? null,
                'browser'           => $validated['browser'] ?? null,
                'operating_system'  => $validated['operating_system'] ?? null,
                'ip_address'        => $request->ip(),
                'user_agent'        => $request->userAgent(),
                'is_trusted'        => false,
                'last_active_at'    => now(),
            ]);

            $isNew = true;
        } else {
            $device->update([
                'device_name'      => $validated['device_name'] ?? $device->device_name,
                'device_type'      => $validated['device_type'] ?? $device->device_type,
                'browser'          => $validated['browser'] ?? $device->browser,
                'operating_system' => $validated['operating_system'] ?? $device->operating_system,
                'ip_address'       => $request->ip(),
                'user_agent'       => $request->userAgent(),
                'last_active_at'   => now(),
            ]);

            $device->refresh();
        }

        return response()->json([
            'success' => true,
            'device'  => [
                'id'               => $device->id,
                'device_name'      => $device->device_name,
                'device_type'      => $device->device_type,
                'browser'          => $device->browser,
                'operating_system' => $device->operating_system,
                'is_trusted'       => $device->is_trusted,
                'last_active_at'   => $device->last_active_at?->toISOString(),
            ],
            'is_new' => $isNew,
        ]);
    }

    /**
     * Verify whether a device fingerprint is trusted.
     *
     * This is a public endpoint used during the login flow
     * to check if the device requires additional verification.
     */
    public function verify(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_fingerprint' => ['required', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $hashedFingerprint = UserDevice::hashFingerprint($validated['device_fingerprint']);

        $device = UserDevice::where('user_id', $user->id)
            ->where('device_fingerprint', $hashedFingerprint)
            ->first();

        if ($device === null) {
            return response()->json([
                'success'    => true,
                'is_trusted' => false,
                'device'     => null,
            ]);
        }

        return response()->json([
            'success'    => true,
            'is_trusted' => $device->is_trusted,
            'device'     => [
                'id'               => $device->id,
                'device_name'      => $device->device_name,
                'device_type'      => $device->device_type,
                'browser'          => $device->browser,
                'operating_system' => $device->operating_system,
                'is_trusted'       => $device->is_trusted,
                'last_active_at'   => $device->last_active_at?->toISOString(),
            ],
        ]);
    }

    /**
     * Mark a device as trusted.
     *
     * Only the device owner can trust their own device.
     */
    public function trust(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'exists:user_devices,id'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $device = UserDevice::findOrFail($validated['device_id']);

        if ($device->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'device_id' => 'You do not own this device.',
            ]);
        }

        $device->trust();

        return response()->json([
            'success' => true,
            'message' => 'Device trusted successfully.',
        ]);
    }

    /**
     * Revoke a device — permanently delete the device record.
     *
     * Only the device owner can revoke their own device.
     */
    public function revoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_id' => ['required', 'exists:user_devices,id'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $device = UserDevice::findOrFail($validated['device_id']);

        if ($device->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'device_id' => 'You do not own this device.',
            ]);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device revoked successfully.',
        ]);
    }

    /**
     * List all devices for the authenticated user.
     */
    public function list(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $devices = UserDevice::where('user_id', $user->id)
            ->orderByDesc('last_active_at')
            ->get()
            ->map(fn (UserDevice $device) => [
                'id'               => $device->id,
                'device_name'      => $device->device_name,
                'device_type'      => $device->device_type,
                'browser'          => $device->browser,
                'operating_system' => $device->operating_system,
                'is_trusted'       => $device->is_trusted,
                'last_active_at'   => $device->last_active_at?->toISOString(),
                'created_at'       => $device->created_at->toISOString(),
            ]);

        return response()->json([
            'success' => true,
            'devices' => $devices,
        ]);
    }
}
