<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for updating AI configuration (admin panel).
 *
 * SECURITY: Validates nested ai_config array with bilingual greetings,
 * routing strategy, and AI message limits.
 */
final class UpdateAIConfigRequest extends FormRequest
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
            'ai_config' => 'required|array',
            'ai_config.greeting_en' => 'nullable|string|max:1000',
            'ai_config.greeting_bm' => 'nullable|string|max:1000',
            'ai_config.routing_strategy' => [
                'nullable',
                'string',
                Rule::in(['round_robin', 'least_loaded', 'skill_based', 'priority_based']),
            ],
            'ai_config.max_ai_messages' => 'nullable|integer|min:1|max:50',
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
            'ai_config.required' => 'The AI configuration is required.',
            'ai_config.array' => 'The AI configuration must be an array.',
            'ai_config.greeting_en.string' => 'The English greeting must be a string.',
            'ai_config.greeting_en.max' => 'The English greeting may not exceed 1000 characters.',
            'ai_config.greeting_bm.string' => 'The Bahasa Malaysia greeting must be a string.',
            'ai_config.greeting_bm.max' => 'The Bahasa Malaysia greeting may not exceed 1000 characters.',
            'ai_config.routing_strategy.in' => 'The routing strategy must be one of: round_robin, least_loaded, skill_based, priority_based.',
            'ai_config.max_ai_messages.integer' => 'The maximum AI messages must be an integer.',
            'ai_config.max_ai_messages.min' => 'The maximum AI messages must be at least 1.',
            'ai_config.max_ai_messages.max' => 'The maximum AI messages may not be greater than 50.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('ai_config.greeting_en') && $this->input('ai_config.greeting_en') !== null) {
            $greetingEn = $this->input('ai_config');
            $greetingEn['greeting_en'] = trim($greetingEn['greeting_en']);
            $this->merge(['ai_config' => $greetingEn]);
        }

        if ($this->has('ai_config.greeting_bm') && $this->input('ai_config.greeting_bm') !== null) {
            $greetingBm = $this->input('ai_config');
            $greetingBm['greeting_bm'] = trim($greetingBm['greeting_bm']);
            $this->merge(['ai_config' => $greetingBm]);
        }
    }
}
