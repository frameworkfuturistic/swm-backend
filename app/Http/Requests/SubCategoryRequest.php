<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SubCategoryRequest extends FormRequest
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
            'categoryId' => 'nullable|exists:categories,id',
            'subCategory' => [
                'required',
                'string',
                'max:50',
                Rule::unique('sub_categories', 'sub_category')
                    ->where('category_id', $this->input('categoryId'))
                    ->ignore($this->route('id'), 'id'), // Exclude the current record by ID
            ],
        ];
    }
}
