<?php

namespace App\Http\Controllers;

use App\Http\Services\DemandService;
use App\Models\Demand;
use App\Models\Ratepayer;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DemandController extends Controller
{
    public function generateYearlyDemand(Request $request)
    {
        try {
            $year = $request->CURRENT_YEAR;
            $ulb_id = $request->ulb_id;
            $service = new DemandService;
            $stats = $service->generateYearlyDemands($year, $ulb_id);

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
    public function showCurrentDemand(int $ratePayerId, $year, $month)
    {
        //   return response()->json($demand->load('ratepayer'));
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

    /**
     * Clean Current Demand.
     */
    public function cleanCurrentDemand(Request $request)
    {
        try {
            $ulb_id = $request->ulb_id;
            $service = new DemandService;
            $retFlag = $service->cleanCurrentDemand($ulb_id);

            if ($retFlag == false) {
                return format_response(
                    'Could not Clean Current Demand',
                    null,
                    Response::HTTP_NOT_MODIFIED
                );
            } else {
                return format_response(
                    'Current Demand Cleaned Successfully',
                    null,
                    Response::HTTP_CREATED
                );
            }
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
     * Clean Current Demand.
     */
    public function mergeCurrentDemand(Request $request)
    {
        try {
            $service = new DemandService;

            $ulb_id = $request->ulb_id;
            $service = new DemandService;
            $retFlag = $stats = $service->mergeDemand($ulb_id);

            if ($retFlag == false) {
                return format_response(
                    'Could not Clean Current Demand',
                    $stats,
                    Response::HTTP_NOT_MODIFIED
                );
            } else {
                return format_response(
                    'Current Demand Cleaned Successfully',
                    $stats,
                    Response::HTTP_CREATED
                );
            }
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
     * Clean Current Demand.
     */
    public function mergeAndCleanCurrentDemand(Request $request)
    {
        try {
            $service = new DemandService;

            $ulb_id = $request->ulb_id;
            $service = new DemandService;
            $mrgStr = 'Could not Merge';
            $cleanStr = 'Could not Clean';
            $flag = true;
            $retStr = '';

            $retFlag = $service->mergeDemand($ulb_id);
            if ($retFlag) {
                $mrgStr = 'Mearged Demand successfully,';
            } else {
                $flag = false;
            }

            $retFlag = $service->cleanCurrentDemand($ulb_id);
            if ($retFlag) {
                $cleanStr = 'Cleaned Demand successfully,';
            } else {
                $flag = false;
            }

            $retStr = $mrgStr.','.$cleanStr;

            if ($flag == false) {
                return format_response(
                    $retStr,
                    null,
                    Response::HTTP_NOT_MODIFIED
                );
            } else {
                return format_response(
                    $retStr,
                    null,
                    Response::HTTP_OK
                );
            }
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
