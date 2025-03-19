<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Ratepayer;

class RateTransactionController extends Controller
{
    // public function getBillAmount(Request $request)
    // {
    //     try {
    //         $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));

    //         $consumerNumber = $request->input('consumer_no', null);
    //         $phoneNumber = $request->input('mobile_no', null);

    //         if (!$consumerNumber && !$phoneNumber) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'consumer_no or mobile_no is required.'
    //             ], 400);
    //         }

    //         $ratepayer = DB::table('ratepayers')
    //             ->select('id', 'consumer_no', 'mobile_no', 'lastpayment_amt', 'lastpayment_date', 'current_demand', 'status')
    //             ->where(function ($query) use ($consumerNumber, $phoneNumber) {
    //                 if ($consumerNumber) {
    //                     $query->where('consumer_no', $consumerNumber);
    //                 }
    //                 if ($phoneNumber) {
    //                     $query->orWhere('mobile_no', $phoneNumber);
    //                 }
    //             })
    //             ->first();

    //         if (!$ratepayer) {
    //             return response()->json([
    //                 'success' => false,
    //                 'message' => 'Consumer not found.'
    //             ], 404);
    //         }

    //         $lastPaymentAmount = $ratepayer->lastpayment_amt ?? 0;
    //         $lastPaymentDate = $ratepayer->lastpayment_date ?? 'N/A';
    //         $currentDemand = $ratepayer->current_demand ?? 0;
    //         $consumerStatus = $ratepayer->status ?? 'UNKNOWN';  // Fetch status from the ratepayer

    //         $pendingAmount = $currentDemand - $lastPaymentAmount;

    //         if ($pendingAmount > 0) {
    //             // Consumer has pending demand
    //             $status = 'PENDING';
    //             $pendingDetails = [
    //                 'transaction_id' => 'Pending',
    //                 'amount' => $pendingAmount,
    //                 'payment_status' => $status
    //             ];
    //         } else {
    //             // No pending, latest payment is the last transaction
    //             $status = 'COMPLETED';
    //             $pendingDetails = [
    //                 'transaction_id' => 'Last Payment',
    //                 'amount' => $lastPaymentAmount,
    //                 'payment_status' => $status
    //             ];
    //         }

    //         return response()->json([
    //             'apiid' => $apiid,
    //             'success' => true,
    //             'message' => 'Bill details fetched successfully',
    //             'data' => [
    //                 'consumer_no' => $ratepayer->consumer_no,
    //                 'mobile_no' => $ratepayer->mobile_no,
    //                 'last_payment_amount' => $lastPaymentAmount,
    //                 'lastpayment_date' => $lastPaymentDate,
    //                 'current_demand' => $currentDemand,
    //                 'pending_amount' => $pendingAmount,
    //                 'consumer_status' => $consumerStatus, // Added consumer status field
    //                 'transaction' => $pendingDetails
    //             ],
    //             'meta' => [
    //                 'epoch' => now()->timestamp,
    //                 'queryTime' => round(microtime(true) - LARAVEL_START, 4),
    //                 'server' => request()->server('SERVER_NAME')
    //             ]
    //         ]);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Error occurred: ' . $e->getMessage(),
    //             'apiid' => $apiid
    //         ], 500);
    //     }
    // }


    // API-ID: RTRANS-001 [RateTransaction]

    public function getBillAmount(Request $request)
    {
        try {
            $validated = $request->validate([
                'consumer_no' => 'required_without:mobile_no',
                'mobile_no' => 'required_without:consumer_no',
            ]);

            $consumerNumber = $validated['consumer_no'] ?? null;
            $phoneNumber = $validated['mobile_no'] ?? null;

            if (!$consumerNumber && !$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either consumer_no or mobile_no is required.',
                ], 400);
            }

            $ratepayers = DB::table('ratepayers')
                ->select(
                    'ratepayers.id',
                    'ratepayers.consumer_no',
                    'ratepayers.mobile_no',
                    'ratepayers.updated_at',
                    'ratepayers.cluster_id'
                )
                ->where('consumer_no', $consumerNumber)
                ->orWhere('mobile_no', $phoneNumber)
                ->get();

