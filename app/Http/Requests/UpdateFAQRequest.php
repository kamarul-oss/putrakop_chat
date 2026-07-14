<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DepartmentResponse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for Agent FAQ update.
 *
 * SECURITY: Only allows updating own entries within the same department.
 */
final class UpdateFAQRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var DepartmentResponse $response */
        $response = $this->route('response');

        return $this->user()->can('update', $response);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var DepartmentResponse $response */
        $response = $this->route('response');

        return [
            'response_key' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'regex:/^[a-zA-Z0-9\s\-_]+$/',
                Rule::unique('department_responses', 'response_key')
                    ->where('department_id', $this->user()->department_id)
                    ->ignore($response->id),
            ],
            'content_en' => 'sometimes|required|string|max:5000',
            'content_bm' => 'sometimes|required|string|max:5000',
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
            'priority.min' => 'Priority must be between 0 and 100.',
            'priority.max' => 'Priority must be between 0 and 100.',
        ];
    }
}
