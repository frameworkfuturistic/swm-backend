<?php

namespace App\Http\Controllers;

use App\Models\PaymentZone;
use App\Models\TCHasZone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TCController extends Controller
{
    /**
     * [GET] /api/tc
     * [GET] /api/tc/current-tc
     * [POST] /api/tc
     * [PUT] /api/tc{id}
     * [PUT] /api/tc/suspend/{id}
     *
     * [PUT] /api/tc/reputation/{ratepayer_d}
     *
     * [GET] /api/tc/zone/entity/search
     * [GET] /api/tc/zone/cluster
     * [GET] /api/tc/zone/ratepayers/search
     *
     * [PUT] /api/tc/update-location/{id}
     *
     * [GET] /api/tc/dashboard
     * [PUT] /api/tc/cancel-pmt
     * [GET] /api/tc/profile
     */
    public function showCurrentTCs(Request $request)
    {
        try {
            $ulbId = $request->ulb_id;

            $tcs = User::with('paymentZones')
                ->where('ulb_id', $ulbId)
                ->where('is_active', 1)
                ->where('role', 'tax_collector')
                ->get();

            // $tcs = User::where('ulb_id', $ulbId)
            //     ->where('is_active', 1)
            //     ->where('role', 'tax_collector')
            //     ->get();

            return format_response(
                'Current TCs',
                $tcs,
                Response::HTTP_CREATED
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
    //showAllTCs

    public function showAllTCs(Request $request)
    {
        try {
            $ulbId = $request->ulb_id;

            $tcs = User::with('paymentZones')
                ->where('ulb_id', $ulbId)
                ->where('role', 'tax_collector')
                ->get();

            // $tcs = User::where('ulb_id', $ulbId)
            //     ->where('is_active', 1)
            //     ->where('role', 'tax_collector')
            //     ->get();

            return format_response(
                'Current TCs',
                $tcs,
                Response::HTTP_CREATED
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function showSuspendedTCs(Request $request)
    {
        try {
            $ulbId = $request->ulb_id;

            $tcs = User::where('ulb_id', $ulbId)
                ->where('is_active', 0)
                ->where('role', 'tax_collector')
                ->get();

            return format_response(
                'Suspended TCs',
                $tcs,
                Response::HTTP_CREATED
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function showTcZones(Request $request)
    {
        try {
            $tcId = auth()->user()->id;
            DB::enableQueryLog();
            $results = DB::table('tc_has_zones as t')
                ->join('payment_zones as z', 't.paymentzone_id', '=', 'z.id')
                ->where('t.is_active', 1)
                ->where('t.tc_id', $tcId)
                ->select('t.paymentzone_id', 'z.payment_zone', 'z.description')
                ->get();

            return format_response(
                'Tc Active Zones',
                $results,
                Response::HTTP_OK
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    //ASSIGN ZONE TO TC
    public function assignZone(Request $request)
    {
        try {
            $validated = $request->validate([
                'tcId' => 'required|exists:users,id',
                'paymentzoneId' => 'required|exists:payment_zones,id',
            ]);

            $tc = User::find($validated['tcId']);
            $paymentzone = PaymentZone::find($validated['paymentzoneId']);

            if ($tc == null) {
                return format_response(
                    'TC not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($tc->role != 'tax_collector') {
                return format_response(
                    'TC not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($paymentzone == null) {
                return format_response(
                    'Payment Zone not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $tcHasZone = TCHasZone::where('tc_id', $tc->id)
                ->where('paymentzone_id', $paymentzone->id)
                ->where('is_active', true)
                ->first();

            if ($tcHasZone != null) {
                return format_response(
                    'Already Assigned',
                    null,
                    Response::HTTP_BAD_REQUEST
                );
            }

            $zone = TCHasZone::create([
                'tc_id' => $validated['tcId'],
                'paymentzone_id' => $validated['paymentzoneId'],
                'allotment_date' => now(),
                // 'deactivation_date' => $validated['deactivationDate'] ?? null,
                'is_active' => true,
                'vrno' => 1,
            ]);

            return format_response(
                'Successfully Created',
                null,
                Response::HTTP_CREATED
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    //REVOKE ZONE FROM TC
    public function revokeZone(Request $request)
    {
        try {
            $validated = $request->validate([
                'tcId' => 'required|exists:users,id',
                'paymentzoneId' => 'required|exists:payment_zones,id',
            ]);

            $tc = User::find($validated['tcId']);
            $paymentzone = PaymentZone::find($validated['paymentzoneId']);

            if ($tc == null) {
                return format_response(
                    'TC not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($tc->role != 'tax_collector') {
                return format_response(
                    'TC not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            if ($paymentzone == null) {
                return format_response(
                    'Payment Zone not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $tcHasZone = TCHasZone::where('tc_id', $tc->id)
                ->where('paymentzone_id', $paymentzone->id)
                ->where('is_active', true)
                ->first();

            if ($tcHasZone == null) {
                return format_response(
                    'No Active Record Found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $tcHasZone->update([
                'is_active' => false,
                'deactivation_date' => now(),
            ]);

            return format_response(
                'Successfully Revoked Zone',
                null,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function tcDashboard(Request $request)
    {
        try {
            $tcId = auth()->user()->id;

            $prevMonth = DB::table('current_payments as p')
                ->select(DB::raw('SUM(p.amount) as totalCollection'))
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->join('payment_zones as z', 'r.paymentzone_id', '=', 'z.id')
                ->join('users as u', 'p.tc_id', '=', 'u.id')
                ->where('p.tc_id', 3)
                ->whereRaw('MONTH(p.payment_date) = MONTH(DATE_SUB(SYSDATE(), INTERVAL 1 MONTH))')
                ->whereRaw('YEAR(p.payment_date) = YEAR(DATE_SUB(SYSDATE(), INTERVAL 1 MONTH))')
                ->get();

            $prevCollection = 0;
            foreach ($prevMonth as $record) {
                $prevCollection = $record->totalCollection;
            }

            $results = DB::table('current_payments as p')
                ->select('z.payment_zone', DB::raw('SUM(p.amount) as totalCollection'))
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->join('payment_zones as z', 'r.paymentzone_id', '=', 'z.id')
                ->join('users as u', 'p.tc_id', '=', 'u.id')
                ->where('p.tc_id', 3)
                ->whereRaw('MONTH(p.payment_date) = MONTH(SYSDATE())')
                ->groupBy('z.id')
                ->get();

            $formattedDate = now()->format('F, Y');

            $response = [
                'collectionMonth' => $formattedDate,
                'tcName' => Auth::user()->name,
                'prevMonthCollection' => $prevCollection,
                'zoneSummary' => $results,
            ];

            return format_response(
                'TC Dashboard',
                $response,
                Response::HTTP_OK
            );
        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function getZoneByID(Request $request, $id)
    {
        try {
            $data = DB::table('payment_zones as z')
                ->Join('wards as w', 'z.ward_id', '=', 'w.id') // Join with wards table to get ward_name
                ->select(
                    'z.id as zoneId',
                    'z.payment_zone as paymentZone',
                    'z.description',
                    'w.ward_name as wardName',
                    'z.apartments',
                    'z.buildings',
                    'z.govt_buildings as govtBuildings',
                    'z.colonies',
                    'z.other_buildings as otherBuildings',
                    'z.residential',
                    'z.commercial',
                    'z.industrial',
                    'z.institutional',
                    'z.monthly_demand as monthlyDemand',
                    'z.yearly_demand as yearlyDemand'
                )
                ->where('z.id', $id)
                ->first();

            return format_response(
                'success',
                $data,
                Response::HTTP_OK
            );
        } catch (\Exception $e) {
            return format_response(
                'success',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
