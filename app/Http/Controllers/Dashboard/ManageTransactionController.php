<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class ManageTransactionController extends Controller
{

    // public function getTransactionData(Request $request)
    // {
    //     try {
    //         $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));
    //         if (!$apiid) {
    //             Log::debug('No apiid passed in the request.');
    //         } else {
    //             Log::debug('apiid received: ' . $apiid);
    //         }
    //         // Fetch the 'is_cancelled' parameter (can be 1 or 0)
    //         $isCancelled = $request->input('is_cancelled', null);
    //         $vrNo = $request->input('vrNo', null);
    //         $ratepayerId = $request->input('ratepayerId', null);
    //         $paymentId = $request->input('paymentId', null);
    //         $fromDate = $request->input('fromDate', null);
    //         $toDate = $request->input('toDate', null);

    //         $query = DB::table('transactions')
    //             ->join('payments', 'transactions.payment_id', '=', 'payments.id')
    //             ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
    //             ->select(
    //                 DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
    //                 DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
    //                 DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
    //                 'transactions.vrno',
    //                 'transactions.payment_id',
    //                 'ratepayers.ratepayer_name',
    //                 'transactions.tc_id',
    //                 'payments.payment_date',
    //                 'payments.payment_mode',
    //                 'payments.payment_status',
    //                 'payments.amount',
    //                 'transactions.is_cancelled',
    //                 'transactions.cancellation_date',
    //                 'transactions.remarks as cancellation_reason',
    //                 'transactions.created_at',
    //                 'transactions.updated_at'
    //             );

    //         if ($vrNo) {
    //             $query->where('transactions.vrno', $vrNo);
    //         }
    //         if ($ratepayerId) {
    //             $query->where('transactions.ratepayer_id', $ratepayerId);
    //         }
    //         if ($paymentId) {
    //             $query->where('transactions.payment_id', $paymentId);
    //         }
    //         if ($fromDate && $toDate) {
    //             $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
    //         }

    //         $activeTransactions = collect();
    //         $cancelledTransactions = collect();


    //         $cancelledTransactionsQuery = DB::table('transactions')
    //             ->join('payments', 'transactions.payment_id', '=', 'payments.id')
    //             ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
    //             ->select(
    //                 DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
    //                 DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
    //                 DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
    //                 'transactions.vrno',
    //                 'transactions.payment_id',
    //                 'ratepayers.ratepayer_name',
    //                 'transactions.tc_id',
    //                 'payments.payment_date',
    //                 'payments.payment_mode',
    //                 'payments.payment_status',
    //                 'payments.amount',
    //                 'transactions.is_cancelled',
    //                 'transactions.cancellation_date',
    //                 'transactions.remarks as cancellation_reason',
    //                 'transactions.created_at',
    //                 'transactions.updated_at'
    //             );


    //         if ($vrNo) {
    //             $cancelledTransactionsQuery->where('transactions.vrno', $vrNo);
    //         }
    //         if ($ratepayerId) {
    //             $cancelledTransactionsQuery->where('transactions.ratepayer_id', $ratepayerId);
    //         }
    //         if ($paymentId) {
    //             $cancelledTransactionsQuery->where('transactions.payment_id', $paymentId);
    //         }
    //         if ($fromDate && $toDate) {
    //             $cancelledTransactionsQuery->whereBetween('transactions.event_time', [$fromDate, $toDate]);
    //         }


    //         if ($isCancelled === '0') {
    //             $activeTransactions = $query->where('transactions.is_cancelled', 0)
    //                 ->whereNull('transactions.cancellation_date')
    //                 ->get();

    //             $cancelledTransactions = collect();
    //         } elseif ($isCancelled === '1') {
    //             $cancelledTransactions = $cancelledTransactionsQuery->where('transactions.is_cancelled', 1)
    //                 ->whereNotNull('transactions.cancellation_date')
    //                 ->get();


    //             $activeTransactions = collect();
    //         } else {

    //             $activeTransactions = $query->where('transactions.is_cancelled', 0)
    //                 ->whereNull('transactions.cancellation_date')
    //                 ->get();

    //             $cancelledTransactions = $cancelledTransactionsQuery->where('transactions.is_cancelled', 1)
    //                 ->whereNotNull('transactions.cancellation_date')
    //                 ->get();
    //         }


    //         $cancelPayload = DB::table('denial_reasons')
    //             ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
    //             ->get();


    //         if ($activeTransactions->isEmpty() && $cancelledTransactions->isEmpty() && $cancelPayload->isEmpty()) {
    //             return format_response('No records found!', null, 404);
    //         }


    //         $reactivePayload = [
    //             'notes' => $request->input('notes', 'This is the json of the transaction and the cancelled transactions. ')
    //         ];

    //         return response()->json([
    //             'apiid' => $apiid,
    //             'success' => true,
    //             'message' => 'Transaction data fetched successfully',
    //             'data' => [
    //                 'activeTransactions' => $activeTransactions,
    //                 'cancelledTransactions' => $cancelledTransactions,
    //                 'cancelPayload' => $cancelPayload,
    //                 'reactivePayload' => $reactivePayload
    //             ],
    //             'meta' => [
    //                 'epoch' => now()->timestamp,
    //                 'queryTime' => round(microtime(true) - LARAVEL_START, 4),
    //                 'server' => request()->server('SERVER_NAME')
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         Log::error('Error while fetching transaction data: ' . $e->getMessage());
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error occurred: ' . $e->getMessage(),
    //             'apiid' => $apiid
    //         ], 500);
    //     }
    // }



    public function getTransactionData(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));

            // Log the received API ID for debugging
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }

            // Fetch the 'is_cancelled' parameter
            $isCancelled = $request->input('is_cancelled', null);

            // Prepare the base query for transactions
            $query = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
                ->select(
                    DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
                    DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
                    DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
                    'transactions.vrno',
                    'transactions.payment_id',
                    'ratepayers.ratepayer_name',
                    'transactions.tc_id',
                    'payments.payment_date',
                    'payments.payment_mode',
                    'payments.payment_status',
                    'payments.amount',
                    'transactions.is_cancelled',
                    'transactions.cancellation_date',
                    'transactions.remarks as cancellation_reason',
                    'transactions.created_at',
                    'transactions.updated_at'
                );

            // Apply filters based on request parameters
            if ($isCancelled === '0') {
                // Only active transactions
                $transactions = $query->where('transactions.is_cancelled', 0)
                    ->whereNull('transactions.cancellation_date')
                    ->get();
            } elseif ($isCancelled === '1') {
                // Only cancelled transactions
                $transactions = $query->where('transactions.is_cancelled', 1)
                    ->whereNotNull('transactions.cancellation_date')
                    ->get();
            } else {
                // Both active and cancelled transactions
                $transactions = $query->get();
            }

            // Format the response
            return response()->json([
                'apiid' => $apiid,
                'success' => true,
                'message' => 'Transaction data fetched successfully',
                'data' => $transactions,
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Error while fetching transaction data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
                'apiid' => $apiid
            ], 500);
        }
    }
}


