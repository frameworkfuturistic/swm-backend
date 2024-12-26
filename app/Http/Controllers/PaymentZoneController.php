<?php

namespace App\Http\Controllers;

use App\Http\Requests\PaymentZoneRequest;
use App\Models\PaymentZone;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * GET /payment-zones  - List all categories.
 * POST /payment-zones  - Create a category:
 * {
 *    "ulb_id": 1,
 *    "payment_zone": "Education"
 *    "description": "Updated description"
 * }
 *
 * GET /payment-zones/{id} - View a single category.
 * PUT /payment-zones/{id} - Update a category
 * {
 *   "ulb_id": 1,
 *   "payment_zone": "Education"
 *   "description": "Updated description"
 * }
 * DELETE /payment-zones/{id} - Delete a category.
 */
class PaymentZoneController extends Controller
{
    /**
     * GET http://127.0.0.1:8000/api/payment-zones
     * Display a listing of the resource.
     */
    public function showAll(Request $request)
    {
        try {
            $ulbId = $request->input('ulb_id', 1);

            DB::enableQueryLog();

            $data = DB::table('payment_zones as z')
                ->leftJoin('ratepayers as r', function ($join) {
                    $join->on('r.paymentzone_id', '=', 'z.id')
                        ->where('r.ulb_id', '=', 1)
                        ->where('r.is_active', '=', true);
                })
                ->select(
                    'z.id as zoneId',
                    'z.payment_zone',
                    DB::raw("'A' as category"),
                    'z.description',
                    DB::raw('SUM(IF(r.entity_id IS NOT NULL, 1, 0)) as entityCount'),
                    DB::raw('SUM(IF(r.cluster_id IS NOT NULL, 1, 0)) as clusterCount')
                )
                ->groupBy('z.id', 'z.payment_zone', 'z.description')
                ->get();

            return format_response(
                'success',
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return format_response(
                'success',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * POST http://127.0.0.1:8000/api/payment-zones
     * Store a newly created resource in storage.
     * {
     *   "paymentZone": "Zone A",
     *   "coordinates": [{"lat": 23.567, "lng": 87.345}, {"lat": 23.768, "lng": 87.567}],
     *   "description": "Residential zone"
     * }
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                // 'ulb_id' => 'required|exists:ulbs,id',
                'paymentZone' => 'required|string|max:50|unique:payment_zones,payment_zone,NULL,id,ulb_id,'.$request->ulb_id,
                'coordinates' => 'required|array',
                'coordinates.*' => 'array|min:2', // Validate lat, lng pairs
                'description' => 'required|string|max:250',
            ]);

            $polygonCoordinates = json_encode($validated['coordinates']);
            $ulbId = $request->ulb_id;

            $paymentZone = DB::table('payment_zones')->insert([
                'ulb_id' => $ulbId,
                'payment_zone' => $validated['paymentZone'],
                'coordinates' => $polygonCoordinates,
                'description' => $validated['description'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return format_response(
                'Payment Zone created successfully!',
                $paymentZone,
                Response::HTTP_CREATED
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                'Validation Failed',
                $e->errors(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during insertion',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET http://127.0.0.1:8000/api/payment-zones/1
     */
    public function show($id)
    {
        try {
            $paymentZone = PaymentZone::find($id);

            if (! $paymentZone) {
                return format_response(
                    'Payment Zone not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            } else {
                return format_response(
                    'success',
                    $paymentZone,
                    Response::HTTP_OK
                );
            }
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET http://127.0.0.1:8000/api/payment-zones/1
     */
    public function showRatepayersPaginated(int $zoneId, int $pagination)
    {
        try {
            $ratepayers = Ratepayer::where('paymentzone_id', $zoneId)->paginate($pagination);

            if (! $ratepayers) {
                return format_response(
                    'Ratepayers not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            } else {
                return format_response(
                    'success',
                    $ratepayers,
                    Response::HTTP_OK
                );
            }
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET http://127.0.0.1:8000/api/payment-zones/1
     */
    public function showRatepayerEntitiesPaginated(int $zoneId, int $pagination)
    {
        try {
            $ratepayers = Ratepayer::where('paymentzone_id', $zoneId)
                ->whereNotNull('entity_id')
                ->paginate($pagination);

            if (! $ratepayers) {
                return format_response(
                    'Ratepayers not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            } else {
                return format_response(
                    'success',
                    $ratepayers,
                    Response::HTTP_OK
                );
            }
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * GET http://127.0.0.1:8000/api/payment-zones/1
     */
    public function showRatepayerClustersPaginated(int $zoneId, int $pagination)
    {
        try {
            $ratepayers = Ratepayer::where('paymentzone_id', $zoneId)
                ->whereNotNull('cluster_id')
                ->paginate($pagination);

            if (! $ratepayers) {
                return format_response(
                    'Ratepayers not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            } else {
                return format_response(
                    'success',
                    $ratepayers,
                    Response::HTTP_OK
                );
            }
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * PUT http://127.0.0.1:8000/api/payment-zones/1
     *{
     *    "paymentZone": "Zone AA",
     *    "coordinates": [{"lat": 23.567, "lng": 81.345}, {"lat": 23.768, "lng": 87.567}],
     *    "description": "Residential zone"
     *}
     * Update the specified resource in storage.
     */
    public function update(PaymentZoneRequest $request, int $id)
    {
        try {
            $paymentZone = PaymentZone::find($id);
            if ($paymentZone == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validated();
            $ulbId = $request->ulb_id;
            $polygonCoordinates = json_encode($validatedData['coordinates']);

            $updateData = [
                'ward_id' => $validatedData['wardId'],
                'payment_zone' => $validatedData['paymentZone'],
                'coordinates' => $polygonCoordinates,
                'description' => $validatedData['description'],
                'updated_at' => now(),
            ];

            $paymentZone->update($updateData);

            return format_response(
                'Payment Zone updated successfully',
                $paymentZone,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(PaymentZone $paymentZone)
    {
        //   $paymentZone->delete();
        //   return response()->json(['message' => 'Payment zone deleted successfully.']);
        return format_response(
            'Could not Process',
            null,
            Response::HTTP_UNPROCESSABLE_ENTITY
        );
    }
}
