<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ClusterRatepayerRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'cluster.appliedtcId' => 'nullable|exists:users,id',
            'cluster.clusterName' => 'required|string|max:60|unique:clusters,cluster_name,NULL,id,ulb_id,'.$this->ulb_id,
            'cluster.clusterAddress' => 'nullable|string|max:255',
            'cluster.landmark' => 'nullable|string|max:100',
            'cluster.pincode' => 'nullable|string|max:6',
            'cluster.clusterType' => 'required|in:Apartment,Building,Govt Institution,Colony,Other,None',
            'cluster.mobileNo' => 'nullable|string|max:12',
            'cluster.whatsappNo' => 'nullable|string|max:12',
            'cluster.longitude' => 'nullable|numeric',
            'cluster.latitude' => 'nullable|numeric',
            'cluster.inclusionDate' => 'nullable|date',
            'cluster.verificationDate' => 'nullable|date',

            'ratepayer.ulb_id' => 'required|exists:ulbs,id',
            'ratepayer.cluster_id' => 'nullable|exists:clusters,id',
            'ratepayer.paymentzone_id' => 'required|exists:payment_zones,id',
            'ratepayer.ratepayer_name' => 'required|string|max:100',
            'ratepayer.ratepayer_address' => 'required|string|max:255',
            'ratepayer.consumer_no' => 'required|string|max:50',
            'ratepayer.longitude' => 'required|numeric|between:-180,180',
            'ratepayer.latitude' => 'required|numeric|between:-90,90',
            'ratepayer.mobile_no' => 'required|digits:10',
            'ratepayer.landmark' => 'nullable|string|max:100',
            'ratepayer.whatsapp_no' => 'nullable|digits:10',
            'ratepayer.bill_date' => 'required|date|after_or_equal:entity.verification_date',
            'ratepayer.opening_demand' => 'required|numeric|min:0',
            'ratepayer.monthly_demand' => 'required|numeric|min:0',
        ];
    }
}
