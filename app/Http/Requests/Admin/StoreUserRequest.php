<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Form request for creating a new User (admin panel).
 *
 * SECURITY: Validates and sanitizes all input before processing.
 * Department assignment is required for agent and manager roles.
 * Password confirmation is enforced.
 */
final class StoreUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email'),
            ],
            'password' => 'required|string|min:8|confirmed',
            'role' => [
                'required',
                Rule::in(['customer', 'agent', 'manager', 'admin']),
            ],
            'department_id' => [
                'required_if:role,agent,manager',
                'nullable',
                'exists:departments,id',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('users', 'phone'),
            ],
            'language_preference' => [
                'nullable',
                Rule::in(['en', 'bm']),
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
            'name.required' => 'The user name is required.',
            'name.max' => 'The user name may not exceed 255 characters.',
            'email.required' => 'The email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'An account with this email address already exists.',
            'password.required' => 'The password is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.required' => 'The user role is required.',
            'role.in' => 'The selected role is invalid.',
            'department_id.required_if' => 'The department is required for agent and manager roles.',
            'department_id.exists' => 'The selected department does not exist.',
            'phone.unique' => 'An account with this phone number already exists.',
            'language_preference.in' => 'The language preference must be English (en) or Bahasa Malaysia (bm).',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string inputs
        if ($this->has('name')) {
            $this->merge(['name' => trim($this->input('name'))]);
        }

        if ($this->has('email')) {
            $this->merge(['email' => strtolower(trim($this->input('email')))]);
        }

        if ($this->has('phone') && $this->input('phone') !== null) {
            $this->merge(['phone' => trim($this->input('phone'))]);
        }
    }
}
