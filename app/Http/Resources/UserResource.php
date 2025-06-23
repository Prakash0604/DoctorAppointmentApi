<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
            'name' => $this->name,
            'email' => $this->email,
            'profile_image' => $this->profile_image,
            'phone' => $this->phone,
            'address' => $this->address,
            'gender' => $this->gender,
            'dob' => $this->dob,
            'status' => $this->status,
            'role_id' => $this->role_id,
            'role' => $this->role ? [
                'id' => $this->role->id,
                'name' => $this->role->name
            ] : null,

            'doctor_profile' => new DoctorProfileResource($this->whenLoaded('doctorProfile')),
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
