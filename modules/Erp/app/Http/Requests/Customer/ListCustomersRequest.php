<?php

namespace Modules\Erp\Http\Requests\Customer;

class ListCustomersRequest extends ErpApiRequest
{
    public function rules(): array
    {
        return [
            'limit' => 'nullable|integer',
            'offset' => 'nullable|integer|min:0',
            'id' => 'nullable|integer|min:1',
            'cif' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'surnames' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:50',
            // Llega tal cual a TO_DATE(?, 'YYYY-MM-DD'): un formato distinto
            // era un ORA-01861 y un 500 en vez de un 422.
            'birth_date' => 'nullable|date_format:Y-m-d',
            // El formato MM-DD[,MM-DD] lo sigue validando el controlador.
            'birthday' => 'nullable|string|max:100',
        ];
    }
}
