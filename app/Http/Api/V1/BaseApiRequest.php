<?php

namespace App\Http\Api\V1;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

abstract class BaseApiRequest extends FormRequest
{
    protected function failedValidation(Validator $validator): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Datos inválidos.',
                'errors' => $validator->errors()->toArray(),
                'code' => 'VALIDATION_ERROR',
            ], 422)
        );
    }

    protected function failedAuthorization(): never
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'No autorizado.',
                'code' => 'FORBIDDEN',
            ], 403)
        );
    }
}
