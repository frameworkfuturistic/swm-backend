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

            $query = DB::table('transactionstable')
                ->join('paymenttable', 'transactionstable.payment_id', '=', 'paymenttable.id')
                ->join('ratepayerstable', 'transactionstable.ratepayer_id', '=', 'ratepayerstable.id')
                ->select(
                    'transactionstable.id',
                    'transactionstable.ulb_id',
                    'transactionstable.ratepayer_id',
                    'ratepayerstable.ratepayer_name',
                    'transactionstable.tc_id',
                    'paymenttable.payment_date',
                    'paymenttable.payment_mode',
                    'paymenttable.payment_status',
                    'paymenttable.amount',
                    'paymenttable.is_canceled',
                    'transactionstable.cancellationdate'
                );

            if ($vrNo) {
                $query->where('transactionstable.vrno', $vrNo);
            }
            if ($ratepayerId) {
                $query->where('transactionstable.ratepayer_id', $ratepayerId);
            }
            if ($paymentId) {
                $query->where('transactionstable.payment_id', $paymentId);
            }
            if ($fromDate && $toDate) {
                $query->whereBetween('transactionstable.event_time', [$fromDate, $toDate]);
            }

            $activeTransactions = $query->whereNull('transactionstable.cancellationdate')->get();

            Log::info('Active Transactions Query:', [
                'query' => $query->toSql(),
                'bindings' => $query->getBindings()
            ]);

            $cancelledTransactionsQuery = DB::table('transactionstable')
                ->join('paymenttable', 'transactionstable.payment_id', '=', 'paymenttable.id')
                ->join('ratepayerstable', 'transactionstable.ratepayer_id', '=', 'ratepayerstable.id')
                ->select(
                    'transactionstable.id',
                    'transactionstable.ulb_id',
                    'transactionstable.ratepayer_id',
                    'ratepayerstable.ratepayer_name',
                    'transactionstable.tc_id',
                    'paymenttable.payment_date',
                    'paymenttable.payment_mode',
                    'paymenttable.payment_status',
                    'paymenttable.amount',
                    'paymenttable.is_canceled',
                    'transactionstable.cancellationdate'
                );

            if ($vrNo) {
                $cancelledTransactionsQuery->where('transactionstable.vrno', $vrNo);
            }
            if ($ratepayerId) {
                $cancelledTransactionsQuery->where('transactionstable.ratepayer_id', $ratepayerId);
            }
            if ($paymentId) {
                $cancelledTransactionsQuery->where('transactionstable.payment_id', $paymentId);
            }
            if ($fromDate && $toDate) {
                $cancelledTransactionsQuery->whereBetween('transactionstable.event_time', [$fromDate, $toDate]);
            }
            $cancelledTransactionsQuery->whereNotNull('transactionstable.cancellationdate');

            $cancelledTransactions = $cancelledTransactionsQuery->get();

            Log::info('Cancelled Transactions Query:', [
                'query' => $cancelledTransactionsQuery->toSql(),
                'bindings' => $cancelledTransactionsQuery->getBindings()
            ]);


            $cancelPayload = null;

            if ($vrNo && $ratepayerId && $paymentId) {

                $cancelPayload = DB::table('transactionstable')
                    ->join('denial_reasons', 'transactionstable.denial_resons_id', '=', 'denial_reasons.id')
                    ->where('transactionstable.vrno', $vrNo)
                    ->where('transactionstable.ratepayer_id', $ratepayerId)
                    ->where('transactionstable.payment_id', $paymentId)
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
