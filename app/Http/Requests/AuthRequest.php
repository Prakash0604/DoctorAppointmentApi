<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AuthRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string',
        ];

        if ($this->routeIs('auth.register')) {
            $rules['name'] = 'required|string|max:255';
            $rules['role_id'] = 'required|exists:roles,id';
            $rules['email'] .= '|unique:users';
            $rules['password'] .= '|min:8|confirmed';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The name field is required.',
            'email.required' => 'The email field is required.',
            'email.email' => 'The email must be a valid email address.',
            'email.unique' => 'The email has already been taken.',
            'password.required' => 'The password field is required.',
            'password.min' => 'The password must be at least 8 characters.',
            'password.confirmed' => 'The password confirmation does not match.',
            'role_id.required' => 'The Role is required',
            'role_id.exists' => 'Role must be a valid type.'
        ];
    }
}
