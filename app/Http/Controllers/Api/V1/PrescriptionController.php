<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\PrescriptionRequest;
use App\Http\Resources\PrescriptionResource;
use App\Models\Prescription;
use App\Models\PrescriptionItem;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Response;

class PrescriptionController extends Controller
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

            $query = Prescription::with(['prescriptionItem', 'appointment']);

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

            $prescription = $query->latest()->paginate($perPage);

            if ($prescription->total() === 0) {
                return $this->sendError('No Prescription found.', Response::HTTP_NOT_FOUND);
            }

            return $this->sendPaginatedResponse($prescription, PrescriptionResource::class, 'Prescription fetched successfully.', Response::HTTP_OK);
        } catch (Exception $e) {
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PrescriptionRequest $request)
    {
        DB::beginTransaction();
        try {
            $prescription = Prescription::create($request->validated());

            if ($request->has('medicine_name') && is_array($request->medicine_name)) {
                foreach ($request->medicine_name as $index => $name) {
                    PrescriptionItem::create([
                        'prescription_id' => $prescription->id,
                        'medicine_name' => $name,
                        'dosage'         => $request->dosage[$index] ?? null,
                        'frequency'      => $request->frequency[$index] ?? null,
                        'duration'       => $request->duration[$index] ?? null,
                        'instructions'   => $request->instructions[$index] ?? null,
                    ]);
                }
            }

            DB::commit();

            return $this->sendResponse(
                new PrescriptionResource($prescription),
                'Prescription has been created successfully.',
                Response::HTTP_CREATED
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {
            $prescription = Prescription::with(['prescriptionItem', 'appointment'])->find($id);
            if (!$prescription) {
                return $this->sendError('Prescription not found.', Response::HTTP_NOT_FOUND);
            }
            return $this->sendResponse(new PrescriptionResource($prescription), 'Prescription details fetched.');
        } catch (Exception $e) {
            return $this->sendError('Something went wrong : ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PrescriptionRequest $request, string $id)
    {
        DB::beginTransaction();

        try {
            $prescription = Prescription::findOrFail($id);
            $prescription->update($request->validated());

            if ($request->has('medicine_name') && is_array($request->medicine_name)) {
                foreach ($request->medicine_name as $index => $name) {
                    PrescriptionItem::updateOrCreate(
                        [
                            'prescription_id' => $prescription->id,
                            'medicine_name'   => $name,
                        ],
                        [
                            'dosage'       => $request->dosage[$index] ?? null,
                            'frequency'    => $request->frequency[$index] ?? null,
                            'duration'     => $request->duration[$index] ?? null,
                            'instructions' => $request->instructions[$index] ?? null,
                        ]
                    );
                }
            }

            DB::commit();

            return $this->sendResponse(
                new PrescriptionResource($prescription->load('prescriptionItem')),
                'Prescription updated successfully.',
                Response::HTTP_OK
            );
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        DB::beginTransaction();
        try {
            $prescription = Prescription::with('prescriptionItem')->find($id);

            if (!$prescription) {
                return $this->sendError('Prescription not found', Response::HTTP_NOT_FOUND);
            }

            foreach ($prescription->prescriptionItem as $item) {
                $item->delete();
            }

            $prescription->delete();

            DB::commit();

            return $this->sendResponse([], 'Prescription deleted successfully.', Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            return $this->sendError('Something went wrong! ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
