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
            $apiid = $request->input('apiid', $request->header('apiid', null));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }

            // Validate the incoming request payload
            $validatedData = $request->validate([
                'vrNo' => 'required|string',
                'ratepayerId' => 'required|integer',
                'paymentId' => 'required|integer',
                'fromDate' => 'required|date',
                'toDate' => 'required|date',
            ]);

            // Extract values from the payload
            $vrNo = $validatedData['vrNo'];
            $ratepayerId = $validatedData['ratepayerId'];
            $paymentId = $validatedData['paymentId'];
            $fromDate = $validatedData['fromDate'];
            $toDate = $validatedData['toDate'];

            // Get Active Transactions
            $activeTransactions = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
                ->select(
                    'transactions.id',
                    'transactions.ulb_id',
                    'transactions.ratepayer_id',
                    'ratepayers.ratepayer_name',
                    'transactions.tc_id',
                    'payments.payment_date',
                    'payments.payment_mode',
                    'payments.payment_status',
                    'payments.amount',
                    'payments.is_canceled',
                    'transactions.vrno',
                    'transactions.cancellation_date',
                    'transactions.cancelledby_id',
                    'transactions.created_at',
                    'transactions.updated_at'
                )
                ->where('transactions.vrno', $vrNo)
                ->where('transactions.ratepayer_id', $ratepayerId)
                ->where('transactions.payment_id', $paymentId)
                ->whereNull('transactions.cancellation_date')
                ->get();

            // Get Cancelled Transactions
            $cancelledTransactions = DB::table('transactions')
                ->join('payments', 'transactions.payment_id', '=', 'payments.id')
                ->join('ratepayers', 'transactions.ratepayer_id', '=', 'ratepayers.id')
                ->select(
                    'transactions.id',
                    'transactions.ulb_id',
                    'transactions.ratepayer_id',
                    'ratepayers.ratepayer_name',
                    'transactions.tc_id',
                    'payments.payment_date',
                    'payments.payment_mode',
                    'payments.payment_status',
                    'payments.amount',
                    'payments.is_canceled',
                    'transactions.vrno',
                    'transactions.cancellation_date',
                    'transactions.cancelledby_id',
                    'transactions.created_at',
                    'transactions.updated_at'
                )
                ->where('transactions.vrno', $vrNo)
                ->where('transactions.ratepayer_id', $ratepayerId)
                ->where('transactions.payment_id', $paymentId)
                ->whereBetween('transactions.created_at', [$fromDate, $toDate])
                ->whereNotNull('transactions.cancellation_date')  // Cancelled transactions have a cancellation date
                ->get();

            // Get Cancellation Reason (Cancel Payload)
            $cancelPayload = DB::table('transactions')
                ->join('denial_reasons', 'transactions.denial_reason_id', '=', 'denial_reasons.id')
                ->where('transactions.vrno', $vrNo)
                ->where('transactions.ratepayer_id', $ratepayerId)
                ->where('transactions.payment_id', $paymentId)
                ->select('denial_reasons.ulb_id', 'denial_reasons.reason')
                ->first();

            // If no records found for active or cancelled transactions
            if ($activeTransactions->isEmpty() && $cancelledTransactions->isEmpty() && !$cancelPayload) {
                return format_response('No records found!', null, 404);
            }

            // Get the Reactive Payload (Notes from the request)
            $reactivePayload = [
                'notes' => $request->input('notes', '')
            ];

            // Return the response with apiid outside of data
            return response()->json([
                'apiid' => $apiid,
                'success' => true,
                'message' => 'Transaction data fetched successfully',
                'data' => [
                    'activeTransactions' => $activeTransactions,
                    'cancelledTransactions' => $cancelledTransactions,
                    'cancelPayload' => $cancelPayload ? [
                        'reasonType' => $cancelPayload->ulb_id,
                        'reason' => $cancelPayload->reason
                    ] : null,
                    'reactivePayload' => $reactivePayload
                ],
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME'),
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
                'apiid' => $apiid,
            ], 500);
        }
    }
}
