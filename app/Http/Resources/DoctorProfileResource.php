<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorProfileResource extends JsonResource
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
            'user_id' => $this->user_id,
            'user' => $this->user ? [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
            ] : null,
            'specializations' => $this->specializationNames(),

            'experience' => $this->experience,
            'qualification' => $this->qualification,
            'bio' => $this->bio,
            'consultation_fee' => $this->consultation_fee,
            'clinic_name' => $this->clinic_name,
            'clinic_address' => $this->clinic_address,
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,

            'schedule' =>  ScheduleResource::collection($this->whenLoaded('schedules')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
