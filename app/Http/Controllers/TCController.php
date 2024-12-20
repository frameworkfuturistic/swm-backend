<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

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