//  public function getTransactionData(Request $request)
//     {
//         try {
//             $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));
//             if (!$apiid) {
//                 Log::debug('No apiid passed in the request.');
//             } else {
//                 Log::debug('apiid received: ' . $apiid);
//             }
//             $vrNo = $request->input('vrNo', null);
//             $ratepayerId = $request->input('ratepayerId', null);
//             $paymentId = $request->input('paymentId', null);
//             $fromDate = $request->input('fromDate', null);
//             $toDate = $request->input('toDate', null);

//             $query = DB::table('transactions')
//                 ->join('payments', 'transactions.payment_id', '=', 'payments.id')
//                 ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
//                 ->select(
//                     DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
//                     DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
//                     DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
//                     // 'transactions.id',
//                     // 'transactions.ulb_id',
//                     // 'transactions.ratepayer_id',
//                     'transactions.vrno',
//                     'transactions.payment_id',
//                     'ratepayers.ratepayer_name',
//                     'transactions.tc_id',
//                     'payments.payment_date',
//                     'payments.payment_mode',
//                     'payments.payment_status',
//                     'payments.amount',
//                     'transactions.is_cancelled',
//                     'transactions.cancellation_date',
//                     'transactions.remarks as cancellation_reason',
//                     'transactions.created_at',
//                     'transactions.updated_at'
//                 );

//             if ($vrNo) {
//                 $query->where('transactions.vrno', $vrNo);
//             }
//             if ($ratepayerId) {
//                 $query->where('transactions.ratepayer_id', $ratepayerId);
//             }
//             if ($paymentId) {
//                 $query->where('transactions.payment_id', $paymentId);
//             }
//             if ($fromDate && $toDate) {
//                 $query->whereBetween('transactions.event_time', [$fromDate, $toDate]);
//             }

