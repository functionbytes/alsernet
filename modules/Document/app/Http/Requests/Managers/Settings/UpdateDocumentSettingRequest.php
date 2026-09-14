<?php

namespace Modules\Document\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDocumentSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('theme')->check() && auth('theme')->user()->can('manage_document_settings');
    }

    public function rules(): array
    {
        return [
            // Email Configuration Settings
            'documents.enable_initial_request' => 'boolean',
            'documents.initial_request_subject' => 'required_if:documents.enable_initial_request,true|string|max:255',
            'documents.initial_request_message' => 'required_if:documents.enable_initial_request,true|string|max:2000',
            'documents.enable_missing_docs' => 'boolean',
            'documents.missing_docs_subject' => 'required_if:documents.enable_missing_docs,true|string|max:255',
            'documents.missing_docs_message' => 'required_if:documents.enable_missing_docs,true|string|max:2000',
            'documents.enable_reminder' => 'boolean',
            'documents.reminder_days' => 'required_if:documents.enable_reminder,true|integer|min:1|max:30',
            'documents.reminder_subject' => 'required_if:documents.enable_reminder,true|string|max:255',
            'documents.reminder_message' => 'required_if:documents.enable_reminder,true|string|max:2000',
            'documents.enable_approval_email' => 'boolean',
            'documents.approval_subject' => 'required_if:documents.enable_approval_email,true|string|max:255',
            'documents.enable_rejection_email' => 'boolean',
            'documents.rejection_subject' => 'required_if:documents.enable_rejection_email,true|string|max:255',
            'documents.enable_completion_email' => 'boolean',
            'documents.completion_subject' => 'required_if:documents.enable_completion_email,true|string|max:255',

            // SLA Configuration Settings
            'documents.sla_enabled' => 'boolean',
            'documents.default_upload_time' => 'required|integer|min:60|max:43200',
            'documents.default_review_time' => 'required|integer|min:60|max:43200',
            'documents.default_approval_time' => 'required|integer|min:60|max:43200',
            'documents.sla_enable_escalation' => 'boolean',
            'documents.sla_escalation_threshold' => 'required_if:documents.sla_enable_escalation,true|integer|min:1|max:100',
            'documents.sla_business_hours_only' => 'boolean',

            // General Document Settings
            'documents.require_documents_confirmation' => 'boolean',
            'documents.allow_bulk_upload' => 'boolean',
            'documents.max_file_size' => 'required|integer|min:1|max:100',
            'documents.allowed_file_types' => 'required|string',
            'documents.send_confirmation_email' => 'boolean',
            'documents.document_expiration_days' => 'required|integer|min:1|max:365',
            'documents.support_email' => 'required|email|max:255',
            'documents.support_phone' => 'required|string|max:20',
        ];
    }

    public function messages(): array
    {
        return [
            'documents.enable_initial_request.boolean' => 'El campo debe ser verdadero o falso',
            'documents.initial_request_subject.required_if' => 'El asunto es requerido cuando está habilitado',
            'documents.initial_request_subject.max' => 'El asunto no puede exceder 255 caracteres',
            'documents.initial_request_message.required_if' => 'El mensaje es requerido cuando está habilitado',
            'documents.initial_request_message.max' => 'El mensaje no puede exceder 2000 caracteres',

            'documents.reminder_days.min' => 'Debe ser al menos 1 día',
            'documents.reminder_days.max' => 'No puede exceder 30 días',

            'documents.default_upload_time.min' => 'Tiempo mínimo: 1 minuto',
            'documents.default_upload_time.max' => 'Tiempo máximo: 30 días',
            'documents.default_review_time.min' => 'Tiempo mínimo: 1 minuto',
            'documents.default_review_time.max' => 'Tiempo máximo: 30 días',
            'documents.default_approval_time.min' => 'Tiempo mínimo: 1 minuto',
            'documents.default_approval_time.max' => 'Tiempo máximo: 30 días',

            'documents.sla_escalation_threshold.min' => 'El umbral debe ser entre 1% y 100%',
            'documents.sla_escalation_threshold.max' => 'El umbral debe ser entre 1% y 100%',

            'documents.max_file_size.min' => 'Tamaño mínimo: 1 MB',
            'documents.max_file_size.max' => 'Tamaño máximo: 100 MB',

            'documents.document_expiration_days.min' => 'Mínimo 1 día',
            'documents.document_expiration_days.max' => 'Máximo 365 días',

            'documents.support_email.email' => 'Ingrese un email válido',
            'documents.support_phone.required' => 'El teléfono es requerido',
        ];
    }

    public function attributes(): array
    {
        return [
            'documents.enable_initial_request' => 'Email de solicitud inicial',
            'documents.initial_request_subject' => 'Asunto de solicitud inicial',
            'documents.initial_request_message' => 'Mensaje de solicitud inicial',
            'documents.enable_missing_docs' => 'Email de documentos faltantes',
            'documents.missing_docs_subject' => 'Asunto de documentos faltantes',
            'documents.missing_docs_message' => 'Mensaje de documentos faltantes',
            'documents.enable_reminder' => 'Recordatorios automáticos',
            'documents.reminder_days' => 'Días para recordatorio',
            'documents.reminder_subject' => 'Asunto de recordatorio',
            'documents.reminder_message' => 'Mensaje de recordatorio',
            'documents.enable_approval_email' => 'Email de aprobación',
            'documents.approval_subject' => 'Asunto de aprobación',
            'documents.enable_rejection_email' => 'Email de rechazo',
            'documents.rejection_subject' => 'Asunto de rechazo',
            'documents.enable_completion_email' => 'Email de finalización',
            'documents.completion_subject' => 'Asunto de finalización',
            'documents.sla_enabled' => 'SLA habilitado',
            'documents.default_upload_time' => 'Tiempo de carga',
            'documents.default_review_time' => 'Tiempo de revisión',
            'documents.default_approval_time' => 'Tiempo de aprobación',
            'documents.sla_enable_escalation' => 'Escalación SLA',
            'documents.sla_escalation_threshold' => 'Umbral de escalación',
            'documents.sla_business_hours_only' => 'Solo horas de negocio',
            'documents.require_documents_confirmation' => 'Confirmar documentos',
            'documents.allow_bulk_upload' => 'Carga masiva',
            'documents.max_file_size' => 'Tamaño máximo de archivo',
            'documents.allowed_file_types' => 'Tipos de archivo permitidos',
            'documents.send_confirmation_email' => 'Email de confirmación',
            'documents.document_expiration_days' => 'Días de expiración',
            'documents.support_email' => 'Email de soporte',
            'documents.support_phone' => 'Teléfono de soporte',
        ];
    }
}
