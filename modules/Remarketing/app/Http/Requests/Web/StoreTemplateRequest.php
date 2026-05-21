<?php

namespace Modules\Remarketing\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class StoreTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('remarketing.templates.create');
    }

    public function rules(): array
    {
        return [
            'store_id' => ['nullable', 'integer', 'exists:remarketing_stores,id'],
            'visibility' => ['required', 'string', 'in:store,user,global'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:campaign,automation,transactional'],
            'subject' => ['nullable', 'string', 'max:255'],
            'preheader' => ['nullable', 'string', 'max:255'],
            'html_content' => ['required', 'string'],
            'json_content' => ['nullable', 'array'],
            'layout_id' => ['nullable', 'integer', 'exists:mailer_layouts,id'],
            'translations' => ['nullable', 'array'],
            'translations.*.lang_id' => ['required_with:translations', 'integer', 'exists:langs,id'],
            'translations.*.subject' => ['nullable', 'string', 'max:255'],
            'translations.*.preheader' => ['nullable', 'string', 'max:255'],
            'translations.*.content' => ['nullable', 'string'],
        ];
    }

    public function attributes(): array
    {
        return [
            'visibility' => 'visibilidad',
            'name' => 'nombre',
            'type' => 'tipo',
            'subject' => 'asunto',
            'html_content' => 'contenido HTML',
        ];
    }
}
