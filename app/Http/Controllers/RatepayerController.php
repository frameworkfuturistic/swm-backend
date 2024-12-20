<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityGeolocationRequest;
use App\Http\Requests\RatepayerRequest;
use App\Http\Services\RatepayerService;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

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
            $ratepayer = Ratepayer::with(['entity', 'cluster'])
                ->where('id', $id)
                ->firstOrFail();

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
}
