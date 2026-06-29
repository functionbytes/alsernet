<?php

namespace Modules\Campaign\Http\Requests\PageTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\RenamePageTemplateDto;
use Modules\Campaign\Models\Template\PageTemplate;

class RenamePageTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $uid = $this->route('uid');
        $pageTemplate = $uid ? PageTemplate::findByUid($uid) : null;

        if (! $pageTemplate) {
            return false;
        }

        return Gate::allows('update', $pageTemplate);
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => trans('campaign::page-templates.errors.name_required'),
        ];
    }

    public function toDto(string $uid): RenamePageTemplateDto
    {
        return RenamePageTemplateDto::fromRequest($uid, $this->validated());
    }
}
