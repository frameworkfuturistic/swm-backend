<?php

namespace App\Http\Requests;

use App\Traits\HandleApiValidation;
use Illuminate\Foundation\Http\FormRequest;

class PaymentRequest extends FormRequest
{
    use HandleApiValidation;

    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'ulb_id' => 'nullable|integer|exists:ulbs,id',
            'ratepayer_id' => 'nullable|integer|exists:ratepayers,id',
            'entity_id' => 'nullable|integer',
            'cluster_id' => 'nullable|integer',
            'tc_id' => 'nullable|integer',
            'receipt_id' => 'nullable|integer',
            'payment_date' => 'nullable|date',
            'payment_mode' => 'nullable|in:CASH,CARD,UPI,CHEQUE,ONLINE',
            'payment_status' => 'required|in:PENDING,COMPLETED,FAILED,REFUNDED',
            'amount' => 'nullable|integer|min:0',
            'refund_initiated' => 'nullable|boolean',
            'refund_verified' => 'nullable|boolean',
            'tran_id' => 'nullable|integer',
            'payment_order_id' => 'nullable|integer',
            'card_number' => 'nullable|string|max:255',
            'upi_id' => 'nullable|string|max:255',
            'cheque_number' => 'nullable|string|max:255',
            'is_canceled' => 'nullable|boolean',
        ];
    }
}
