<?php

namespace App\Http\Services;

use App\Models\Demand;
use App\Models\DemandHasPayment;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\Ratepayer;
use App\Models\Transaction;

// recordTransaction->createTransactionRecord
// recordTransaction->createPaymentRecord

/**
 * Created on: 04/12/2024
 * Author:
 *    Anil Mishra
 * Purpose:
 *    This service is mainly used as base business logic for
 *    recording visit records.
 *
 * Called By:
 *    1. TransactionController
 *       Should broadcast transaction after this operation
 *
 * Effects:
 *    1. Field Tracking Dashboard
 *    2. TC Mobile app for transaction serach
 *
 * Possible Enhancements:
 *    1. Payment gateway integration.
 *
 * Business Logic:
 *    Create Transaction Record
 *    If Payment Transaction then create Payment Records also
 *       Update Transaction record with Payment iD
 */
class TransactionService
{
    public $transaction = null;

    public $payment = null;

    public $ratepayer = null;

    public function extractRatepayerDetails(int $ratepayerId)
    {
        $ratepayer = Ratepayer::find($ratepayerId);
    }

    /**
     * A. createNewTransaction (This is for every request)
     */
    public function createNewTransaction($validatedData): Transaction
    {
        $transaction = Transaction::create([
            'ulb_id' => $validatedData['ulb_id'],
            'tc_id' => $validatedData['tc_id'],
            'ratepayer_id' => $validatedData['ratepayer_id'],
            'entity_id' => $validatedData['entity_id'],
            'cluster_id' => $validatedData['cluster_id'],
            'event_time' => $validatedData['event_time'],
            'event_type' => $validatedData['event_type'],
            'remarks' => $validatedData['remarks'],
            'auto_remarks' => $validatedData['auto_remarks'],
        ]);

        return $transaction;
    }

    /**
     * B. createNewPayment
     */
    public function createNewPayment($validatedData, $tranId)
    {
        // Step 1: Fetch pending demands for the ratepayer (FIFO order)
        $pendingDemands = Demand::where('ratepayer_id', $validatedData['ratepayer_id'])
            ->whereRaw('demand > payment')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();

        $remainingAmount = $validatedData['amount'];
        $adjustedDemands = [];

        //Create an array of demands to be adjusted
        foreach ($pendingDemands as $demand) {
            $outstandingAmount = $demand->demand - $demand->payment;

            if ($remainingAmount >= $outstandingAmount) {
                $remainingAmount -= $outstandingAmount;

                // Update demand as fully paid
                $demand->payment += $outstandingAmount;
                $demand->save();

                $adjustedDemands[] = [
                    'demand_id' => $demand->id,
                    'demand' => $outstandingAmount,
                    'payment' => $outstandingAmount,
                ];
            } else {
                break; // Partial payment not allowed
            }
        }

        if ($remainingAmount > 0) {
            throw new \Exception('Payment amount must fully cover one or more pending demands.');
        }

        // Step 2: Create the Payment record
        $payment = Payment::create([
            'ulb_id' => $validatedData['ulb_id'],
            'ratepayer_id' => $validatedData['ratepayer_id'],
            'tc_id' => $validatedData['tc_id'],
            'tran_id' => $tranId,
            'payment_status' => 'COMPLETED', //PENDING','COMPLETED','FAILED','REFUNDED'
            'payment_date' => now(),
            'payment_mode' => $validatedData['paymentMode'],
            'amount' => $validatedData['amount'],
        ]);

        // Step 3: Link payment with adjusted demands
        foreach ($adjustedDemands as $adjustment) {
            DemandHasPayment::create([
                'demand_id' => $adjustment['demand_id'],
                'payment_id' => $payment->id,
                'demand' => $adjustment['demand'],
                'payment' => $adjustment['payment'],
            ]);
        }
    }

    public function createPaymentOrder($validatedData): PaymentOrder
    {
        $paymentOrder = new PaymentOrder;

        return $paymentOrder;
    }

