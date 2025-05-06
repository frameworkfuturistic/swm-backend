<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Services\TransactionService;
use App\Models\CurrentDemand;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Ratepayer;
use App\Services\ReceiptService;
use Exception;
use Illuminate\Support\Facades\Auth;

class RateTransactionController extends Controller
{

    // protected $emailService;

    // API-ID: RTRANS-001 [RateTransaction]

    public function getCurrentBill(Request $request)
    {
        try {

            $authKey = $request->header('AUTH-KEY') ?? $request->header('Auth-Key') ?? $request->header('auth-key');


            if (!$authKey || $authKey !== config('app.auth_key')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized client. !'
                ], Response::HTTP_UNAUTHORIZED);
            }

            $request->validate([
                'ulb_id' => 'required|exists:ulbs,id',
                'consumer_no' => 'nullable|string',
                'mobile_no' => 'nullable|string',
            ]);

            if (!$request->consumer_no && !$request->mobile_no) {
                return response()->json([
                    'success' => false,
                    'message' => 'Either consumer_no or mobile_no is required.',
                ], Response::HTTP_FORBIDDEN);
            }

            $ratepayer = Ratepayer::where('ulb_id', $request->ulb_id)
                ->where('is_active', 1)
                ->whereNull('cluster_id')
                ->where(function ($query) use ($request) {
                    if ($request->consumer_no) {
                        $query->where('consumer_no', $request->consumer_no);
                    }
                    if ($request->mobile_no) {
                        $query->orWhere('mobile_no', $request->mobile_no);
                    }
                })->first();

            if (!$ratepayer) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ratepayer not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            // New Modifications
            $pendingDemands = CurrentDemand::where('ratepayer_id', $ratepayer->id)
<<<<<<< HEAD
               ->where('is_active', true)
               // ->whereRaw('ifnull(demand,0) > ifnull(payment,0)')
               ->whereRaw('ifnull(total_demand,0) - ifnull(payment,0) > 0')
               ->whereRaw('(bill_month + (bill_year * 12)) <= (MONTH(CURRENT_DATE) + (YEAR(CURRENT_DATE) * 12))')
               ->orderBy('bill_year')
               ->orderBy('bill_month')
               ->get();
=======
                ->where('is_active', true)
                ->whereRaw('ifnull(demand,0) > ifnull(payment,0)')
                ->orderBy('bill_year')
                ->orderBy('bill_month')
                ->get();
>>>>>>> 91358e16033a41c35da098d412d57e99affcfb19

            // End new modifications
            $totalSum = $pendingDemands->sum('total_demand');
            $totalCount = $pendingDemands->count('total_demand');

            $firstPeriod = '';
            $lastPeriod = '';

            if ($pendingDemands->isNotEmpty()) {
                $sorted = $pendingDemands->sortBy([
                    ['bill_year', 'asc'],
                    ['bill_month', 'asc'],
                ]);

                $first = $sorted->first();
                $last = $sorted->last();

                $firstPeriod = \Carbon\Carbon::createFromDate($first->bill_year, $first->bill_month)->format('M-Y');
                $lastPeriod = \Carbon\Carbon::createFromDate($last->bill_year, $last->bill_month)->format('M-Y');
            }

            $billPeriods = $firstPeriod . ' to ' . $lastPeriod;

            // $currentDemand = DB::table('current_demands')
            //     ->where([
            //         ['ulb_id', $request->ulb_id],
            //         ['ratepayer_id', $ratepayer->id],
            //         ['is_active', 1],
            //     ])
            //    //  ->whereNull('payment_id')
            //     ->selectRaw("
            //       SUM(total_demand) as total_sum, 
            //       COUNT(*) as total_count, 
            //       GROUP_CONCAT(DISTINCT CONCAT(MONTHNAME(STR_TO_DATE(bill_month, '%m')), ' ', bill_year) ORDER BY bill_year, bill_month SEPARATOR ', ') as bill_periods
            //    ")
            //     ->first();

            // $totalSum = $currentDemand->total_sum;
            // $totalCount = $currentDemand->total_count;
            // $billPeriods = $currentDemand->bill_periods;

