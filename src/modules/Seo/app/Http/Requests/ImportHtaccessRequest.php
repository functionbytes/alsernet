<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportHtaccessRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('Seo.redirects.create') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'htaccess_content' => ['required', 'string', 'max:500000'],
            'skip_existing' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'htaccess_content.required' => 'Debe pegar el contenido del archivo .htaccess.',
            'htaccess_content.max' => 'El contenido es demasiado largo (máx. 500 KB).',
        ];
    }
}
