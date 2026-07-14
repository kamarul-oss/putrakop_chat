<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for creating a new Department.
 *
 * SECURITY: All input is validated and sanitized before processing.
 * Only admins can create departments.
 */
final class StoreDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', Department::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_bm' => 'required|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_bm' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'required|integer|min:0|max:100',
            'max_queue_size' => 'required|integer|min:1|max:100',
            'max_agents' => 'required|integer|min:1|max:50',
            'business_hours' => 'nullable|json',
            'ai_config' => 'nullable|json',
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
            'name_en.required' => 'The English department name is required.',
            'name_en.max' => 'The English department name may not exceed 255 characters.',
            'name_bm.required' => 'The Bahasa Malaysia department name is required.',
            'name_bm.max' => 'The Bahasa Malaysia department name may not exceed 255 characters.',
            'description_en.max' => 'The English description may not exceed 1000 characters.',
            'description_bm.max' => 'The Bahasa Malaysia description may not exceed 1000 characters.',
            'color.max' => 'The color value may not exceed 7 characters (e.g., #FF0000).',
            'priority.min' => 'The priority must be at least 0.',
            'priority.max' => 'The priority may not be greater than 100.',
            'max_queue_size.min' => 'The maximum queue size must be at least 1.',
            'max_queue_size.max' => 'The maximum queue size may not be greater than 100.',
            'max_agents.min' => 'The maximum agents must be at least 1.',
            'max_agents.max' => 'The maximum agents may not be greater than 50.',
            'business_hours.json' => 'The business hours must be valid JSON.',
            'ai_config.json' => 'The AI configuration must be valid JSON.',
        ];
    }
}
