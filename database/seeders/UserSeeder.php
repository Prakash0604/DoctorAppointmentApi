<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [

            [
                'name' => 'Admin User',
                'email' => 'admin@gmail.com',
                'password' => bcrypt('123456789'),
                'role_id' => 1,
                'status' => 'active',
                'profile_image' => 'default.png',
                'phone' => '1234567890',
                'address' => '123 Admin Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],

            [
                'name' => 'Doctor User',
                'email' => 'doctor@gmail.com',
                'password' => bcrypt('123456789'),
                'role_id' => 2,
                'status' => 'active',
                'profile_image' => 'default.png',
                'phone' => '1234567890',
                'address' => '123 doctor Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Patient User',
                'email' => 'patient@gmail.com',
                'password' => bcrypt('123456789'),
                'role_id' => 3,
                'status' => 'active',
                'profile_image' => 'default.png',
                'phone' => '9800000000',
                'address' => '123 Patient Street',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ];

        DB::table('users')->insert($data);
    }
}
