<?php

namespace Modules\HelpdeskTickets\Services;

use Illuminate\Validation\Rule;
use Modules\Core\Rules\ValidMimeMagicBytes;
use Modules\Helpdesk\Models\Setting;
use Modules\Helpdesk\Services\HelpdeskSettings;
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
        $settings = app(HelpdeskSettings::class);

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
                    'max:'.$settings->attachmentMaxKilobytes(),
                    'mimes:'.implode(',', $settings->attachmentExtensions()),
                    new ValidMimeMagicBytes($settings->attachmentMimeTypes()),
                    Rule::prohibitedIf(fn () => ! filter_var(
                        Setting::get('tickets.guest_file_upload_enable', true),
                        FILTER_VALIDATE_BOOLEAN,
                    )),
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
