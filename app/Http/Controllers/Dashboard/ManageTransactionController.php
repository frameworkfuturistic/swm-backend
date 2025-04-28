<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\DenialReason;
use App\Models\Payment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ManageTransactionController extends Controller
{

    // API-ID: MDASH-001 [Manager Dashboard]
    public function getTransactionData(Request $request)
    {
        try {
            $request->merge([
               'is_canceled' => filter_var($request->input('is_canceled'), FILTER_VALIDATE_BOOLEAN)
            ]);

            $validated = $request->validate([
               'zoneId' => ['required', 'exists:payment_zones,id'],
               'is_canceled' => ['required', 'boolean'],
           ]);

            $query = Payment::query()
               ->select([
                  'ratepayers.id as ratepayer_id',
                  'payments.id as payment_id',
                  'ratepayers.ratepayer_name',
                  'ratepayers.ratepayer_address',
                  'ratepayers.consumer_no',
                  'payments.tran_id',
                  'payments.payment_date',
                  'payments.payment_mode',
                  'payments.payment_status',
                  'payments.upi_id',
                  'payments.cheque_number',
                  'payments.card_number',
                  'payments.bank_name',
                  'payments.neft_id',
                  'payments.amount',
                  DB::raw("IF(payments.refund_initiated IS NULL, '', IF(payments.refund_initiated = 1, 'YES', '')) as RefundInitiated"),
               ])
               ->join('ratepayers', 'payments.ratepayer_id', '=', 'ratepayers.id')
               ->where('ratepayers.paymentzone_id', $validated['zoneId'])
               ->where('payments.is_canceled', $validated['is_canceled']);

            $transactions = $query->get();

            return response()->json([
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
            ], 500);
        }
    }


    // API-ID: MDASH-002 [Active and Deactive Transaction]
    public function toggleTransactionStatus(Request $request)
    {
        try {
            $request->merge([
               'is_canceled' => filter_var($request->input('is_canceled'), FILTER_VALIDATE_BOOLEAN)
            ]);

            $validator = Validator::make($request->all(), [
               'payment_id' => 'nullable|integer|exists:payments,id',  // Ensure it's numeric and exists in the `payments` table
               'is_canceled' => 'required|boolean',  // Validate `is_canceled` as a boolean
               'cancellation_reason' => 'nullable|string|max:50',  // Ensure it's a string with a max length of 50
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()->all(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $paymentId = $request->input('payment_id');
            $isCancelled = $request->input('is_canceled');


            // Find the payment by ID
            $payment = Payment::findOrFail($paymentId);

            // Update the `is_cancelled` field
            $payment->is_canceled = $isCancelled;
            $payment->save();  // Save the updated record

            $message = $isCancelled
                ? 'Transaction cancelled successfully'
                : 'Transaction reactivated successfully';

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    
                ],
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error while toggling transaction status: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
