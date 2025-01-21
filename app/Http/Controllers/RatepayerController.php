<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityGeolocationRequest;
use App\Http\Requests\RatepayerRequest;
use App\Http\Services\RatepayerService;
use App\Models\Cluster;
use App\Models\Entity;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

/**
 * Created on 07/12/2024
 * Author: Anil Mishra
 *
 * APIs
 * [POST]  /api/ratepayers               1. Admin can add Ratepayer
 * [PUT]   /api/ratepayers/{id}          2. Admin can update Ratepayerity data
 * [GET]   /api/ratepayers/{id}          3. Admin can See Ratepayer data
 * [PUT]   /api/ratepayers/geolocation   4. TC can set geolocation of a Ratepayer
 */
class RatepayerController extends Controller
{
    /**
     * [POST]  /api/ratepayers               1. Admin can add Entities
     * Completed [OK]
     */
    public function store(RatepayerRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $ulbId = $request->ulb_id;

            // Validate and extract entity data
            // $entityData = $request->input('entity');
            $ratepayer = Ratepayer::create([
                'ulb_id' => $ulbId,
                'cluster_id' => $validatedData['clusterId'],
                'entity_id' => $validatedData['entityId'],
                'ward_id' => $validatedData['wardId'],
                'rate_id' => $validatedData['rateId'],
                'paymentzone_id' => $validatedData['paymentzoneId'],
                'ratepayer_name' => $validatedData['ratepayerName'],
                'ratepayer_address' => $validatedData['ratepayerAddress'],
                'consumer_no' => $validatedData['consumerNo'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'mobile_no' => $validatedData['mobileNo'],
                'landmark' => $validatedData['landmark'],
                'whatsapp_no' => $validatedData['whatsappNo'],
                'bill_date' => $validatedData['billDate'],
                'opening_demand' => $validatedData['openingDemand'],
                'monthly_demand' => $validatedData['monthlyDemand'],
                'is_active' => true,
            ]);

            return format_response(
                'Ratepayer created successfully',
                $ratepayer,
                Response::HTTP_CREATED
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
     * [PUT]   /api/ratepayers/{id}          2. Admin can update Ratepayer data
     * Completed [OK]
     */
    public function update(RatepayerRequest $request, int $id)
    {
        try {
            $entity = Ratepayer::find($id);
            if ($entity == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validated();

            $updateData = [
                'cluster_id' => $validatedData['clusterId'],
                'entity_id' => $validatedData['entityId'],
                'paymentzone_id' => $validatedData['paymentzoneId'],
                'ratepayer_name' => $validatedData['ratepayerName'],
                'ratepayer_address' => $validatedData['ratepayerAddress'],
                'consumer_no' => $validatedData['consumerNo'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'mobile_no' => $validatedData['mobileNo'],
                'landmark' => $validatedData['landmark'],
                'whatsapp_no' => $validatedData['whatsappNo'],
                'bill_date' => $validatedData['billDate'],
                'opening_demand' => $validatedData['openingDemand'],
                'monthly_demand' => $validatedData['monthlyDemand'],
            ];

            $entity->update($updateData);

            return format_response(
                'Entity updated successfully',
                $entity,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * [PUT]   /api/ratepayers/{id}          3. Admin can update Entity data
     * Completed [OK]
     */
    public function updateGeoLocation(EntityGeolocationRequest $request, int $id)
    {
        try {
            $ratepayer = Ratepayer::find($id);
            if ($ratepayer == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }
            $validatedData = $request->validated();

            $updateData = [
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
            ];

            $ratepayer->update($updateData);

            return format_response(
                'Ratepayer Geolocation updated successfully',
                $ratepayer,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * [GET]   /api/entities/{id}          4. Admin can See Entity data
     * Completed [OK]
     */
    public function show($id)
    {
        try {
            $ratepayer = DB::table('ratepayers as r')
                ->join('wards as w', 'r.ward_id', '=', 'w.id')
                ->leftJoin('payment_zones as z', 'r.paymentzone_id', '=', 'z.id')
                ->leftJoin('sub_categories as s', 'r.subcategory_id', '=', 's.id')
                ->leftJoin('categories as c', 's.category_id', '=', 'c.id')
                ->select(
                    'r.ward_id',
                    'w.ward_name',
                    'r.entity_id',
                    'r.cluster_id',
                    'r.paymentzone_id',
                    'z.payment_zone',
                    'r.last_payment_id',
                    'r.subcategory_id',
                    's.sub_category',
                    's.category_id',
                    'c.category',
                    'r.rate_id',
                    'r.last_transaction_id',
                    'r.ratepayer_name',
                    'r.ratepayer_address',
                    'r.consumer_no',
                    'r.holding_no',
                    'r.longitude',
                    'r.latitude',
                    'r.mobile_no',
                    'r.landmark',
                    'r.whatsapp_no',
                    'r.usage_type',
                    'r.status',
                    'r.reputation',
                    'r.lastpayment_amt',
                    'r.lastpayment_date',
                    'r.lastpayment_mode',
                    'r.schedule_date',
                    'r.is_active'
                )
                ->where('r.id', $id)
                ->first();
            $entity = Entity::find($ratepayer->entity_id);
            $cluster = Cluster::find($ratepayer->cluster_id);

            $response = [
                'ratepayer' => $ratepayer,
                'entity' => $entity,
                'cluster' => $cluster,
            ];

            // $ratepayer = Ratepayer::with(['entity', 'cluster'])
            //     ->where('id', $id)
            //     ->firstOrFail();

            return format_response(
                'Ratepayer Details',
                $response,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function showZoneRatepayers($id)
    {
        try {
            $ratepayers = Ratepayer::where('paymentzone_id', $id)->get();

            return format_response(
                'Ratepayer Details',
                $ratepayers,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function showZoneRatepayersPaginated($id, $page_size)
    {
        try {
            $ratepayers = Ratepayer::where('paymentzone_id', $id)->paginate($page_size);

            return format_response(
                'Ratepayer Details',
                $ratepayers,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function deepSearch(Request $request)
    {
        try {
            $service = new RatepayerService;
            $results = $service->deepSearch($request);

            return format_response(
                'Success',
                $results,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }

    public function searchNearby(Request $request)
    {
        try {
            $request->validate([
                'longitude' => 'required|numeric',
                'latitude' => 'required|numeric',
                'radius' => 'nullable|numeric|min:1|max:10000',
            ]);

            $longitude = $request->input('longitude');
            $latitude = $request->input('latitude');
            $radius = $request->input('radius', 100); // Default radius is 100 meters

            $ratepayers = Ratepayer::nearby($longitude, $latitude, $radius)->get();

            return format_response(
                'Success',
                $ratepayers,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }

    public function updateRateID(Request $request, int $ratepayer_id)
    {
        try {
            $validator = Validator::make($request->all(), [
                'rateId' => 'required|integer|exists:rate_list,id', // Ensures the ID is valid and exists in the 'ratepayers' table
            ]);

            if ($validator->fails()) {
                $errorMessages = $validator->errors()->all();

                return format_response(
                    'validation error',
                    $errorMessages,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $ratepayer = Ratepayer::find($ratepayer_id);
            if ($ratepayer == null) {
                return format_response(
                    'Ratepayer does not exist',
                    null,
                    Response::HTTP_UNPROCESSABLE_ENTITY
                );
            }

            $validatedData = $validator->validated();
            $ratepayer->rate_id = $request->rateId;

            $updateData = [
                'rateID' => $ratepayer->rate_id,
            ];

            return format_response(
                'Ratepayer Rate ID is updated',
                $updateData,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during ratepayer update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function showDeactiavtedRatepayer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'zoneId' => 'required|integer|exists:payment_zones,id', // Ensures the ID is valid and exists in the 'ratepayers' table
        ]);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();

            return format_response(
                'validation error',
                $errorMessages,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $ratepayer = Ratepayer::where('is_active', false)
                ->where('paymentzone_id', $request->zoneId)
                ->get();

            return format_response(
                'Ratepayer Details',
                $ratepayer,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function activateRatepayer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
        ]);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();

            return format_response(
                'validation error',
                $errorMessages,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $ratepayer = Ratepayer::find($request->ratepayerId);
            $ratepayer->is_active = true;
            $ratepayer->save();

            return format_response(
                'Ratepayer Activated',
                $ratepayer,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function deactiavteRatepayer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
        ]);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();

            return format_response(
                'validation error',
                $errorMessages,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        try {
            $ratepayer = Ratepayer::find($request->ratepayerId);
            $ratepayer->is_active = false;
            $ratepayer->save();

            return format_response(
                'Ratepayer Deactivated',
                $ratepayer,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
