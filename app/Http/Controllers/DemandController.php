<?php

namespace App\Http\Controllers;

use App\Http\Services\DemandService;
use App\Models\CurrentDemand;
use App\Models\DemandNotice;
use App\Models\PaymentZone;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DemandController extends Controller
{
    public function generateYearlyDemand(Request $request)
    {
        try {
            $year = $request->CURRENT_YEAR;
            $month = 5;
            $ulb_id = $request->ulb_id;
            $tcId = Auth::user()->id;
            $service = new DemandService;
            $stats = $service->generateYearlyDemands($year, $month, $ulb_id, $tcId);

            return format_response(
                'Demand Generated Successfully',
                $stats,
                Response::HTTP_CREATED
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

    public function generateRatepayerDemands(Request $request, $year, $id)
    {
        try {
            $ratepayer = Ratepayer::find($id);
            $service = new DemandService;

            $ulb_id = $request->ulb_id;
            $service = new DemandService;
            $stats = $service->generateRatepayerDemands($ratepayer, $year);

            return format_response(
                'Demand Generated Successfully',
                $stats,
                Response::HTTP_CREATED
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
     * Display the specified resource.
     */
    public function showYearlyDemand(int $ratePayerId, $year)
    {
        //   return response()->json($demand->load('ratepayer'));
    }

    /**
     * Display the specified resource.
     */
    public function showPendingDemands(int $ratePayerId, $year)
    {
        //   return response()->json($demand->load('ratepayer'));
    }

    /**
     * Display the specified resource.
     */
    public function showRatepayerCurrentDemand(int $id)
    {
        try {
            $ratepayers = CurrentDemand::where('ratepayer_id', $id)
                ->where('is_active', true)
                ->whereRaw('(bill_month + (bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
               ->get();

            return format_response(
                'Current Demand',
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

    /**
     * Display the specified resource.
     */
    public function showWardWiseCurrentDemand(int $ratePayerId, $year, $month, $ward)
    {
        //   return response()->json($demand->load('ratepayer'));
    }

    /**
     * Display the specified resource.
     */
    public function showZoneWiseCurrentDemand(int $ratePayerId, $year, $month, $zone)
    {
        //   return response()->json($demand->load('ratepayer'));
    }

    public function printableDemandNotice(Request $request, $id)
    {
      try {
         $notice = DemandNotice::find($id);
            if ($notice == null) {
               return format_response(
                  'Invalid Notice No',
                  null,
                  Response::HTTP_NOT_FOUND
              );
            }

         $data = DB::table('demand_notices as d')
            ->join('ratepayers as r', 'r.id', '=', 'd.ratepayer_id')
            ->join('sub_categories as s', 'r.subcategory_id', '=', 's.id')
            ->join('categories as c', 's.category_id', '=', 'c.id')
            ->join('wards as w', 'r.ward_id', '=', 'w.id')
            ->select([
               'r.ratepayer_name as name',
               'r.mobile_no as mobile_no',
               'r.ratepayer_address as address',
               's.sub_category',
               'd.demand_no',
               DB::raw("DATE_FORMAT(d.generated_on, '%d %M, %Y %h:%i %p') as demand_date"),
               'r.consumer_no',
               'w.ward_name as ward_no',
               'r.holding_no',
               's.sub_category as type',
               'd.demand_amount as amount',
            ])
            ->where('d.id', $id)
            ->first(); // or ->get() for multiple results

         $data->transactions = DB::table('demand_noticedetails as d')
            ->where('d.demandnotice_id', $id)
            ->select([
               'd.bill_month',
               'd.rate as rate_per_month',
               'd.amount',
            ])
            ->get();


         return format_response(
               'Demand Notice Details',
               $data,
               Response::HTTP_OK
         );
      } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_NOT_FOUND
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

    public function zoneCurrentDemands(Request $request, $id )
    {
        try {
         //   $request->validate([
         //       'zoneId' => ['required', 'exists:payment_zones,id'],
         //   ]);

            $zone = PaymentZone::find($id);
            if ($zone == null) {
               return format_response(
                  'Database error occurred',
                  null,
                  Response::HTTP_NOT_FOUND
              );
            }

            // Working Fine
            // =============================
            $startsWith = $request->query('starts-with');
            if ($startsWith == 'all') {
               $startsWith = '';
            }
            // $qry = DB::table('current_demands as c')
            //    ->select(
            //       'c.ratepayer_id',
            //       'r.consumer_no',
            //       'r.ratepayer_name',
            //       'r.ratepayer_address',
            //       'r.mobile_no',
            //       'r.holding_no',
            //       'ct.category',
            //       'sc.sub_category',
            //       'r.reputation',
            //       'r.lastpayment_amt',
            //       DB::raw('IF(
            //             ((r.latitude IS NOT NULL) AND (r.longitude IS NOT NULL)
            //             AND (r.latitude BETWEEN -90 AND 90)
            //             AND (r.longitude BETWEEN -180 AND 180)),
            //             true,
            //             false
            //       ) as validCoordinates'),
            //       DB::raw('DATE_FORMAT(r.lastpayment_date, "%d/%m/%Y") as lastpayment_date'),
            //       DB::raw('SUM(c.total_demand) as totalDemand')
            //    )
            //    ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
            //    ->join('sub_categories as sc', 'r.subcategory_id', '=', 'sc.id')
            //    ->join('categories as ct', 'sc.category_id', '=', 'ct.id')
            //    ->whereRaw('IFNULL(c.total_demand, 0) - IFNULL(c.payment, 0) > 0')
            //    ->whereRaw('(c.bill_month + (c.bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
            //    ->where('r.paymentzone_id', $id)
            //    ->where('r.ratepayer_name', 'LIKE', $startsWith . '%')
            //    ->whereNull('cluster_id')
            //    ->groupBy(
            //       'c.ratepayer_id',
            //       'r.consumer_no',
            //       'r.ratepayer_name',
            //       'r.ratepayer_address',
            //       'r.mobile_no',
            //       'r.holding_no',
            //       'ct.category',
            //       'sc.sub_category',
            //       'r.reputation',
            //       'r.lastpayment_amt',
            //       'r.lastpayment_date',
            //       'r.latitude',
            //       'r.longitude'
            //    )
            //    ->orderBy('r.ratepayer_name');

               // Algorithm for Alternate Screen
            $qry = DB::table('entities as e')
               ->select([
                  'e.ratepayer_id',
                  'r.consumer_no',
                  'r.ratepayer_name',
                  'r.ratepayer_address',
                  'r.mobile_no',
                  'r.holding_no',
                  'c.category',
                  's.sub_category',
                  'r.reputation',
                  'r.lastpayment_amt',
                  DB::raw('
                        IF(
                           (r.latitude IS NOT NULL AND r.longitude IS NOT NULL AND 
                           r.latitude BETWEEN -90 AND 90 AND 
                           r.longitude BETWEEN -180 AND 180),
                           TRUE, FALSE
                        ) as validCoordinates
                  '),
                  DB::raw('DATE_FORMAT(r.lastpayment_date, "%d/%m/%Y") as lastpayment_date'),
                  DB::raw('SUM(IFNULL(d.total_demand, 0)) as totalDemand')
               ])
               ->join('ratepayers as r', 'e.ratepayer_id', '=', 'r.id')
               ->join('sub_categories as s', 'r.subcategory_id', '=', 's.id')
               ->join('categories as c', 's.category_id', '=', 'c.id')
               ->leftJoin('current_demands as d', 'e.ratepayer_id', '=', 'd.ratepayer_id')
               ->where('r.paymentzone_id', $id)
               ->where('r.ratepayer_name', 'LIKE', $startsWith . '%')
               ->groupBy([
                  'e.ratepayer_id',
                  'r.consumer_no',
                  'r.ratepayer_name',
                  'r.ratepayer_address',
                  'r.mobile_no',
                  'r.holding_no',
                  'c.category',
                  's.sub_category',
                  'r.reputation',
                  'r.lastpayment_amt',
                  'r.latitude',
                  'r.longitude',
                  'r.lastpayment_date',
               ])
               ->orderBy('r.ratepayer_name');
            
               $results = $qry->get();

               return format_response(
                'Show Pending Demands from '.$zone->payment_zone,
                $results,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Database error during entity update: '.$e->getMessage());

            return format_response(
                'Database error occurred',
                null,
                Response::HTTP_NOT_FOUND
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

    //
    public function zoneCurrentClusterDemands(Request $request, $id)
    {
        try {

            $zone = PaymentZone::find($id);

            if ($zone == null) {
               return format_response(
                  'Database error occurred',
                  null,
                  Response::HTTP_NOT_FOUND
              );
            }

            $startsWith = $request->query('starts-with');
            if ($startsWith == 'all') {
               $startsWith = '';
            }

            DB::enableQueryLog();

            // $currentMonth = now()->month;
            // $currentYear = now()->year;
            
            // $qry = DB::table('clusters as c')
            //    ->select(
            //       'c.id as cluster_id',
            //       'c.ratepayer_id',
            //       'r.consumer_no',
            //       'r.ratepayer_name',
            //       'r.ratepayer_address',
            //       DB::raw('cast(SUM(IFNULL(d.total_demand, 0)) as char) as clusterDemand')
            //    )
            //    ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
            //    ->join('entities as e', 'c.id', '=', 'e.cluster_id')
            //    ->leftJoin('current_demands as d', 'e.ratepayer_id', '=', 'd.ratepayer_id')
            //    ->where('r.paymentzone_id', $id)
            //    ->where('r.is_active', true)
            //    ->groupBy('c.id', 'c.ratepayer_id', 'r.consumer_no', 'r.ratepayer_name', 'r.ratepayer_address')
            //    ->orderBy('r.ratepayer_name');

            $qry = DB::table('clusters as c')
               ->select(
                  'c.id as cluster_id',
                  'c.ratepayer_id',
                  'r.consumer_no',
                  'r.ratepayer_name',
                  'r.ratepayer_address',
                  DB::raw('SUM(IFNULL(d.total_demand, 0) - IFNULL(d.payment, 0)) as clusterDemand')
               )
               ->join('ratepayers as r', 'r.id', '=', 'c.ratepayer_id')
               ->join('entities as e', 'c.id', '=', 'e.cluster_id')
               ->leftJoin('current_demands as d', function ($join) {
                  $join->on('e.ratepayer_id', '=', 'd.ratepayer_id')
                        ->whereRaw('((d.bill_year * 12) + d.bill_month) <= ((YEAR(CURRENT_DATE) * 12) + MONTH(CURRENT_DATE))');
               })
               ->where('r.paymentzone_id', $id)
               ->where('r.ratepayer_name', 'LIKE', $startsWith . '%')
               ->groupBy('c.ratepayer_id', 'c.id', 'r.consumer_no', 'r.ratepayer_name', 'r.ratepayer_address')
               ->orderBy('r.ratepayer_name');

            $results = $qry->get();
            return format_response(
               'Show Pending Demands from '.$zone->payment_zone,
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


    public function clusterDemands($id)
    {
        try {
            // $zone = PaymentZone::find($id);

            $year = request('year');
            $month = request('month');

            $query = DB::table('ratepayers as r')
            ->join('entities as e', 'e.id', '=', 'r.entity_id')
            ->join('current_demands as c', 'r.id', '=', 'c.ratepayer_id')
            ->select(
                'c.ratepayer_id',
                'r.consumer_no',
                'r.ratepayer_name',
                'r.ratepayer_address',
                'r.mobile_no',
                'r.reputation',
                'r.lastpayment_amt',
                DB::raw('IF((r.latitude IS NOT NULL AND r.longitude IS NOT NULL AND r.latitude BETWEEN -90 AND 90 AND r.longitude BETWEEN -180 AND 180), TRUE, FALSE) as validCoordinates'),
                DB::raw('DATE_FORMAT(r.lastpayment_date, "%d/%m/%Y") as lastpayment_date'),
                DB::raw('SUM(c.total_demand) as totalDemand')
            )
            ->whereRaw('(ifnull(c.total_demand,0) - ifnull(c.payment,0)) > 0')
            // ->whereRaw('(c.bill_month + (c.bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
            ->whereRaw('(c.bill_month + (c.bill_year * 12)) <= (? + (? * 12))', [$month ?? date('n'), $year ?? date('Y')])
            ->where('c.is_active', 1)
            ->where('e.cluster_id', $id)
            ->groupBy('c.ratepayer_id');

            $results = $query->get();


            // $results = DB::table('current_demands as c')
            //     ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
            //     ->select(
            //         'c.ratepayer_id',
            //         'r.consumer_no',
            //         'r.ratepayer_name',
            //         'r.ratepayer_address',
            //         'r.mobile_no',
            //         'r.reputation',
            //         'r.lastpayment_amt',
            //         DB::raw('if(((latitude IS NOT NULL) AND (longitude IS NOT NULL) AND (latitude BETWEEN -90 AND 90) AND (longitude BETWEEN -180 AND 180)),true,false) as validCoordinates'),
            //         DB::raw('DATE_FORMAT(r.lastpayment_date,"%d/%m/%Y") as lastpayment_date'),
            //         DB::raw('SUM(c.total_demand) as totalDemand')
            //     )
            //     ->whereRaw('c.total_demand - c.payment > 0')  // Ensure unpaid demand exists
            //     ->whereRaw('MONTH(SYSDATE()) >= c.bill_month')  // Ensure current month is less than or equal to bill_month
            //     ->where('r.paymentzone_id', $id)  // Ensure current month is less than or equal to bill_month
            //     ->whereRaw('cluster_id IS NOT NULL')  // Ensure current month is less than or equal to bill_month
            //     ->groupBy('c.ratepayer_id', 'r.consumer_no', 'r.ratepayer_name', 'r.ratepayer_address', 'r.mobile_no')  // Group by ratepayer_id and relevant columns
            //     ->orderBy('r.ratepayer_name')
            //     ->get();

            return format_response(
                'Show Pending Cluster Demands ',
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

 public function pendingDemandNotices(Request $request)
    {
      try 
      {
         $data = DB::table('demand_notices as d')
            ->join('ratepayers as r', 'd.ratepayer_id', '=', 'r.id')
            ->select(
               'd.id',
               'r.ratepayer_name',
               'r.ratepayer_address',
               'r.mobile_no',
               'd.demand_no',
               'd.generated_on',
               'd.demand_amount'
            )
            ->whereNull('d.served_on')
            ->get();

             return format_response(
                'Show Pending Cluster Demand Notices ',
                $data,
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
    
   public function test()
   {
      
   }
}
