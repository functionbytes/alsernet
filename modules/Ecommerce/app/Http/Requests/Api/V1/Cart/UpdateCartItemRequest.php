<?php

namespace Modules\Ecommerce\Http\Requests\Api\V1\Cart;

use App\Http\Api\V1\BaseApiRequest;

class UpdateCartItemRequest extends BaseApiRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'qty' => ['required', 'integer', 'min:1', 'max:999'],
        ];
    }
}
