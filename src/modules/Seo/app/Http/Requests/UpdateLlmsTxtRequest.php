<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLlmsTxtRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.llms.update') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'llms_txt' => ['nullable', 'string', 'max:50000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'llms_txt.max' => 'El contenido es demasiado largo (máx. 50 KB).',
        ];
    }
}
