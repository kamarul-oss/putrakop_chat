<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\KnowledgeBase;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for updating an existing Knowledge Base article (admin panel).
 *
 * SECURITY: All fields are optional. Only provided fields are validated and updated.
 * Route model binding provides the target KnowledgeBase instance.
 */
final class UpdateKnowledgeBaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        /** @var KnowledgeBase $target */
        $target = $this->route('knowledge_base');

        return $this->user()->can('update', $target);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title_en' => 'sometimes|required|string|max:255',
            'title_bm' => 'nullable|string|max:255',
            'content_en' => 'sometimes|required|string|max:10000',
            'content_bm' => 'nullable|string|max:10000',
            'department_id' => 'nullable|exists:departments,id',
            'category' => 'nullable|string|max:100',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0|max:100',
            'trigger_keywords' => 'nullable|array|max:20',
            'trigger_keywords.*' => 'string|max:100',
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
            'title_en.required' => 'The English title is required when provided.',
            'title_en.max' => 'The English title may not exceed 255 characters.',
            'title_bm.max' => 'The Bahasa Malaysia title may not exceed 255 characters.',
            'content_en.required' => 'The English content is required when provided.',
            'content_en.max' => 'The English content may not exceed 10000 characters.',
            'content_bm.max' => 'The Bahasa Malaysia content may not exceed 10000 characters.',
            'department_id.exists' => 'The selected department does not exist.',
            'category.max' => 'The category may not exceed 100 characters.',
            'priority.min' => 'The priority must be at least 0.',
            'priority.max' => 'The priority may not be greater than 100.',
            'trigger_keywords.array' => 'The trigger keywords must be an array.',
            'trigger_keywords.max' => 'The trigger keywords may not contain more than 20 items.',
            'trigger_keywords.*.string' => 'Each trigger keyword must be a string.',
            'trigger_keywords.*.max' => 'Each trigger keyword may not exceed 100 characters.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('title_en')) {
            $this->merge(['title_en' => trim($this->input('title_en'))]);
        }

        if ($this->has('title_bm') && $this->input('title_bm') !== null) {
            $this->merge(['title_bm' => trim($this->input('title_bm'))]);
        }

        if ($this->has('category') && $this->input('category') !== null) {
            $this->merge(['category' => trim($this->input('category'))]);
        }
    }
}
