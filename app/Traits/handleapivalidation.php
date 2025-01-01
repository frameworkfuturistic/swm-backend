<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;

trait HandleApiValidation
{
    /**
     * Handle failed validation attempt.
     *
     * @return void
     *
     * @throws \Illuminate\Http\Exceptions\HttpResponseException
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            format_response(
                'validation error',
                $validator->errors()->all(),
                Response::HTTP_UNPROCESSABLE_ENTITY
            )
        );
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array
     */
    protected function getCustomMessages()
    {
        return [
            'required' => 'The :attribute field is required.',
            'integer' => 'The :attribute must be an integer.',
            'exists' => 'The selected :attribute is invalid.',
        ];
    }
}
