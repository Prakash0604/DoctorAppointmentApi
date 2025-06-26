<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
class ValidPatient implements Rule
{
    public function passes($attribute, $value)
    {
        return DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $value)
            ->where('roles.name', 'Patient')
            ->exists();
    }

    public function message()
    {
        return 'The selected patient is invalid or is not a patient.';
    }
}