<?php

namespace Modules\Erp\Http\Requests\Customer;

class UpdateLopdRequest extends ErpApiRequest
{
    public function rules(): array
    {
        return [
            'email' => 'required|email',
            'accepted_at' => 'required|date',
            'no_commercial_info' => 'required|boolean',
            'no_data_to_third_parties' => 'required|boolean',
        ];
    }
}
