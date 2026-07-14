<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\RoutingRule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DepartmentConfigController extends Controller
{
    /**
     * Get all routing rules for a department.
     */
    public function getRoutingRules(Department $department): JsonResponse
    {
        $rules = $department->routingRules()
            ->orderBy('priority', 'asc')
            ->get();

        return response()->json([
            'success' => true,
            'data'    => [
                'rules' => $rules,
            ],
        ]);
    }

    /**
     * Replace all routing rules for a department.
     */
    public function updateRoutingRules(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'rules'                         => 'required|array|min:1',
            'rules.*.rule_type'             => 'required|string|in:round_robin,skill_based,least_loaded,random',
            'rules.*.priority'              => 'nullable|integer|min:0',
            'rules.*.is_active'             => 'nullable|boolean',
            'rules.*.config'                => 'nullable|array',
            'rules.*.conditions'            => 'nullable|array',
        ]);

        DB::beginTransaction();

        try {
            // Remove existing rules
            $department->routingRules()->delete();

            // Create new rules
            foreach ($validated['rules'] as $index => $ruleData) {
                $department->routingRules()->create([
                    'rule_type'   => $ruleData['rule_type'],
                    'priority'    => $ruleData['priority'] ?? $index,
                    'is_active'   => $ruleData['is_active'] ?? true,
                    'config'      => $ruleData['config'] ?? null,
                    'conditions'  => $ruleData['conditions'] ?? null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Routing rules updated',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to update routing rules: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get the AI configuration for a department.
     */
    public function getAIConfig(Department $department): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => [
                'ai_config' => $department->ai_config ?? [
                    'greeting_en'        => null,
                    'greeting_bm'        => null,
                    'routing_strategy'   => 'round_robin',
                    'max_ai_messages'    => 5,
                ],
            ],
        ]);
    }

    /**
     * Update the AI configuration for a department.
     */
    public function updateAIConfig(Request $request, Department $department): JsonResponse
    {
        $validated = $request->validate([
            'ai_config'                              => 'required|array',
            'ai_config.greeting_en'                  => 'nullable|string|max:1000',
            'ai_config.greeting_bm'                  => 'nullable|string|max:1000',
            'ai_config.routing_strategy'             => 'nullable|in:round_robin,least_loaded,skill_based,priority_based',
            'ai_config.max_ai_messages'              => 'nullable|integer|min:1|max:50',
        ]);

        $currentConfig = $department->ai_config ?? [];
        $updatedConfig = array_merge($currentConfig, $validated['ai_config']);

        $department->update(['ai_config' => $updatedConfig]);

        return response()->json([
            'success' => true,
            'data'    => [
                'ai_config' => $department->fresh()->ai_config,
            ],
            'message' => 'AI configuration updated',
        ]);
    }
}
