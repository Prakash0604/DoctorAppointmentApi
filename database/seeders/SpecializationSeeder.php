<?php

namespace Database\Seeders;

use App\Models\Specialization;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SpecializationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data=['Cardiology','Dermatology','Endocrinology','Gastroenterology','Hematology','General Surgery','Cardiothoracic Surgery','Neurosurgery','Physical Medicine and Rehabilitation','Emergency Medicine'];

        foreach ($data as $spec) {
            Specialization::create(['name' => $spec]);
        }
    }
}
