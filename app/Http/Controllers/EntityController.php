<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityGeolocationRequest;
use App\Http\Requests\EntityRatepayerRequest;
use App\Http\Requests\EntityRequest;
use App\Models\Cluster;
use App\Models\Entity;
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
 * [POST]  /api/entities               1. Admin can add Entities
 * [POST]  /api/entity_atepayers       2. Admin can add Entities alongwith Ratepayers
 * [PUT]   /api/entities/{id}          3. Admin can update Entity data
 * [GET]   /api/entities/{id}          4. Admin can See Entity data
 * [GET]   /api/entities/search        5. Admin can see filter and Deep Search Entities
 * [PUT]   /api/entities/geolocation   6. TC can set geolocation of an entity
 */
class EntityController extends Controller
{
    /**
     * [POST]  /api/entities               1. Admin can add Entities
     * Completed [OK]
     * Request json =>json/entities/entityRequest.json
     * Response json =>json/entities/entityResponse.json
     */
    public function storeDiscarded(EntityRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $ulbId = $request->ulb_id;

            // Validate and extract entity data
            // $entityData = $request->input('entity');
            $entity = Entity::create([
                'ulb_id' => $ulbId,
                'cluster_id' => $validatedData['clusterId'],
                'subcategory_id' => $validatedData['subcategoryId'],
                'ward_id' => $validatedData['wardId'],
                'verifiedby_id' => $validatedData['verifiedbyId'] ?? null,
                'appliedtc_id' => $validatedData['appliedtcId'] ?? null,
                'holding_no' => $validatedData['holdingNo'],
                'entity_name' => $validatedData['entityName'],
                'entity_address' => $validatedData['entityAddress'],
                'pincode' => $validatedData['pincode'],
                'mobile_no' => $validatedData['mobileNo'],
                'landmark' => $validatedData['landmark'],
                'whatsapp_no' => $validatedData['whatsappNo'],
                'longitude' => $validatedData['longitude'],
                'latitude' => $validatedData['latitude'],
                'inclusion_date' => $validatedData['inclusionDate'],
                'verification_date' => $validatedData['verificationDate'],
                'opening_demand' => $validatedData['openingDemand'],
                'monthly_demand' => $validatedData['monthlyDemand'],
                'usage_type' => $validatedData['usageType'],
                'status' => $validatedData['status'],
                'is_active' => true,
                'is_verified' => false,
            ]);

            return format_response(
                'Entity created successfully',
                $entity,
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
    public function storeWithRatePayers(EntityRatepayerRequest $request)
    {
        try {
            $validatedData = $request->validated();
            $entityData = $validatedData['entity'];
            $ratepayerData = $validatedData['ratepayer'];
            // $ratepayerData = $request->input('ratepayer');

            $ulbId = $request->ulb_id;

            // Validate and extract entity data
            // $entityData = $request->input('entity');
            $entity = Entity::create([
                'ulb_id' => $ulbId,
                //  'cluster_id' => $entityData['clusterId'],
                'ward_id' => $entityData['wardId'],
                'subcategory_id' => $entityData['subcategoryId'],
                //  'verifiedby_id' => $entityData['verifiedbyId'] ?? null,
                'appliedtc_id' => auth()->id(),
                'holding_no' => $entityData['holdingNo'],
                'entity_name' => $entityData['entityName'],
                'entity_address' => $entityData['entityAddress'],
                'pincode' => $entityData['pincode'],
                'mobile_no' => $entityData['mobileNo'],
                'landmark' => $entityData['landmark'],
                'whatsapp_no' => $entityData['whatsappNo'],
                'longitude' => $entityData['longitude'],
                'latitude' => $entityData['latitude'],
                'inclusion_date' => $entityData['inclusionDate'],
                //  'verification_date' => $entityData['verificationDate'],
                'opening_demand' => $entityData['openingDemand'],
                'monthly_demand' => $entityData['monthlyDemand'],
                'usage_type' => $entityData['usageType'],
                'status' => $entityData['status'],
                'vrno' => 1,
                'is_active' => true,
                'is_verified' => false,
            ]);

            // Validate and extract ratepayer data
            //  DB::enableQueryLog();

            $ratePayer = Ratepayer::create([
                'ulb_id' => $ulbId,
                'entity_id' => $entity->id, // Link the entity
                'ward_id' => $ratepayerData['wardId'], // Link the entity
                'subcategory_id' => $ratepayerData['subcategoryId'], // Link the entity
                //   'cluster_id' => $ratepayerData['cluster_id'],
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
                'vrno' => 1,
                //  'bill_date' => $ratepayerData['bill_date'],
                'opening_demand' => $ratepayerData['openingDemand'],
                'monthly_demand' => $ratepayerData['monthlyDemand'],
                'is_active' => true,
            ]);

            // Commit transaction
            DB::commit();

            return format_response(
                'Entity and Ratepayer created successfully',
                $entity,
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
    public function update(EntityRatepayerRequest $request, int $id)
    {
        try {
            DB::beginTransaction();
            $entity = Entity::find($id);
            if ($entity == null) {
                return format_response(
                    'Invalid Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $ratepayer = Ratepayer::where('entity_id', $id)
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
            $columns = Schema::getColumnListing('log_entities');
            DB::table('log_entities')->insertUsing(
                $columns,
                DB::table('entities')->select('*')->where('id', $id)
            );

            $columns = Schema::getColumnListing('log_ratepayers');
            DB::table('log_ratepayers')->insertUsing(
                $columns,
                DB::table('ratepayers')->select('*')->where('id', $ratepayer->id)
            );

            $updateEntityData = [
                //  'cluster_id' => $validatedData['clusterId'],
                'subcategory_id' => $validatedData['entity']['subcategoryId'],
                //  'verifiedby_id' => $validatedData['entity']['verifiedbyId'] ?? null,
                //  'appliedtc_id' => $validatedData['entity']['appliedtcId'] ?? null,
                'holding_no' => $validatedData['entity']['holdingNo'],
                'entity_name' => $validatedData['entity']['entityName'],
                'entity_address' => $validatedData['entity']['entityAddress'],
                'pincode' => $validatedData['entity']['pincode'],
                'mobile_no' => $validatedData['entity']['mobileNo'],
                'landmark' => $validatedData['entity']['landmark'],
                'whatsapp_no' => $validatedData['entity']['whatsappNo'],
                'longitude' => $validatedData['entity']['longitude'],
                'latitude' => $validatedData['entity']['latitude'],
                'inclusion_date' => $validatedData['entity']['inclusionDate'],
                //  'verification_date' => $validatedData['entity']['verificationDate'],
                'opening_demand' => $validatedData['entity']['openingDemand'],
                'monthly_demand' => $validatedData['entity']['monthlyDemand'],
                'usage_type' => $validatedData['entity']['usageType'],
                'status' => $validatedData['entity']['status'],
                'vrno' => $entity->vrno + 1,
            ];

            $updateRatepayerData = [
                'ward_id' => $validatedData['ratepayer']['wardId'], // Link the entity
                'subcategory_id' => $validatedData['ratepayer']['subcategoryId'], // Link the entity
                //   'cluster_id' => $ratepayerData['cluster_id'],
                'paymentzone_id' => $validatedData['ratepayer']['paymentzoneId'],
                //  'last_payment_id' => null, // Initialize as null, can be updated later
                //  'last_transaction_id' => null, // Initialize as null, can be updated later
                'ratepayer_name' => $validatedData['ratepayer']['ratepayerName'],
                'ratepayer_address' => $validatedData['ratepayer']['ratepayerAddress'],
                'consumer_no' => $validatedData['ratepayer']['consumerNo'],
                'longitude' => $validatedData['ratepayer']['longitude'],
                'latitude' => $validatedData['ratepayer']['latitude'],
                'mobile_no' => $validatedData['ratepayer']['mobileNo'],
                'landmark' => $validatedData['ratepayer']['landmark'],
                'whatsapp_no' => $validatedData['ratepayer']['whatsappNo'],
                'vrno' => 1,
                //  'bill_date' => $ratepayerData['bill_date'],
                'opening_demand' => $validatedData['ratepayer']['openingDemand'],
                'monthly_demand' => $validatedData['ratepayer']['monthlyDemand'],
                'vrno' => $ratepayer->vrno + 1,
            ];

            $entity->update($updateEntityData);
            $ratepayer->update($updateRatepayerData);

            DB::commit();

            return format_response(
                'Entity updated successfully',
                $entity,
                Response::HTTP_OK
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
    public function updateGeoLocation(EntityGeolocationRequest $request, int $id)
    {
        try {
            $entity = Entity::find($id);
            if ($entity == null) {
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

            $entity->update($updateData);

            return format_response(
                'Entity Geolocation updated successfully',
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

    public function mapCluster(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'entityId' => 'required|integer|exists:entities,id',
                'clusterId' => 'required|integer|exists:clusters,id',
            ]);
            $entityId = $validatedData['entityId'];
            $clusterId = $validatedData['clusterId'];

            $entity = Entity::find($entityId);
            $cluster = Cluster::find($clusterId);

            if ($entity == null) {
                return format_response(
                    'Invalid Entity Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($entity->is_active = 0) {
                return format_response(
                    'Entity is Inactive',
                    null,
                    Response::HTTP_METHOD_NOT_ALLOWED
                );
            }

            if ($cluster == null) {
                return format_response(
                    'Invalid Cluster Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($cluster->is_active = 0) {
                return format_response(
                    'Entity is Inactive',
                    null,
                    Response::HTTP_METHOD_NOT_ALLOWED
                );
            }

            $ratepayer = Ratepayer::find($entity->id);
            if ($ratepayer == null) {
                return format_response(
                    'Invalid Ratepayer Reference id',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $ratepayer->is_active = 0;
            $ratepayer->update();

            $updateData = [
                'cluster_id' => $validatedData['clusterId'],
                'entity_id' => $validatedData['entityId'],
            ];

            $entity->update($updateData);

            return format_response(
                'Entity maped with Cluster successfully',
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

    //  public function index()
    //  {
    //      $entities = Entity::with(['ulb', 'cluster', 'zone', 'subCategory'])->get();

    //      return response()->json($entities);
    //  }

    /**
     * [GET]   /api/entities/{id}          4. Admin can See Entity data
     * Completed [OK]
     */
    public function show($id)
    {
        try {
            $entity = Entity::findOrFail($id);

            return format_response(
                'Entities',
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
     * [GET]   /api/entities/{id}          4. Admin can See Entity data
     * Completed [OK]
     */
    public function showAll(int $paginate)
    {
        try {
            $entity = Entity::paginate($paginate);

            return format_response(
                'Entities Paginated',
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
}
