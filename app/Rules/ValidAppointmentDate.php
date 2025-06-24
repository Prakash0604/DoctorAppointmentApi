<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValidAppointmentDate implements Rule
{
    protected $doctorId;

    public function __construct($doctorId)
    {
        $this->doctorId = $doctorId;
    }

    public function passes($attribute, $value)
    {
        if (!$this->doctorId || !$value) {
            return false;
        }
        $dateTime = \Carbon\Carbon::parse($value);
        $dayIndex = $dateTime->dayOfWeek; 
        $schedule = DB::table('doctor_profiles')
            ->join('schedules', 'doctor_profiles.id', '=', 'schedules.doctor_profile_id')
            ->where('doctor_profiles.user_id', $this->doctorId)
            ->whereJsonContains('schedules.day_of_week', $dayIndex)
            ->first();
        return $schedule !== null;
    }



    public function message()
    {
        return 'The selected appointment date is not available for this doctor.';
    }
}
