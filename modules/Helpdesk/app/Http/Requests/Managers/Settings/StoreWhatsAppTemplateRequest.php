<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Modules\Helpdesk\Models\Campaigns\WhatsAppTemplate;

class StoreWhatsAppTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.whatsapp-templates.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:512', 'regex:/^[a-z0-9_]+$/', 'unique:helpdesk.helpdesk_whatsapp_templates,external_id'],
            'language' => ['required', 'string', 'in:es,en,en_US,en_GB,pt_BR,pt_PT,fr,de,it'],
            'category' => ['required', 'string', 'in:'.implode(',', WhatsAppTemplate::CATEGORIES)],
            'body' => ['required', 'string', 'max:1024'],
            // Ejemplos de cada {{n}} del body, separados por coma — Meta los exige
            // cuando la plantilla tiene variables.
            'body_examples' => ['nullable', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre técnico es obligatorio.',
            'name.regex' => 'El nombre técnico solo puede tener minúsculas, números y guion bajo (ej. bienvenida_cliente_v1).',
            'name.unique' => 'Ya existe una plantilla local con ese nombre técnico.',
            'language.required' => 'El idioma es obligatorio.',
            'language.in' => 'El idioma seleccionado no es válido.',
            'category.required' => 'La categoría es obligatoria.',
            'category.in' => 'La categoría debe ser: utility, marketing o authentication.',
            'body.required' => 'El cuerpo del mensaje es obligatorio.',
            'body.max' => 'El cuerpo no puede superar los 1024 caracteres (límite de Meta).',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre técnico',
            'language' => 'idioma',
            'category' => 'categoría',
            'body' => 'cuerpo del mensaje',
            'body_examples' => 'ejemplos de variables',
        ];
    }

    /**
     * Meta exige exactamente un ejemplo por cada {{n}} del body cuando hay
     * variables — si el conteo no coincide, la API rechazaría la creación con
     * un error menos claro que este.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $body = (string) $this->input('body');
            preg_match_all('/\{\{\s*\d+\s*\}\}/', $body, $matches);
            $paramCount = count(array_unique($matches[0]));

            if ($paramCount === 0) {
                return;
            }

            $examples = $this->parsedBodyExamples();

            if (count($examples) !== $paramCount) {
                $validator->errors()->add(
                    'body_examples',
                    "El cuerpo tiene {$paramCount} variable(s) ({{n}}) — debes dar exactamente {$paramCount} ejemplo(s) separados por coma."
                );
            }
        });
    }

    /**
     * @return array<int, string>
     */
    public function parsedBodyExamples(): array
    {
        $raw = (string) $this->input('body_examples', '');

        if (trim($raw) === '') {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $raw)), fn ($v) => $v !== ''));
    }
}
