<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Response;


    
      /**
     * @OA\Get(
     *     tags={"Appointment"},
     *     path="/api/v1/doctor/appointments",
     *     summary="Get Doctor appointment list of own for doctor",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */
class AppointmentController extends Controller
{
      /**
     * @OA\Get(
     *     tags={"Appointment"},
     *     path="/api/v1/patient/appointments",
     *     summary="Get Doctor appointment list of own for patient",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */ 
    
    public function index(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $currentUser = getCurrentUser();

            if (!$currentUser || !isset($currentUser->role_name)) {
                return $this->sendError('Unauthorized or invalid user.', Response::HTTP_FORBIDDEN);
            }

            $query = Appointment::query();

            switch ($currentUser->role_name) {
                case 'Doctor':
                    $query->where('doctor_id', $currentUser->id);
                    break;

                case 'Patient':
                    $query->where('patient_id', $currentUser->id);
                    break;

                case 'Admin':
                    break;

                default:
                    return $this->sendError('Unauthorized role.', Response::HTTP_FORBIDDEN);
            }

            $appointments = $query->latest()->paginate($perPage);

            if ($appointments->total() === 0) {
                return $this->sendError('No appointments found.', Response::HTTP_NOT_FOUND);
            }

            return $this->sendPaginatedResponse($appointments, AppointmentResource::class, 'Appointments fetched successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

  /**
     * @OA\Get(
     *     tags={"Appointment"},
     *     path="/api/v1/patient/todays-appointment",
     *     summary="Get Todays list of Doctor appointment for patient",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */ 

  public function test(){}

    /**
     * @OA\Get(
     *     tags={"Appointment"},
     *     path="/api/v1/doctor/todays-appointment",
     *     summary="Get Todays list of Doctor appointment for Doctors",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */ 

    public function todaysAppointment(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $currentUser = getCurrentUser();
            if (!$currentUser || !isset($currentUser->role_name)) {
                return $this->sendError('Unauthorized or invalid user.', Response::HTTP_FORBIDDEN);
            }

            $query = Appointment::query()->whereDate('appointment_date', now()->toDateString());

            switch ($currentUser->role_name) {
                case 'Doctor':
                    $query->where('doctor_id', $currentUser->id);
                    break;

                case 'Patient':
                    $query->where('patient_id', $currentUser->id);
                    break;

                case 'Admin':
                    break;

                default:
                    return $this->sendError('Unauthorized role.', Response::HTTP_FORBIDDEN);
            }

            $appointments = $query->latest()->paginate($perPage);

            if ($appointments->isEmpty()) {
                return $this->sendError('No appointments found for today.', Response::HTTP_NOT_FOUND);
            }

            return $this->sendPaginatedResponse(
                $appointments,
                AppointmentResource::class,
                'Today\'s appointments fetched successfully.'
            );
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/doctor/appointments/toggle-status/{id}",
     *     tags={"Doctor"},
     *     summary="Change Appointment Status",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="provide the appointment id",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="201", description="Appointment status  changed successfully"),
     * )
     */

    public function toggleAppointmentStatus(Request $request, $id)
    {
        try {
            $user = getCurrentUser();

            if (!$user || $user->role_name !== 'Doctor') {
                return $this->sendError('Only doctors are allowed to update appointment status.', Response::HTTP_FORBIDDEN);
            }

            $request->validate([
                'status' => 'required|in:completed,canceled',
            ]);

            $appointment = Appointment::where('id', $id)
                ->where('doctor_id', $user->id)
                ->first();

            if (!$appointment) {
                return $this->sendError('Appointment not found.', Response::HTTP_NOT_FOUND);
            }

            $appointment->status = $request->status;
            $appointment->save();

            return $this->sendResponse(new AppointmentResource($appointment), 'Appointment status updated successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


      /**
     * @OA\Post(
     *     path="/api/v1/patient/appointments",
     *     summary="Store Appointment",
     *     tags={"Patient"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"doctor_id","appointment_date","start_time","end_time","notes"},
     *             @OA\Property(property="doctor_id", type="string", example="2"),
     *             @OA\Property(property="appointment_date", type="string", example="2025-05-04"),
     *             @OA\Property(property="start_time", type="time", format="time", example="09:00"),
     *             @OA\Property(property="end_time", type="time", example="15:00"),
     *             @OA\Property(property="notes", type="string", example="hello test notes"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Update Specification",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appointment sheduled successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */

    public function store(AppointmentRequest $request)
    {
        try {
            $appointmentExists = Appointment::where('appointment_date', $request->appointment_date)
                ->where('doctor_id', $request->doctor_id)
                ->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time)
                ->exists();

            if ($appointmentExists) {
                return $this->sendError('This appointment slot has already been booked.');
            }

            $appointment = Appointment::create($request->validated());

            return $this->sendResponse(new AppointmentResource($appointment), 'Appointment has been booked successfully.', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/patient/appointments",
     *     tags={"Doctor"},
     *     summary="Get Appointment Detail",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="Get Appointment Detail",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="200", description="Appointment Detail Fetched successfully"),
     * )
     */
    public function show(string $id)
    {
        try {
            $appointment = Appointment::find($id);
            if (!$appointment) {
                return $this->sendError('Appointment not found.', Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse(new AppointmentResource($appointment), 'Appointment details fetched.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
   /**
     * @OA\Put(
     *     path="/api/v1/patient/appointments/{id}",
     *     summary="Update Appointment",
     *     tags={"Patient"},
     *     security={{"bearerAuth":{}}},
     *      @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="provide the appointment id",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"doctor_id","appointment_date","start_time","end_time","notes"},
     *             @OA\Property(property="doctor_id", type="string", example="2"),
     *             @OA\Property(property="appointment_date", type="string", example="2025-05-05"),
     *             @OA\Property(property="start_time", type="time", format="time", example="09:00"),
     *             @OA\Property(property="end_time", type="time", example="15:00"),
     *             @OA\Property(property="notes", type="string", example="hello test notes updated"),
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Update Appointment",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Appointment Updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(AppointmentRequest $request, string $id)
    {
        try {
            $app = Appointment::find($id);

            $appointmentExists = Appointment::where('appointment_date', $request->appointment_date)
                ->where('doctor_id', $request->doctor_id)
                ->where('start_time', $request->start_time)
                ->where('end_time', $request->end_time)
                ->where('id', '!=', $id)
                ->exists();

            if ($appointmentExists) {
                return $this->sendError('This appointment slot has already been booked.');
            }

            $app->update($request->validated());

            return $this->sendResponse(new AppointmentResource($app), 'Appointment has been updated successfully.', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


   
    /**
     * @OA\Delete(
     *     path="/api/doctor/patient/appointments/{id}",
     *     tags={"Patient"},
     *     summary="Delete Appointment",
     *     @OA\Parameter(
     *         name="id",
     *         in="query",
     *         description="provide the appointment id",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(response="201", description="Appointment Deleted successfully"),
     * )
     */
    public function destroy(string $id)
    {
        try {
            $appointment = Appointment::find($id);

            if(!$appointment){
                return $this->sendError('Appointment not found', Response::HTTP_NOT_FOUND);
            }

            $appointment->delete();
            return $this->sendResponse([], 'Appointment deleted successfully.', Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Appointment not found.', Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
