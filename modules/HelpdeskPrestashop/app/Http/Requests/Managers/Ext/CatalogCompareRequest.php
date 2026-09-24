<?php

namespace Modules\HelpdeskPrestashop\Http\Requests\Managers\Ext;

use Illuminate\Foundation\Http\FormRequest;

class CatalogCompareRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        $max = (int) config('helpdeskprestashop.ext.catalog.compare_max', 3);

        return [
            'product_ids' => ['required', 'array', 'min:2', 'max:'.$max],
            'product_ids.*' => ['integer', 'min:1', 'distinct'],
        ];
    }

    public function messages(): array
    {
        return [
            'product_ids.min' => 'Elige al menos dos productos para comparar.',
            'product_ids.max' => 'Como mucho se comparan tres productos.',
            'product_ids.*.distinct' => 'Ese producto ya está en la comparación.',
        ];
    }
}
