<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PaymentWebhookRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'event_type' => 'nullable|string|max:50',
            'razorpay_payment_id' => 'nullable|string|max:255',
            'payload' => 'nullable|array',
        ];
    }

    public function prepareForValidation()
    {
        $this->merge([
            'payload' => json_encode($this->payload),
        ]);
    }
}
