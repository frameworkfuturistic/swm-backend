<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageTransactionController extends Controller
{
    public function getTransactionData(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }
            $vrNo = $request->input('vrNo', null);
            $ratepayerId = $request->input('ratepayerId', null);
            $paymentId = $request->input('paymentId', null);
            $fromDate = $request->input('fromDate', null);
            $toDate = $request->input('toDate', null);

            $query = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
                ->select(
                    DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
                    DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
                    DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
                    // 'transactions.id',
                    // 'transactions.ulb_id',
                    // 'transactions.ratepayer_id',
                    'ratepayers.ratepayer_name',
                    'transactions.tc_id',
                    'payments.payment_date',
                    'payments.payment_mode',
                    'payments.payment_status',
                    'payments.amount',
                    'payments.is_canceled',
                    'transactions.cancellation_date'
                );

            if ($vrNo) {
                $query->where('transactions.vrno', $vrNo);
            }
            if ($ratepayerId) {
                $query->where('transactions.ratepayer_id', $ratepayerId);
            }
            if ($paymentId) {
                $query->where('transactions.payment_id', $paymentId);
            }
            if ($fromDate && $toDate) {
                $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
            }

            $activeTransactions = $query->whereNull('transactions.cancellation_date')->get();

            Log::info('Active Transactions Query:', [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            $cancelledTransactionsQuery = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
                ->select(
                    DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
                    DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
                    DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
                    // 'transactions.id',
                    // 'transactions.ulb_id',
                    // 'transactions.ratepayer_id',
                    'ratepayers.ratepayer_name',
                    'transactions.tc_id',
                    'payments.payment_date',
                    'payments.payment_mode',
                    'payments.payment_status',
                    'payments.amount',
                    'payments.is_canceled',
                    'transactions.cancellation_date'
                );

            if ($vrNo) {
                $cancelledTransactionsQuery->where('transactions.vrno', $vrNo);
            }
            if ($ratepayerId) {
                $cancelledTransactionsQuery->where('transactions.ratepayer_id', $ratepayerId);
            }
            if ($paymentId) {
                $cancelledTransactionsQuery->where('transactions.payment_id', $paymentId);
            }
            if ($fromDate && $toDate) {
                $cancelledTransactionsQuery->whereBetween('transactions.event_time', [$fromDate, $toDate]);
            }
            $cancelledTransactionsQuery->whereNotNull('transactions.cancellation_date');

            $cancelledTransactions = $cancelledTransactionsQuery->get();

            Log::info('Cancelled Transactions Query:', [
                'query' => $cancelledTransactionsQuery->toSql(),
                'bindings' => $cancelledTransactionsQuery->getBindings()
            ]);


            $cancelPayload = null;

            if ($vrNo && $ratepayerId && $paymentId) {

                $cancelPayload = DB::table('transactions')
                    ->join('denial_reasons', 'transactions.denial_reason_id', '=', 'denial_reasons.id')
                    ->where('transactions.vrno', $vrNo)
                    ->where('transactions.ratepayer_id', $ratepayerId)
                    ->where('transactions.payment_id', $paymentId)
                    ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
                    ->first();
            } else {

                $cancelPayload = DB::table('denial_reasons')
                    ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
                    ->get();
            }


            if ($activeTransactions->isEmpty() && $cancelledTransactions->isEmpty() && $cancelPayload->isEmpty()) {
                return format_response('No records found!', null, 404);
            }


            $reactivePayload = [
                'notes' => $request->input('notes', 'This is the json of the transaction and the cance transactions. ')
            ];


            return response()->json([
                'apiid' => $apiid,
                'success' => true,
                'message' => 'Transaction data fetched successfully',
                'data' => [
                    'activeTransactions' => $activeTransactions,
                    'cancelledTransactions' => $cancelledTransactions,
                    'cancelPayload' => $cancelPayload,
                    'reactivePayload' => $reactivePayload
                ],
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ]);
        } catch (\Exception $e) {
            // Log and return error response if exception occurs
            Log::error('Error while fetching transaction data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
                'apiid' => $apiid
            ], 500);
        }
    }
}


