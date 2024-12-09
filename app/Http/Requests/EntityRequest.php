<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EntityRequest extends FormRequest
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
            'clusterId' => 'nullable|exists:clusters,id',
            'subcategoryId' => 'required|exists:sub_categories,id',
            'verifiedbyId' => 'required|exists:users,id',
            'appliedtcId' => 'required|exists:users,id',
            'holdingNo' => 'required|string|max:50',
            'entityName' => 'required|string|max:100',
            'entityAddress' => 'required|string|max:255',
            'pincode' => 'required|digits:6',
            'mobileNo' => 'required|digits:10',
            'landmark' => 'nullable|string|max:100',
            'whatsappNo' => 'nullable|digits:10',
            'longitude' => 'required|numeric|between:-180,180',
            'latitude' => 'required|numeric|between:-90,90',
            'inclusionDate' => 'required|date|before_or_equal:today',
            'verificationDate' => 'nullable|date|after_or_equal:entity.inclusion_date',
            'openingDemand' => 'required|numeric|min:0',
            'monthlyDemand' => 'required|numeric|min:0',
            'usageType' => 'required|in:Residential,Commercial,Industrial,Institutional',
            'status' => 'required|in:pending,verified,rejected',
        ];
    }
}
