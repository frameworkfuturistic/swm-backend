<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Http\Response;

class ClusterDemandController extends Controller
{

    // API-ID: DEMAND-001 [ClusterDemandController]
    public function getClusterDemands(Request $request)
    {
        try {
            $ratepayerClusterDemand = DB::table('ratepayers as rp')
                ->join(DB::raw('
                (
                    SELECT e.cluster_id, SUM(d.demand) AS clusterDemand
                    FROM ratepayers r
                    INNER JOIN entities e ON r.entity_id = e.id
                    INNER JOIN current_demands d ON r.id = d.ratepayer_id
                    WHERE e.cluster_id IS NOT NULL
                      AND (d.bill_month + (d.bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))
                    GROUP BY e.cluster_id
                ) as a
            '), 'rp.cluster_id', '=', 'a.cluster_id')
                ->select(
                    'a.cluster_id',
                    'rp.id as ratepayer_id',
                    'rp.consumer_no',
                    'rp.ratepayer_name',
                    'rp.ratepayer_address',
                    'a.clusterDemand'
                )
                ->get();

            return format_response(
                'Success',
                [
                    'status' => 'success',
                    'data' => $ratepayerClusterDemand,
                    'metadata' => [
                        'generated_at' => Carbon::now()->toDateTimeString()
                    ]
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch cluster demand data',
                'error' => $e->getMessage()
            ], 500);
        }
    }




    // API-ID: DEMAND-002 [ClusterDemandController]
    public function getRatepayersDemands(Request $request)
    {
        try {
            $ratepayerDemand = DB::table('current_demands as d')
                ->join('ratepayers as p', 'd.ratepayer_id', '=', 'p.id')
                ->select(
                    'p.id as ratepayer_id',
                    'p.ratepayer_name',
                    'p.ratepayer_address',
                    'p.consumer_no',
                    'd.bill_month',
                    'd.bill_year',
                    'd.demand'
                )
                ->where('p.paymentzone_id', 1)
                ->whereRaw('(d.bill_month + (d.bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
                ->get();

            return format_response(
                'Success',
                [
                    'status' => 'success',
                    'data' => $ratepayerDemand,
                    'metadata' => [
                        'generated_at' => Carbon::now()->toDateTimeString()
                    ]
                ],
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to fetch ratepayer demand data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
