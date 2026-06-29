<?php

namespace Modules\Campaign\Http\Requests\EmailTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\DeletePageTemplatesDto;
use Modules\Campaign\Models\Template\SystemEmailTemplate;

class DeleteEmailTemplatesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Gate::allows('delete', new SystemEmailTemplate);
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
