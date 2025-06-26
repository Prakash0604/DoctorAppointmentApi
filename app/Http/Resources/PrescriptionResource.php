<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'doctor' => $this->doctor ? [
                'id' => $this->doctor->id,
                'name' => $this->doctor->name,
            ] : null,
            'patient_id' => $this->patient_id,
            'patient' => $this->patient ? [
                'id' => $this->patient->id,
                'name' => $this->patient->name,
            ] : null,
            'appointment_id' => $this->appointment_id,
            'appointment' => new AppointmentResource($this->whenLoaded('appointment')),
            'notes' => $this->notes,
            'prescription_item' => PrescriptionItemResource::collection($this->whenLoaded('prescriptionItem')),
            'created_by' => $this->createBy ? $this->createBy->name : null,
            'updated_by' => $this->updatedBy ? $this->updatedBy->name : null,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
