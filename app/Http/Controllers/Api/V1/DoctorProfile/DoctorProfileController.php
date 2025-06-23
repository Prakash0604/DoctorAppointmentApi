<?php

namespace App\Http\Controllers\Api\V1\DoctorProfile;

use App\Http\Controllers\Controller;
use App\Http\Requests\DoctorProfileRequest;
use App\Http\Resources\DoctorProfileResource;
use App\Http\Resources\UserResource;
use App\Models\DoctorProfile;
use App\Models\Schedule;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DoctorProfileController extends Controller
{
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $doctors = User::whereHas('role', function ($row) {
            $row->where('name', 'Doctor');
        })->where('status', 'active')->latest()->paginate($perPage);

        if ($doctors->isEmpty()) {
            return $this->sendError('Doctor not found', 404);
        }
        return $this->sendPaginatedResponse($doctors, UserResource::class, 'Doctor fetched successfully.');
    }

    public function getDoctor($id)
    {
        try {
            $doctors = User::with('doctorProfile.schedule', 'role')->whereHas('role', function ($row) {
                $row->where('name', 'Doctor');
            })->where('status', 'active')->find($id);
            if (!$doctors) {
                return $this->sendError('Doctor Profile not found.', 404);
            }
            return $this->sendResponse(new UserResource($doctors), 'Doctor Profile details fetched.');
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error : ' . $e->getMessage(), 500);
        }
    }
    public function storeOrUpdateSpecialization(DoctorProfileRequest $request)
    {
        DB::beginTransaction();
        try {
            $userId = Auth::id();
            $data = $request->validated();
            $profile = DoctorProfile::updateOrCreate(
                ['user_id' => $userId],
                $data
            );

            if ($profile) {
                Schedule::updateOrCreate(
                    [
                        'doctor_profile_id' => $profile->id,
                    ],
                    [
                        'day_of_week' => $request->day_of_week,
                        'start_time' => $request->start_time,
                        'end_time' => $request->end_time,
                        'slot_duration' => $request->slot_duration
                    ]
                );
            }

            DB::commit();
            return $this->sendResponse(new DoctorProfileResource($profile), 'Doctor Profile saved successfully.', 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to save profile: ' . $e->getMessage(), 500);
        }
    }




    public function show($id)
    {
        try {
            $profile = DoctorProfile::find($id);
            if (!$profile) {
                return $this->sendError('Doctor Profile not found.', 404);
            }
            return $this->sendResponse(new DoctorProfileResource($profile), 'Doctor Profile details fetched.');
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error : ' . $e->getMessage(), 500);
        }
    }


    public function showOwnProfile()
    {
        try {
            $auth = Auth::id();
            $profile = DoctorProfile::with('schedule')->where('user_id', $auth)->first();
            if (!$profile) {
                return $this->sendError('Doctor Profile not found.', 404);
            }
            return $this->sendResponse(new DoctorProfileResource($profile), 'Doctor Profile details fetched.');
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error: ' . $e->getMessage(), 500);
        }
    }
}
