<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntityRatepayerRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules()
    {
        return [
            'entity.clusterId' => 'nullable|exists:clusters,id',
            'entity.wardId' => 'nullable|exists:wards,id',
            'entity.subcategoryId' => 'required|exists:sub_categories,id',
            'entity.verifiedbyId' => 'required|exists:users,id',
            'entity.appliedtcId' => 'required|exists:users,id',
            'entity.holdingNo' => 'required|string|max:50',
            'entity.entityName' => 'required|string|max:100',
            'entity.entityAddress' => 'required|string|max:255',
            'entity.pincode' => 'required|digits:6',
            'entity.mobileNo' => 'required|digits:10',
            'entity.landmark' => 'nullable|string|max:100',
            'entity.whatsappNo' => 'nullable|digits:10',
            'entity.longitude' => 'required|numeric|between:-180,180',
            'entity.latitude' => 'required|numeric|between:-90,90',
            'entity.inclusionDate' => 'required|date|before_or_equal:today',
            'entity.verificationDate' => 'nullable|date|after_or_equal:entity.inclusion_date',
            'entity.openingDemand' => 'required|numeric|min:0',
            'entity.monthlyDemand' => 'required|numeric|min:0',
            'entity.usageType' => 'required|in:Residential,Commercial,Industrial,Institutional',
            'entity.status' => 'required|in:pending,verified,rejected',
            'ratepayer.ulbId' => 'required|exists:ulbs,id',
            'ratepayer.clusterId' => 'nullable|exists:clusters,id',
            'ratepayer.wardId' => 'nullable|exists:wards,id',
            'ratepayer.paymentzoneId' => 'required|exists:payment_zones,id',
            'ratepayer.ratepayerName' => 'required|string|max:100',
            'ratepayer.ratepayerAddress' => 'required|string|max:255',
            'ratepayer.consumerNo' => 'required|string|max:50',
            'ratepayer.longitude' => 'required|numeric|between:-180,180',
            'ratepayer.latitude' => 'required|numeric|between:-90,90',
            'ratepayer.mobileNo' => 'required|digits:10',
            'ratepayer.landmark' => 'nullable|string|max:100',
            'ratepayer.whatsappNo' => 'nullable|digits:10',
            'ratepayer.billDate' => 'required|date|after_or_equal:entity.verification_date',
            'ratepayer.openingDemand' => 'required|numeric|min:0',
            'ratepayer.monthlyDemand' => 'required|numeric|min:0',
        ];
    }
}
