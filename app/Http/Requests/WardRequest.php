<?php

namespace App\Http\Requests;

use App\Traits\HandleApiValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WardRequest extends FormRequest
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
            'remarks' => 'nullable|string',
            'wardName' => [
                'required',
                'string',
                'max:60',
                Rule::unique('wards', 'ward_name')
                    ->where('ulb_id', $this->input('ulb_id'))
                    ->ignore($this->route('id'), 'id'), // Exclude the current record by ID
            ],
            //
        ];
    }
}
