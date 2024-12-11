<?php

namespace App\Http\Controllers;

use App\Http\Requests\EntityGeolocationRequest;
use App\Http\Requests\EntityRatepayerRequest;
use App\Http\Requests\EntityRequest;
use App\Models\Entity;
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
    public function store(EntityRequest $request)
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
                'cluster_id' => $entityData['clusterId'],
                'ward_id' => $entityData['wardId'],
                'subcategory_id' => $entityData['subcategoryId'],
                'verifiedby_id' => $entityData['verifiedbyId'] ?? null,
                'appliedtc_id' => $entityData['appliedtcId'] ?? null,
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
                'verification_date' => $entityData['verificationDate'],
                'opening_demand' => $entityData['openingDemand'],
                'monthly_demand' => $entityData['monthlyDemand'],
                'usage_type' => $entityData['usageType'],
                'status' => $entityData['status'],
                'is_active' => true,
                'is_verified' => false,
            ]);

            // Validate and extract ratepayer data
            //  DB::enableQueryLog();

            $ratePayer = Ratepayer::create([
                'ulb_id' => $ulbId,
                'entity_id' => $entity->id, // Link the entity
                'ward_id' => $ratepayerData['wardId'], // Link the entity
                //   'cluster_id' => $ratepayerData['cluster_id'],
                //  'paymentzone_id' => $ratepayerData['paymentzoneId'],
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
    public function update(EntityRequest $request, int $id)
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
                'cluster_id' => $validatedData['clusterId'],
                'subcategory_id' => $validatedData['subcategoryId'],
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

    //  GET /api/entities/search?
    //  Multiple filter combinations
    //  search=john                           // Partial text search
    //  ulb_id=1                              // Filter by ULB
    //  cluster_id=2                          // Filter by Cluster
    //  is_active=true                        // Boolean filter
    //  usage_type=Commercial                 // Enum filter
    //  start_date=2023-01-01&end_date=2023-12-31 // Date range
    //  min_monthly_demand=100&max_monthly_demand=1000 // Numeric range
    //  sort_by=monthly_demand&sort_direction=asc // Sorting
    //  per_page=20                           // Pagination
    public function deepSearch(Request $request)
    {
        $ulbId = $request->ulb_id;

        // Start with base query
        $query = Entity::query()
            // Eager load relationships if needed
            ->with([
                // 'ulb:id,name',
                'cluster:id,name',
                'subcategory:id,name',
                'verifyUser:id,name',
                'applyTcUser:id,name',
            ]);

        $query->where('ulb_id', $ulbId);

        if ($request->filled('cluster_id')) {
            $query->where('cluster_id', $request->input('cluster_id'));
        }

        if ($request->filled('subcategory_id')) {
            $query->where('subcategory_id', $request->input('subcategory_id'));
        }

        // Text-based searches with partial matching
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('holding_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('holding_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('entity_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('entity_address', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('mobile_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('landmark', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Boolean filters
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        if ($request->has('is_verified')) {
            $query->where('is_verified', $request->boolean('is_verified'));
        }

        // Enum filters
        if ($request->filled('usage_type')) {
            $query->where('usage_type', $request->input('usage_type'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Date range filters
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('inclusion_date', [
                $request->input('start_date'),
                $request->input('end_date'),
            ]);
        }

        // Numeric range filters
        if ($request->filled('min_monthly_demand') && $request->filled('max_monthly_demand')) {
            $query->whereBetween('monthly_demand', [
                $request->input('min_monthly_demand'),
                $request->input('max_monthly_demand'),
            ]);
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDirection = $request->input('sort_direction', 'desc');

        // Validate sort column to prevent SQL injection
        $allowedSortColumns = [
            'id', 'entity_name', 'holding_no', 'monthly_demand',
            'inclusion_date', 'created_at', 'updated_at',
        ];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('per_page', 15);
        $perPage = max(1, min(100, $perPage)); // Limit between 1 and 100

        // Execute pagination
        $results = $query->paginate($perPage);

        // Optional: Transform results if needed
        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'total_pages' => $results->lastPage(),
                'total_items' => $results->total(),
                'per_page' => $results->perPage(),
            ],
        ]);
    }
}
