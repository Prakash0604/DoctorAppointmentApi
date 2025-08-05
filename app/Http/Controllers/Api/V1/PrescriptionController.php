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


      /**
     * @OA\Get(
     *     tags={"Doctor"},
     *     path="/api/v1/doctor/prescriptions",
     *     summary="Get Doctor Prescription list of own for doctor",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */
class PrescriptionController extends Controller
{
      /**
     * @OA\Get(
     *     tags={"Patient"},
     *     path="/api/v1/patient/prescriptions",
     *     summary="Get Doctor prescription list of own for patient",
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
     * @OA\Post(
     *     path="/api/v1/patient/prescriptions",
     *     summary="Store prescription",
     *     tags={"Doctor"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"appointment_id","patient_id","notes","item_ids","medicine_name","dosage","frequency","duration","instructions","item_ids"},
     *             @OA\Property(property="appointment_id", type="string", example="2"),
     *             @OA\Property(property="patient_id", type="string", example="2"),
     *             @OA\Property(property="notes", type="string", example="testing notes"),
     *             @OA\Property(property="item_ids", type="object",
     *             @OA\Property(property="medicine_name", type="string", example=" Amoxicillin"),
     *             @OA\Property(property="dosage", type="string", example="1"),
     *             @OA\Property(property="frequency", type="string", example="2"),
     *             @OA\Property(property="duration", type="string", example="2"),
     *             @OA\Property(property="instructions", type="string", example="2"),
     *             @OA\Property(property="item_ids", type="string", example="2"),        
     *               ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Store prescription",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Prescription created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
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
     * @OA\Get(
     *     tags={"Patient"},
     *     path="/api/v1/patient/prescriptions/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Get Prescription Detail",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     summary="Get Doctor prescription detial",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
     */ 

    public function test(){}

      /**
     * @OA\Get(
     *     tags={"Doctor"},
     *     path="/api/v1/doctor/prescriptions/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Get Prescription Detail",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     summary="Get Doctor prescription detial",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
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
     * @OA\Put(
     *     path="/api/v1/patient/prescriptions/{id}",
     *     summary="Store prescription",
     *     tags={"Doctor"},
     *       @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Get Prescription Detail",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"appointment_id","patient_id","notes","item_ids","medicine_name","dosage","frequency","duration","instructions","item_ids"},
     *             @OA\Property(property="appointment_id", type="string", example="3"),
     *             @OA\Property(property="patient_id", type="string", example="3"),
     *             @OA\Property(property="notes", type="string", example="testing notes update"),
     *             @OA\Property(property="item_ids", type="object",
     *             @OA\Property(property="medicine_name", type="string", example=" Amlodipine"),
     *             @OA\Property(property="dosage", type="string", example="3"),
     *             @OA\Property(property="frequency", type="string", example="3"),
     *             @OA\Property(property="duration", type="string", example="3"),
     *             @OA\Property(property="instructions", type="string", example="Please take a dose  at least 3 times a day and duration is after meal"),
     *             @OA\Property(property="item_ids", type="string", example="2"),        
     *               ),
     *         ),
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Update prescription",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Prescription updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     )
     * )
     */
    public function update(PrescriptionRequest $request, string $id)
    {
        DB::beginTransaction();
        try {
            $prescription = Prescription::findOrFail($id);
            $prescription->update($request->validated());

            $itemIds = $request->item_ids ?? [];
            $existingIds = $prescription->prescriptionItem()->pluck('id')->toArray();
            $toDelete = array_diff($existingIds, $itemIds);
            PrescriptionItem::destroy($toDelete);

            foreach ($request->medicine_name as $index => $name) {
                $itemId = $itemIds[$index] ?? null;

                $data = [
                    'medicine_name' => $name,
                    'dosage'        => $request->dosage[$index] ?? null,
                    'frequency'     => $request->frequency[$index] ?? null,
                    'duration'      => $request->duration[$index] ?? null,
                    'instructions'  => $request->instructions[$index] ?? null,
                ];

                if ($itemId) {
                    $prescription->prescriptionItem()->where('id', $itemId)->update($data);
                } else {
                    $prescription->prescriptionItem()->create($data);
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
     * @OA\Delete(
     *     tags={"Doctor"},
     *     path="/api/v1/doctor/prescriptions/{id}",
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="Get Prescription id",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     summary="Delete Doctor Prescription",
     *     @OA\Response(response="200", description="Success"),
     *     security={{"bearerAuth":{}}}
     * )
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
