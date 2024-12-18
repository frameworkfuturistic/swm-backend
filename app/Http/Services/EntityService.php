<?php

namespace App\Http\Services;

use App\Models\Entity;
use Illuminate\Http\Request;

class RatepayerService
{
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
