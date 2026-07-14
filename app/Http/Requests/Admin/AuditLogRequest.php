<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\AuditLog;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for listing/filtering Audit Logs (admin panel).
 *
 * SECURITY: All filter parameters are optional. Date range validation
 * ensures start_date is never after end_date.
 */
final class AuditLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', AuditLog::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'auditable_type' => 'nullable|string|max:255',
            'auditable_id' => 'nullable|integer',
            'event' => [
                'nullable',
                'string',
                Rule::in(['created', 'updated', 'deleted']),
            ],
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date|before_or_equal:end_date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
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
            'auditable_type.max' => 'The auditable type may not exceed 255 characters.',
            'auditable_id.integer' => 'The auditable ID must be an integer.',
            'event.string' => 'The event must be a string.',
            'event.in' => 'The event must be one of: created, updated, deleted.',
            'user_id.exists' => 'The selected user does not exist.',
            'start_date.date' => 'The start date must be a valid date.',
            'start_date.before_or_equal' => 'The start date must be before or equal to the end date.',
            'end_date.date' => 'The end date must be a valid date.',
            'end_date.after_or_equal' => 'The end date must be after or equal to the start date.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('auditable_type') && $this->input('auditable_type') !== null) {
            $this->merge(['auditable_type' => trim($this->input('auditable_type'))]);
        }
    }
}
