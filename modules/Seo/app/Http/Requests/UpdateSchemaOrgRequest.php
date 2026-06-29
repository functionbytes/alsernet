<?php

namespace Modules\Seo\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSchemaOrgRequest extends FormRequest
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
            'schema_type' => ['nullable', 'string', 'max:50', 'in:Article,Product,FAQPage,Recipe,Event,HowTo,WebPage'],
            'schema_custom' => [
                'nullable',
                'string',
                'max:50000',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (empty($value)) {
                        return;
                    }

                    $decoded = json_decode($value, true);

                    if (json_last_error() !== JSON_ERROR_NONE) {
                        $fail('El schema debe ser JSON válido: '.json_last_error_msg());

                        return;
                    }

                    if (! is_array($decoded)) {
                        $fail('El schema debe ser un objeto JSON.');

                        return;
                    }

                    if (! isset($decoded['@context']) || $decoded['@context'] !== 'https://schema.org') {
                        $fail('El schema debe incluir `"@context": "https://schema.org"`.');
                    }

                    if (! isset($decoded['@type'])) {
                        $fail('El schema debe incluir `@type` (ej: "Article").');
                    }
                },
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'schema_type.in' => 'Tipo de schema no soportado.',
            'schema_custom.max' => 'El JSON es demasiado grande (máx. 50 KB).',
        ];
    }
}
