<?php

namespace App\Http\Services;

use App\Models\CurrentDemand;
use App\Models\CurrentPayment;
use App\Models\CurrentTransaction;
use App\Models\Demand;
use App\Models\Payment;
use App\Models\PaymentOrder;
use App\Models\Ratepayer;
use Exception;
use Illuminate\Database\Eloquent\Collection;

/**
 * Service class for handling transaction-related operations
 * Manages transactions, payments, and demand adjustments
 */
class TransactionService
{
    /**
     * Current transaction instance
     */
    public ?CurrentTransaction $transaction = null;

    /**
     * Current payment instance
     */
    public ?Payment $payment = null;

    /**
     * Current ratepayer instance
     */
    public ?Ratepayer $ratepayer = null;

    public int $demandTillDate = 0;

    public function extractRatepayerDetails(int $id)
    {
        $this->ratepayer = Ratepayer::find($id);
    }

    /**
     * Create a new transaction record
     *
     * @param  array  $validatedData  Validated input data
     */
    public function createNewTransaction(array $validatedData): CurrentTransaction
    {
        return CurrentTransaction::create([
            'ulb_id' => $validatedData['ulbId'],
            'tc_id' => $validatedData['tcId'],
            'ratepayer_id' => $validatedData['ratepayerId'],
            'entity_id' => $validatedData['entityId'],
            'cluster_id' => $validatedData['clusterId'],
            'event_time' => now(),
            'event_type' => $validatedData['eventType'],
            'remarks' => $validatedData['remarks'],
            'vrno' => 1,
        ]);
    }

    /**
     * Create a new payment and adjust demands
     *
     * @param  array  $validatedData  Validated input data
     * @param  int  $tranId  Transaction ID
     *
     * @throws Exception
     */
    public function createNewPayment(array $validatedData, int $tranId): CurrentPayment
    {
        // Create payment record
        $payment = $this->createPaymentRecord($validatedData, $tranId);

        // Process and adjust demands
        $this->processPendingDemands($validatedData['ratepayerId'], $validatedData['amount'], $payment);

        return $payment;
    }

    /**
     * Process pending demands and adjust payments
     *
     * @throws Exception
     */
    protected function processPendingDemands(int $ratepayerId, float $amount, CurrentPayment $payment): void
    {
        $pendingDemands = $this->getPendingDemands($ratepayerId);
        $this->demandTillDate = $pendingDemands->sum('total_demand');

        $remainingAmount = $amount;

        foreach ($pendingDemands as $demand) {
            $outstandingAmount = $demand->demand - $demand->payment;

            if ($remainingAmount >= $outstandingAmount) {
                $this->adjustDemand($demand, $outstandingAmount, $payment->id);
                $remainingAmount -= $outstandingAmount;
            } else {
                break; // Partial payments not allowed
            }
        }

        if ($remainingAmount > 0) {
            throw new Exception('Payment amount must fully cover one or more pending demands.');
        }
    }

    /**
     * Get pending demands for a ratepayer
     */
    protected function getPendingDemands(int $ratepayerId): Collection
    {
        return CurrentDemand::where('ratepayer_id', $ratepayerId)
            ->whereRaw('demand > payment')
            ->orderBy('bill_year')
            ->orderBy('bill_month')
            ->get();
    }

    /**
     * Adjust an individual demand record
     */
    protected function adjustDemand(CurrentDemand $demand, float $amount, int $paymentId): void
    {
        $demand->payment += $amount;
        $demand->payment_id = $paymentId;
        $demand->last_payment_date = now();
        $demand->save();
    }

    /**
     * Create payment record
     */
    protected function createPaymentRecord(array $validatedData, int $tranId): CurrentPayment
    {
        return CurrentPayment::create([
            'ulb_id' => $validatedData['ulbId'],
            'ratepayer_id' => $validatedData['ratepayerId'],
            'tc_id' => $validatedData['tcId'],
            'entity_id' => $validatedData['entityId'],
            'cluster_id' => $validatedData['clusterId'],
            'tran_id' => $tranId,
            'payment_status' => 'COMPLETED',
            'payment_date' => now(),
            'payment_mode' => $validatedData['paymentMode'],
            'vrno' => 1,
            'amount' => $validatedData['amount'],
        ]);
    }

    /**
     * Create a new payment order for gateway transactions
     */
    public function createPaymentOrder(array $validatedData): PaymentOrder
    {
        // Implement payment order creation logic
        return new PaymentOrder;
    }

    /**
     * Record a complete transaction with optional payment
     */
    public function recordTransaction(array $validatedData): bool
    {
        try {
            $this->transaction = $this->createNewTransaction($validatedData);

            if ($validatedData['eventType'] === 'PAYMENT') {
                $this->payment = $this->createNewPayment($validatedData, $this->transaction->id);
            }

            return true;
        } catch (Exception $e) {
            // Log the error here
            return false;
        }
    }
}
