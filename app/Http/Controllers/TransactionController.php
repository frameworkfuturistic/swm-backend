<?php

namespace App\Http\Controllers;

use App\Events\SiteVisitedEvent;
use App\Http\Services\TransactionService;
use App\Models\CurrentTransaction;
use App\Models\Payment;
use App\Models\Ratepayer;
use App\Models\TempEntities;
use App\Models\Transaction;
use App\Services\NumberGeneratorService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Razorpay\Api\Api;

/**
 * Created on 04/12/2024
 * Author:
 *    Anil Mishra
 * Api mapping:
 *    Route::post('transactions/add', [TransactionController::class, 'recordTransaction']);
 *
 * Request url:
 *    recordTransaction => [POST] http://127.0.0.1:8000/api/transactions
 *    suspendTransaction =>
 *    updateTransaction =>
 *
 *
 * Effects:
 *    1. Field Tracking Dashboard
 *    2. TC Mobile app for transaction serach
 *
 * Possible Enhancements:
 *    1. Payment gateway integration.
 */
class TransactionController extends Controller
{
   protected $numberGenerator;
   public function __construct(NumberGeneratorService $numberGenerator)
    {
        $this->numberGenerator = $numberGenerator;
    }

    /**
     * Usage
     *  // Generate a transaction number
     *  $transactionNumber = $this->numberGenerator->generateTransactionNumber();
     */