//             // $activeTransactions = $query->whereNull('transactions.cancellation_date')->get();
//             $activeTransactions = $query->where('transactions.is_cancelled', 0)
//                 ->whereNull('transactions.cancellation_date')
//                 ->get();



//             Log::info('Active Transactions Query:', [
//                 'query' => $query->toSql(),
//                 'bindings' => $query->getBindings()
//             ]);

//             $cancelledTransactionsQuery = DB::table('transactions')
//                 ->join('payments', 'transactions.payment_id', '=', 'payments.id')
//                 ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
//                 ->select(
//                     DB::raw("CONCAT('TRX1000', transactions.id) AS id"),
//                     DB::raw("CONCAT('ULB-', transactions.ulb_id) AS ulb_id"),
//                     DB::raw("CONCAT('RATEPAYER100', transactions.ratepayer_id) AS ratepayer_id"),
//                     // 'transactions.id',
//                     // 'transactions.ulb_id',
//                     // 'transactions.ratepayer_id',
//                     'transactions.vrno',
//                     'transactions.payment_id',
//                     'ratepayers.ratepayer_name',
//                     'transactions.tc_id',
//                     'payments.payment_date',
//                     'payments.payment_mode',
//                     'payments.payment_status',
//                     'payments.amount',
//                     'transactions.is_cancelled',
//                     'transactions.is_cancelled',
//                     'transactions.cancellation_date',
//                     'transactions.remarks as cancellation_reason',
//                     'transactions.created_at',
//                     'transactions.updated_at'
//                 );

//             if ($vrNo) {
//                 $cancelledTransactionsQuery->where('transactions.vrno', $vrNo);
//             }
//             if ($ratepayerId) {
//                 $cancelledTransactionsQuery->where('transactions.ratepayer_id', $ratepayerId);
//             }
//             if ($paymentId) {
//                 $cancelledTransactionsQuery->where('transactions.payment_id', $paymentId);
//             }
//             if ($fromDate && $toDate) {
//                 $cancelledTransactionsQuery->whereBetween('transactions.event_time', [$fromDate, $toDate]);
//             }
//             // $cancelledTransactionsQuery->whereNotNull('transactions.cancellation_date');
//             $cancelledTransactionsQuery = $cancelledTransactionsQuery->where('transactions.is_cancelled', 1)
//                 ->whereNotNull('transactions.cancellation_date');



//             $cancelledTransactions = $cancelledTransactionsQuery->get();

//             Log::info('Cancelled Transactions Query:', [
//                 'query' => $cancelledTransactionsQuery->toSql(),
//                 'bindings' => $cancelledTransactionsQuery->getBindings()
//             ]);


//             $cancelPayload = null;

//             if ($vrNo && $ratepayerId && $paymentId) {

//                 $cancelPayload = DB::table('transactions')
//                     ->join('denial_reasons', 'transactions.denial_reason_id', '=', 'denial_reasons.id')
//                     ->where('transactions.vrno', $vrNo)
//                     ->where('transactions.ratepayer_id', $ratepayerId)
//                     ->where('transactions.payment_id', $paymentId)
//                     ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
//                     ->first();
//             } else {

//                 $cancelPayload = DB::table('denial_reasons')
//                     ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
//                     ->get();
//             }


//             if ($activeTransactions->isEmpty() && $cancelledTransactions->isEmpty() && $cancelPayload->isEmpty()) {
//                 return format_response('No records found!', null, 404);
//             }


//             $reactivePayload = [
//                 'notes' => $request->input('notes', 'This is the json of the transaction and the cance transactions. ')
//             ];


//             return response()->json([
//                 'apiid' => $apiid,
//                 'success' => true,
//                 'message' => 'Transaction data fetched successfully',
//                 'data' => [
//                     'activeTransactions' => $activeTransactions,
//                     'cancelledTransactions' => $cancelledTransactions,
//                     'cancelPayload' => $cancelPayload,
//                     'reactivePayload' => $reactivePayload
//                 ],
//                 'meta' => [
//                     'epoch' => now()->timestamp,
//                     'queryTime' => round(microtime(true) - LARAVEL_START, 4),
//                     'server' => request()->server('SERVER_NAME')
//                 ]
//             ]);
//         } catch (\Exception $e) {
//             // Log and return error response if exception occurs
//             Log::error('Error while fetching transaction data: ' . $e->getMessage());
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Error occurred: ' . $e->getMessage(),
//                 'apiid' => $apiid
//             ], 500);
//         }
//     }