    /**
     * 1. recordTransaction
     */
    public function recordTransaction($validatedData)
    {
        try {
            $ratepayer = Ratepayer::find($validatedData['ratepayerId']);

            // Step 1: Create the Transaction
            $this->createTransactionRecord($validatedData);

            // Step 2: If the event_type is 'PAYMENT', create a Payment
            if ($validatedData['event_type'] === 'PAYMENT') {
                $this->createPaymentRecord($validatedData, $this->transaction->id);
            }

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 2. createTransactionRecord
     */
    private function createTransactionRecord($validatedData)
    {
        $transaction = Transaction::create([
            'ulb_id' => $validatedData['ulb_id'],
            'tc_id' => $validatedData['tc_id'],
            'ratepayer_id' => $validatedData['ratepayer_id'],
            'entity_id' => $validatedData['entity_id'],
            'cluster_id' => $validatedData['cluster_id'],
            'event_time' => $validatedData['event_time'],
            'event_type' => $validatedData['event_type'],
            'remarks' => $validatedData['remarks'],
            'auto_remarks' => $validatedData['auto_remarks'],
        ]);
    }

    /**
     * 3. createPaymentRecord
     */
    private function createPaymentRecord($validatedData, $tranId)
    {
        // Step 1: Fetch pending demands for the ratepayer (FIFO order)
        $pendingDemands = Demand::where('ratepayer_id', $validatedData['ratepayer_id'])
            ->whereRaw('demand > payment')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();

        $remainingAmount = $validatedData['amount'];
        $adjustedDemands = [];

        //Create an array of demands to be adjusted
        foreach ($pendingDemands as $demand) {
            $outstandingAmount = $demand->demand - $demand->payment;

            if ($remainingAmount >= $outstandingAmount) {
                $remainingAmount -= $outstandingAmount;

                // Update demand as fully paid
                $demand->payment += $outstandingAmount;
                $demand->save();

                $adjustedDemands[] = [
                    'demand_id' => $demand->id,
                    'demand' => $outstandingAmount,
                    'payment' => $outstandingAmount,
                ];
            } else {
                break; // Partial payment not allowed
            }
        }

        if ($remainingAmount > 0) {
            throw new \Exception('Payment amount must fully cover one or more pending demands.');
        }

        // Step 2: Create the Payment record
        $payment = Payment::create([
            'ulb_id' => $validatedData['ulb_id'],
            'ratepayer_id' => $validatedData['ratepayer_id'],
            'tc_id' => $validatedData['tc_id'],
            'payment_date' => $validatedData['event_time'],
            'payment_mode' => $validatedData['payment_mode'],
            'amount' => $validatedData['amount'],
        ]);

        // Step 3: Link payment with adjusted demands
        foreach ($adjustedDemands as $adjustment) {
            DemandHasPayment::create([
                'demand_id' => $adjustment['demand_id'],
                'payment_id' => $payment->id,
                'demand' => $adjustment['demand'],
                'payment' => $adjustment['payment'],
            ]);
        }
    }

    private function createPaymentRecord1($validatedData, $tranId)
    {
        // Step 1: Fetch pending demands for the ratepayer
        $pendingDemands = Demand::where('ratepayer_id', $validatedData['ratepayer_id'])
            ->whereRaw('demand > payment')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();

        if ($pendingDemands->isEmpty()) {
            return response()->json(['success' => false, 'message' => 'No pending demands to adjust payment against.'], 400);
        }

        // Step 2: Validate if the payment amount matches full monthly demands
        $totalDemand = $pendingDemands->pluck('demand')->sum();
        if ($validatedData['amount'] % $totalDemand !== 0) {
            return response()->json(['success' => false, 'message' => 'Payment must be in multiples of pending demands.'], 400);
        }

        $adjustableDemands = [];
        $remainingAmount = $validatedData['amount'];

        foreach ($pendingDemands as $demand) {
            if ($remainingAmount >= ($demand->demand - $demand->payment)) {
                $adjustableDemands[] = $demand;
                $remainingAmount -= ($demand->demand - $demand->payment);
            }
            if ($remainingAmount === 0) {
                break;
            }
        }

        if ($remainingAmount > 0) {
            return response()->json(['success' => false, 'message' => 'Payment amount exceeds pending demands.'], 400);
        }

        // Step 3: Create a payment record
        $payment = Payment::create([
            'ulb_id' => $validatedData['ulb_id'],
            'ratepayer_id' => $validatedData['ratepayer_id'],
            'tc_id' => $validatedData['tc_id'],
            'payment_date' => $validatedData['event_time'],
            'payment_mode' => $validatedData['payment_mode'],
            'amount' => $validatedData['amount'],
            'tran_id' => null, // To be updated after transaction creation
        ]);

        // Step 4: Adjust demands and link with payment
        foreach ($adjustableDemands as $demand) {
            DemandHasPayment::create([
                'demand_id' => $demand->id,
                'payment_id' => $payment->id,
                'demand' => $demand->demand,
                'payment' => $demand->demand - $demand->payment,
            ]);

            $demand->update([
                'payment' => $demand->payment + ($demand->demand - $demand->payment),
            ]);
        }

        // Step 5: Create a transaction and update the payment record
        // $transaction = Transaction::create([
        //    'ulb_id' => $validatedData['ulb_id'],
        //    'tc_id' => $validatedData['tc_id'],
        //    'ratepayer_id' => $validatedData['ratepayer_id'],
        //    'event_time' => $validatedData['event_time'],
        //    'event_type' => 'PAYMENT',
        //    'remarks' => 'Payment adjusted against demands',
        // ]);

        $payment->update(['tran_id' => $tranId]);

        return $payment;
    }
}
