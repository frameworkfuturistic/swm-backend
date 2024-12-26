<?php

namespace App\Http\Controllers;

use App\Models\PaymentZone;
use App\Models\TCHasZone;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
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

            $tcs = User::where('ulb_id', $ulbId)
                ->where('is_active', 1)
                ->where('role', 'tax_collector')
                ->get();

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

    public function suspend(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if ($user == null) {
                return format_response(
                    'TC not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $user->is_active = 0;
            $user->update();

            return format_response(
                'Successfully Suspended'.$user->name,
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

    public function revoke(Request $request, $id)
    {
        try {
            $user = User::find($id);
            if ($user == null) {
                return format_response(
                    'TC not found',
                    null,
                    Response::HTTP_NOT_FOUND
                );
            }

            $user->is_active = 1;
            $user->update();

            return format_response(
                'Successfully Revoked Suspension'.$user->name,
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
}