            if ($ratepayers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consumer not found.',
                ], 404);
            }

            foreach ($ratepayers as $ratepayer) {
                $lastPayment = DB::table('ratepayers')
                    ->join('demands', 'ratepayers.id', '=', 'demands.ratepayer_id')
                    ->where('ratepayers.id', $ratepayer->id)
                    ->orderBy('demands.created_at', 'desc')
                    ->select(
                        'demands.demand',
                        'demands.updated_at as last_payment_date',
                        'demands.bill_month as last_payment_bill_month'
                    )
                    ->first();

                $lastPaymentAmount = $lastPayment->demand ?? 0;
                $lastPaymentDate = $lastPayment->last_payment_date ?? null;
                $lastPaymentBillMonth = $lastPayment->last_payment_bill_month ?? null;

                $currentDemands = DB::table('ratepayers')
                    ->join('current_demands', 'ratepayers.id', '=', 'current_demands.ratepayer_id')
                    ->where('ratepayers.id', $ratepayer->id)
                    ->select(
                        'current_demands.demand',
                        'current_demands.ulb_id',
                        'current_demands.bill_month',
                        'current_demands.bill_year',
                        'current_demands.vrno'
                    )
                    ->get();

                $clusterName = null;
                if ($ratepayer->cluster_id) {
                    $cluster = DB::table('clusters')
                        ->select('cluster_name')
                        ->where('id', $ratepayer->cluster_id)
                        ->first();

                    if ($cluster) {
                        $clusterName = $cluster->cluster_name;
                    }
                }

                $billDetails[] = [
                    'consumer_no' => $ratepayer->consumer_no,
                    'mobile_no' => $ratepayer->mobile_no,
                    'last_payment_amount' => $lastPaymentAmount,
                    'last_payment_date' => $lastPaymentDate,
                    'last_payment_bill_month' => $lastPaymentBillMonth,
                    'cluster_name' => $clusterName,
                    'current_demands' => $currentDemands,
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Bill details fetched successfully',
                'data' => $billDetails,
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }



    // API-ID: RTRANS-002 [RateTransaction]

    public function postPaymentTransaction(Request $request)
    {
        try {
            $validated = $request->validate([
                'consumer_no' => 'nullable|string',
                'mobile_no' => 'nullable|string',
                'payment_amount' => 'required',
                'remarks' => 'nullable|string',
            ]);

            $consumerNumber = $validated['consumer_no'] ?? null;
            $phoneNumber = $validated['mobile_no'] ?? null;
            $paymentAmount = $validated['payment_amount'];
            $remarks = $validated['remarks'] ?? null;

            if (!$consumerNumber && !$phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either consumer_no or mobile_no is required.',
                ], 400);
            }

            $ratepayers = DB::table('ratepayers')
                ->leftJoin('current_demands', 'ratepayers.id', '=', 'current_demands.ratepayer_id')
                ->select(
                    'ratepayers.id',
                    'ratepayers.consumer_no',
                    'ratepayers.mobile_no',
                    'ratepayers.current_demand',
                    'ratepayers.status',
                    'ratepayers.entity_id',
                    'ratepayers.ulb_id',
                    'ratepayers.cluster_id',
                    'current_demands.demand as current_demand_amount'
                )
                ->where('ratepayers.consumer_no', $consumerNumber)
                ->orWhere('ratepayers.mobile_no', $phoneNumber)
                ->get();

            if ($ratepayers->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Consumer not found.',
                ], 404);
            }

            if ($ratepayers->count() > 1 && $phoneNumber) {
                return response()->json([
                    'success' => false,
                    'message' => 'Multiple consumers found with the same mobile number. Please provide the consumer number.',
                ], 400);
            }

            $ratepayer = $ratepayers->first();

            if ($ratepayer->current_demand <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No pending amount to be paid.',
                ], 400);
            }

            if ($paymentAmount > $ratepayer->current_demand) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment amount exceeds the current demand.',
                ], 400);
            }

            DB::beginTransaction();

            $originalCurrentDemand = $ratepayer->current_demand;
            $newCurrentDemand = $ratepayer->current_demand - $paymentAmount;

            $transactionData = [
                'ulb_id' => $ratepayer->ulb_id,
                'cluster_id' => $ratepayer->cluster_id,
                'ratepayer_id' => $ratepayer->id,
                'entity_id' => $ratepayer->entity_id,
                'payment_id' => null,
                'event_time' => now(),
                'event_type' => 'PAYMENT',
                'remarks' => $remarks,
                'paymentStatus' => $newCurrentDemand <= 0 ? 'COMPLETED' : 'PENDING',
                'tc_id' => null,
            ];

            $transactionId = DB::table('transactions')->insertGetId($transactionData);

            DB::table('ratepayers')
                ->where('id', $ratepayer->id)
                ->update([
                    'current_demand' => $newCurrentDemand,
                    'last_payment_id' => $transactionId,
                    'lastpayment_amt' => $paymentAmount,
                    'lastpayment_date' => now(),
                    'status' => $newCurrentDemand <= 0 ? 'verified' : $ratepayer->status,
                ]);

            if ($newCurrentDemand == 0) {
                DB::table('demands')
                    ->where('ratepayer_id', $ratepayer->id)
                    ->update([
                        'demand' => $originalCurrentDemand,
                        'total_demand' => $originalCurrentDemand,
                    ]);

                DB::table('current_demands')
                    ->where('ratepayer_id', $ratepayer->id)
                    ->delete();
            } else {
                DB::table('current_demands')
                    ->where('ratepayer_id', $ratepayer->id)
                    ->update([
                        'demand' => $newCurrentDemand,
                    ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Payment transaction recorded and ratepayer updated successfully.',
                'data' => [
                    'consumer_no' => $ratepayer->consumer_no,
                    'mobile_no' => $ratepayer->mobile_no,
                    'payment_amount' => $paymentAmount,
                    'paymentStatus' => $newCurrentDemand <= 0 ? 'COMPLETED' : 'PENDING',
                    'remarks' => $remarks,
                ],
                'meta' => [
                    'epoch' => now()->timestamp,
                    'queryTime' => round(microtime(true) - LARAVEL_START, 4),
                    'server' => request()->server('SERVER_NAME')
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error occurred while processing payment transaction: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Error occurred while posting payment: ' . $e->getMessage(),
            ], 500);
        }
    }
}



