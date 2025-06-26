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

class AppointmentController extends Controller
{
    /**
     * Display a listing of the resource.
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
     * Display the specified resource.
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
     * Update the specified resource in storage.
     */
    public function update(AppointmentRequest $request, string $id)
    {
        try {
            $app = Appointment::findOrFail($id);

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
     * Remove the specified resource from storage.
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