// public function getTransactionData(Request $request)
    // {
    //     try {
    //         $apiid = $request->input('apiid', $request->header('apiid', null));
    //         if (!$apiid) {
    //             Log::debug('No apiid passed in the request.');
    //         } else {
    //             Log::debug('apiid received: ' . $apiid);
    //         }

    //         // Validate the incoming request payload
    //         $validatedData = $request->validate([
    //             'vrNo' => 'required|string',
    //             'ratepayerId' => 'required|string',
    //             'paymentId' => 'required|string',
    //             'fromDate' => 'required|date',
    //             'toDate' => 'required|date',
    //         ]);

    //         // Extract values from the payload
    //         $vrNo = $validatedData['vrNo'];
    //         $ratepayerId = $validatedData['ratepayerId'];
    //         $paymentId = $validatedData['paymentId'];
    //         $fromDate = $validatedData['fromDate'];
    //         $toDate = $validatedData['toDate'];

    //         // Get Active Transactions
    //         $activeTransactions = DB::table('transactionstable')
    //             ->join('paymenttable', 'transactionstable.payment_id', '=', 'paymenttable.id')
    //             ->join('ratepayerstable', 'transactionstable.ratepayer_id', '=', 'ratepayerstable.id')
    //             ->select(
    //                 'transactionstable.id',
    //                 'transactionstable.ulb_id',
    //                 'transactionstable.ratepayer_id',
    //                 'ratepayerstable.ratepayer_name',
    //                 'transactionstable.tc_id',
    //                 'paymenttable.payment_date',
    //                 'paymenttable.payment_mode',
    //                 'paymenttable.payment_status',
    //                 'paymenttable.amount',
    //                 'paymenttable.is_canceled',
    //                 'transactionstable.cancellationdate'
    //             )
    //             ->where('transactionstable.vrno', $vrNo)
    //             ->where('transactionstable.ratepayer_id', $ratepayerId)
    //             ->where('transactionstable.payment_id', $paymentId)
    //             ->whereNull('transactionstable.cancellationdate')
    //             ->get();

    //         // Get Cancelled Transactions
    //         $cancelledTransactions = DB::table('transactionstable')
    //             ->join('paymenttable', 'transactionstable.payment_id', '=', 'paymenttable.id')
    //             ->join('ratepayerstable', 'transactionstable.ratepayer_id', '=', 'ratepayerstable.id')
    //             ->select(
    //                 'transactionstable.id',
    //                 'transactionstable.ulb_id',
    //                 'transactionstable.ratepayer_id',
    //                 'ratepayerstable.ratepayer_name',
    //                 'transactionstable.tc_id',
    //                 'paymenttable.payment_date',
    //                 'paymenttable.payment_mode',
    //                 'paymenttable.payment_status',
    //                 'paymenttable.amount',
    //                 'paymenttable.is_canceled',
    //                 'transactionstable.cancellationdate'
    //             )
    //             ->where('transactionstable.vrno', $vrNo)
    //             ->where('transactionstable.ratepayer_id', $ratepayerId)
    //             ->where('transactionstable.payment_id', $paymentId)
    //             ->whereBetween('transactionstable.created_at', [$fromDate, $toDate])
    //             ->whereNotNull('transactionstable.cancellation_date')  // Cancelled transactions have a cancellation date
    //             ->get();

    //         // Get Cancellation Reason (Cancel Payload)
    //         // $cancelPayload = DB::table('transactionstable')
    //         //     ->join('denial_reasons', 'transactionstable.denial_reason_id', '=', 'denial_reasons.id')
    //         //     ->where('transactionstable.vrno', $vrNo)
    //         //     ->where('transactionstable.ratepayer_id', $ratepayerId)
    //         //     ->where('transactionstable.payment_id', $paymentId)
    //         //     ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
    //         //     ->first();

    //         // If no records found for active or cancelled transactions
    //         // if ($activeTransactions->isEmpty() && $cancelledTransactions->isEmpty() && !$cancelPayload) {
    //         //     return format_response('No records found!', null, 404);
    //         // }

    //         // Get the Reactive Payload (Notes from the request)
    //         $reactivePayload = [
    //             'notes' => $request->input('notes', '')
    //         ];

    //         // Return the response with apiid outside of data
    //         return response()->json([
    //             'apiid' => $apiid,
    //             'success' => true,
    //             'message' => 'Transaction data fetched successfully',
    //             'data' => [
    //                 'activeTransactions' => $activeTransactions,
    //                 'cancelledTransactions' => $cancelledTransactions,
    //                 // 'cancelPayload' => $cancelPayload ? [
    //                 //     'reasonType' => $cancelPayload->ulb_id,
    //                 //     'reason' => $cancelPayload->reason
    //                 // ] : null,
    //                 'reactivePayload' => $reactivePayload
    //             ],
    //             'meta' => [
    //                 'epoch' => now()->timestamp,
    //                 'queryTime' => round(microtime(true) - LARAVEL_START, 4),
    //                 'server' => request()->server('SERVER_NAME'),
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error occurred: ' . $e->getMessage(),
    //             'apiid' => $apiid,
    //         ], 500);
    //     }
    // }
