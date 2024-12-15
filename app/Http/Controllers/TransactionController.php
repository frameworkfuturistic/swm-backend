<?php

namespace App\Http\Controllers;

use App\Events\SiteVisitedEvent;
use App\Http\Services\TransactionService;
use App\Models\Payment;
use App\Models\Transaction;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Razorpay\Api\Api;

/**
 * Created on 04/12/2024
 * Author:
 *    Anil Mishra
 * Api mapping:
 *    Route::post('transactions/add', [TransactionController::class, 'recordTransaction']);
 *
 * Request url:
 *    recordTransaction => [POST] http://127.0.0.1:8000/api/transactions
 *    suspendTransaction =>
 *    updateTransaction =>
 *
 *
 * Effects:
 *    1. Field Tracking Dashboard
 *    2. TC Mobile app for transaction serach
 *
 * Possible Enhancements:
 *    1. Payment gateway integration.
 */
class TransactionController extends Controller
{
    /**
     * 1. api/transactions/payment/cash
     * 2.
     */
    public function cashPayment(Request $request)
    {
        $tranService = new TransactionService;

        $validatedData = $request->validate([
            // 'ulbId' => 'required|exists:ulbs,id',
            'tcId' => 'required|exists:users,id',
            'ratepayerId' => 'required|exists:ratepayers,id',
            //  'eventType' => 'required|in:PAYMENT,DENIAL,DOOR-CLOSED,DEFERRED,OTHER',
            'remarks' => 'nullable|string|max:250',
            'autoRemarks' => 'nullable|string|max:250',
            'amount' => 'required|integer|min:1',
            //  'paymentMode' => 'required_if:event_type,PAYMENT|in:cash,card,upi,cheque,online',
        ]);

        $validatedData['ulbId'] = $request->ulbId;
        $validatedData['entityId'] = $request->input('entityId');
        $validatedData['clusterId'] = $request->input('clusterId');
        $validatedData['eventType'] = $request->input('PAYMENT');
        $validatedData['paymentMode'] = 'CASH';

        // Start a transaction to ensure data integrity

        DB::beginTransaction();
        try {

            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);
            $payment = $tranService->createNewPayment($validatedData, $transaction->id);

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $tranService->transaction->id,
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->ratepayer_address,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                    'tranType' => $tranService->transaction->event_type,
                    'pmtMode' => $tranService->transaction->payment_mode,
                    'pmtAmount' => $tranService->payment->amount,
                    'remarks' => $tranService->transaction->remarks,
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
                broadcast(new SiteVisitedEvent(
                    $responseData,
                ))->toOthers();

                return format_response(
                    'success',
                    $responseData,
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollBack();

                return format_response(
                    'An error occurred during insertion',
                    null,
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (Exception $e) {
            DB::rollBack();

            return format_response(
                'An error occurred during insertion',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Request body
     *{
     *    "tc_id": 5,
     *    "ratepayer_id": 10,
     *    "payment_mode": "cash",
     *    "amount": 3000,
     *    "event_time": "2024-11-29 12:00:00",
     *    "remarks": "Payment for 3 months."
     *}
     */
    public function store(Request $request)
    {
        $tranService = new TransactionService;

        $validatedData = $request->validate([
            // 'ulbId' => 'required|exists:ulbs,id',
            'tcId' => 'required|exists:users,id',
            'ratepayerId' => 'required|exists:ratepayers,id',
            'eventType' => 'required|in:PAYMENT,DENIAL,DOOR-CLOSED,DEFERRED,OTHER',
            'remarks' => 'nullable|string|max:250',
            'autoRemarks' => 'nullable|string|max:250',
            'amount' => 'required_if:event_type,PAYMENT|integer|min:1',
            'paymentMode' => 'required_if:event_type,PAYMENT|in:cash,card,upi,cheque,online',
        ]);

        $validatedData['entityId'] = $request->input('entityId');
        $validatedData['clusterId'] = $request->input('clusterId');

        // Start a transaction to ensure data integrity

        DB::beginTransaction();
        try {
            $success = $tranService->recordTransaction($validatedData);
            if ($success == true) {
                $responseData = [
                    'tranId' => $tranService->transaction->id,
                    'tcId' => $validatedData['tc_id'],
                    'clusterId' => $tranService->transaction->cluster_id,
                    'entityId' => $tranService->transaction->entity_id,
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->ratepayer_address,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                    'tranType' => $tranService->transaction->event_type,
                    'pmtMode' => $tranService->transaction->payment_mode,
                    'pmtAmount' => $tranService->payment->amount,
                    'remarks' => $tranService->transaction->remarks,
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
                broadcast(new SiteVisitedEvent(
                    $responseData,
                ))->toOthers();

                return format_response(
                    'success',
                    $responseData,
                    Response::HTTP_CREATED
                );
            } else {
                DB::rollBack();

                return format_response(
                    'An error occurred during insertion',
                    null,
                    Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (Exception $e) {
            DB::rollBack();

            return format_response(
                'An error occurred during insertion',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
