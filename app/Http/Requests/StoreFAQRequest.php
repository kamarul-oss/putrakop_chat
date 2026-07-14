<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for Agent FAQ creation.
 *
 * SECURITY: Validates and sanitizes all input before processing.
 * Agents can only create entries for their own department.
 */
final class StoreFAQRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', \App\Models\DepartmentResponse::class);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'response_key' => [
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('department_responses', 'response_key')
                    ->where('department_id', $this->user()->department_id),
            ],
            'content_en' => 'required|string|max:5000',
            'content_bm' => 'required|string|max:5000',
            'trigger_keywords' => 'nullable|array|max:20',
            'trigger_keywords.*' => 'string|max:100',
            'priority' => 'integer|min:0|max:100',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'response_key.required' => 'Response key is required.',
            'response_key.regex' => 'Response key may only contain letters, numbers, spaces, hyphens, and underscores.',
            'response_key.unique' => 'A response with this key already exists in your department.',
            'content_en.required' => 'English content is required.',
            'content_bm.required' => 'Bahasa Malaysia content is required.',
            'trigger_keywords.max' => 'Maximum 20 trigger keywords allowed.',
            'trigger_keywords.*.max' => 'Each keyword may not exceed 100 characters.',
            'priority.min' => 'Priority must be between 0 and 100.',
            'priority.max' => 'Priority must be between 0 and 100.',
        ];
    }
}
