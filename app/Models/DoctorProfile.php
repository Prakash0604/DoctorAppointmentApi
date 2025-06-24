<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class DoctorProfile extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'specialization_id', 'experience', 'qualification', 'bio', 'consultation_fee', 'clinic_name', 'clinic_address', 'latitude', 'longitude'];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->user_id = Auth::id();
            }
        });
    }

    public function specializationNames()
    {
        return Specialization::whereIn('id', $this->specialization_id)->pluck('name');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function schedules()
    {
        return $this->hasMany(Schedule::class);
    }


    protected $casts = [
        'specialization_id' => 'array'
    ];
}
