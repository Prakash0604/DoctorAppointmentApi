<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

if (!function_exists('getDayName')) {
    function getDayName($dayNumber)
    {
        $days = [
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday',
        ];

        return $days[$dayNumber] ?? 'Invalid Day';
    }
}

if (!function_exists('getCurrentUser')) {
    function getCurrentUser()
    {
        // $users = User::find(Auth::guard('admin')->id());
        $user = DB::table('users')
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->select('users.id','users.name','users.email','users.profile_image','phone','address','gender','dob','status', 'roles.name as role_name')
            ->where('users.id', Auth::id())
            ->first();
        return $user;
    }
}
