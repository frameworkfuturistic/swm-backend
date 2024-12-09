<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentZoneRequest extends FormRequest
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
            'paymentZone' => [
                'required',
                'string',
                'max:60',
                Rule::unique('payment_zones', 'payment_zone')
                    ->where('ulb_id', $this->input('ulb_id'))
                    ->ignore($this->route('id'), 'id'), // Exclude the current record by ID
            ],
            'coordinates' => 'required|array', // Must be an array
            'coordinates.*' => 'array|min:2|max:2', // Each coordinate pair must be an array with exactly 2 elements (lat, lng)
            'coordinates.*.0' => 'numeric|between:-90,90', // Latitude validation
            'coordinates.*.1' => 'numeric|between:-180,180', // Longitude validation
            'description' => 'required|string|max:250', // Description validation
        ];
    }
}
