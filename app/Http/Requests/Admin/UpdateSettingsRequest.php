<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Setting;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Form request for batch-updating Application Settings.
 *
 * SECURITY: All settings updates are restricted to admins.
 * Validates the settings array structure, ensuring each entry
 * has a valid key, value, and type.
 */
final class UpdateSettingsRequest extends FormRequest
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
            'settings' => 'required|array|min:1',
            'settings.*.key' => 'required|string|max:255',
            'settings.*.value' => 'required|string',
            'settings.*.type' => [
                'required',
                'string',
                'in:string,integer,boolean,json,text',
            ],
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
            'settings.required' => 'The settings array is required.',
            'settings.array' => 'The settings must be an array.',
            'settings.min' => 'At least one setting must be provided.',
            'settings.*.key.required' => 'Each setting must have a key.',
            'settings.*.key.string' => 'The setting key must be a string.',
            'settings.*.key.max' => 'The setting key may not exceed 255 characters.',
            'settings.*.value.required' => 'Each setting must have a value.',
            'settings.*.value.string' => 'The setting value must be a string.',
            'settings.*.type.required' => 'Each setting must have a type.',
            'settings.*.type.string' => 'The setting type must be a string.',
            'settings.*.type.in' => 'The setting type must be one of: string, integer, boolean, json, text.',
        ];
    }
}
