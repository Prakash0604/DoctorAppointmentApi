<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Traits\Blameable;
use Illuminate\Support\Facades\Auth;

class Appointment extends Model
{
    use HasFactory,Blameable,SoftDeletes;
    protected $fillable=['doctor_id','patient_id','appointment_date','start_time','end_time','status','notes','created_by','updated_by'];

     protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (Auth::check()) {
                $model->patient_id = Auth::id();
            }
        });
    }

    public function doctor(){
        return $this->belongsTo(User::class,'doctor_id');
    }

    public function patient(){
        return $this->belongsTo(User::class,'patient_id');
    }

    public function createBy(){
        return $this->belongsTo(User::class,'created_by');
    }

    public function updatedBy(){
        return $this->belongsTo(User::class,'updated_by');
    }

}
