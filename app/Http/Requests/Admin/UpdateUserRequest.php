<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

/**
 * Form request for updating an existing User (admin panel).
 *
 * SECURITY: All fields are optional. Password is only hashed if provided.
 * Email uniqueness excludes the current user. Role changes are validated
 * against the existing department assignment.
 */
final class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $this->user()->can('update', $target);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var User $target */
        $target = $this->route('user');

        return [
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($target->id),
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => [
                'sometimes',
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
                Rule::unique('users', 'phone')->ignore($target->id),
            ],
            'language_preference' => [
                'nullable',
                Rule::in(['en', 'bm']),
            ],
            'is_active' => 'sometimes|boolean',
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
            'name.required' => 'The user name is required when provided.',
            'name.max' => 'The user name may not exceed 255 characters.',
            'email.required' => 'The email address is required when provided.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'An account with this email address already exists.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role.required' => 'The user role is required when provided.',
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

    /**
     * Handle the password hashing before saving.
     *
     * Call this method after validation to conditionally hash the password.
     * Returns the validated data with the password hashed if provided.
     *
     * @return array<string, mixed>
     */
    public function validatedWithHashedPassword(): array
    {
        $validated = $this->validated();

        if (isset($validated['password']) && $validated['password'] !== null) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        return $validated;
    }
}
