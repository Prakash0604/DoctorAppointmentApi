<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'doctor_name' => $this->doctor->name,
            ] : null,
            'patient_id' => $this->patient_id,
            'patient' => $this->patient ? [
                'id' => $this->patient->id,
                'patient_name' => $this->patient->name,
            ] : null,
            'appointment_date' => $this->appointment_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'status' => $this->status,
            'notes' => $this->notes,
            'created_by' => $this->createBy->name,
            'updatedBy' => $this->updatedBy->name,

        ];
    }
}
