<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

//  -- Verify Payments
//  -- Modify Payment Records
//  -- Verify Cancellations
//  -- Collect Cash
//  -- Collect Cheque
//  -- Cheque Verification
//  -- Cheque Reconciliation and update payment
//  -- UPI Verification and Reconciliation
//  -- Initiate UPI Refund
//  -- Waive off Demand against order

class AccountsController extends Controller
{
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
}
