<?php

namespace Modules\Forms\Services;

use Modules\Forms\Models\Form;

/**
 * Construye reglas de validación dinámicas para un submission público
 * basándose en los campos del formulario + su tipo + reglas custom.
 */
class FormValidationBuilder
{
    /**
     * @return array{rules: array<string, array<int, string>>, attributes: array<string, string>}
     */
    public function buildForSubmission(Form $form): array
    {
        $rules = ['_hp' => ['nullable', 'size:0']];
        $attributes = [];

        foreach ($form->fields()->visible()->ordered()->get() as $field) {
            if ($field->isLayoutField()) {
                continue;
            }

            $fieldRules = $field->is_required ? ['required'] : ['nullable'];

            match ($field->type) {
                'email' => $fieldRules[] = 'email',
                'url' => $fieldRules[] = 'url',
                'number', 'slider', 'rating', 'nps' => $fieldRules[] = 'numeric',
                'date' => $fieldRules[] = 'date',
                'file' => array_push($fieldRules, 'file', 'max:'.(config('forms.max_file_size_mb', 10) * 1024)),
                'signature' => array_push($fieldRules, 'string', 'max:'.(int) config('forms.signature.max_length', 524288), 'regex:/^data:image\/(png|jpeg|jpg);base64,[A-Za-z0-9+\/=]+$/'),
                default => null,
            };

            foreach ($field->validation_rules ?? [] as $rule) {
                $fieldRules[] = $rule;
            }

            $rules[$field->key] = $fieldRules;
            $attributes[$field->key] = $field->label;
        }

        return ['rules' => $rules, 'attributes' => $attributes];
    }
}
