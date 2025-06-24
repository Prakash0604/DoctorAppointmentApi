<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\DB;
class ValidDoctor implements Rule
{
    public function passes($attribute, $value)
    {
        return DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->where('users.id', $value)
            ->where('roles.name', 'Doctor')
            ->exists();
    }

    public function message()
    {
        return 'The selected doctor is invalid or is not a Doctor.';
    }
}