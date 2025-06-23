<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class ProfileUpdateRequest extends FormRequest
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
        return [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($this->user()->id)],
            'profile_image'=>'nullable|mimes:png,jpg,jpeg',
            'phone'=>'nullable|numeric|min:5',
            'address'=>'nullable|string',
            'gender'=>'nullable|in:male,female,others',
            'dob'=>'nullable|date',
        ];
    }
}
