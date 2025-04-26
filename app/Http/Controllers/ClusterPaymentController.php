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
use Carbon\Carbon;

class ClusterPaymentController extends Controller
{
    /**
     * Process payment for a cluster ratepayer
     * 
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function processClusterPayment(Request $request)
    {
        // Validate the request
        $validationResult = $this->validateRequest($request);
        if ($validationResult !== true) {
            return $validationResult;
        }
        
        // Get the authenticated tax collector
        $tc_id = Auth::id();
        $ulb_id = $request->user()->ulb_id;
        
        // Check if the ratepayer is a cluster ratepayer
        $ratepayerValidation = $this->validateClusterRatepayer($request->input('payment.ratepayer_id'));
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
        $demandValidation = $this->validatePaymentAmount($request->entities, $request->input('payment.payment_amount'));
        if ($demandValidation !== true) {
            return $demandValidation;
        }
        
        // Begin transaction
        DB::beginTransaction();
        
        try {
            // Create payment record
            $payment = $this->createPayment($request, $tc_id, $ulb_id, $cluster_id);
            
            // Create transaction record
            $transaction = $this->createClusterTransaction($request, $tc_id, $ulb_id, $cluster_id, $payment->id);
            
            // Update payment with transaction id
            $payment->tran_id = $transaction->id;
            $payment->save();
            
            // Update demands for included entities
            $this->updateEntityDemands($request->entities, $payment->id, $tc_id, $ulb_id);
            
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
      //   $validator = Validator::make($request->all(), [
      //       'ratepayer_id' => 'required|exists:ratepayers,id',
      //       'payment_mode' => 'required|in:CASH,CARD,UPI,CHEQUE,ONLINE,WHATSAPP',
      //       'payment_amount' => 'required|integer|min:1',
      //       'included_entities' => 'required|array|min:1',
      //       'included_entities.*' => 'exists:entities,id',
      //       'longitude' => 'nullable|numeric',
      //       'latitude' => 'nullable|numeric',
      //       'remarks' => 'nullable|string|max:250',
      //       // Payment mode specific fields
      //       'card_number' => 'required_if:payment_mode,CARD|nullable|string|max:25',
      //       'upi_id' => 'required_if:payment_mode,UPI|nullable|string|max:100',
      //       'cheque_number' => 'required_if:payment_mode,CHEQUE|nullable|string|max:25',
      //       'bank_name' => 'required_if:payment_mode,CHEQUE|nullable|string|max:25',
      //   ]);

            $validator = Validator::make($request->all(), [
               'payment.ratepayer_id' => 'required|exists:ratepayers,id',
               'payment.payment_mode' => 'required|in:CASH,CARD,UPI,CHEQUE,ONLINE,WHATSAPP',
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
     
        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }
        
        return true;
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
    private function validatePaymentAmount($includedEntities, $paymentAmount)
    {
        $totalDemand = 0;
        
        foreach ($includedEntities as $entityId) {
            $entityRatepayer = Ratepayer::where('entity_id', $entityId)->first();
            
            if ($entityRatepayer) {
                $demands = CurrentDemand::where('ratepayer_id', $entityRatepayer->id)
                    ->whereNull('payment_id')
                    ->where('is_active', true)
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
    private function createPayment(Request $request, $tcId, $ulbId, $clusterId)
    {
        $payment = new Payment();
        $payment->ulb_id = $ulbId;
        $payment->ratepayer_id = $request->input('payment.ratepayer_id');
        $payment->cluster_id = $clusterId;
        $payment->entity_id = null;
        $payment->tc_id = $tcId;
        $payment->payment_date = Carbon::now();
        $payment->payment_mode = $request->input('payment.payment_mode');
        $payment->payment_status = 'COMPLETED';
        $payment->amount = $request->input('payment.payment_amount');
        $payment->payment_verified = true;
        $payment->vrno = 0; // Initial VRNo is 0
        
        // Set payment mode specific details
        if ($request->input('payment.payment_mode') == 'CARD') {
            $payment->card_number = $request->input('payment.card_no');
        } elseif ($request->input('payment.payment_mode') == 'UPI') {
            $payment->upi_id = $request->input('payment.upi_id');
        } elseif ($request->input('payment.payment_mode') == 'CHEQUE') {
            $payment->cheque_number = $request->input('payment.cheque_no');
            $payment->bank_name = $request->input('payment.bank');
        }
        
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
    private function createClusterTransaction(Request $request, $tcId, $ulbId, $clusterId, $paymentId)
    {
        $transaction = new CurrentTransaction();
        $transaction->ulb_id = $ulbId;
        $transaction->tc_id = $tcId;
        $transaction->ratepayer_id = $request->input('payment.ratepayer_id');
        $transaction->entity_id = null;
        $transaction->cluster_id = $clusterId;
        $transaction->payment_id = $paymentId;
        $transaction->event_time = Carbon::now();
        $transaction->event_type = 'PAYMENT';
        $transaction->remarks = $request->input('payment.remarks');
        $transaction->longitude = $request->input('payment.longitude');
        $transaction->latitude = $request->input('payment.latitude');
        $transaction->is_verified = true;
        $transaction->is_cancelled = false;
        $transaction->vrno = 0; // Initial VRNo is 0
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
    private function updateEntityDemands($includedEntities, $paymentId, $tcId, $ulbId)
    {
        foreach ($includedEntities as $entityId) {
            // Find the entity ratepayer
            $entityRatepayer = Ratepayer::where('entity_id', $entityId)->first();
            
            if (!$entityRatepayer) {
                continue;
            }
            
            // Get all outstanding demands for this entity
            $demands = CurrentDemand::where('ratepayer_id', $entityRatepayer->id)
                ->whereNull('payment_id')
                ->where('is_active', true)
                ->get();
            
            foreach ($demands as $demand) {
                // Update the demand with payment information
                $demand->payment = $demand->total_demand; // Pay full amount
                $demand->payment_id = $paymentId;
                $demand->tc_id = $tcId;
                $demand->vrno = 0; // Initial VRNo is 0
                $demand->save();
                
                // Create entity transaction record
                $this->createEntityTransaction($entityId['entity_id'], $entityRatepayer->id, $paymentId, $tcId, $ulbId);
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
        $entityTransaction = new CurrentTransaction();
        $entityTransaction->ulb_id = $ulbId;
        $entityTransaction->tc_id = $tcId;
        $entityTransaction->ratepayer_id = $ratepayerId;
        $entityTransaction->entity_id = $entityId;
        $entityTransaction->cluster_id = null;
        $entityTransaction->payment_id = $paymentId;
        $entityTransaction->event_time = Carbon::now();
        $entityTransaction->event_type = 'PAYMENT';
        $entityTransaction->auto_remarks = "Payment applied from cluster payment";
        $entityTransaction->is_verified = true;
        $entityTransaction->is_cancelled = false;
        $entityTransaction->vrno = 0; // Initial VRNo is 0
        $entityTransaction->save();
    }
}