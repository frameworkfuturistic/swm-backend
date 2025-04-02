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
use Exception;
use Illuminate\Support\Facades\Auth;

class RateTransactionController extends Controller
{

    // API-ID: RTRANS-001 [RateTransaction]

    public function getCurrentBill(Request $request)
    {
      try {

         $authKey = $request->header('AUTH_KEY') ?? $request->header('Auth_Key') ?? $request->header('auth_key');


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

         $currentDemand = DB::table('current_demands')
               ->where([
                  ['ulb_id', $request->ulb_id],
                  ['ratepayer_id', $ratepayer->id],
                  ['is_active', 1],
               ])
               ->whereNull('payment_id')
               ->selectRaw("
                  SUM(total_demand) as total_sum, 
                  COUNT(*) as total_count, 
                  GROUP_CONCAT(DISTINCT CONCAT(MONTHNAME(STR_TO_DATE(bill_month, '%m')), ' ', bill_year) ORDER BY bill_year, bill_month SEPARATOR ', ') as bill_periods
               ")
               ->first();
         
         $totalSum = $currentDemand->total_sum;
         $totalCount = $currentDemand->total_count;
         $billPeriods = $currentDemand->bill_periods;

         if ($totalSum === 0) {
             return response()->json([
                 'success' => false,
                 'message' => 'No active demand found for this ratepayer.',
             ], Response::HTTP_NOT_FOUND);
         }

         $data = [
             'consumer_no'          => $ratepayer->consumer_no,
             'ratepayer_id'         => $ratepayer->id,
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

      $ratepayer = RatePayer::find($request->ratepayer_id);

      $validatedData['ulbId'] = $ratepayer->ulb_id;
      $validatedData['ratepayerId'] = $request->ratepayer_id;
      $validatedData['tcId'] = 1;
      $validatedData['entityId'] = $ratepayer->entity_id;
      $validatedData['clusterId'] = $ratepayer->cluster_id;
      $validatedData['eventType'] = 'PAYMENT';
      $validatedData['remarks'] = 'Whats app Bot payment';
      $validatedData['longitude'] = '0.0';
      $validatedData['latitude'] = '0.0';
      $validatedData['paymentMode'] = 'ONLINE';

      // Start a transaction to ensure data integrity
      DB::beginTransaction();
      try {
          $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
          $transaction = $tranService->createNewTransaction($validatedData);
          $payment = $tranService->createNewPayment($validatedData, $transaction->id);
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

              return format_response(
                  'success',
                  $responseData,
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

          return format_response(
              'An error occurred during insertion. ' . $e->getMessage() . ' Demand Till Date = ' . $tranService->demandTillDate,
              null,
              Response::HTTP_INTERNAL_SERVER_ERROR
          );
      }
    }
}




