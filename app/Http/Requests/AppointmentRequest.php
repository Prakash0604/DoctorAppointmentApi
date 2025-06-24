<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\ValidDoctor;
use App\Rules\ValidScheduleTime;
use App\Rules\ValidAppointmentDate;


class AppointmentRequest extends FormRequest
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
        $doctorId = $this->input('doctor_id');
        $appointmentDate = $this->input('appointment_date');
        $startTime = $this->input('start_time');
        $endTime = $this->input('end_time');
        // dd($doctorId,$appointmentDate,$startTime,$endTime);
        return [
            'doctor_id' => ['bail', 'required', new ValidDoctor()],
            'appointment_date' => ['bail', 'required', 'date', new ValidAppointmentDate($doctorId)],
            'start_time' => [
                'bail',
                'required',
                'date_format:H:i',
                new ValidScheduleTime($doctorId, $appointmentDate, $startTime, $endTime)
            ],
            'end_time' => [
                'bail',
                'required',
                'date_format:H:i',
                new ValidScheduleTime($doctorId, $appointmentDate, $startTime, $endTime)
            ],
            'status'=>'nullable|in:booked,completed,cancelled',
            'notes'=>'nullable|string'
        ];
    }
}
