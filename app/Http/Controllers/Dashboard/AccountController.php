<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AccountController extends Controller
{
    public function getPaymentSummary(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'ACDASH - 001'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }
            // Fetch payment collection summary
            $paymentCollectionSummary = DB::table('paymenttable')
                ->selectRaw('
                SUM(CASE WHEN payment_mode = "CASH" THEN amount ELSE 0 END) as cash_collected,
                SUM(CASE WHEN payment_mode = "CASH" AND payment_verified IS NOT NULL THEN amount ELSE 0 END) as cash_verified,
                SUM(CASE WHEN payment_mode = "CHEQUE" THEN amount ELSE 0 END) as cheques_collected,
                SUM(CASE WHEN payment_mode = "CHEQUE" AND payment_verified IS NOT NULL THEN amount ELSE 0 END) as cheques_verified,
                SUM(CASE WHEN payment_mode = "CHEQUE" AND payment_status = "RELEASED" THEN amount ELSE 0 END) as cheques_released,
                SUM(CASE WHEN payment_mode = "CHEQUE" AND payment_status = "REFUNDED" THEN amount ELSE 0 END) as cheques_refunded,
                SUM(CASE WHEN payment_mode NOT IN ("CASH", "CHEQUE") THEN amount ELSE 0 END) as other_payments_collected,
                SUM(CASE WHEN payment_mode NOT IN ("CASH", "CHEQUE") AND payment_verified IS NOT NULL THEN amount ELSE 0 END) as other_payments_verified
            ')
                ->first();

            // Fetch cash verification details
            $cashVerification = DB::table('paymenttable')
                ->where('payment_mode', 'CASH')
                ->leftJoin('ratepayerstable', 'paymenttable.ratepayer_id', '=', 'ratepayerstable.id')
                ->leftJoin('users', 'paymenttable.tc_id', '=', 'users.id')
                ->select(
                    'paymenttable.id',
                    'paymenttable.vrno',
                    'paymenttable.ratepayer_id',
                    'ratepayerstable.ratepayer_name',
                    'paymenttable.tc_id',
                    'users.name',
                    'paymenttable.payment_date',
                    'paymenttable.amount',
                    'paymenttable.payment_verified'
                )
                ->get();

            // Fetch cheque verification details
            $chequeVerification = DB::table('paymenttable')
                ->where('payment_mode', 'CHEQUE')
                ->leftJoin('ratepayerstable', 'paymenttable.ratepayer_id', '=', 'ratepayerstable.id')
                ->leftJoin('users', 'paymenttable.tc_id', '=', 'users.id')
                ->select(
                    'paymenttable.id',
                    'paymenttable.vrno',
                    'paymenttable.ratepayer_id',
                    'ratepayerstable.ratepayer_name',
                    'paymenttable.tc_id',
                    'users.name',
                    'paymenttable.payment_date',
                    'paymenttable.amount',
                    'paymenttable.payment_verified',
                    'paymenttable.cheque_number',
                    'paymenttable.bank_name',
                    'paymenttable.ratepayercheque_id'
                )
                ->get();

            // Fetch other payments details
            $otherPayments = DB::table('paymenttable')
                ->whereNotIn('payment_mode', ['CASH', 'CHEQUE'])
                ->leftJoin('ratepayerstable', 'paymenttable.ratepayer_id', '=', 'ratepayerstable.id')
                ->leftJoin('users', 'paymenttable.tc_id', '=', 'users.id')
                ->select(
                    'paymenttable.id',
                    'paymenttable.vrno',
                    'paymenttable.ratepayer_id',
                    'ratepayerstable.ratepayer_name',
                    'paymenttable.tc_id',
                    'users.name',
                    'paymenttable.payment_date',
                    'paymenttable.amount',
                    'paymenttable.payment_verified',
                    'paymenttable.payment_mode',
                    // 'paymenttable.payment_details',
                    'paymenttable.payment_status'
                )
                ->get();

            // Fetch cheque reconciliation details
            $chequeReconciliation = DB::table('paymenttable')
                ->where('payment_mode', 'CHEQUE')
                ->selectRaw('
                SUM(CASE WHEN payment_status = "COMPLETED" THEN 1 ELSE 0 END) as clear,
                SUM(CASE WHEN payment_status = "REFUNDED" THEN 1 ELSE 0 END) as bounce,
                SUM(CASE WHEN payment_status IS NULL THEN 1 ELSE 0 END) as pending
            ')
                ->first();

            // Fetch cheque list
            $chequeList = DB::table('paymenttable')
                ->where('payment_mode', 'CHEQUE')
                ->leftJoin('ratepayerstable', 'paymenttable.ratepayer_id', '=', 'ratepayerstable.id')
                ->select(
                    'paymenttable.id',
                    'paymenttable.cheque_number',
                    // 'paymenttable.cheque_date',
                    'paymenttable.bank_name',
                    'paymenttable.amount',
                    'paymenttable.ratepayer_id',
                    'ratepayerstable.ratepayer_name',
                    // 'paymenttable.payment_id',
                    'paymenttable.vrno',
                    'paymenttable.payment_verified as is_verified',
                    'paymenttable.payment_status as is_returned',
                    // 'paymenttable.realization_date',
                    // 'paymenttable.return_reason'
                )
                ->get();

            // Fetch monthly cheque data
            $monthlyData = DB::table('paymenttable')
                ->where('payment_mode', 'CHEQUE')
                ->selectRaw("
                DATE_FORMAT(created_at, '%b') as month,
                COUNT(*) as collected,
                SUM(CASE WHEN payment_status = 'RELEASED' THEN 1 ELSE 0 END) as realized,
                SUM(CASE WHEN payment_status = 'REFUNDED' THEN 1 ELSE 0 END) as returned
            ")
                ->groupBy('month')
                ->get();

            // Fetch tax collector data
            $tcData = DB::table('paymenttable')
                ->where('payment_mode', 'CHEQUE')
                ->selectRaw("
                tc_id,
                COUNT(*) as collected,
                SUM(CASE WHEN payment_status = 'RELEASED' THEN 1 ELSE 0 END) as realized,
                SUM(CASE WHEN payment_status = 'REFUNDED' THEN 1 ELSE 0 END) as returned
            ")
                ->groupBy('tc_id')
                ->get();

            // Prepare the response
            return response()->json([
                'apiid' => $apiid,
                'success' => true,
                'message' => 'Transaction data fetched successfully',
                'data' => [
                    'paymentCollectionSummary' => [
                        [
                            'cash_collected' => $paymentCollectionSummary->cash_collected,
                            'cash_verified' => $paymentCollectionSummary->cash_verified,
                            'cheques_collected' => $paymentCollectionSummary->cheques_collected,
                            'cheques_verified' => $paymentCollectionSummary->cheques_verified,
                            'cheques_released' => $paymentCollectionSummary->cheques_released,
                            'cheques_refunded' => $paymentCollectionSummary->cheques_refunded,
                            'other_payments_collected' => $paymentCollectionSummary->other_payments_collected,
                            'other_payments_verified' => $paymentCollectionSummary->other_payments_verified,
                        ]
                    ],
                    'cashVerification' => $cashVerification->toArray(),
                    'chequeVerification' => $chequeVerification->toArray(),
                    'otherPayments' => $otherPayments->toArray(),
                    'chequeReconciliation' => [
                        'details' => [
                            'clear' => $chequeReconciliation->clear,
                            'bounce' => $chequeReconciliation->bounce,
                            'pending' => $chequeReconciliation->pending,
                        ],
                        'chequeList' => $chequeList->toArray(),
                        'monthlyData' => $monthlyData->toArray(),
                        'tcData' => $tcData->toArray(),
                    ]
                ],
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ]);
            return response()->json($response);
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
