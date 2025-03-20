<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\Ratepayer;
use App\Events\SiteVisitedEvent;
use App\Http\Services\TransactionService;
use Illuminate\Support\Facades\Auth;
use Exception;

class RateTransactionController extends Controller
{

    // API-ID: RTRANS-001 [RateTransaction]

    public function getBillAmountModified(Request $request)
    {
        try {

            $authKey = $request->header('AUTH_KEY');

            if (!$authKey || $authKey !== config('app.auth_key')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized client.'
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
                ->whereNotNull('payment_id')
                ->sum('total_demand');

            if ($currentDemand === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No active demand found for this ratepayer.',
                ], Response::HTTP_NOT_FOUND);
            }

            $data = [
                'consumer_no'          => $ratepayer->consumer_no,
                'last_payment_amount'  => $ratepayer->lastpayment_amt,
                'last_payment_date'    => $ratepayer->lastpayment_date,
                'last_payment_mode'    => $ratepayer->lastpayment_mode,
                'total_demand'         => $currentDemand, // Fixed: No need to use $currentDemand->total_demand
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

    public function postPaymentTransaction(Request $request)
    {
        Log::info('Request parameters: ' . json_encode($request->all()));
        $request->merge([
            'consumer_no' => $request->has('consumer_no') ? trim((string)$request->consumer_no) : null,
            'mobile_no' => $request->has('mobile_no') ? trim((string)$request->mobile_no) : null,
        ]);

        Log::info('Cleaned request parameters: ' . json_encode($request->all()));

        $validator = Validator::make($request->all(), [
            'consumer_no' => 'nullable|string|exists:ratepayers,consumer_no',
            'mobile_no' => 'nullable|string|exists:ratepayers,mobile_no',
            'remarks' => 'nullable|string|max:255',
            'amount' => 'required|numeric|between:0,50000',
            'scheduleDate' => 'nullable|date|date_format:Y-m-d|after_or_equal:today',
            'longitude' => 'required|numeric|between:-180,180',      // Required, valid longitude
            'latitude' => 'required|numeric|between:-90,90',         // Required, valid latitude
        ]);

        if (!$request->consumer_no && !$request->mobile_no) {
            return format_response(
                'validation error',
                ['Either consumer_no or mobile_no is required.'],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

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

        $ratepayers = Ratepayer::where(function ($query) use ($validatedData) {
            if ($validatedData['consumer_no']) {
                $query->where('consumer_no', $validatedData['consumer_no']);
            }
            if ($validatedData['mobile_no']) {
                $query->orWhere('mobile_no', $validatedData['mobile_no']);
            }
        })->get();

        if ($ratepayers->isEmpty()) {
            return format_response(
                'Ratepayer not found',
                null,
                Response::HTTP_NOT_FOUND
            );
        }

        // Check if multiple ratepayers are found for the given mobile_no
        if ($validatedData['mobile_no'] && $ratepayers->count() > 1) {
            return format_response(
                'Multiple ratepayers found for the given mobile number. Please provide a consumer number.',
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $ratepayer = $ratepayers->first();

        $validatedData['ratepayerId'] = $ratepayer->id;

        // Check for pending demands
        $pendingDemands = DB::table('current_demands')
            ->where('ulb_id', $ratepayer->ulb_id)
            ->where('ratepayer_id', $ratepayer->id)
            ->where('is_active', 1)
            ->whereNotNull('payment_id')
            ->sum('total_demand');

        if ($pendingDemands === 0) {
            return format_response(
                'No pending demands found for this ratepayer.',
                null,
                Response::HTTP_NOT_FOUND
            );
        }

        if ($validatedData['amount'] < $pendingDemands) {
            return format_response(
                'Payment amount must fully cover one or more pending demands. Demand Till Date = ' . $pendingDemands,
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['paymentMode'] = 'WHATSAPP';

        // Start a transaction to ensure data integrity
        DB::beginTransaction();
        try {
            // Log the ratepayerId for debugging
            Log::info('Ratepayer ID: ' . $validatedData['ratepayerId']);

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

                // Broadcast transaction to Dashboard
                broadcast(new SiteVisitedEvent(
                    $responseData,
                ))->toOthers();

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
