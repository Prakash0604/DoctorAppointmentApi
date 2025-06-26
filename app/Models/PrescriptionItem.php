<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Blameable;

class PrescriptionItem extends Model
{
    use HasFactory,Blameable, SoftDeletes;
    protected $fillable = ['prescription_id', 'medicine_name', 'dosage', 'frequency', 'duration', 'instructions','created_by','updated_by','deleted_by'];

    public function prescription()
    {
        return $this->belongsTo(Prescription::class, 'prescription_id');
    }
}
