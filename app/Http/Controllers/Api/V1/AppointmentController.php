<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AppointmentRequest;
use App\Http\Resources\AppointmentResource;
use App\Models\Appointment;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
                return $this->sendError('Unauthorized or invalid user.', 401);
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
                    return $this->sendError('Unauthorized role.', 403);
            }

            $appointments = $query->latest()->paginate($perPage);

            if ($appointments->total() === 0) {
                return $this->sendError('No appointments found.', 404);
            }

            return $this->sendPaginatedResponse($appointments, AppointmentResource::class, 'Appointments fetched successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), 500);
        }
    }

    public function todaysAppointment(Request $request)
    {
        try {
            $perPage = (int) $request->get('per_page', 10);
            $currentUser = getCurrentUser();
            if (!$currentUser || !isset($currentUser->role_name)) {
                return $this->sendError('Unauthorized or invalid user.', 401);
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
                    return $this->sendError('Unauthorized role.', 403);
            }

            $appointments = $query->latest()->paginate($perPage);

            if ($appointments->isEmpty()) {
                return $this->sendError('No appointments found for today.', 404);
            }

            return $this->sendPaginatedResponse(
                $appointments,
                AppointmentResource::class,
                'Today\'s appointments fetched successfully.'
            );
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), 500);
        }
    }


    public function toggleAppointmentStatus(Request $request, $id)
    {
        try {
            $user = getCurrentUser();

            if (!$user || $user->role_name !== 'Doctor') {
                return $this->sendError('Only doctors are allowed to update appointment status.', 403);
            }

            $request->validate([
                'status' => 'required|in:completed,canceled',
            ]);

            $appointment = Appointment::where('id', $id)
                ->where('doctor_id', $user->id)
                ->first();

            if (!$appointment) {
                return $this->sendError('Appointment not found.', 404);
            }

            $appointment->status = $request->status;
            $appointment->save();

            return $this->sendResponse(new AppointmentResource($appointment), 'Appointment status updated successfully.');
        } catch (\Throwable $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), 500);
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

            return $this->sendResponse(new AppointmentResource($appointment), 'Appointment has been booked successfully.', 201);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage());
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
                return $this->sendError('Appointment not found.', 404);
            }
            return $this->sendResponse(new AppointmentResource($appointment), 'Appointment details fetched.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong : ' . $e->getMessage(), 500);
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

            return $this->sendResponse(new AppointmentResource($app), 'Appointment has been updated successfully.', 200);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage());
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $appointment = Appointment::findOrFail($id);

            $appointment->delete();
            return $this->sendResponse([], 'Appointment deleted successfully.');
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return $this->sendError('Appointment not found.', 404);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), 500);
        }
    }
}
