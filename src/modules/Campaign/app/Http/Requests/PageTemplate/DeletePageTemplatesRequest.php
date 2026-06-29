<?php

namespace Modules\Campaign\Http\Requests\PageTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\DeletePageTemplatesDto;
use Modules\Campaign\Models\Template\PageTemplate;

class DeletePageTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        // La autorización por item se re-chequea en el servicio.
        return Gate::allows('delete', new PageTemplate);
    }

    public function rules(): array
    {
        return [
            'uids' => 'required',
        ];
    }

    public function toDto(): DeletePageTemplatesDto
    {
        return DeletePageTemplatesDto::fromRequest($this->validated());
    }
}
