<?php

namespace Modules\Campaign\Http\Requests\EmailTemplate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Gate;
use Modules\Campaign\Dto\PageTemplate\RenamePageTemplateDto;
use Modules\Campaign\Models\Template\SystemEmailTemplate;

class RenameEmailTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $uid = $this->route('uid');
        $tpl = $uid ? SystemEmailTemplate::findByUid($uid) : null;

        if (! $tpl) {
            return false;
        }

        return Gate::allows('update', $tpl);
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
            'name.required' => trans('campaign::email-templates.errors.name_required'),
        ];
    }

    public function toDto(string $uid): RenamePageTemplateDto
    {
        return RenamePageTemplateDto::fromRequest($uid, $this->validated());
    }
}
