<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use App\Models\DenialReason;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class ManageTransactionController extends Controller
{

    // API-ID: MDASH-001 [Manager Dashboard]
    public function getTransactionData(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }

            $isCancelled = $request->input('is_cancelled', null);
            $dateFrom = $request->input('date_from', null);
            $dateTo = $request->input('date_to', null);
            $vrno = $request->input('vrno', null);

            $query = DB::table('current_transactions')
                ->join('payments', 'current_transactions.payment_id', '=', 'payments.id')
                ->join('ratepayers', 'current_transactions.ratepayer_id', '=', 'ratepayers.id')
                ->leftJoin('users', 'current_transactions.cancelledby_id', '=', 'users.id')
                ->select(
                    DB::raw("CONCAT('TRX1000', current_transactions.id) AS id"),
                    DB::raw("CONCAT('ULB-', current_transactions.ulb_id) AS ulb_id"),
                    DB::raw("CONCAT('RATEPAYER100', current_transactions.ratepayer_id) AS ratepayer_id"),
                    'current_transactions.vrno',
                    'current_transactions.payment_id',
                    'current_transactions.denial_reason_id',
                    'ratepayers.ratepayer_name',
                    'current_transactions.tc_id',
                    'payments.payment_date',
                    'payments.payment_mode',
                    'payments.payment_status',
                    'payments.amount',
                    'current_transactions.is_cancelled',
                    'users.name as cancelled_by',
                    'current_transactions.cancellation_date',
                    'current_transactions.remarks as cancellation_reason',
                    'current_transactions.created_at',
                    'current_transactions.updated_at'
                );



            if ($isCancelled === '0') {
                $query->where('current_transactions.is_cancelled', 0)
                    ->whereNull('current_transactions.cancellation_date');
            } elseif ($isCancelled === '1') {
                $query->where('current_transactions.is_cancelled', 1)
                    ->whereNotNull('current_transactions.cancellation_date');
            }


            if ($dateFrom && $dateTo) {
                $query->whereBetween('payments.payment_date', [$dateFrom, $dateTo]);
            }


            if ($vrno) {
                $query->where('current_transactions.vrno', 'like', "%$vrno%");
            }


            $transactions = $query->get();


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




    // API-ID: MDASH-002 [Active and Deactive Transaction]
    public function toggleTransactionStatus(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-002'));

            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }

            $validator = Validator::make($request->all(), [
                'transaction_id' => 'nullable|string',
                'is_cancelled' => 'required|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid data',
                    'errors' => $validator->errors()->all(),
                ], Response::HTTP_BAD_REQUEST);
            }

            $transactionId = $request->input('transaction_id');
            $isCancelled = $request->input('is_cancelled');

            Log::debug('Received toggleTransactionStatus request: ', $request->all());

            if (strpos($transactionId, 'TRX1000') === 0) {
                $transactionId = substr($transactionId, 7);
            }


            if (!is_numeric($transactionId)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid transaction ID format',
                ], Response::HTTP_BAD_REQUEST);
            }

            $transaction = DB::table('transactions')
                ->where('id', $transactionId)
                ->first();

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], Response::HTTP_NOT_FOUND);
            }

            $cancelledById = Auth::id();

            if ($isCancelled == 1 && $transaction->is_cancelled == 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is already cancelled',
                ], Response::HTTP_BAD_REQUEST);
            }

            if ($isCancelled == 0 && $transaction->is_cancelled == 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction is already not cancelled',
                ], Response::HTTP_BAD_REQUEST);
            }

            DB::transaction(function () use ($transactionId, $isCancelled, $cancelledById) {
                if ($isCancelled) {
                    DB::table('transactions')->where('id', $transactionId)->update([
                        'is_cancelled' => 1,
                        'cancelledby_id' => $cancelledById,
                        'cancellation_date' => now(),
                        'paymentStatus' => 'FAILED',
                    ]);
                } else {
                    DB::table('transactions')->where('id', $transactionId)->update([
                        'is_cancelled' => 0,
                        'cancelledby_id' => null,
                        'cancellation_date' => null,
                        'paymentStatus' => null,
                    ]);
                }
            });


            $message = $isCancelled
                ? 'Transaction cancelled successfully'
                : 'Transaction reactivated successfully';

            return response()->json([
                'apiid' => $apiid,
                'success' => true,
                'message' => $message,
                'data' => [
                    'transaction_id' => 'TRX1000' . $transactionId,
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
