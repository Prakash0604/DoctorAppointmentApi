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
use Illuminate\Http\Response;

/**
 * @OA\Tag(
 *     name="Patient",
 *     description="API Endpoints for managing Doctor Profile"
 * )
 */
class DoctorProfileController extends Controller
{

      /**
     * @OA\Get(
     *     tags={"Patient"},
     *     path="/api/v1/patient/doctor-specialists",
     *     summary="Get Doctor List to patient",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */
    public function index(Request $request)
    {
        $perPage = $request->get('per_page', 10);
        $doctors = User::whereHas('role', function ($row) {
            $row->where('name', 'Doctor');
        })->where('status', 'active')->latest()->paginate($perPage);

        if ($doctors->isEmpty()) {
            return $this->sendError('Doctor not found', Response::HTTP_NOT_FOUND);
        }
        return $this->sendPaginatedResponse($doctors, UserResource::class, 'Doctor fetched successfully.', Response::HTTP_OK);
    }

       /**
     * @OA\Get(
     *     tags={"Patient"},
     *     path="/api/v1/patient/doctor-specialists/{id}",
     *     summary="Get Doctro Detail",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */

    public function getDoctor($id)
    {
        try {
            $doctors = User::with('doctorProfile.schedules', 'role')->whereHas('role', function ($row) {
                $row->where('name', 'Doctor');
            })->where('status', 'active')->find($id);
            if (!$doctors) {
                return $this->sendError('Doctor Profile not found.', Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse(new UserResource($doctors), 'Doctor Profile details fetched.', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

     /**
     * @OA\Post(
     *     path="/api/v1/doctor/doctor-specializations",
     *     summary="Update the shedule date of the doctor",
     *     tags={"Doctor"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"experience","qualification","day_of_week","start_time","end_time","slot_duration"},
     *             @OA\Property(property="experience", type="string", example="2yrs"),
     *             @OA\Property(property="qualification", type="string", example="Masters if Information Technologies"),
     *             @OA\Property(property="bio", type="string", example="I am a laravel web developer"),
     *             @OA\Property(property="consultation_fee", type="integer", example="25000"),
     *             @OA\Property(property="clinic_name", type="string", example="Gokumldham Social Clinic"),
     *             @OA\Property(property="clinic_address", type="string", example="Nepal- kathmandu"),
     *             @OA\Property(property="latitude", type="string", example="192.168.1.1"),
     *             @OA\Property(property="longitude", type="string", example="192.168.1.1"),
     *             @OA\Property(property="day_of_week", type="object", example="1,2,3"),
     *             @OA\Property(property="start_time", type="time", format="time", example="09:00"),
     *             @OA\Property(property="end_time", type="time", example="15:00"),
     *             @OA\Property(property="slot_duration", type="string", example="45"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Update Specification",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Specification Created/Updated Successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
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
                        'slot_duration' => $request->slot_duration,
                    ]
                );
            }

            DB::commit();
            return $this->sendResponse(new DoctorProfileResource($profile), 'Doctor Profile saved successfully.', Response::HTTP_OK);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->sendError('Failed to save profile: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }



    // public function show($id)
    // {
    //     try {
    //         $profile = DoctorProfile::find($id);
    //         if (!$profile) {
    //             return $this->sendError('Doctor Profile not found.', Response::HTTP_NOT_FOUND);
    //         }
    //         return $this->sendResponse(new DoctorProfileResource($profile), 'Doctor Profile details fetched.', Response::HTTP_OK);
    //     } catch (\Exception $e) {
    //         return $this->sendError('Internal Server Error : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
    //     }
    // }

           /**
     * @OA\Get(
     *     path="/api/v1/doctor/get-detail",
     *     summary="Show own Profile",
     *     tags={"Doctor"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=201,
     *         description="Shows own profile",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile Fetched Successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */

    public function showOwnProfile()
    {
        try {
            $auth = Auth::id();
            $profile = DoctorProfile::with('schedules')->where('user_id', $auth)->first();
            if (!$profile) {
                return $this->sendError('Doctor Profile not found.', Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse(new DoctorProfileResource($profile), 'Doctor Profile details fetched.', Response::HTTP_OK);
        } catch (\Exception $e) {
            return $this->sendError('Internal Server Error: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
