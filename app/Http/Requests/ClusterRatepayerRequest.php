<?php

namespace App\Http\Requests;

use App\Traits\HandleApiValidation;
use Illuminate\Foundation\Http\FormRequest;

class ClusterRatepayerRequest extends FormRequest
{
    use HandleApiValidation;

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
            // 'cluster.appliedtcId' => 'nullable|exists:users,id',
            'cluster.wardId' => 'nullable|exists:wards,id',
            'cluster.clusterName' => 'required|string|max:60|unique:clusters,cluster_name,NULL,id,ulb_id,'.$this->ulb_id,
            'cluster.clusterAddress' => 'nullable|string|max:255',
            'cluster.landmark' => 'nullable|string|max:100',
            'cluster.pincode' => 'nullable|string|max:6',
            'cluster.clusterType' => 'required|in:Apartment,Building,Govt Institution,Colony,Other,None',
            'cluster.mobileNo' => 'nullable|string|max:12',
            'cluster.whatsappNo' => 'nullable|string|max:12',
            // 'cluster.longitude' => 'nullable|numeric',
            // 'cluster.latitude' => 'nullable|numeric',
            'cluster.inclusionDate' => 'nullable|date',
            // 'cluster.verificationDate' => 'nullable|date',

            // 'ratepayer.clusterId' => 'nullable|exists:clusters,id',
            'ratepayer.wardId' => 'nullable|exists:wards,id',
            'ratepayer.subcategoryId' => 'nullable|exists:sub_categories,id',
            'ratepayer.paymentzoneId' => 'required|exists:payment_zones,id',
            'ratepayer.ratepayerName' => 'required|string|max:100',
            'ratepayer.ratepayerAddress' => 'required|string|max:255',
            'ratepayer.consumerNo' => 'required|string|max:50',
            'ratepayer.longitude' => 'required|numeric|between:-180,180',
            'ratepayer.latitude' => 'required|numeric|between:-90,90',
            'ratepayer.mobileNo' => 'required|digits:10',
            'ratepayer.landmark' => 'nullable|string|max:100',
            'ratepayer.whatsappNo' => 'nullable|digits:10',
            // 'ratepayer.billDate' => 'nullable|max:11',
            // 'ratepayer.openingDemand' => 'required|numeric|min:0',
            'ratepayer.monthlyDemand' => 'required|numeric|min:0',
        ];
    }
}
