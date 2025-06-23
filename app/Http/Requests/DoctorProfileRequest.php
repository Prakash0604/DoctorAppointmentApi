<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DoctorProfileRequest extends FormRequest
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
        $rule = [
            'specialization_id' => 'required|array',
            'specialization_id.*' => 'required|exists:specializations,id',

            'experience' => 'required|string',
            'qualification' => 'required|string',
            'bio' => 'nullable|string',
            'consultation_fee' => 'nullable|numeric',
            'clinic_name' => 'required|string',
            'clinic_address' => 'required|string',
            'latitude' => 'nullable|string',
            'longitude' => 'nullable|string'
        ];

        if ($this->has('day_of_week')) {
            $rule['day_of_week']   = 'required|array|min:1';
            $rule['day_of_week.*'] = 'integer|between:0,6';
            $rule['start_time']    = 'required|date_format:H:i';
            $rule['end_time']      = 'required|date_format:H:i|after:start_time';
            $rule['slot_duration'] = 'required|integer|min:5|max:120';
        }

        return $rule;
    }
}
