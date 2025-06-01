<?php

namespace App\Http\Controllers;

use App\Events\SiteVisitedEvent;
use App\Http\Services\TransactionService;
use App\Models\CurrentDemand;
use App\Models\CurrentTransaction;
use App\Models\Entity;
use App\Models\Payment;
use App\Models\Ratepayer;
use App\Models\SubCategory;
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

    public function getTCPaymentRecords(Request $request)
    {
        // Validate the date parameter
        $validator = Validator::make($request->all(), [
            'date' => 'required|date_format:Y-m-d',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Please use YYYY-MM-DD format.',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Get date from request and use authenticated user's ID as tc_id
            $date = $request->input('date');
            $tcId = Auth::id();
            
            // Query to fetch payment records
            $payments = DB::table('payments as p')
                ->select(
                    'p.ratepayer_id',
                    'p.tran_id as payment_id',
                    'r.ratepayer_name',
                    'r.ratepayer_address',
                    'r.mobile_no',
                    DB::raw("IFNULL(p.receipt_no, 'NA') as receipt_no"),
                    'p.payment_mode',
                    DB::raw("DATE_FORMAT(p.payment_date, '%h:%i %p') as payment_time"),
                    'p.amount'
                )
                ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
                ->where('p.tc_id', $tcId)
                ->whereRaw('DATE(payment_date) = ?', [$date])
                ->get();
            
            return response()->json([
                'success' => true,
                'data' => $payments,
                'message' => 'Payment records retrieved successfully'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving payment records: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Usage
     *  // Generate a transaction number
     *  $transactionNumber = $this->numberGenerator->generateTransactionNumber();
     */

     public function getReceipt(Request $request, $ratepayerId)
     {
         $isLastPayment = $request->query('is_lastpayment');

         $tableToValidate = $isLastPayment == "1" ? 'ratepayers' : 'current_transactions';

         $validator = Validator::make(
            ['ratepayer_id' => $ratepayerId],
            [
               'ratepayer_id' => [
                     'required',
                     'integer',
                     Rule::exists($tableToValidate, 'id')
               ],
            ]
         );

         // $validator = Validator::make(['ratepayer_id' => $ratepayerId], [
         //     'ratepayer_id' => 'required|integer|exists:ratepayers,id',
         // ]);
     
         if ($validator->fails()) {
             return response()->json([
                 'status' => 'error',
                 'message' => 'Invalid ratepayer ID.',
                 'errors' => $validator->errors(),
             ], 422);
         }
     
         try {
            $query="";
            if ($isLastPayment == "1") {
               $ratepayer = Ratepayer::find($ratepayerId);
               $query = DB::table('current_transactions')
                  ->select([
                     'rec_name as ratepayer_name',
                     'rec_address as ratepayer_address',
                     'rec_consumerno as consumer_no',
                     DB::raw("DATE_FORMAT(event_time, '%d/%m/%Y') as payment_date"),
                     'rec_paymentmode as payment_mode',
                     'rec_receiptno as receipt_no',
                     'rec_period as period',
                     'rec_amount as amount',
                     DB::raw("rec_monthlycharge as monthly_demand"),
                  //  DB::raw("cast(rec_monthlycharge as char) as monthly_demand"),
                     'rec_tcname as tc_name',
                     'rec_tcmobile as tc_mobile',
                     'rec_ward as ward_name',
                     'rec_category as category',
                     'rec_subcategory as sub_category',
                     'rec_chequeno as cheque_no',
                     'rec_chequedate as cheque_date',
                     'rec_bankname as bank_name',
                     'rec_nooftenants'
                  ])
                  ->where('ratepayer_id', $ratepayerId)
                  ->orderByDesc('id');
            } else {
               $query = DB::table('current_transactions')
                  ->select([
                     'rec_name as ratepayer_name',
                     'rec_address as ratepayer_address',
                     'rec_consumerno as consumer_no',
                     DB::raw("DATE_FORMAT(event_time, '%d/%m/%Y') as payment_date"),
                     'rec_paymentmode as payment_mode',
                     'rec_receiptno as receipt_no',
                     'rec_period as period',
                     'rec_amount as amount',
                     DB::raw("rec_monthlycharge as monthly_demand"),
                  //  DB::raw("cast(rec_monthlycharge as char) as monthly_demand"),
                     'rec_tcname as tc_name',
                     'rec_tcmobile as tc_mobile',
                     'rec_ward as ward_name',
                     'rec_category as category',
                     'rec_subcategory as sub_category',
                     'rec_chequeno as cheque_no',
                     'rec_chequedate as cheque_date',
                     'rec_bankname as bank_name',
                     'rec_nooftenants'
                  ])
                  ->where('id', $ratepayerId);
            }

            $latestPayment= $query->first();
     
             if (!$latestPayment) {
               return format_response(
                  'Could not fetch data',
                  null,
                  Response::HTTP_NOT_FOUND
               );
   
            }else {
                  return format_response(
                     'Success',
                     $latestPayment,
                     Response::HTTP_OK
                  );
             }
         } catch (\Exception $e) {
            return format_response(
               'Could not fetch data',
               null,
               Response::HTTP_NOT_FOUND
            );
         }
     }


   //   public function getReceipt(Request $request, $ratepayerId)
   //   {
   //       // Validate the ratepayer ID as a positive integer and check existence
   //       $validator = Validator::make(['ratepayer_id' => $ratepayerId], [
   //           'ratepayer_id' => 'required|integer|exists:ratepayers,id',
   //       ]);
     
   //       if ($validator->fails()) {
   //           return response()->json([
   //               'status' => 'error',
   //               'message' => 'Invalid ratepayer ID.',
   //               'errors' => $validator->errors(),
   //           ], 422);
   //       }
     
   //       try {
   //          $isLastPayment = $request->query('is_lastpayment');

   //          $query="";
   //          $ratepayer = Ratepayer::find($ratepayerId);
   //          if ($ratepayer?->cluster_id) {
   //             $query = DB::table('current_transactions')
   //             ->select([
   //                 'rec_name as ratepayer_name',
   //                 'rec_address as ratepayer_address',
   //                 'rec_consumerno as consumer_no',
   //                 DB::raw("DATE_FORMAT(event_time, '%d/%m/%Y') as payment_date"),
   //                 'rec_paymentmode as payment_mode',
   //                 'rec_receiptno as receipt_no',
   //                 'rec_period as period',
   //                 'rec_amount as amount',
   //                 DB::raw("rec_monthlycharge as monthly_demand"),
   //                //  DB::raw("cast(rec_monthlycharge as char) as monthly_demand"),
   //                 'rec_tcname as tc_name',
   //                 'rec_tcmobile as tc_mobile',
   //                 'rec_ward as ward_name',
   //                 'rec_category as category',
   //                 'rec_subcategory as sub_category',
   //                 'rec_chequeno as cheque_no',
   //                 'rec_chequedate as cheque_date',
   //                 'rec_bankname as bank_name',
   //                 'rec_nooftenants'
   //             ])
   //             ->where('ratepayer_id', $ratepayerId)
   //             ->orderByDesc('id');
   //          } else {
   //             $query = DB::table('payments as p')
   //             ->select(
   //                'r.ratepayer_name',
   //                'r.ratepayer_address',
   //                DB::raw('IFNULL(r.consumer_no, "") as consumer_no'), 
   //                'p.payment_date',
   //                'p.payment_mode',
   //                'p.receipt_no',
   //                DB::raw("CONCAT(payment_from, ' to ', payment_to) as period"),
   //                DB::raw('cast(ifnull(p.amount,0) as char) as amount'),
   //                DB::raw('cast(ifnull(r.monthly_demand,0) as char) as monthly_demand'),
   //                'u.name as tc_name',
   //                DB::raw("'' as mobile_no"),
   //                'w.ward_name',
   //                DB::raw("'' as category"),
   //                's.sub_category',
   //                DB::raw("'' as cheque_no"),
   //                DB::raw("'' as cheque_date"),
   //                DB::raw("'' as bank_name"),
   //                DB::raw("'1' as rec_nooftenants"),
   //             )
   //             ->join('ratepayers as r', 'p.ratepayer_id', '=', 'r.id')
   //             ->join('wards as w', 'r.ward_id', '=', 'w.id')
   //             ->join('users as u', 'p.tc_id', '=', 'u.id')
   //             ->leftJoin('sub_categories as s', 'r.subcategory_id', '=', 's.id')
   //             ->where('p.ratepayer_id', $ratepayerId)
   //             ->orderByDesc('p.id');
   //          }



   //          $latestPayment= $query->first();
     
   //           if (!$latestPayment) {
   //             return format_response(
   //                'Could not fetch data',
   //                null,
   //                Response::HTTP_NOT_FOUND
   //             );
   
   //          }else {
   //                return format_response(
   //                   'Success',
   //                   $latestPayment,
   //                   Response::HTTP_OK
   //                );
   //           }
   //       } catch (\Exception $e) {
   //          return format_response(
   //             'Could not fetch data',
   //             null,
   //             Response::HTTP_NOT_FOUND
   //          );
   //       }
   //   }

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
            'amount' => 'required|numeric|between:0,5000000',
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

        $data = DB::table('ratepayers as r')
            ->select(
               'w.ward_name',
               'r.consumer_no',
               'r.ratepayer_name',
               'r.ratepayer_address',
               'c.category',
               'sc.sub_category',
               'r.monthly_demand'
            )
            ->join('wards as w', 'r.ward_id', '=', 'w.id')
            ->leftJoin('sub_categories as sc', 'r.subcategory_id', '=', 'sc.id')
            ->leftJoin('categories as c', 'sc.category_id', '=', 'c.id')
            ->where('r.id', $validatedData['ratepayerId'])
            ->first();

        $tranService = new TransactionService;

        $ratepayer = Ratepayer::find($validatedData['ratepayerId']);
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
        $validatedData['entityId'] = $ratepayer->entity_id;
        $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['paymentMode'] = $request->paymentMode;

        $validatedData['rec_ward'] = $data->ward_name ?? '';
        $validatedData['rec_consumerno'] = $data->consumer_no ?? '';
        $validatedData['rec_name'] = $this->truncateString($data->ratepayer_name ?? '',40);
        $validatedData['rec_address'] = $this->truncateString($data->ratepayer_address ?? '',40);
        $validatedData['rec_category'] = $data->category ?? '';
        $validatedData['rec_subcategory'] = $data->sub_category ?? '';
        $validatedData['rec_monthlycharge'] = $data->monthly_demand ?? '';
        $validatedData['rec_amount'] = $request->amount;
        $validatedData['rec_paymentmode'] = $request->paymentMode;
        $validatedData['rec_tcname'] = $request->user()->name;
        $validatedData['rec_tcmobile'] ='';
        $validatedData['rec_chequeno'] = $request->chequeNo;
        $validatedData['rec_chequedate'] = $request->chequeDate;
        $validatedData['rec_bankname'] = $request->bankName;
        $validatedData['remarks'] = $request->remarks;
        $validatedData['utrNo'] = $request->utrNo;
        $validatedData['upiId'] = $request->upiId;

        // Start a transaction to ensure data integrity

        DB::beginTransaction();
        try {

            $tranService->extractRatepayerDetails($validatedData['ratepayerId']);
            $transaction = $tranService->createNewTransaction($validatedData);
            $payment = $tranService->createNewPayment($validatedData, $transaction->id);

            $transaction->payment_id = $payment->id;
            $transaction->rec_receiptno =$payment->receipt_no;
            $transaction->rec_period = $payment->payment_from.' to '.$payment->payment_to;
            $transaction->rec_nooftenants = "1";

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
               //  broadcast(new SiteVisitedEvent(
               //      $responseData,
               //  ))->toOthers();

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

    function truncateString($string, $length = 45) {
      return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
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
      $currentYear = date('Y');
      $currentMonth = date('n'); // 1–12 for Jan–Dec

      $validator = Validator::make($request->all(), [
         'ulb_id' => 'required|exists:ulbs,id',
         'ward_id' => 'nullable|numeric',
         'subcategory_id' => 'required|numeric|exists:sub_categories,id',
         'holding_no' => 'nullable|string|max:255',
         'entity_name' => 'required|string|max:255',
         'entity_address' => 'required|string',
         'pincode' => 'nullable|string|size:6',
         'mobile_no' => 'nullable|string|max:15',
         'landmark' => 'nullable|string|max:100',
         'whatsapp_no' => 'nullable|string|max:12',
         'longitude' => 'nullable|numeric|between:-180,180',
         'latitude' => 'nullable|numeric|between:-90,90',
         'from_month' => 'required|integer|between:1,12',
         'from_year' => "required|integer|between:2020,{$currentYear}",
         'to_month' => 'required|integer|between:1,12',
         'to_year' => "required|integer|between:2020,{$currentYear}",
      ]);

      $validator->after(function ($validator) use ($request, $currentYear, $currentMonth) {
         $fromYear = (int) $request->input('from_year');
         $fromMonth = (int) $request->input('from_month');
         $toYear = (int) $request->input('to_year');
         $toMonth = (int) $request->input('to_month');

         if ($toYear > $currentYear || ($toYear === $currentYear && $toMonth > $currentMonth)) {
               $validator->errors()->add('to_month', 'The “To” month/year can’t be later than the current month.');
         }

         if ($toYear < $fromYear || ($toYear === $fromYear && $toMonth < $fromMonth)) {
               $validator->errors()->add('to_month', 'The “To” month/year must be the same as or after the “From” month/year.');
         }
      });

      if ($validator->fails()) {
         return format_response(
               'validation error',
               $validator->errors()->all(),
               Response::HTTP_UNPROCESSABLE_ENTITY
         );
      }

      DB::beginTransaction();

      try {
         $subCategory = SubCategory::findOrFail($request->subcategory_id);
         $monthlyRate = $subCategory->rate;

         // Create Entity
         $entity = Entity::create([
               'ulb_id' => $request->ulb_id,
               'ward_id' => $request->ward_id,
               'subcategory_id' => $request->subcategory_id,
               'holding_no' => $request->holding_no,
               'entity_name' => $request->entity_name,
               'entity_address' => $request->entity_address,
               'pincode' => $request->pincode,
               'mobile_no' => $request->mobile_no,
               'landmark' => $request->landmark,
               'whatsapp_no' => $request->whatsapp_no,
               'longitude' => $request->longitude,
               'latitude' => $request->latitude,
               'monthly_demand' => $monthlyRate,
               'is_active' => true,
               'is_verified' => false,
               'vrno' => 0,
         ]);

         // Create Ratepayer
         $ratepayer = Ratepayer::create([
               'ulb_id' => $request->ulb_id,
               'ward_id' => $request->ward_id,
               'subcategory_id' => $request->subcategory_id,
               'holding_no' => $request->holding_no,
               'ratepayer_name' => $request->entity_name,
               'ratepayer_address' => $request->entity_address,
               'pincode' => $request->pincode,
               'mobile_no' => $request->mobile_no,
               'landmark' => $request->landmark,
               'whatsapp_no' => $request->whatsapp_no,
               'longitude' => $request->longitude,
               'latitude' => $request->latitude,
               'monthly_demand' => $monthlyRate,
               'vrno' => 0,
               'entity_id' => $entity->id,
         ]);

         // Link entity with ratepayer
         $entity->update(['ratepayer_id' => $ratepayer->id]);

         // Generate demand rows
         $start = \Carbon\Carbon::createFromDate($request->from_year, $request->from_month, 1);
         $end = \Carbon\Carbon::createFromDate($request->to_year, $request->to_month, 1);

         while ($start <= $end) {
               CurrentDemand::create([
                  'ulb_id' => $request->ulb_id,
                  'ratepayer_id' => $ratepayer->id,
                  'bill_month' => $start->month,
                  'bill_year' => $start->year,
                  'demand' => $monthlyRate,
                  'total_demand' => $monthlyRate,
                  'vrno' => 0
               ]);
               $start->addMonth();
         }

         DB::commit();

         return format_response(
               'Successfully added Ratepayer with Demands',
               $ratepayer,
               Response::HTTP_CREATED
         );
      } catch (\Exception $e) {
         DB::rollBack();
         return format_response(
               'Error while adding Ratepayer with Demands',
               ['error' => $e->getMessage()],
               Response::HTTP_BAD_REQUEST
         );
      }
   }


   //  public function createTempEntity(Request $request)
   //  {
   //      try {
   //          $currentYear  = date('Y');
   //          $currentMonth = date('n'); // 1–12 for Jan–Dec

   //          $validator = Validator::make($request->all(), [
   //              'ulb_id' => 'required|exists:ulbs,id',
   //              'ward_id' => 'nullable|numeric',
   //              'subcategory_id' => 'required|numeric',
   //              'holding_no' => 'nullable|string|max:255',
   //              'entity_name' => 'required|string|max:255',
   //              'entity_address' => 'required|string',
   //              'pincode' => 'nullable|string|size:6',
   //              'mobile_no' => 'nullable|string|max:15',
   //              'landmark' => 'nullable|string|max:100',
   //              'whatsapp_no' => 'nullable|string|max:12',
   //              'longitude' => 'nullable|numeric|between:-180,180',
   //              'latitude' => 'nullable|numeric|between:-90,90',
   //             'from_month' => 'required|integer|between:1,12',
   //             'from_year'  => "required|integer|between:2020,{$currentYear}",
   //             'to_month' => 'required|integer|between:1,12',
   //             'to_year'  => "required|integer|between:2020,{$currentYear}",
   //          ]);

   //          $validator->after(function ($validator) use ($request, $currentYear, $currentMonth) {
   //          $fromYear  = (int) $request->input('from_year');
   //          $fromMonth = (int) $request->input('from_month');
   //          $toYear    = (int) $request->input('to_year');
   //          $toMonth   = (int) $request->input('to_month');

   //          // (A) Ensure "From" is not before Jan 2020
   //          //     — This is effectively guaranteed by from_year ≥ 2020 and from_month ≥ 1.
   //          //       So you don’t need an extra check here unless you want a custom error message.

   //          // (B) Ensure To‐date is not in the future (i.e. > current month/year)
   //          if ($toYear > $currentYear
   //                || ($toYear === $currentYear && $toMonth > $currentMonth)
   //          ) {
   //                $validator->errors()->add(
   //                   'to_month',
   //                   'The “To” month/year can’t be later than the current month.'
   //                );
   //          }

   //          // (C) Ensure To‐date ≥ From‐date
   //          if (
   //                $toYear < $fromYear
   //                || ($toYear === $fromYear && $toMonth < $fromMonth)
   //             ) {
   //                   $validator->errors()->add(
   //                      'to_month',
   //                      'The “To” month/year must be the same as or after the “From” month/year.'
   //                   );
   //             }
   //          });
         

   //          if ($validator->fails()) {
   //              $errorMessages = $validator->errors()->all();

   //              return format_response(
   //                  'validation error',
   //                  $errorMessages,
   //                  Response::HTTP_UNPROCESSABLE_ENTITY
   //              );
   //          }
            
   //          $validatedData = $validator->validated();
   //          if ($request->zone_id == null)
   //          {
   //             $validatedData['zone_id'] =1;
   //          }            

   //          // $tcId = Auth::user()->id;
   //          $tcId = Auth::check() ? Auth::user()->id : 0;

   //          $validatedData['tc_id'] = $tcId;

   //          $tempEntity = TempEntities::create($validatedData);

   //          return format_response(
   //              'Success',
   //              $tempEntity,
   //              Response::HTTP_CREATED
   //          );


   //      } catch (Exception $e) {
   //          DB::rollBack();

   //          return format_response(
   //              'An error occurred during insertion. ',
   //              null,
   //              Response::HTTP_INTERNAL_SERVER_ERROR
   //          );
   //      }
   //  }

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

    /**
     * Get transactions for a specific ratepayer
     * 
     * @param int $ratepayerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getTransactionsByRatepayer($ratepayerId)
    {
        try {
            // Validate the ratepayer ID
            if (!is_numeric($ratepayerId)) {
               return format_response(
                  'validation error',
                  null,
                  Response::HTTP_BAD_REQUEST
              );
            }

            // Execute the query using DB facade
            // $current_transactions = DB::table('current_transactions as t')
            //     ->select(
            //         't.transaction_no',
            //         't.event_type',
            //         't.event_time',
            //         't.schedule_date',
            //         'p.payment_mode',
            //         'p.amount',
            //         't.remarks'
            //     )
            //     ->join('ratepayers as r', 't.ratepayer_id', '=', 'r.id')
            //     ->leftJoin('payments as p', 't.payment_id', '=', 'p.id')
            //     ->where('t.ratepayer_id', $ratepayerId)
            //     ->orderBy('t.id', 'desc')
            //     ->get();

            $qry = DB::table('current_transactions as t')
               ->join('payments as p', 't.id', '=', 'p.tran_id')
               ->where('p.ratepayer_id', $ratepayerId)
               ->select(
                  't.transaction_no',
                  't.event_type',
                  't.event_time',
                  't.schedule_date',
                  'p.payment_mode',
                  'p.amount',
                  't.remarks'
               )
               ->orderByDesc('t.event_time');
               $current_transactions = $qry->get();

            // $qry = DB::table('transactions as t')
            // ->select(
            //    't.transaction_no',
            //    't.event_type',
            //    't.event_time',
            //    't.schedule_date',
            //    'p.payment_mode',
            //    'p.amount',
            //    't.remarks'
            // )
            // ->join('ratepayers as r', 't.ratepayer_id', '=', 'r.id')
            // ->leftJoin('payments as p', 't.payment_id', '=', 'p.id')
            // ->where('t.ratepayer_id', $ratepayerId)
            // ->orderBy('t.id', 'desc');

            $qry = DB::table('transactions as t')
               ->join('payments as p', 't.id', '=', 'p.tran_id')
               ->where('p.ratepayer_id', $ratepayerId)
               ->select(
                  't.transaction_no',
                  't.event_type',
                  't.event_time',
                  't.schedule_date',
                  'p.payment_mode',
                  'p.amount',
                  't.remarks'
               )
               ->orderByDesc('t.event_time');
    
            $old_transactions = $qry->get();

            return format_response(
               'Success',
               [
                  'current_trans' => $current_transactions,
                  'old_trans' => $old_transactions
               ] ,
               Response::HTTP_OK
            );

        } catch (\Exception $e) {
            return format_response(
               'validation error',
               null,
               Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

}
