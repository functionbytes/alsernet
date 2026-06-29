<?php

namespace Modules\Attention\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use Modules\Core\Models\Lang;
use Modules\Mailer\Models\MailerVariable;
use Modules\Mailer\Models\MailerVariableLang;

/**
 * Seeds email template variables specific to the Attention (peticiones) module.
 * These variables are used in attention email templates for variable replacement.
 */
class AttentionEmailVariablesSeeder extends Seeder
{
    public function run(): void
    {
        $langs = Lang::where('available', true)->get();

        if ($langs->isEmpty()) {
            $this->command->warn('No languages found - skipping attention variable translations');

            return;
        }

        $variables = [
            // ========================
            // CITIZEN / CUSTOMER
            // ========================
            [
                'key' => 'customer_name',
                'name' => 'CUSTOMER_NAME',
                'description' => 'Nombre completo del ciudadano',
                'example_value' => 'Juan Garcia Lopez',
                'category' => 'attention_customer',
            ],
            [
                'key' => 'customer_firstname',
                'name' => 'CUSTOMER_FIRSTNAME',
                'description' => 'Primer nombre del ciudadano',
                'example_value' => 'Juan',
                'category' => 'attention_customer',
            ],
            [
                'key' => 'customer_lastname',
                'name' => 'CUSTOMER_LASTNAME',
                'description' => 'Apellidos del ciudadano',
                'example_value' => 'Garcia Lopez',
                'category' => 'attention_customer',
            ],
            [
                'key' => 'customer_email',
                'name' => 'CUSTOMER_EMAIL',
                'description' => 'Email del ciudadano',
                'example_value' => 'juan.garcia@example.com',
                'category' => 'attention_customer',
            ],
            [
                'key' => 'customer_phone',
                'name' => 'CUSTOMER_PHONE',
                'description' => 'Telefono del ciudadano',
                'example_value' => '+57 300 123 4567',
                'category' => 'attention_customer',
            ],
            [
                'key' => 'customer_dni',
                'name' => 'CUSTOMER_DNI',
                'description' => 'Documento de identidad del ciudadano',
                'example_value' => '12345678',
                'category' => 'attention_customer',
            ],

            // ========================
            // ATTENTION / peticiones INFO
            // ========================
            [
                'key' => 'attention_radicado',
                'name' => 'ATTENTION_RADICADO',
                'description' => 'Numero de radicado del peticiones',
                'example_value' => 'peticiones-2026-000123',
                'category' => 'attention_info',
            ],
            [
                'key' => 'attention_uid',
                'name' => 'ATTENTION_UID',
                'description' => 'Identificador unico del peticiones',
                'example_value' => 'df8633a9-20dd-46fa-80c6-eba8a139f8a3',
                'category' => 'attention_info',
            ],
            [
                'key' => 'attention_subject',
                'name' => 'ATTENTION_SUBJECT',
                'description' => 'Asunto de la solicitud peticiones',
                'example_value' => 'Solicitud de informacion sobre tramites',
                'category' => 'attention_info',
            ],
            [
                'key' => 'attention_description',
                'name' => 'ATTENTION_DESCRIPTION',
                'description' => 'Descripcion detallada de la solicitud',
                'example_value' => 'Solicito informacion sobre los tramites necesarios para...',
                'category' => 'attention_info',
            ],
            [
                'key' => 'attention_status',
                'name' => 'ATTENTION_STATUS',
                'description' => 'Estado actual del peticiones',
                'example_value' => 'En Proceso',
                'category' => 'attention_info',
            ],
            [
                'key' => 'attention_type',
                'name' => 'ATTENTION_TYPE',
                'description' => 'Tipo de peticiones (Peticion, Queja, Reclamo, etc.)',
                'example_value' => 'Peticion',
                'category' => 'attention_info',
            ],
            [
                'key' => 'attention_category',
                'name' => 'ATTENTION_CATEGORY',
                'description' => 'Categoria de la solicitud',
                'example_value' => 'Servicios publicos',
                'category' => 'attention_info',
            ],

            // ========================
            // ASSIGNMENT / DEPARTMENT
            // ========================
            [
                'key' => 'department_name',
                'name' => 'DEPARTMENT_NAME',
                'description' => 'Nombre del departamento encargado',
                'example_value' => 'Atencion al Ciudadano',
                'category' => 'attention_assignment',
            ],
            [
                'key' => 'assigned_user_name',
                'name' => 'ASSIGNED_USER_NAME',
                'description' => 'Nombre del funcionario asignado',
                'example_value' => 'Maria Rodriguez',
                'category' => 'attention_assignment',
            ],
            [
                'key' => 'assigned_user_email',
                'name' => 'ASSIGNED_USER_EMAIL',
                'description' => 'Email del funcionario asignado',
                'example_value' => 'maria.rodriguez@entidad.gov.co',
                'category' => 'attention_assignment',
            ],

            // ========================
            // DATES
            // ========================
            [
                'key' => 'created_date',
                'name' => 'CREATED_DATE',
                'description' => 'Fecha de radicacion del peticiones',
                'example_value' => '10/02/2026',
                'category' => 'attention_dates',
            ],
            [
                'key' => 'created_datetime',
                'name' => 'CREATED_DATETIME',
                'description' => 'Fecha y hora de radicacion',
                'example_value' => '10/02/2026 14:30',
                'category' => 'attention_dates',
            ],
            [
                'key' => 'resolved_date',
                'name' => 'RESOLVED_DATE',
                'description' => 'Fecha de resolucion del peticiones',
                'example_value' => '25/02/2026',
                'category' => 'attention_dates',
            ],
            [
                'key' => 'resolution_date',
                'name' => 'RESOLUTION_DATE',
                'description' => 'Fecha y hora de la resolucion',
                'example_value' => '25/02/2026 10:00',
                'category' => 'attention_dates',
            ],

            // ========================
            // RESOLUTION
            // ========================
            [
                'key' => 'resolution',
                'name' => 'RESOLUTION',
                'description' => 'Texto de la respuesta/resolucion oficial',
                'example_value' => 'Se resuelve favorablemente su solicitud. Los documentos solicitados seran enviados a su correo electronico en un plazo de 3 dias habiles.',
                'category' => 'attention_resolution',
            ],
            [
                'key' => 'response_type',
                'name' => 'RESPONSE_TYPE',
                'description' => 'Medio de respuesta utilizado',
                'example_value' => 'Correo electronico',
                'category' => 'attention_resolution',
            ],

            // ========================
            // URLS
            // ========================
            [
                'key' => 'tracking_url',
                'name' => 'TRACKING_URL',
                'description' => 'URL para consultar el estado del peticiones',
                'example_value' => 'https://example.com/peticiones/tracking/peticiones-2026-000123',
                'category' => 'attention_links',
            ],
            [
                'key' => 'attention_url',
                'name' => 'ATTENTION_URL',
                'description' => 'URL directa al detalle del peticiones',
                'example_value' => 'https://example.com/attentions/show/abc-123',
                'category' => 'attention_links',
            ],
            [
                'key' => 'portal_url',
                'name' => 'PORTAL_URL',
                'description' => 'URL del portal de atencion al ciudadano',
                'example_value' => 'https://example.com',
                'category' => 'attention_links',
            ],
        ];

        $count = 0;
        foreach ($variables as $variable) {
            $mailerVariable = MailerVariable::updateOrCreate(
                ['key' => $variable['key'], 'module' => 'attention'],
                [
                    'uid' => (string) Str::ulid(),
                    'name' => $variable['name'],
                    'description' => $variable['description'],
                    'example_value' => $variable['example_value'],
                    'category' => $variable['category'],
                    'module' => 'attention',
                    'is_system' => false,
                    'is_enabled' => true,
                ]
            );

            foreach ($langs as $lang) {
                MailerVariableLang::updateOrCreate(
                    ['mailer_variable_id' => $mailerVariable->id, 'lang_id' => $lang->id],
                    [
                        'name' => $variable['name'],
                        'description' => $variable['description'],
                        'value' => $variable['example_value'],
                    ]
                );
            }

            $count++;
        }

        $this->command->info("Attention email variables seeded ({$count} variables, {$langs->count()} language(s))");
    }
}
