<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
    use App\Helpers\DayHelper;

class Schedule extends Model
{
    use HasFactory;
    protected $fillable=['doctor_profile_id','day_of_week','start_time','end_time','slot_duration'];

    public function doctorProfile(){
        return $this->belongsTo(DoctorProfile::class,'doctor_profile_id');
    }


public function getDayNamesAttribute()
{
    return collect($this->day_of_week)
        ->map(fn($day) => getDayName($day))
        ->toArray();
}

     protected $casts = [
        'day_of_week' => 'array',
    ];
}