// public function postPaymentTransaction(Request $request)
    // {
    //     try {
    //         $apiid = $request->input('apiid', $request->header('apiid', 'MDASH-001'));
    //         $consumerNumber = $request->input('consumer_no', null);
    //         $phoneNumber = $request->input('mobile_no', null);
    //         $paymentAmount = $request->input('payment_amount', 0);
    //         $remarks = $request->input('remarks', null);
    //         $ulbId = $request->input('ulb_id', 1);
    //         $tcId = $request->input('tc_id', 1);

    //         if (!$consumerNumber && !$phoneNumber) {
    //             return response()->json(['success' => false, 'message' => 'consumer_no or mobile_no is required.'], 400);
    //         }


    //         $ratepayer = DB::table('ratepayers')
    //             ->join('transactions', 'ratepayers.id', '=', 'transactions.ratepayer_id')
    //             ->select(
    //                 'ratepayers.id',
    //                 'ratepayers.consumer_no',
    //                 'ratepayers.mobile_no',
    //                 'ratepayers.current_demand',
    //                 'ratepayers.status',
    //                 'ratepayers.entity_id'
    //             )
    //             ->where(function ($query) use ($consumerNumber, $phoneNumber) {
    //                 if ($consumerNumber) {
    //                     $query->where('ratepayers.consumer_no', $consumerNumber);
    //                 }
    //                 if ($phoneNumber) {
    //                     $query->orWhere('ratepayers.mobile_no', $phoneNumber);
    //                 }
    //             })
    //             ->first();

    //         if (!$ratepayer) {
    //             return response()->json(['success' => false, 'message' => 'Consumer not found.'], 404);
    //         }

    //         // Calculate pending amount and payment status
    //         $pendingAmount = $ratepayer->current_demand - $paymentAmount;
    //         $paymentStatus = $pendingAmount <= 0 ? 'COMPLETED' : 'PENDING';

    //         // Insert into transactions table and get the inserted id
    //         $transactionData = [
    //             'ulb_id' => $ulbId,
    //             'tc_id' => $tcId,
    //             'ratepayer_id' => $ratepayer->id,
    //             'entity_id' => $ratepayer->entity_id,
    //             'payment_id' => null,
    //             'event_time' => now(),
    //             'event_type' => 'PAYMENT',
    //             'remarks' => $remarks,
    //             'paymentStatus' => $paymentStatus,
    //             'created_at' => now(),
    //             'updated_at' => now()
    //         ];

    //         $transactionId = DB::table('transactions')->insertGetId($transactionData);

    //         $newCurrentDemand = max($pendingAmount, 0);

    //         // Updating the ratepayer record
    //         $ratepayerUpdateData = [
    //             'current_demand' => $newCurrentDemand,
    //             'last_payment_id' => $transactionId,
    //             'lastpayment_amt' => $paymentAmount,
    //             'lastpayment_date' => now(),
    //             'status' => $paymentStatus === 'COMPLETED' ? 'verified' : $ratepayer->status,
    //             'updated_at' => now()
    //         ];

    //         $updateStatus = DB::table('ratepayers')->where('id', $ratepayer->id)->update($ratepayerUpdateData);

    //         if ($updateStatus) {
    //             return response()->json([
    //                 'apiid' => $apiid,
    //                 'success' => true,
    //                 'message' => 'Payment transaction recorded and ratepayer updated successfully.',
    //                 'data' => [
    //                     'consumer_no' => $ratepayer->consumer_no,
    //                     'mobile_no' => $ratepayer->mobile_no,
    //                     'payment_amount' => $paymentAmount,
    //                     'payment_status' => $paymentStatus,
    //                     'remarks' => $remarks,
    //                 ],
    //                 'meta' => [
    //                     'epoch' => now()->timestamp,
    //                     'queryTime' => round(microtime(true) - LARAVEL_START, 4),
    //                     'server' => request()->server('SERVER_NAME')
    //                 ]
    //             ]);
    //         } else {
    //             return response()->json(['success' => false, 'message' => 'Failed to update ratepayer record.', 'apiid' => $apiid], 500);
    //         }
    //     } catch (\Exception $e) {
    //         return response()->json(['success' => false, 'message' => 'Error occurred while posting payment: ' .
    //             $e->getMessage(), 'apiid' => $apiid], 500);
    //     }
    // }
