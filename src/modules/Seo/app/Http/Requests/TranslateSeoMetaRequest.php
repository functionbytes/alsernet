<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TranslateSeoMetaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.metas.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'target_lang' => ['required', 'string', 'in:EN,ES,FR,DE,IT,PT,NL,PL,RU,JA,ZH'],
            'fields' => ['required', 'array', 'min:1'],
            'fields.*' => ['in:title,description,og_title,og_description,keywords'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'target_lang.required' => 'El idioma de destino es obligatorio.',
            'target_lang.in' => 'Idioma de destino no soportado.',
            'fields.required' => 'Debe seleccionar al menos un campo a traducir.',
        ];
    }
}
