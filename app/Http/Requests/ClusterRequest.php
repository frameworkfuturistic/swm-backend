<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClusterRequest extends FormRequest
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
            'appliedtcId' => 'nullable|exists:users,id',
            'clusterName' => [
                'required',
                'string',
                'max:60',
                Rule::unique('clusters', 'cluster_name')
                    ->where('ulb_id', $this->input('ulb_id'))
                    ->ignore($this->route('id'), 'id'), // Exclude the current record by ID
            ],

            // 'clusterName' => 'required|string|max:60',
            // 'clusterName' => 'required|string|max:60|unique:clusters,cluster_name,NULL,id,ulb_id,'.$this->input('ulb_id'),
            'clusterAddress' => 'nullable|string|max:255',
            'landmark' => 'nullable|string|max:100',
            'pincode' => 'nullable|string|max:6',
            'clusterType' => 'required|in:Apartment,Building,Govt Institution,Colony,Other,None',
            'mobileNo' => 'nullable|string|max:12',
            'whatsappNo' => 'nullable|string|max:12',
            'longitude' => 'nullable|numeric',
            'latitude' => 'nullable|numeric',
            'inclusionDate' => 'nullable|date',
            'verificationDate' => 'nullable|date',
        ];
    }

    public function messages()
    {
        return [
            'clusterName.required' => 'The cluster name is required.',
            'clusterName.unique' => 'The cluster name must be unique for the given ULB.',
        ];
    }
}
