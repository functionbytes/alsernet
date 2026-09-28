<?php

namespace Modules\Erp\Http\Requests\Customer;

class UpsertCustomerRequest extends ErpApiRequest
{
    public function rules(): array
    {
        return [
            // `id` presente = UPDATE; el controlador responde 404 si no existe.
            'id' => 'sometimes|integer|min:1',
            'name' => 'required|string',
            'surnames' => 'required|string',
            'cif' => 'required|string',
            'email' => 'required|email',
            'lopd_accepted_at' => 'required|date',
            'no_commercial_info' => 'required|boolean',
            'no_data_to_third_parties' => 'required|boolean',
            'catalogs' => 'required',
            'contact_person' => 'nullable|string',
            'observations' => 'nullable|string',
            'birth_date' => 'nullable|date',
        ];
    }
}
