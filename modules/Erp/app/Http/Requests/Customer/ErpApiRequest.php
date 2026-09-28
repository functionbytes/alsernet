<?php

namespace Modules\Erp\Http\Requests\Customer;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Base de los FormRequest de la API /api/erp/*: la autenticación la resuelve
 * el middleware `erp.api-auth`, y el 422 mantiene el formato que ya consumen
 * los clientes ({success, error, errors}).
 */
abstract class ErpApiRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'success' => false,
            'error' => 'Validation failed',
            'errors' => $validator->errors(),
        ], 422));
    }
}
