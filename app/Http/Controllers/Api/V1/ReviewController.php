<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReviewRequest;
use App\Http\Resources\ReviewResource;
use App\Models\Appointment;
use App\Models\Review;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
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

            $query = Review::query();

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

            $reviews = $query->latest()->paginate($perPage);

            if ($reviews->total() === 0) {
                return $this->sendError('No Review found.', Response::HTTP_NOT_FOUND);
            }

            return $this->sendPaginatedResponse($reviews, ReviewResource::class, 'Review fetched successfully.', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(ReviewRequest $request)
    {
        try {
            $appointment = Appointment::where('id', $request->appointment_id)
                ->where('patient_id', Auth::id())
                ->first();

            if (!$appointment) {
                return $this->sendError('Invalid or unauthorized appointment', Response::HTTP_FORBIDDEN);
            }

            $review = Review::where('appointment_id', $appointment->id)->exists();
            if ($review === true) {
                return $this->sendError(['message' => 'Review already submitted.'], Response::HTTP_CONFLICT);
            }

            $review = Review::create([
                'doctor_id' => $appointment->doctor_id,
                'appointment_id' => $appointment->id,
                'rating' => $request->rating,
                'comment' => $request->comment,
            ]);

            return $this->sendResponse(new ReviewResource($review), 'Review Submitted successfully', Response::HTTP_CREATED);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong !' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $review = Review::with(['patient', 'doctor', 'appointment'])->find($id);
            if (!$review) {
                return $this->sendError('Review not found.', Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse(new ReviewResource($review), 'Review details fetched.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
