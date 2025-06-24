<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ValidScheduleTime implements Rule
{
    protected $doctorId;
    protected $appointmentDate;
    protected $startTime;
    protected $endTime;
    protected $errorMessage = 'The appointment time is invalid.';

    public function __construct($doctorId, $appointmentDate, $startTime, $endTime)
    {
        $this->doctorId = $doctorId;
        $this->appointmentDate = $appointmentDate;
        $this->startTime = $startTime;
        $this->endTime = $endTime;
    }

    public function passes($attribute, $value)
    {
        if (!$this->doctorId || !$this->appointmentDate || !$this->startTime || !$this->endTime) {
            $this->errorMessage = 'Doctor, date, start time, or end time is missing.';
            return false;
        }

        $date = Carbon::parse($this->appointmentDate);
        $dayOfWeek = $date->dayOfWeek;

        $schedule = DB::table('doctor_profiles')
            ->join('schedules', 'doctor_profiles.id', '=', 'schedules.doctor_profile_id')
            ->where('doctor_profiles.user_id', $this->doctorId)
            ->whereJsonContains('schedules.day_of_week', $dayOfWeek)
            ->whereTime('schedules.start_time', '<=', $this->startTime)
            ->whereTime('schedules.end_time', '>=', $this->endTime)
            ->first();

        if (!$schedule) {
            $this->errorMessage = 'The selected appointment time does not match any schedule for the doctor.';
            return false;
        }

        $start = Carbon::createFromFormat('H:i', $this->startTime);
        $end = Carbon::createFromFormat('H:i', $this->endTime);

        $actualDuration = $start->diffInMinutes($end);
        $expectedDuration = (int) $schedule->slot_duration;

        if ($actualDuration > $expectedDuration) {
            $this->errorMessage = "The selected time slot must be between {$expectedDuration} minutes.";
            return false;
        }

        return true;
    }



    public function message()
    {
        return $this->errorMessage;
    }
}
