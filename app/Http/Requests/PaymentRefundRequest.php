<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentRefundRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'payment_id' => 'nullable|integer|exists:payments,id',
            'razorpay_refund_id' => 'nullable|string|max:100',
            'refund_amount' => 'nullable|integer|min:0',
            'refund_status' => 'required|in:INITIATED,PROCESSED,FAILED',
            'refund_reason' => 'nullable|string|max:255',
        ];
    }
}
