<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Department;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing Department.
 *
 * SECURITY: All input is validated and sanitized before processing.
 * All fields are optional — only provided fields are updated.
 * Only admins can update departments.
 */
final class UpdateDepartmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $department = $this->route('department');

        return $this->user()->can('update', $department);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $departmentId = $this->route('department')?->id;

        return [
            'name_en' => 'sometimes|required|string|max:255',
            'name_bm' => 'sometimes|required|string|max:255',
            'description_en' => 'nullable|string|max:1000',
            'description_bm' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string',
            'is_active' => 'boolean',
            'priority' => 'sometimes|required|integer|min:0|max:100',
            'max_queue_size' => 'sometimes|required|integer|min:1|max:100',
            'max_agents' => 'sometimes|required|integer|min:1|max:50',
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
            'name_en.required' => 'The English department name is required when provided.',
            'name_en.max' => 'The English department name may not exceed 255 characters.',
            'name_bm.required' => 'The Bahasa Malaysia department name is required when provided.',
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
