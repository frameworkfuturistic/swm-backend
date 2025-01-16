<?php

namespace App\Http\Controllers;

use App\Http\Services\DemandService;
use App\Models\CurrentDemand;
use App\Models\Demand;
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
            $ulb_id = $request->ulb_id;
            $tcId = Auth::user()->id;
            $service = new DemandService;
            $stats = $service->generateYearlyDemands($year, $ulb_id, $tcId);

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
                ->whereRaw('MONTH(SYSDATE()) >= bill_month')
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

    public function zoneCurrentDemands($id)
    {
        try {
            $zone = PaymentZone::find($id);

            $results = DB::table('current_demands as c')
                ->join('ratepayers as r', 'c.ratepayer_id', '=', 'r.id')
                ->select(
                    'c.ratepayer_id',
                    'r.consumer_no',
                    'r.ratepayer_name',
                    'r.ratepayer_address',
                    'r.mobile_no',
                    'r.reputation',
                    'r.lastpayment_amt',
                    DB::raw('if(((latitude IS NOT NULL) AND (longitude IS NOT NULL) AND (latitude BETWEEN -90 AND 90) AND (longitude BETWEEN -180 AND 180)),true,false) as validCoordinates'),
                    DB::raw('DATE_FORMAT(r.lastpayment_date,"%d/%m/%Y") as lastpayment_date'),
                    DB::raw('SUM(c.total_demand) as totalDemand')
                )
                ->whereRaw('c.total_demand - c.payment > 0')  // Ensure unpaid demand exists
                ->whereRaw('MONTH(SYSDATE()) >= c.bill_month')  // Ensure current month is less than or equal to bill_month
                ->where('r.paymentzone_id', $id)  // Ensure current month is less than or equal to bill_month
                ->groupBy('c.ratepayer_id', 'r.consumer_no', 'r.ratepayer_name', 'r.ratepayer_address', 'r.mobile_no')  // Group by ratepayer_id and relevant columns
                ->orderBy('r.ratepayer_name')
                ->get();

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
}
