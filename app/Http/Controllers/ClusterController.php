<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClusterRatepayerRequest;
use App\Http\Requests\ClusterRequest;
use App\Http\Requests\EntityGeolocationRequest;
use App\Models\Cluster;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Created on 06/12/2024
 * Author: Anil Mishra
 *
 * APIs
 * [POST]  /api/clusters               1. Admin can add Clusters
 * [POST]  /api/cluster_ratepayers     2. Admin can add Cluster alongwith Ratepayers
 * [PUT]   /api/clusters/{id}          3. Admin can update Cluster data
 * [GET]   /api/clusters/{id}          4. Admin can See Cluster data
 * [PUT]   /api/clusters/geolocation   6. TC can set geolocation of a cluster
 */
class ClusterController extends Controller
{
    /**
     * [POST]  /api/clusters               1. Admin can add Clusters
     * Completed [Pending]
     */
    public function store(ClusterRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $ulbId = $request->ulb_id;

            $cluster = Cluster::create([
                'ulb_id' => $ulbId,
                'tc_id' => $validatedData['appliedtcId'] ?? null,
                'cluster_name' => $validatedData['clusterName'],
                'cluster_address' => $validatedData['clusterAddress'],
                'landmark' => $validatedData['landmark'],
                'pincode' => $validatedData['pincode'],
                'cluster_type' => $validatedData['clusterType'],
                'mobile_no' => $validatedData['mobileNo'],
                'whatsapp_no' => $validatedData['whatsappNo'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'inclusion_date' => $validatedData['inclusionDate'],
                'is_active' => true,
                'is_verified' => false,
            ]);

            return format_response(
                'Cluster created successfully',
                $cluster,
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
     * [POST]  /api/entity_atepayers       2. Admin can add Entities alongwith Ratepayers
     * Completed [OK]
     * Request json =>json/entities/entityWithRatepayersRequest.json
     * Response json =>json/entities/entityWithRatepayersResponse.json
     */
    public function storeWithRatePayers(ClusterRatepayerRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $clusterData = $validatedData['cluster'];
            $ratepayerData = $validatedData['ratepayer'];
            $ulbId = $request->ulb_id;

            DB::beginTransaction();

            $cluster = Cluster::create([
                'ulb_id' => $ulbId,
                'tc_id' => $clusterData['appliedtcId'] ?? null,
                'cluster_name' => $clusterData['clusterName'],
                'cluster_address' => $clusterData['clusterAddress'],
                'landmark' => $clusterData['landmark'],
                'pincode' => $clusterData['pincode'],
                'cluster_type' => $clusterData['clusterType'],
                'mobile_no' => $clusterData['mobileNo'],
                'whatsapp_no' => $clusterData['whatsappNo'],
                'longitude' => $clusterData['longitude'],
                'latitude' => $clusterData['latitude'],
                'inclusion_date' => $clusterData['inclusionDate'],
                'is_active' => true,
                'is_verified' => false,
            ]);

            // Validate and extract ratepayer data
            //  DB::enableQueryLog();

            $ratePayer = Ratepayer::create([
                'ulb_id' => $ulbId,
                'entity_id' => $cluster->id, // Link the entity
                'paymentzone_id' => $ratepayerData['paymentzoneId'],
                'last_payment_id' => null, // Initialize as null, can be updated later
                'last_transaction_id' => null, // Initialize as null, can be updated later
                'ratepayer_name' => $ratepayerData['ratepayerName'],
                'ratepayer_address' => $ratepayerData['ratepayerAddress'],
                'consumer_no' => $ratepayerData['consumerNo'],
                'longitude' => $ratepayerData['longitude'],
                'latitude' => $ratepayerData['latitude'],
                'mobile_no' => $ratepayerData['mobileNo'],
                'landmark' => $ratepayerData['landmark'],
                'whatsapp_no' => $ratepayerData['whatsappNo'],
                'bill_date' => $ratepayerData['billDate'],
                'opening_demand' => $ratepayerData['openingDemand'],
                'monthly_demand' => $ratepayerData['monthlyDemand'],
                'is_active' => true,
            ]);

            // Commit transaction
            DB::commit();

            return format_response(
                'Entity and Ratepayer created successfully',
                $cluster,
                Response::HTTP_CREATED
            );
        } catch (\Illuminate\Database\QueryException $e) {
            DB::rollBack();
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Unexpected error during entity update: '.$e->getMessage());

            return format_response(
                'An unexpected error occurred',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * [PUT]   /api/entities/{id}          3. Admin can update Entity data
     * Completed [OK]
     * Request json =>json/entities/entityRequest.json
     * Response json =>json/entities/entityResponse.json
     */
    public function update(ClusterRequest $request, int $id)
    {
        try {
            $entity = Cluster::find($id);
            if ($entity == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validated();

            $updateData = [
                'tc_id' => $validatedData['appliedtcId'] ?? null,
                'cluster_name' => $validatedData['clusterName'],
                'cluster_address' => $validatedData['clusterAddress'],
                'landmark' => $validatedData['landmark'],
                'pincode' => $validatedData['pincode'],
                'cluster_type' => $validatedData['clusterType'],
                'mobile_no' => $validatedData['mobileNo'],
                'whatsapp_no' => $validatedData['whatsappNo'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'inclusion_date' => $validatedData['inclusionDate'],
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
     * [PUT]   /api/entities/{id}          3. Admin can update Entity data
     * Completed [OK]
     * Request json =>json/entities/entityRequest.json
     * Response json =>json/entities/entityResponse.json
     */
    public function updateGeoLocation(EntityGeolocationRequest $request, int $id)
    {
        try {
            $cluster = Cluster::find($id);
            if ($cluster == null) {
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

            $cluster->update($updateData);

            return format_response(
                'Entity Geolocation updated successfully',
                $cluster,
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
            $cluster = Cluster::findOrFail($id);

            return format_response(
                'Show Cluster Record',
                $cluster,
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
    public function showAll(Request $request)
    {
        try {
            $ulbId = $request->ulb_id;
            $clusters = Cluster::select(
                'id',
                'cluster_name',
                'cluster_address',
                'pincode',
                'landmark',
                'cluster_type',
                'mobile_no',
                'whatsapp_no',
                'longitude',
                'latitude'
            )->where('ulb_id', $ulbId)->get();

            return format_response(
                'Show All Cluster Record',
                $clusters,
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
