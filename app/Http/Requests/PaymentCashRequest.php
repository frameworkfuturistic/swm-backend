<?php

namespace App\Http\Requests;

use App\Traits\HandleApiValidation;
use Illuminate\Foundation\Http\FormRequest;

class PaymentCashRequest extends FormRequest
{
    use HandleApiValidation;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'tcId' => 'required|exists:users,id',
            'ratepayerId' => 'required|exists:ratepayers,id',
            // 'eventType' => 'required|in:PAYMENT,DENIAL,DOOR-CLOSED,DEFERRED,OTHER',
            'remarks' => 'nullable|string|max:250',
            // 'autoRemarks' => 'nullable|string|max:250',
            'amount' => 'required_if:event_type,PAYMENT|integer|min:1',
            // 'paymentMode' => 'required_if:event_type,PAYMENT|in:cash,card,upi,cheque,online',
            //
        ];
    }
}