            if ($totalSum === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active demand found for this ratepayer.',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = [
                'consumer_no'          => $ratepayer->consumer_no,
                'ratepayer_id'         => $ratepayer->id,
                'ratepayer_name'       => $ratepayer->ratepayer_name,
                'ratepayer_address'    => $ratepayer->ratepayer_address,
                'last_payment_amount'  => $ratepayer->lastpayment_amt,
                'last_payment_date'    => $ratepayer->lastpayment_date,
                'last_payment_mode'    => $ratepayer->lastpayment_mode,
                'month_count'          => $totalCount,
                'bill_period'          => $billPeriods,
                'total_demand'         => $totalSum,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Bill details fetched successfully',
                'data' => $data,
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

    public function postPayment(Request $request)
    {
        $receiptService = new ReceiptService();
        $authKey = $request->header('AUTH_KEY');

        if (!$authKey || $authKey !== config('app.auth_key')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized client.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        $validator = Validator::make($request->all(), [
            'ratepayer_id' => 'required|exists:ratepayers,id',
            'amount' => 'required|numeric|min:3',
            'transaction_id' => 'required|string|min:5|max:50',
        ]);

        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();

            return format_response(
                'validation error',
                $errorMessages,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validatedData = $validator->validated();

        $tranService = new TransactionService;

        // Check for pending demands
        $pendingDemands = DB::table('current_demands')
            ->where('ratepayer_id', $request->ratepayer_id)
            ->where('is_active', 1)
            ->whereNull('payment_id')
            ->sum('total_demand');


        if ($pendingDemands === 0) {
            return format_response(
                'No pending demands found for this ratepayer.',
                null,
                Response::HTTP_NOT_FOUND
            );
        }

        if ($request->amount > $pendingDemands) {
            return format_response(
                'Payment amount must fully cover one or more pending demands. Demand Till Date = ' . $pendingDemands,
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }


        $ratepayer = Ratepayer::find($request->ratepayer_id);



        $validatedData['ulbId'] = $ratepayer->ulb_id;
        $validatedData['ratepayerId'] = $request->ratepayer_id;
        $validatedData['vendorReceipt'] = $request->transaction_id;
        $validatedData['tcId'] = 1;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['remarks'] = 'Whats app Bot payment';
        $validatedData['longitude'] = '0.0';
        $validatedData['latitude'] = '0.0';
        $validatedData['paymentMode'] = 'WHATSAPP';

        // Start a transaction to ensure data integrity
        DB::beginTransaction();
        try {
            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);
            $payment = $tranService->createNewPayment($validatedData, $transaction->id);
            // dd($payment->toArray());

            // dd("Hii");
            $transaction->payment_id = $payment->id;
            $transaction->save();
            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->mobile_no,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $validatedData['longitude'],
                    'latitude' => $validatedData['latitude'],
                    'tranType' => $transaction->event_type,
                    'pmtMode' => $validatedData['paymentMode'],
                    'pmtAmount' => $validatedData['amount'],
                ];
                DB::commit();

                /**
                 * New Changes Start Here
                 */

                // Prepare payment data
                $paymentData = [
                    'name' => $tranService->ratepayer->ratepayer_name ?? '',
                    'mobile' => $tranService->ratepayer->mobile_no ?? '',
                    'address' => $tranService->ratepayer->ratepayer_address ?? '',
                    // 'transaction_no' => $request->transaction_id ?? '',receipt_no
                    'receipt_no' => $payment->receipt_no ?? '',
                    'consumer_no' => $tranService->ratepayer->consumer_no ?? '',
                    'category' => $tranService->ratepayer->usage_type,
                    'ward_no' => 'WARD 1' ?? '',
                    'holding_no' => $tranService->ratepayer->holding_no ?? '',
                    //'type' => $paymentData['type'] ?? '',
                    'payment_from' => $payment->payment_from ?? '',
                    'payment_to' => $payment->payment_to ?? '',
                    // 'from_date' => now(),
                    // 'to_date' => now(),
                    'rate_per_month' => $tranService->ratepayer->monthly_demand,
                    'amount' => $validatedData['amount'] ?? 0,
                    'total' => $validatedData['amount'] ?? 0,
                    'payment_mode' => 'Whatsapp',
                    'gst_no' =>  '',
                    'pan_no' =>  '',
                    'customer_remarks' => '',
                    'mobile' => $tranService->ratepayer->mobile_no ?? '',
                ];
                // dd($paymentData);

                // Generate PDF receipt
                $receiptData = $receiptService->generateReceipt($paymentData);
                $encodedPdf = base64_encode($receiptData['content']);
                return format_response(
                    'success',
                    $encodedPdf,
                    // $receiptData['content'],
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
            // dd($e->getMessage());
            return format_response(
                'An error occurred during insertion. ' . $e->getMessage() . ' Demand Till Date = ' . $tranService->demandTillDate,
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
