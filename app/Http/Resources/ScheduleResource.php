<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ScheduleResource extends JsonResource
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
            'doctor_profile_id' => $this->doctor_profile_id,
            'day_of_week' => $this->day_of_week,
            'day_names' => $this->day_names,
            'start_time' =>  $this->start_time  ? \Carbon\Carbon::parse($this->start_time)->format('h:i A') : null,
            'end_time' =>  $this->end_time  ? \Carbon\Carbon::parse($this->end_time)->format('h:i A') : null,

            'slot_duration' => $this->slot_duration,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
        ];
    }
}
