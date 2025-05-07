<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\Payment;
use App\Models\CurrentDemand;
use App\Models\CurrentTransaction;
use App\Models\Ratepayer;
use App\Models\Cluster;
use App\Models\Entity;
use App\Services\NumberGeneratorService;
use Carbon\Carbon;
use Illuminate\Http\Response;

class ClusterPaymentController extends Controller
{
   function truncateString($string, $length = 45) {
      return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
    }

    /**
     * Process payment for a cluster ratepayer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processClusterPayment(Request $request)
    {
        // Validate the request
        $validator = $this->validateRequest($request);
        if ($validator->fails()) {
            $errorMessages = $validator->errors()->all();
            return format_response(
               'validation error',
               $errorMessages,
               Response::HTTP_UNPROCESSABLE_ENTITY
         );
     }

       $validatedData = $validator->validated();
        // Get the authenticated tax collector
        $tc_id = Auth::id();
        $ulb_id = $request->user()->ulb_id;
        
        // Check if the ratepayer is a cluster ratepayer
        $ratepayerValidation = $this->validateClusterRatepayer($request->input('payment.ratepayer_id')); //Validates if cluster ratepayer exists
        if ($ratepayerValidation !== true) {
            return $ratepayerValidation;
        }
        
        // Get cluster info from ratepayer
        $ratepayer = Ratepayer::findOrFail($request->input('payment.ratepayer_id'));
        $cluster_id = $ratepayer->cluster_id;
        
        // Validate included entities
        $entitiesValidation = $this->validateIncludedEntities($cluster_id, $request->entities);
        if ($entitiesValidation !== true) {
            return $entitiesValidation;
        }
        
        // Check that payment amount matches total demands
        $demandValidation = $this->validatePaymentAmount($request->entities, $request->input('payment.payment_amount'), $request->payment['year'],$request->payment['month']);
        if ($demandValidation !== true) {
            return $demandValidation;
        }
        
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
            ->where('r.id', $request->input('payment.ratepayer_id'))
            ->first();

        $ratepayer = Ratepayer::find($request->input('payment.ratepayer_id'));
        $validatedData['ulbId'] = $request->ulb_id;
        $validatedData['tcId'] = Auth::user()->id;
      //   $validatedData['entityId'] = $ratepayer->entity_id;
      //   $validatedData['clusterId'] = $ratepayer->cluster_id;
        $validatedData['eventType'] = 'PAYMENT';
        $validatedData['paymentMode'] = $request->input('payment.payment_mode');

        $validatedData['rec_ward'] = $data->ward_name ?? '';
        $validatedData['rec_consumerno'] = $data->consumer_no ?? '';
        $validatedData['rec_name'] = $this->truncateString($data->ratepayer_name ?? '',40);
        $validatedData['rec_address'] = $this->truncateString($data->ratepayer_address ?? '',40);
        $validatedData['rec_category'] = $data->category ?? '';
        $validatedData['rec_subcategory'] = $data->sub_category ?? '';
        $validatedData['rec_monthlycharge'] = $data->monthly_demand ?? '';
        $validatedData['rec_amount'] = $request->input('payment.payment_amount');
        $validatedData['rec_paymentmode'] = $request->input('payment.payment_mode');
        $validatedData['rec_tcname'] = $request->user()->name;
        $validatedData['rec_tcmobile'] ='';
        $validatedData['rec_chequeno'] = $request->input('payment.cheque_no');
        $validatedData['rec_chequedate'] = $request->input('payment.cheque_date');
        $validatedData['rec_bankname'] = $request->input('payment.bank');
        $validatedData['remarks'] = $request->input('payment.remarks');
        $validatedData['utrNo'] = $request->input('payment.utr_no');
        $validatedData['upiId'] = $request->input('payment.upi_id');


        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Create payment record
            $payment = $this->createPayment($request, $validatedData, $tc_id, $ulb_id, $cluster_id,$request->payment['year'],$request->payment['month']);
            
            // Create transaction record
            $transaction = $this->createClusterTransaction($request,$validatedData, $tc_id, $ulb_id, $cluster_id, $payment->id);
            
            // Update demands for included entities
            $this->updateEntityDemands($request->entities, $payment->id, $tc_id, $ulb_id, $request->payment['year'],$request->payment['month']);
            
            $paymentOld = Payment::where('ratepayer_id', $request->input('payment.ratepayer_id'))
               ->latest('id') // or 'created_at' if you want by date
               ->first();

            $paymentFrom = $paymentOld?->payment_to; 
            $paymentTo = $payment->payment_from;

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
                  ->where('r.id', $request->input('payment.ratepayer_id'))
                  ->first();

            // $transaction->rec_receiptno =$payment->receipt_no;
            // $transaction->rec_ward = $data->ward_name ?? '';
            // $transaction->rec_consumerno = $data->consumer_no ?? '';
            // $transaction->rec_name = $data->ratepayer_name ?? '';
            // $transaction->rec_address = $data->ratepayer_address ?? '';
            // $transaction->rec_category = $data->category ?? '';
            // $transaction->rec_subcategory = $data->sub_category ?? '';

            // $transaction->rec_monthlycharge = $data->monthly_demand ?? '';
            // $transaction->rec_period = $paymentFrom.' to '.$paymentTo;
            // $transaction->rec_amount = $request->input('payment.payment_amount');
            // $transaction->rec_paymentmode = $request->input('payment.payment_mode');
            // $transaction->rec_tcname = $request->user()->name;
            // $transaction->rec_tcmobile ='';
            // $transaction->rec_chequeno = $request->input('payment.cheque_no');
            // $transaction->rec_chequedate = $request->input('payment.cheque_date');
            // $transaction->rec_bankname = $request->input('payment.bank');
            // $transaction->save();

            $transaction->payment_from = $paymentFrom;
            $transaction->payment_to = $paymentTo;
            $transaction->save();

            // Update payment with transaction id
            $payment->tran_id = $transaction->id;
            $payment->save();
            
            DB::commit();
            
            return response()->json([
                'status' => 'success',
                'message' => 'Cluster payment processed successfully',
                'payment_id' => $payment->id,
                'transaction_id' => $transaction->id,
                'vrno' => $payment->vrno,
                'entities_updated' => count($request->entities)
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to process payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Validate request data
     * 
     * @param Request $request
     * @return true|Response
     */
    private function validateRequest(Request $request)
    {
         $validator = Validator::make($request->all(), [
            'payment.ratepayer_id' => 'required|exists:ratepayers,id',
            'payment.payment_mode' => 'required|in:CASH,CARD,UPI,CHEQUE,DD,NEFT,ONLINE,WHATSAPP',
            'payment.year' => 'required|integer|min:2021|max:2030',
            'payment.month' => 'required|integer|min:1|max:12',
            'payment.payment_amount' => 'required|integer|min:1',
            'payment.longitude' => 'nullable|numeric',
            'payment.latitude' => 'nullable|numeric',
            'payment.remarks' => 'nullable|string|max:250',
      
            // Conditional fields based on payment_mode
            'payment.card_number' => 'required_if:payment.payment_mode,CARD|nullable|string|max:25',
            'payment.upi_id' => 'required_if:payment.payment_mode,UPI|nullable|string|max:100',
            'payment.cheque_no' => 'required_if:payment.payment_mode,CHEQUE|nullable|string|max:25',
            'payment.bank' => 'required_if:payment.payment_mode,CHEQUE|nullable|string|max:25',
      
            // Entities array
            'entities' => 'required|array|min:1',
            'entities.*.entity_id' => 'required|exists:entities,id',
         ]);
     
      //   if ($validator->fails()) {
      //       return response()->json([
      //           'status' => 'error',
      //           'message' => 'Validation error',
      //           'errors' => $validator->errors()
      //       ], 422);
      //   }
        
        return  $validator;
    }
    
