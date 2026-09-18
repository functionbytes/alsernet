<?php

namespace Modules\HelpdeskTickets\Services;

use Modules\HelpdeskTickets\Models\TicketCategory;

class TicketCategoryValidationBuilder
{
    /**
     * Build validation rules and attribute labels for a public ticket form submission.
     *
     * @return array{rules: array<string, array<string>>, attributes: array<string, string>}
     */
    public function buildForSubmission(TicketCategory $category): array
    {
        $rules = [
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'customer_email' => ['required', 'email'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'website_token' => ['nullable', 'string'],
        ];

        $attributes = [
            'subject' => 'asunto',
            'description' => 'descripción',
            'customer_email' => 'correo electrónico',
            'customer_name' => 'nombre',
        ];

        $fields = $category->fields()
            ->where('is_visible', true)
            ->ordered()
            ->get();

        foreach ($fields as $field) {
            $fieldRules = $field->is_required ? ['required'] : ['nullable'];

            match ($field->type) {
                'email' => $fieldRules[] = 'email',
                'phone' => $fieldRules[] = 'regex:/^\+?[\d\s\-\(\)\.]+$/',
                'number' => $fieldRules[] = 'numeric',
                'date' => $fieldRules[] = 'date',
                'file' => array_push(
                    $fieldRules,
                    'file',
                    'max:'.config('helpdesk.attachments.max_size', 10240),
                    'mimes:'.implode(',', config('helpdesk.attachments.allowed_extensions', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'txt', 'zip'])),
                ),
                'select', 'radio' => $field->options
                    ? $fieldRules[] = 'in:'.implode(',', array_column($field->options, 'value'))
                    : null,
                default => null,
            };

            $rules[$field->key] = $fieldRules;
            $attributes[$field->key] = $field->label;
        }

        return ['rules' => $rules, 'attributes' => $attributes];
    }
}
