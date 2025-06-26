<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PrescriptionItemResource extends JsonResource
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
            'prescription_id' => $this->prescription_id,
            'prescription' => $this->prescription ? [
                'id' => $this->prescription->id,
                'doctor_id' => $this->prescription->doctor_id,
                'patient_id' => $this->prescription->patient_id,
                'appointment_id' => $this->prescription->appointment_id,
            ] : null,
            'medicine_name'=>$this->medicine_name,
            'dosage'=>$this->dosage,
            'frequency'=>$this->frequency,
            'duration'=>$this->duration,
            'instructions'=>$this->instructions
        ];
    }
}