    /**
     * Validate that ratepayer is a cluster ratepayer
     * 
     * @param int $ratepayerId
     * @return true|Response
     */
    private function validateClusterRatepayer($ratepayerId) 
    {
        $ratepayer = Ratepayer::find($ratepayerId);
        
        if (!$ratepayer) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid ratepayer'
            ], 400);
        }
        
        if (!$ratepayer->cluster_id) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ratepayer is not a cluster'
            ], 400);
        }
        
        return true;
    }
    
    /**
     * Validate that all included entities belong to the cluster
     * 
     * @param int $clusterId
     * @param array $includedEntities
     * @return true|Response
     */
    private function validateIncludedEntities($clusterId, $includedEntities)
    {
        // Get all entities belonging to the cluster
        $clusterEntities = Entity::where('cluster_id', $clusterId)->pluck('id')->toArray();
        
        $includedEntityIds = array_column($includedEntities, 'entity_id');
        $invalidEntities = array_diff($includedEntityIds, $clusterEntities);

        // Check if all included entities belong to this cluster
      //   $invalidEntities = array_diff($includedEntities, $clusterEntities);
        
        if (!empty($invalidEntities)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Some entities do not belong to this cluster',
                'invalid_entities' => $invalidEntities
            ], 400);
        }
        
        return true;
    }
    
    /**
     * Validate that payment amount matches total demands
     * 
     * @param array $includedEntities
     * @param int $paymentAmount
     * @return true|Response
     */
    private function validatePaymentAmount($includedEntities, $paymentAmount, $year, $month)
    {
        $totalDemand = 0;
        
        foreach ($includedEntities as $entityId) {
            $entityRatepayer = Ratepayer::where('entity_id', $entityId)->first();
            
            if ($entityRatepayer) {
                $demands = CurrentDemand::where('ratepayer_id', $entityRatepayer->id)
                  //   ->whereNull('payment_id')
                    ->where('is_active', true)
                    ->whereRaw('ifnull(demand,0)-ifnull(payment,0) >0')
                    ->whereRaw('(bill_month + (bill_year * 12)) <= (? + (? * 12))', [$month, $year])
                    ->sum('total_demand');
                
                $totalDemand += $demands;
            }
        }
        
        if ($totalDemand != $paymentAmount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Payment amount and adjusted demands do not match',
                'total_demand' => $totalDemand,
                'payment_amount' => $paymentAmount
            ], 400);
        }
        
        return true;
    }
    
    /**
     * Create payment record
     * 
     * @param Request $request
     * @param int $tcId
     * @param int $ulbId
     * @param int $clusterId
     * @return Payment
     */
    private function createPayment(Request $request, $validatedData, $tcId, $ulbId, $clusterId, $year, $month)
    {
        $receiptNo = app(NumberGeneratorService::class)->generate('receipt_no');
        $formattedDate = date('F-Y', mktime(0, 0, 0, $month, 1, $year));
        $payment = new Payment();
        $payment->ulb_id = $ulbId;
        $payment->receipt_no = $receiptNo;
        $payment->ratepayer_id = $request->input('payment.ratepayer_id');
        $payment->cluster_id = $clusterId;
        $payment->entity_id = null;
        $payment->tc_id = $tcId;
        $payment->payment_date = Carbon::now();
        $payment->payment_mode = $request->input('payment.payment_mode');
        $payment->payment_to = $formattedDate;
        $payment->payment_status = 'PENDING';
        $payment->amount = $request->input('payment.payment_amount');
        $payment->payment_verified = false;
        $payment->vrno = 0; // Initial VRNo is 0
        
        
     if (isset($validatedData['rec_chequeno'])) {
      $payment->cheque_number = $validatedData['rec_chequeno'];
     }
     if (isset($validatedData['rec_upiId'])) {
      $payment->upi_id = $validatedData['rec_upiId'];
     }

     if (isset($validatedData['rec_bankname'])) {
      $payment->bank_name = $validatedData['rec_bankname'];
     }
     if (isset($validatedData['rec_utrNo'])) {
      $payment->neft_id = $validatedData['rec_utrNo'];
      if (isset($validatedData['rec_chequedate'])) {
         $payment->neft_date = $validatedData['rec_chequedate'];
        }
     }

     if (isset($validatedData['rec_chequedate'])) {
      $payment->neft_date = $validatedData['rec_chequedate'];
     }

      //   // Set payment mode specific details
      //   if ($request->input('payment.payment_mode') == 'CARD') {
      //       $payment->card_number = $request->input('payment.card_no');
      //   } elseif ($request->input('payment.payment_mode') == 'UPI') {
      //       $payment->upi_id = $request->input('payment.upi_id');
      //   } else {
      //       $payment->cheque_number = $request->input('payment.cheque_no');
      //       $payment->bank_name = $request->input('payment.bank');
      //   }
        
        $payment->save();
        
        return $payment;
    }
    
    /**
     * Create transaction record for cluster
     * 
     * @param Request $request
     * @param int $tcId
     * @param int $ulbId
     * @param int $clusterId
     * @param int $paymentId
     * @return CurrentTransaction
     */
    private function createClusterTransaction(Request $request, $validatedData, $tcId, $ulbId, $clusterId, $paymentId)
    {
        $transactionNo = app(NumberGeneratorService::class)->generate('transaction_no');
        $transaction = new CurrentTransaction();
        $transaction->ulb_id = $ulbId;
        $transaction->tc_id = $tcId;
        $transaction->ratepayer_id = $request->input('payment.ratepayer_id');
        $transaction->entity_id = null;
        $transaction->cluster_id = $clusterId;
        $transaction->transaction_no = $transactionNo;
        $transaction->payment_id = $paymentId;
        $transaction->event_time = Carbon::now();
        $transaction->event_type = 'PAYMENT';
        $transaction->remarks = $request->input('payment.remarks');
        $transaction->longitude = $request->input('payment.longitude');
        $transaction->latitude = $request->input('payment.latitude');
        $transaction->is_verified = false;
        $transaction->is_cancelled = false;
        $transaction->vrno = 0; // Initial VRNo is 0

        if (isset($validatedData['rec_ward'])) {
         $transaction->rec_ward = $validatedData['rec_ward'];
        }
        if (isset($validatedData['rec_consumerno'])) {
         $transaction->rec_consumerno = $validatedData['rec_consumerno'];
        }
        if (isset($validatedData['rec_name'])) {
         $transaction->rec_name = $validatedData['rec_name'];
        }
        if (isset($validatedData['rec_address'])) {
         $transaction->rec_address = $validatedData['rec_address'];
        }
        if (isset($validatedData['rec_category'])) {
         $transaction->rec_category = $validatedData['rec_category'];
        }
        if (isset($validatedData['rec_subcategory'])) {
         $transaction->rec_subcategory = $validatedData['rec_subcategory'];
        }
        if (isset($validatedData['rec_monthlycharge'])) {
         $transaction->rec_monthlycharge = $validatedData['rec_monthlycharge'];
        }
        if (isset($validatedData['rec_amount'])) {
         $transaction->rec_amount = $validatedData['rec_amount'];
        }
        if (isset($validatedData['rec_paymentmode'])) {
         $transaction->rec_paymentmode = $validatedData['rec_paymentmode'];
        }
        if (isset($validatedData['rec_tcname'])) {
         $transaction->rec_tcname = $validatedData['rec_tcname'];
        }
        if (isset($validatedData['rec_tcmobile'])) {
         $transaction->rec_tcmobile = $validatedData['rec_tcmobile'];
        }
        if (isset($validatedData['rec_chequeno'])) {
         $transaction->rec_chequeno = $validatedData['rec_chequeno'];
        }
        if (isset($validatedData['rec_chequedate'])) {
         $transaction->rec_chequedate = $validatedData['rec_chequedate'];
        }
        if (isset($validatedData['rec_bankname'])) {
         $transaction->rec_bankname = $validatedData['rec_bankname'];
        }
        

        $transaction->save();
        
        return $transaction;
    }
    
    /**
     * Update demands for included entities
     * 
     * @param array $includedEntities
     * @param int $paymentId
     * @param int $tcId
     * @param int $ulbId
     * @return void
     */
    private function updateEntityDemands($includedEntities, $paymentId, $tcId, $ulbId, $year, $month)
    {
        foreach ($includedEntities as $entityId) {
            // Find the entity ratepayer
            $entityRatepayer = Ratepayer::where('entity_id', $entityId)->first();
            
            if (!$entityRatepayer) {
                continue;
            }
            
            // Get all outstanding demands for this entity
            $demands = CurrentDemand::where('ratepayer_id', $entityRatepayer->id)
               //  ->whereNull('payment_id')
                ->where('is_active', true)
                ->whereRaw('ifnull(demand,0)-ifnull(payment,0) > 0')
                ->whereRaw('(bill_month + (bill_year * 12)) <= (? + (? * 12))', [$month, $year])
                ->get();
            
            foreach ($demands as $demand) {
                // Update the demand with payment information
                $demand->payment = $demand->total_demand; // Pay full amount
                $demand->payment_id = $paymentId;
                $demand->tc_id = $tcId;
                $demand->vrno = 0; // Initial VRNo is 0
                $demand->save();
                
                // Create entity transaction record
               //  $this->createEntityTransaction($entityId['entity_id'], $entityRatepayer->id, $paymentId, $tcId, $ulbId);
            }
        }
    }
    
    /**
     * Create transaction record for entity
     * 
     * @param int $entityId
     * @param int $ratepayerId
     * @param int $paymentId
     * @param int $tcId
     * @param int $ulbId
     * @return void
     */
    private function createEntityTransaction($entityId, $ratepayerId, $paymentId, $tcId, $ulbId)
    {
        $transactionNo = app(NumberGeneratorService::class)->generate('transaction_no');
        $entityTransaction = new CurrentTransaction();
        $entityTransaction->ulb_id = $ulbId;
        $entityTransaction->tc_id = $tcId;
        $entityTransaction->ratepayer_id = $ratepayerId;
        $entityTransaction->transaction_no = $transactionNo;
        $entityTransaction->entity_id = $entityId;
        $entityTransaction->cluster_id = null;
        $entityTransaction->payment_id = $paymentId;
        $entityTransaction->event_time = Carbon::now();
        $entityTransaction->event_type = 'PAYMENT';
        $entityTransaction->auto_remarks = "Payment applied from cluster payment";
        $entityTransaction->is_verified = false;
        $entityTransaction->is_cancelled = false;
        $entityTransaction->vrno = 0; // Initial VRNo is 0
        $entityTransaction->save();
    }
}