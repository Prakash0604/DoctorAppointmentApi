<?php

namespace App\Http\Requests;

use App\Rules\ValidPatient;
use Illuminate\Foundation\Http\FormRequest;

class PrescriptionRequest extends FormRequest
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
            'appointment_id' => 'required|exists:appointments,id',
            'patient_id' => ['bail', 'required', new ValidPatient()],
            'notes' => 'nullable|string',
        ];

        if ($this->has('medicine_name')) {
            $rule['medicine_name'] = 'required|array';
            $rule['medicine_name.*'] = 'required|string|min:2|max:100';

            $rule['dosage'] = 'required|array';
            $rule['dosage.*'] = 'required|string|min:2|max:50|regex:/^[a-zA-Z0-9\s]+$/';

            $rule['frequency'] = 'required|array';
            $rule['frequency.*'] = 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9\s\/]+$/';

            $rule['duration'] = 'required|array';
            $rule['duration.*'] = 'required|string|min:3|max:50|regex:/^[a-zA-Z0-9\s]+$/';

            $rule['instructions'] = 'nullable|array';
            $rule['instructions.*'] = 'nullable|string|min:3|max:100';
        }

        return $rule;
    }
}