    /**
     * 
     * 1. api/transactions/payment/cash
     * 2.
     */
    public function cashPayment(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
            'remarks' => 'nullable|string|max:255',
            'amount' => 'required|numeric|between:0,50000',
            'scheduleDate' => 'nullable|date|date_format:Y-m-d|after_or_equal:today',
            'longitude' => 'required|numeric|between:-180,180',      // Required, valid longitude
            'latitude' => 'required|numeric|between:-90,90',         // Required, valid latitude
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

        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['paymentMode'] = 'CASH';

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
                    //   'remarks' => $validatedData['remarks'],
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
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
                'An error occurred during insertion. '.$e->getMessage().' Demand Till Date = '.$tranService->demandTillDate,
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Request body
     *{
     *    "tc_id": 5,
     *    "ratepayer_id": 10,
     *    "payment_mode": "cash",
     *    "amount": 3000,
     *    "event_time": "2024-11-29 12:00:00",
     *    "remarks": "Payment for 3 months."
     *}
     */
    public function store(Request $request)
    {
        $tranService = new TransactionService;

        $validatedData = $request->validate([
            // 'ulbId' => 'required|exists:ulbs,id',
            'tcId' => 'required|exists:users,id',
            'ratepayerId' => 'required|exists:ratepayers,id',
            'eventType' => 'required|in:PAYMENT,DENIAL,DOOR-CLOSED,DEFERRED,OTHER',
            'remarks' => 'nullable|string|max:250',
            'autoRemarks' => 'nullable|string|max:250',
            'amount' => 'required_if:event_type,PAYMENT|integer|min:1',
            'paymentMode' => 'required_if:event_type,PAYMENT|in:cash,card,upi,cheque,online',
        ]);

        $validatedData['entityId'] = $request->input('entityId');
        $validatedData['clusterId'] = $request->input('clusterId');

        // Start a transaction to ensure data integrity

        DB::beginTransaction();
        try {
            $success = $tranService->recordTransaction($validatedData);
            if ($success == true) {
                $responseData = [
                    'tranId' => $tranService->transaction->id,
                    'tcId' => $validatedData['tc_id'],
                    'clusterId' => $tranService->transaction->cluster_id,
                    'entityId' => $tranService->transaction->entity_id,
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->ratepayer_address,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                    'tranType' => $tranService->transaction->event_type,
                    'pmtMode' => $tranService->transaction->payment_mode,
                    'pmtAmount' => $tranService->payment->amount,
                    'remarks' => $tranService->transaction->remarks,
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
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
                'An error occurred during insertion',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function deferred(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
            'remarks' => 'nullable|string|max:255',                  // Optional, must be a string with a max length of 255
            'scheduleDate' => 'nullable|date|date_format:Y-m-d|after_or_equal:today',
            'longitude' => 'required|numeric|between:-180,180',      // Required, valid longitude
            'latitude' => 'required|numeric|between:-90,90',         // Required, valid latitude
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

        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'DEFERRED';

        DB::beginTransaction();
        try {

            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);
            $tranService->updateScheduleDate($validatedData);

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'eventType' => 'DEFERRED',
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->mobile_no,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                    'tranType' => $transaction->event_type,
                    //   'remarks' => $validatedData['remarks'],
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
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
                'An error occurred during insertion. '.$e->getMessage().' Demand Till Date = '.$tranService->demandTillDate,
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function doorClosed(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
            'remarks' => 'nullable|string|max:255',                  // Optional, must be a string with a max length of 255
            'longitude' => 'required|numeric|between:-180,180',      // Required, valid longitude
            'latitude' => 'required|numeric|between:-90,90',         // Required, valid latitude
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

        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'DOOR-CLOSED';

        $tranService = new TransactionService;

        DB::beginTransaction();
        try {

            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'eventType' => $validatedData['eventType'],
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->mobile_no,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
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
                'An error occurred during insertion. '.$e->getMessage().' Demand Till Date = '.$tranService->demandTillDate,
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }

    public function denial(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
            'remarks' => 'nullable|string|max:255',                  // Optional, must be a string with a max length of 255
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'longitude' => 'required|numeric|between:-180,180',      // Required, valid longitude
            'latitude' => 'required|numeric|between:-90,90',         // Required, valid latitude
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

        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'DENIAL';

        DB::beginTransaction();
        try {

            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'eventType' => $validatedData['eventType'],
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->mobile_no,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                    //   'remarks' => $validatedData['remarks'],
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
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
                'An error occurred during insertion. '.$e->getMessage().' Demand Till Date = '.$tranService->demandTillDate,
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }

    public function chequeCollection(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ratepayerId' => 'required|integer|exists:ratepayers,id',
            'chequeNo' => 'required|string|max:50',
            'chequeDate' => 'required|date',
            'bankName' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'longitude' => 'required|numeric|between:-180,180',      // Required, valid longitude
            'latitude' => 'required|numeric|between:-90,90',         // Required, valid latitude
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

        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'CHEQUE';
        $validatedData['remarks'] = 'Cheque collected';

        DB::beginTransaction();
        try {
            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);
            $tranService->createChequeRecord($validatedData, $transaction->id);

            if ($transaction != null) {
                $responseData = [
                    'tranId' => $transaction->id,
                    'eventType' => 'CHEQUE',
                    'consumerNo' => $tranService->ratepayer->consumer_no,
                    'ratepayerName' => $tranService->ratepayer->ratepayer_name,
                    'ratepayerAddress' => $tranService->ratepayer->ratepayer_address,
                    'mobileNo' => $tranService->ratepayer->mobile_no,
                    'landmark' => $tranService->ratepayer->landmark,
                    'longitude' => $tranService->ratepayer->longitude,
                    'latitude' => $tranService->ratepayer->latitude,
                    'tranType' => $transaction->event_type,
                    //   'remarks' => $validatedData['remarks'],
                ];
                DB::commit();

                //Broadcast transaction to Dashboard
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

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create ratepayer cheque: '.$e->getMessage(),
            ], 500);
        }

    }

    public function tcTransactionSummary(Request $request)
    {
        try {

            $tranService = new TransactionService;
            $records = $tranService->tcMonthTransactionSummary();

            return format_response(
                'Success',
                $records,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function ratepayerTransactions(Request $request, $id)
    {
        try {
            $data = ['id' => $id];
            $validator = Validator::make($data, [
                'id' => 'required|integer|exists:ratepayers,id', // Ensures the ID is valid and exists in the 'ratepayers' table
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

            $ratepayerId = $validatedData['id'];

            $tranService = new TransactionService;
            $records = $tranService->ratepayerTransactions($ratepayerId);

            return format_response(
                'Success',
                $records,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function createTempEntity(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'ulb_id' => 'required|exists:ulbs,id',
                'zone_id' => 'nullable|numeric',
                 'tc_id' => 'nullable|numeric',
                'subcategory_id' => 'required|numeric',
                //  'entity_id' => 'nullable|numeric',
                //  'cluster_id' => 'nullable|numeric',
                'holding_no' => 'nullable|string|max:255',
                'entity_name' => 'required|string|max:255',
                'entity_address' => 'required|string',
                'pincode' => 'nullable|string|size:6',
                'mobile_no' => 'nullable|string|max:15',
                'landmark' => 'nullable|string|max:100',
                'whatsapp_no' => 'nullable|string|max:12',
                'longitude' => 'nullable|numeric|between:-180,180',
                'latitude' => 'nullable|numeric|between:-90,90',
                //  'verification_date' => 'nullable|date',
                //  'is_verified' => 'boolean',
                //  'is_rejected' => 'boolean',
                'usage_type' => ['required', Rule::in(['Residential', 'Commercial', 'Industrial', 'Institutional'])],
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
            if ($request->zone_id == null)
            {
               $validatedData['zone_id'] =1;
            }            

            // $tcId = Auth::user()->id;
            $tcId = Auth::check() ? Auth::user()->id : 0;

            $validatedData['tc_id'] = $tcId;

            $tempEntity = TempEntities::create($validatedData);

            return format_response(
                'Success',
                $tempEntity,
                Response::HTTP_CREATED
            );
        } catch (Exception $e) {
            DB::rollBack();

            return format_response(
                'An error occurred during insertion. ',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function cancellation(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'tranId' => 'required|integer|exists:current_transactions,id',
                'remarks' => 'nullable|string|max:255',
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
            $tcId = Auth::user()->id;

            $tranId = $request->tranId;
            $transaction = CurrentTransaction::find($tranId);
            $transaction->is_cancelled = true;
            $transaction->cancelledby_id = $tcId;
            $transaction->cancellation_date = now();
            $transaction->remarks = $validatedData['remarks'];
            $transaction->save();

            return format_response(
                'Success',
                null,
                Response::HTTP_OK
            );
        } catch (Exception $e) {
            DB::rollBack();

            return format_response(
                'An error occurred during insertion. '.$e->getMessage(),
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function zoneTransactionSummary(Request $request)
    {
        try {
            // $data = ['id' => $id];
            $validator = Validator::make($request->all(), [
                'zoneId' => 'required|integer|exists:payment_zones,id', // Ensures the ID is valid and exists in the 'ratepayers' table
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
            $records = $tranService->zoneTransactionSummary($validatedData['zoneId']);

            return format_response(
                'Zone Transaction Summary',
                $records,
                Response::HTTP_OK
            );

        } catch (\Illuminate\Validation\ValidationException $e) {
            return format_response(
                $e->getMessage(),
                null,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        } catch (\Exception $e) {
            return format_response(
                'An error occurred during data extraction',
                null,
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

    }
}
