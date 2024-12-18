<?php

namespace App\Http\Services;

use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RatepayerService
{
    public function store(array $validatedData): Ratepayer
    {
        try {
            $ulbId = $validatedData['ulb_id'];

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

            return $ratepayer;
        } catch (\Exception $e) {
            return null;
        }
    }

    //  GET /api/ratepayers/search?
    //  Multiple filter combinations

    // search=john                           // Partial text search
    // wardId=1
    // rateId=1
    // subcategoryId=1
    // paymentzoneId=1
    // ratepayer=test
    // consumerNo=er45
    // reputation=1
    // usage_type
    // is_active
    // status
    // reputation
    // sort_by = ratepayerName
    // per_page=20                           // Pagination

    public function deepSearch(Request $request)
    {
        $ulbId = $request->ulb_id;

        // Base query
        $query = DB::table('ratepayers as r')
            ->select([
                'r.id',
                DB::raw("IF(r.entity_id IS NOT NULL, 'Entity', 'Cluster') AS ratepayer_type"),
                'r.consumer_no',
                'r.ratepayer_name',
                'r.ratepayer_address',
                'r.holding_no',
                'w.ward_name',
                'r.mobile_no',
                'r.landmark',
                'z.payment_zone',
                'r.whatsapp_no',
                'r.usage_type',
                'r.status',
                'r.reputation',
            ])
            ->join('wards as w', 'r.ward_id', '=', 'w.id') // INNER JOIN
            ->leftJoin('payment_zones as z', 'r.paymentzone_id', '=', 'z.id') // LEFT JOIN
            ->leftJoin('sub_categories as s', 'r.subcategory_id', '=', 's.id'); // LEFT JOIN

        //   $results = $query->get(); // Execute the query

        // Conditional filters
        if ($request->filled('wardId')) {
            $query->where('ratepayers.ward_id', $request->input('wardId'));
        }
        if ($request->filled('clusterId')) {
            $query->where('ratepayers.cluster_id', $request->input('clusterId'));
        }

        if ($request->filled('rateId')) {
            $query->where('ratepayers.rate_id', $request->input('rateId'));
        }

        if ($request->filled('subcategoryId')) {
            $query->where('ratepayers.subcategory_id', $request->input('subcategoryId'));
        }

        if ($request->filled('paymentzoneId')) {
            $query->where('ratepayers.paymentzone_id', $request->input('paymentzoneId'));
        }

        // Text-based search
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->orWhere('r.holding_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('r.ratepayer_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('r.ratepayer_address', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('r.mobile_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('r.landmark', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('r.consumer_no', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Boolean filter
        if ($request->has('isActive')) {
            $query->where('ratepayers.is_active', $request->boolean('isActive'));
        }

        // Enum filters
        if ($request->filled('usageType')) {
            $query->where('ratepayers.usage_type', $request->input('usageType'));
        }

        if ($request->filled('status')) {
            $query->where('ratepayers.status', $request->input('status'));
        }

        if ($request->filled('reputation')) {
            $query->where('ratepayers.reputation', $request->input('reputation'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'ratepayer_name'); // Default sort column
        $sortDirection = $request->input('sortDirection', 'desc'); // Default direction

        // Allowed columns for sorting to prevent SQL injection
        $allowedSortColumns = [
            'id', 'ratepayer_name', 'holding_no', 'sub_category',
            'consumer_no', 'created_at', 'updated_at',
        ];

        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortDirection);
        } else {
            $query->orderBy('created_at', 'desc');
        }

        // Pagination
        $perPage = $request->input('perPage', 15);
        $perPage = max(1, min(100, $perPage)); // Limit per page to a range of 1–100

        // Execute and paginate results
        $results = $query->paginate($perPage);

        return $results;
    }

    public function deepSearchEloquent(Request $request)
    {
        $ulbId = $request->ulb_id;

        // Start with base query
        $query = Ratepayer::query()
            // Eager load relationships if needed
            ->with([
                // 'ulb:id,name',
                'ward:id,ward_name',
                'cluster:id,cluster_name',
                'paymentZone:id,payment_zone',
                'subCategory:id,sub_category',
            ]);

        $query->where('ulb_id', $ulbId);

        if ($request->filled('wardId')) {
            $query->where('ward_id', $request->input('wardId'));
        }

        if ($request->filled('rateId')) {
            $query->where('rate_id', $request->input('rateId'));
        }

        if ($request->filled('subcategoryId')) {
            $query->where('subcategory_id', $request->input('subcategory_id'));
        }

        if ($request->filled('paymentzoneId')) {
            $query->where('paymentzone_id', $request->input('paymentzoneId'));
        }

        // Text-based searches with partial matching
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where(function ($q) use ($searchTerm) {
                $q->where('holding_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('holding_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ratepayer_name', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('ratepayer_address', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('mobile_no', 'LIKE', "%{$searchTerm}%")
                    ->orWhere('landmark', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Boolean filters
        if ($request->has('isActive')) {
            $query->where('is_active', $request->boolean('isActive'));
        }

        // Enum filters
        if ($request->filled('usageType')) {
            $query->where('usage_type', $request->input('usageType'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('reputation')) {
            $query->where('reputation', $request->input('reputation'));
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'ratepayerName');
        $sortDirection = $request->input('sortDirection', 'desc');

        // Validate sort column to prevent SQL injection
        $allowedSortColumns = [
            'id', 'ratepayer_name', 'holding_no', 'sub_category',
            'consumer_no', 'created_at', 'updated_at',
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

        return $results;
    }
}
