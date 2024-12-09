<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RatepayerRequest extends FormRequest
{
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
            'ulbId' => 'required|exists:ulbs,id',
            'clusterId' => 'nullable|exists:clusters,id',
            'entityId' => 'nullable|exists:clusters,id',
            'rateId' => 'nullable|exists:rate_list,id',
            'paymentzoneId' => 'required|exists:payment_zones,id',
            'ratepayerName' => 'required|string|max:100',
            'ratepayerAddress' => 'required|string|max:255',
            'consumerNo' => 'required|string|max:50',
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'mobileNo' => 'required|digits:10',
            'landmark' => 'nullable|string|max:100',
            'whatsappNo' => 'nullable|digits:10',
            'bill_date' => 'required|date|after_or_equal:entity.verification_date',
            'openingDemand' => 'required|numeric|min:0',
            'monthlyDemand' => 'required|numeric|min:0',
        ];
    }
}
