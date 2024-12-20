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
use Illuminate\Support\Facades\Schema;

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
                'ward_id' => $validatedData['wardId'],
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
                'vrno' => 1,
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
                'appliedtc_id' => auth()->id(),
                'ward_id' => $clusterData['wardId'],
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
                'cluster_id' => $cluster->id, // Link the entity
                'ward_id' => $ratepayerData['wardId'],
                'subcategory_id' => $ratepayerData['subcategoryId'], // Link the entity
                'paymentzone_id' => $ratepayerData['paymentzoneId'],
                //  'last_payment_id' => null, // Initialize as null, can be updated later
                //  'last_transaction_id' => null, // Initialize as null, can be updated later
                'ratepayer_name' => $ratepayerData['ratepayerName'],
                'ratepayer_address' => $ratepayerData['ratepayerAddress'],
                'consumer_no' => $ratepayerData['consumerNo'],
                'longitude' => $ratepayerData['longitude'],
                'latitude' => $ratepayerData['latitude'],
                'mobile_no' => $ratepayerData['mobileNo'],
                'landmark' => $ratepayerData['landmark'],
                'whatsapp_no' => $ratepayerData['whatsappNo'],
                //  'bill_date' => $ratepayerData['billDate'],
                'opening_demand' => $ratepayerData['openingDemand'],
                'monthly_demand' => $ratepayerData['monthlyDemand'],
                'is_active' => true,
            ]);

            // Commit transaction
            DB::commit();

            return format_response(
                'Cluster and Ratepayer created successfully',
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
    public function update(ClusterRatepayerRequest $request, int $id)
    {
        try {
            DB::beginTransaction();
            $cluster = Cluster::find($id);
            if ($cluster == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $ratepayer = Ratepayer::where('cluster_id', $id)
                ->where('is_active', 1) // Assuming 1 means active
                ->first();

            if ($ratepayer == null) {
                return format_response(
                    'Record not found or There is no active ratepayer for selected Entity',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $validatedData = $request->validated();
            $columns = Schema::getColumnListing('log_clusters');
            DB::table('log_clusters')->insertUsing(
                $columns,
                DB::table('clusters')->select('*')->where('id', $id)
            );

            $columns = Schema::getColumnListing('log_ratepayers');
            DB::table('log_ratepayers')->insertUsing(
                $columns,
                DB::table('ratepayers')->select('*')->where('id', $ratepayer->id)
            );

            $updateClusterData = [
                'tc_id' => $validatedData['cluster']['appliedtcId'] ?? null,
                'ward_id' => $validatedData['cluster']['wardId'],
                'cluster_name' => $validatedData['cluster']['clusterName'],
                'cluster_address' => $validatedData['cluster']['clusterAddress'],
                'landmark' => $validatedData['cluster']['landmark'],
                'pincode' => $validatedData['cluster']['pincode'],
                'cluster_type' => $validatedData['cluster']['clusterType'],
                'mobile_no' => $validatedData['cluster']['mobileNo'],
                'whatsapp_no' => $validatedData['cluster']['whatsappNo'],
                'longitude' => $validatedData['cluster']['longitude'],
                'latitude' => $validatedData['cluster']['latitude'],
                'inclusion_date' => $validatedData['cluster']['inclusionDate'],
                'vrno' => $cluster->vrno + 1,
            ];

            $updateRatepayerData = [
                'ward_id' => $validatedData['ratepayer']['wardId'], // Link the entity
                'subcategory_id' => $validatedData['ratepayer']['subcategoryId'], // Link the entity
                'paymentzone_id' => $validatedData['ratepayer']['paymentzoneId'],
                'ratepayer_name' => $validatedData['ratepayer']['ratepayerName'],
                'ratepayer_address' => $validatedData['ratepayer']['ratepayerAddress'],
                'consumer_no' => $validatedData['ratepayer']['consumerNo'],
                'longitude' => $validatedData['ratepayer']['longitude'],
                'latitude' => $validatedData['ratepayer']['latitude'],
                'mobile_no' => $validatedData['ratepayer']['mobileNo'],
                'landmark' => $validatedData['ratepayer']['landmark'],
                'whatsapp_no' => $validatedData['ratepayer']['whatsappNo'],
                'opening_demand' => $validatedData['ratepayer']['openingDemand'],
                'monthly_demand' => $validatedData['ratepayer']['monthlyDemand'],
                'vrno' => $ratepayer->vrno + 1,
            ];

            $cluster->update($updateClusterData);
            $ratepayer->update($updateRatepayerData);

            DB::commit();

            return format_response(
                'Cluster updated successfully',
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
    public function show()
    {
        try {
            $cluster = Cluster::all();

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

    public function showById($id)
    {
        try {
            $cluster = Cluster::find($id);

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
