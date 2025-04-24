<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class AccountController extends Controller
{


    // API-ID: ACDASH-001 [Account Dashboard]
    public function getPaymentSummary(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'ACDASH - 001'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }
            $paymentCollectionSummary = DB::table('payments')
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


            return response()->json([
                'apiid' => $apiid,
                'success' => true,
                'message' => 'Transaction data fetched successfully',
                'data' => [

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
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ]);
            return response()->json($response);
        } catch (\Exception $e) {
            Log::error('Error while fetching transaction data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
                'apiid' => $apiid
            ], 500);
        }
    }




    // API-ID: ACDASH-002 [Account Dashboard]
    public function getAccountSummary(Request $request)
    {
        try {
            $apiid = $request->input('apiid', $request->header('apiid', 'ACDASH - 001'));
            if (!$apiid) {
                Log::debug('No apiid passed in the request.');
            } else {
                Log::debug('apiid received: ' . $apiid);
            }


            $filter = $request->query('status', null);
            $vrno = $request->query('searchKey', null);
            $chequeNo = $request->query('searchKey', null);
            $paymentMode = $request->query('paymentMode', null);
            $tc_id = $request->query('tc', null);
            $ratepayer_name = $request->query('searchKey', null);
            $date_from = $request->query('fromDate', null);
            $date_to = $request->query('toDate', null);
            $payment_status = $request->query('paymentStatus', null);


            $cashVerification = [];
            $chequeVerification = [];
            $otherPayments = [];
            $chequeReconciliation = [];


            // Fetch cashVerification details if filter is 1 or null
            if ($filter === '1' || $filter === null) {
                $cashVerificationQuery = DB::table('payments')
                    ->where('payment_mode', 'CASH')
                    ->leftJoin('ratepayers', 'payments.ratepayer_id', '=', 'ratepayers.id')
                    ->leftJoin('users', 'payments.tc_id', '=', 'users.id')
                    ->select(
                        DB::raw("CONCAT('PMT-2000', payments.id) AS id"),
                        DB::raw("CONCAT('Ratepayer 100', ratepayer_id) AS ratepayer_id"),
                        'payments.vrno',
                        'ratepayers.ratepayer_name',
                        'payments.tc_id',
                        'users.name as tc_name',
                        'payments.payment_date',
                        'payments.amount',
                        'payments.payment_verified'
                    );

                if ($vrno || $ratepayer_name) {
                    $cashVerificationQuery->where(function ($query) use ($vrno, $ratepayer_name) {
                        if ($vrno) {
                            $query->orWhere('payments.vrno', 'like', '%' . $vrno . '%');
                        }

                        if ($ratepayer_name) {
                            $query->orWhere('ratepayers.ratepayer_name', 'like', '%' . $ratepayer_name . '%');
                        }
                    });
                }
                if ($tc_id) {
                    $cashVerificationQuery->Where('payments.tc_id', '=', $tc_id);
                }
                if ($date_from) {
                    $cashVerificationQuery->whereDate('payments.payment_date', '>=', $date_from);
                }
                if ($date_to) {
                    $cashVerificationQuery->whereDate('payments.payment_date', '<=', $date_to);
                }

                if ($payment_status === '0') {
                    $cashVerificationQuery->where('payments.payment_verified', '=', 0); //Unverfied
                    Log::debug('Applying payment_verified filter: 0');
                } elseif ($payment_status === '1') {
                    $cashVerificationQuery->where('payments.payment_verified', '=', 1); //Verfied
                    Log::debug('Applying payment_verified filter: 1');
                }
                Log::debug($cashVerificationQuery->toSql());
                $cashVerification = $cashVerificationQuery->get();
            }




            // Fetch chequeVerification details if filter is 2 or null
            if ($filter === '2' || $filter === null) {
                $chequeVerification = DB::table('payments')
                    ->where('payment_mode', 'CHEQUE')
                    ->leftJoin('ratepayers', 'payments.ratepayer_id', '=', 'ratepayers.id')
                    ->leftJoin('users', 'payments.tc_id', '=', 'users.id')
                    ->select(
                        DB::raw("CONCAT('PMT-2000', payments.id) AS id"),
                        DB::raw("CONCAT('Ratepayer 100', ratepayer_id) AS ratepayer_id"),
                        'payments.vrno',
                        'ratepayers.ratepayer_name',
                        'payments.tc_id',
                        'users.name as tc_name',
                        'payments.payment_date',
                        'payments.amount',
                        'payments.payment_verified',
                        'payments.cheque_number',
                        'payments.bank_name',
                        'payments.ratepayercheque_id'
                    );


                if ($vrno || $ratepayer_name || $chequeNo) {
                    $chequeVerification->where(function ($query) use ($vrno, $ratepayer_name, $chequeNo) {
                        if ($vrno) {
                            $query->orWhere('payments.vrno', 'like', '%' . $vrno . '%');
                        }

                        if ($ratepayer_name) {
                            $query->orWhere('ratepayers.ratepayer_name', 'like', '%' . $ratepayer_name . '%');
                        }
                        if ($chequeNo) {
                            $query->orWhere('payments.cheque_number', 'like', '%' . $chequeNo . '%');
                        }
                    });
                }
                if ($tc_id) {
                    $chequeVerification->Where('payments.tc_id', '=', $tc_id);
                }
                if ($date_from) {
                    $chequeVerification->whereDate('payments.payment_date', '>=', $date_from);
                }
                if ($date_to) {
                    $chequeVerification->whereDate('payments.payment_date', '<=', $date_to);
                }

                if ($payment_status === '0') {
                    $chequeVerification->where('payments.payment_verified', '=', 0); //Unverifed
                    Log::debug('Applying payment_verified filter: 0');
                } elseif ($payment_status === '1') {
                    $chequeVerification->where('payments.payment_verified', '=', 1); //Verifed
                    Log::debug('Applying payment_verified filter: 1');
                }
                Log::debug($chequeVerification->toSql());
                $chequeVerification = $chequeVerification->get();
            }




            // Fetch chequereconciliation details if filter is 3 or null
            if ($filter === '3' || $filter === null) {
                $chequeReconciliation = DB::table('payments')
                    ->join('ratepayer_cheques', 'payments.ratepayercheque_id', '=', 'ratepayer_cheques.id')
                    ->leftJoin('ratepayers', 'ratepayer_cheques.ratepayer_id', '=', 'ratepayers.id')
                    ->leftJoin('users', 'payments.tc_id', '=', 'users.id')
                    ->select(
                        'ratepayer_cheques.id as id',
                        DB::raw("CONCAT('PMT-2000', payments.id) AS payment_id"),
                        DB::raw("CONCAT('Ratepayer 100', ratepayer_cheques.ratepayer_id) AS ratepayer_id"),
                        'ratepayer_cheques.cheque_no',
                        'ratepayer_cheques.cheque_date',
                        'ratepayer_cheques.bank_name',
                        'ratepayer_cheques.amount',
                        'ratepayers.ratepayer_name',
                        'payments.vrno',
                        'ratepayer_cheques.is_verified',
                        'payments.tc_id',
                        'users.name as tc_name',
                        'ratepayer_cheques.is_returned',
                        'payments.payment_status',
                        'ratepayer_cheques.realization_date',

                    );

                if ($vrno || $ratepayer_name || $chequeNo) {
                    $chequeReconciliation->where(function ($query) use ($vrno, $ratepayer_name, $chequeNo) {
                        if ($vrno) {
                            $query->orWhere('payments.vrno', 'like', '%' . $vrno . '%');
                        }

                        if ($ratepayer_name) {
                            $query->orWhere('ratepayers.ratepayer_name', 'like', '%' . $ratepayer_name . '%');
                        }
                        if ($chequeNo) {
                            $query->orWhere('ratepayer_cheques.cheque_no', 'like', '%' . $chequeNo . '%');
                        }
                    });
                }
                if ($date_from) {
                    $chequeReconciliation->whereDate('ratepayer_cheques.cheque_date', '>=', $date_from);
                }
                if ($date_to) {
                    $chequeReconciliation->whereDate('ratepayer_cheques.cheque_date', '<=', $date_to);
                }


                if ($payment_status === '0') {
                    $chequeReconciliation->where('ratepayer_cheques.paymentStatus', '=', 'PENDING');
                    Log::debug('Applying paymentStatus filter: PENDING');
                } elseif ($payment_status === '1') {
                    $chequeReconciliation->where('ratepayer_cheques.paymentStatus', '=', 'REALIZED');
                    Log::debug('Applying paymentStatus filter: REALIZED');
                } elseif ($payment_status === '2') {
                    $chequeReconciliation->where('ratepayer_cheques.paymentStatus', '=', 'RETURNED');
                    Log::debug('Applying paymentStatus filter: RETURNED');
                }


                Log::debug($chequeReconciliation->toSql());
                $chequeReconciliation = $chequeReconciliation->get();
            }




            // Fetch otherPayments details if filter is 4 or null
            if ($filter === '4' || $filter === null) {
                $otherPayments = DB::table('payments')
                    ->whereNotIn('payment_mode', ['CASH', 'CHEQUE'])
                    ->leftJoin('ratepayers', 'payments.ratepayer_id', '=', 'ratepayers.id')
                    ->leftJoin('current_transactions', 'payments.tran_id', '=', 'current_transactions.id')
                    ->leftJoin('users', 'payments.tc_id', '=', 'users.id')
                    ->select(
                        DB::raw("CONCAT('PMT-2000', payments.id) AS id"),
                        DB::raw("CONCAT('Ratepayer 100', payments.ratepayer_id) AS ratepayer_id"),
                        'payments.vrno',
                        'ratepayers.ratepayer_name',
                        'payments.tc_id',
                        'users.name as tc_name',
                        'payments.payment_date',
                        'payments.amount',
                        'payments.payment_verified',
                        'payments.payment_mode',
                        'current_transactions.remarks as payment_detail',
                        'payments.payment_status'
                    );

                if ($vrno || $ratepayer_name) {
                    $otherPayments->where(function ($query) use ($vrno, $ratepayer_name) {
                        if ($vrno) {
                            $query->orWhere('payments.vrno', 'like', '%' . $vrno . '%');
                        }

                        if ($ratepayer_name) {
                            $query->orWhere('ratepayers.ratepayer_name', 'like', '%' . $ratepayer_name . '%');
                        }
                    });
                }
                if ($paymentMode) {
                    $otherPayments->where('payments.payment_mode', 'like', '%' . $paymentMode . '%');
                }

                if ($date_from) {
                    $otherPayments->whereDate('payments.payment_date', '>=', $date_from);
                }
                if ($date_to) {
                    $otherPayments->whereDate('payments.payment_date', '<=', $date_to);
                }

                if ($payment_status === '0') {
                    $otherPayments->where('payments.payment_verified', '=', 0); //Unverfied
                    Log::debug('Applying payment_verified filter: 0');
                } elseif ($payment_status === '1') {
                    $otherPayments->where('payments.payment_verified', '=', 1); //Verfied
                    Log::debug('Applying payment_verified filter: 1');
                }
                Log::debug($otherPayments->toSql());
                $otherPayments = $otherPayments->get();
            }

            $responseData = [
                'apiid' => $apiid,
                'success' => true,
                'message' => 'Transaction data fetched successfully',
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ];

            if ($filter === null) {
                $responseData['data'] = [
                    'cashVerification' => $cashVerification->toArray(),
                    'chequeVerification' => $chequeVerification->toArray(),
                    'otherPayments' => $otherPayments->toArray(),
                    'chequeReconciliation' => [
                        'chequeList' => $chequeReconciliation->toArray(),
                    ]
                ];
            } else {
                if ($filter === '1') {
                    $responseData['data'] = $cashVerification->toArray();
                } elseif ($filter === '2') {
                    $responseData['data'] = $chequeVerification->toArray();
                } elseif ($filter === '4') {
                    $responseData['data'] = $otherPayments->toArray();
                } elseif ($filter === '3') {
                    $responseData['data'] = $chequeReconciliation->toArray();
                }
            }

            return response()->json($responseData);
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
