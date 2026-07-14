<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating chat routing rules (admin panel).
 *
 * SECURITY: Validates that at least one rule is provided. Each rule
 * must specify a valid rule_type and nested config/conditions arrays.
 */
final class UpdateRoutingRulesRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('update', Setting::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'rules' => 'required|array|min:1',
            'rules.*.rule_type' => [
                'required',
                'string',
                Rule::in(['round_robin', 'skill_based', 'least_loaded', 'random']),
            ],
            'rules.*.priority' => 'nullable|integer|min:0',
            'rules.*.is_active' => 'nullable|boolean',
            'rules.*.config' => 'nullable|array',
            'rules.*.conditions' => 'nullable|array',
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'rules.required' => 'At least one routing rule is required.',
            'rules.array' => 'The rules must be an array.',
            'rules.min' => 'At least one routing rule is required.',
            'rules.*.rule_type.required' => 'Each rule must specify a rule type.',
            'rules.*.rule_type.in' => 'The rule type must be one of: round_robin, skill_based, least_loaded, random.',
            'rules.*.priority.integer' => 'The rule priority must be an integer.',
            'rules.*.priority.min' => 'The rule priority must be at least 0.',
            'rules.*.is_active.boolean' => 'The is_active flag must be a boolean.',
            'rules.*.config.array' => 'The rule config must be an array.',
            'rules.*.conditions.array' => 'The rule conditions must be an array.',
        ];
    }
}
