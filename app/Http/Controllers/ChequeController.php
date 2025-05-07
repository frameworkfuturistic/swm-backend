<?php

namespace App\Http\Controllers;

use App\Models\CurrentTransaction;
use App\Models\RatepayerCheque;
use App\Services\NumberGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ChequeController extends Controller
{
   /**
     * Store a new ratepayer cheque/payment record and add related transaction
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate the request data
        $validator = Validator::make($request->all(), [
            'ulb_id' => 'required|exists:ulbs,id',
            'ratepayer_id' => 'required|exists:ratepayers,id',
            'payment_mode' => 'required|in:CHEQUE,DD,NEFT',
            'cheque_no' => 'required|string|max:50',
            'cheque_date' => 'required|date',
            'bank_name' => 'required|string|max:50',
            'amount' => 'required|numeric|min:0',
            'tc_id' => 'required|exists:users,id', // Tax collector ID
            'remarks' => 'nullable|string|max:250',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Start database transaction
            DB::beginTransaction();

            // Create the ratepayer cheque record
            $ratepayerCheque = RatepayerCheque::create([
                'ulb_id' => $request->ulb_id,
                'ratepayer_id' => $request->ratepayer_id,
                'tran_id' => 0, // Will update this after creating transaction
                'payment_mode' => $request->payment_mode,
                'cheque_no' => $request->cheque_no,
                'cheque_date' => $request->cheque_date,
                'bank_name' => $request->bank_name,
                'amount' => $request->amount,
                'is_verified' => false,
                'is_returned' => false,
            ]);

            // Get VR Number (you might have a different way to generate this)
            $lastVrNo = CurrentTransaction::where('ulb_id', $request->ulb_id)
                ->max('vrno') ?? 0;
            $newVrNo = $lastVrNo + 1;

            $transactionNo = app(NumberGeneratorService::class)->generate('transaction_no');

            // Create the current transaction record
            $transaction = CurrentTransaction::create([
                'ulb_id' => $request->ulb_id,
                'tc_id' => $request->tc_id,
                'ratepayer_id' => $request->ratepayer_id,
                'entity_id' => $request->entity_id ?? null,
                'cluster_id' => $request->cluster_id ?? null,
                'transaction_no' => $transactionNo,
                'event_time' => now(),
                'event_type' => 'CHEQUE', // Set event type to CHEQUE
                'remarks' => $request->remarks,
                'auto_remarks' => $this->generateAutoRemarks($request->payment_mode, $request->cheque_no, $request->amount),
                'longitude' => $request->longitude ?? null,
                'latitude' => $request->latitude ?? null,
                'is_verified' => true,
                'is_cancelled' => false,
                'vrno' => 0,
            ]);

            // Update the ratepayer cheque with transaction ID
            $ratepayerCheque->update(['tran_id' => $transaction->id]);

            // Commit transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => ucfirst(strtolower($request->payment_mode)) . ' payment recorded successfully',
                'ratepayer_cheque' => $ratepayerCheque,
                'transaction' => $transaction
            ], 201);

        } catch (\Exception $e) {
            // Rollback transaction if any error occurs
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to record payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate automatic remarks based on payment details
     *
     * @param  string  $paymentMode
     * @param  string  $chequeNo
     * @param  float   $amount
     * @return string
     */
    private function generateAutoRemarks($paymentMode, $chequeNo, $amount)
    {
        $paymentTypeText = match($paymentMode) {
            'CHEQUE' => 'Cheque',
            'DD' => 'Demand Draft',
            'NEFT' => 'NEFT Transfer',
            default => 'Payment'
        };

        return "$paymentTypeText received with reference no: $chequeNo for amount: ₹" . number_format($amount, 2);
    }

    /**
     * Mark a cheque as verified
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function markAsVerified($id, Request $request)
    {
        try {
            $ratepayerCheque = RatepayerCheque::findOrFail($id);
            
            $ratepayerCheque->update([
                'is_verified' => true,
                'realization_date' => $request->realization_date ?? now()->format('Y-m-d')
            ]);

            // Also update the related transaction if needed
            if ($ratepayerCheque->tran_id) {
                CurrentTransaction::where('id', $ratepayerCheque->tran_id)->update([
                    'verification_date' => $request->realization_date ?? now()->format('Y-m-d'),
                    'verifiedby_id' => Auth::id()
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as verified successfully',
                'data' => $ratepayerCheque
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payment as verified',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark a cheque as returned
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function markAsReturned($id, Request $request)
    {
        $validator = Validator::make($request->all(), [
            'return_reason' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $ratepayerCheque = RatepayerCheque::findOrFail($id);
            
            $ratepayerCheque->update([
                'is_returned' => true,
                'return_reason' => $request->return_reason
            ]);

            // Also update the related transaction if needed
            if ($ratepayerCheque->tran_id) {
                CurrentTransaction::where('id', $ratepayerCheque->tran_id)->update([
                    'is_cancelled' => true,
                    'cancellation_date' => now()->format('Y-m-d'),
                    'cancelledby_id' => Auth::id(),
                    'auto_remarks' => "Payment returned: {$request->return_reason}"
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Payment marked as returned',
                'data' => $ratepayerCheque
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark payment as returned',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
