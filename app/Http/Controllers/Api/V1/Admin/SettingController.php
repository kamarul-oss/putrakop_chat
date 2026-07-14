<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller
{
    /**
     * List all settings, optionally filtered by group.
     */
    public function index(Request $request): JsonResponse
    {
        $query = Setting::query();

        // Filter by group
        if ($request->filled('group')) {
            $query->where('group', $request->input('group'));
        }

        $settings = $query->orderBy('group', 'asc')
                          ->orderBy('key', 'asc')
                          ->get();

        return response()->json([
            'success' => true,
            'data'    => $settings,
        ]);
    }

    /**
     * Bulk update settings.
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'settings'            => 'required|array',
            'settings.*.key'      => 'required|string|max:255',
            'settings.*.value'    => 'required|string',
            'settings.*.type'     => 'required|in:string,integer,boolean,json,text',
            'settings.*.group'    => 'nullable|string|max:255',
            'settings.*.label'    => 'nullable|string|max:255',
            'settings.*.description' => 'nullable|string',
        ]);

        foreach ($validated['settings'] as $settingData) {
            Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value'       => $settingData['value'],
                    'type'        => $settingData['type'],
                    'group'       => $settingData['group'] ?? null,
                    'label'       => $settingData['label'] ?? null,
                    'description' => $settingData['description'] ?? null,
                ]
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Settings updated',
        ]);
    }

    /**
     * Get a single setting by key.
     */
    public function show(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $setting,
        ]);
    }

    /**
     * Delete a setting by key.
     */
    public function destroy(string $key): JsonResponse
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Setting not found',
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Setting deleted',
        ]);
    }
}
