<?php

namespace Modules\Helpdesk\Http\Requests\Managers\Settings;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFeaturesSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('helpdesk.settings.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'feature_email_enabled' => ['sometimes', 'boolean'],
            'feature_tickets_enabled' => ['sometimes', 'boolean'],
            'feature_schedule_enabled' => ['sometimes', 'boolean'],
            'feature_snooze_enabled' => ['sometimes', 'boolean'],
            'feature_assign_enabled' => ['sometimes', 'boolean'],
            'feature_tags_enabled' => ['sometimes', 'boolean'],
            'feature_search_enabled' => ['sometimes', 'boolean'],
            'feature_note_enabled' => ['sometimes', 'boolean'],
            'feature_csat_enabled' => ['sometimes', 'boolean'],
            'feature_merge_enabled' => ['sometimes', 'boolean'],
            'feature_move_team_enabled' => ['sometimes', 'boolean'],
            'feature_spam_enabled' => ['sometimes', 'boolean'],
            'feature_block_contact_enabled' => ['sometimes', 'boolean'],
            'feature_delete_conv_enabled' => ['sometimes', 'boolean'],
            'feature_forward_enabled' => ['sometimes', 'boolean'],
            'feature_preview_conv_enabled' => ['sometimes', 'boolean'],
            'feature_tab_customer360_enabled' => ['sometimes', 'boolean'],
            'feature_composer_hsm_enabled' => ['sometimes', 'boolean'],
            'feature_composer_note_enabled' => ['sometimes', 'boolean'],
            'feature_composer_attach_enabled' => ['sometimes', 'boolean'],
            'feature_composer_emoji_enabled' => ['sometimes', 'boolean'],
            'feature_composer_mention_enabled' => ['sometimes', 'boolean'],
            'feature_composer_canned_enabled' => ['sometimes', 'boolean'],
            'feature_composer_record_enabled' => ['sometimes', 'boolean'],
            'feature_composer_ai_enabled' => ['sometimes', 'boolean'],
            'feature_tab_assist_enabled' => ['sometimes', 'boolean'],
            'feature_rp_email_enabled' => ['sometimes', 'boolean'],
            'feature_rp_schedule_enabled' => ['sometimes', 'boolean'],
            'feature_rp_note_enabled' => ['sometimes', 'boolean'],
            'feature_rp_stats_enabled' => ['sometimes', 'boolean'],
            'feature_rp_status_enabled' => ['sometimes', 'boolean'],
            'feature_rp_tags_section_enabled' => ['sometimes', 'boolean'],
            'feature_rp_integrations_enabled' => ['sometimes', 'boolean'],
            'feature_tab_general_enabled' => ['sometimes', 'boolean'],
            'feature_tab_carts_enabled' => ['sometimes', 'boolean'],
            'feature_tab_files_enabled' => ['sometimes', 'boolean'],
            'feature_tab_tickets_enabled' => ['sometimes', 'boolean'],
            'feature_tab_document_enabled' => ['sometimes', 'boolean'],
            'feature_tab_previous_enabled' => ['sometimes', 'boolean'],
            'feature_tab_activity_enabled' => ['sometimes', 'boolean'],
            'feature_tab_technology_enabled' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.boolean' => 'El campo :attribute debe ser verdadero o falso.',
        ];
    }

    public function attributes(): array
    {
        return [
            'feature_email_enabled' => 'enviar email',
            'feature_tickets_enabled' => 'crear ticket',
            'feature_schedule_enabled' => 'agendar mensaje',
            'feature_snooze_enabled' => 'posponer conversación',
            'feature_assign_enabled' => 'asignar agente',
            'feature_tags_enabled' => 'etiquetar',
            'feature_search_enabled' => 'buscar en conversación',
            'feature_note_enabled' => 'añadir nota interna',
            'feature_csat_enabled' => 'encuesta CSAT',
            'feature_merge_enabled' => 'fusionar conversación',
            'feature_move_team_enabled' => 'mover a equipo',
            'feature_spam_enabled' => 'marcar spam',
            'feature_block_contact_enabled' => 'bloquear contacto',
            'feature_delete_conv_enabled' => 'eliminar conversación',
            'feature_forward_enabled' => 'reenviar',
            'feature_preview_conv_enabled' => 'conversaciones anteriores',
            'feature_tab_customer360_enabled' => 'pestaña cliente 360',
            'feature_composer_hsm_enabled' => 'plantillas HSM',
            'feature_composer_note_enabled' => 'nota interna (redactor)',
            'feature_composer_attach_enabled' => 'adjuntar archivos',
            'feature_composer_emoji_enabled' => 'emoji',
            'feature_composer_mention_enabled' => 'mencionar agente',
            'feature_composer_canned_enabled' => 'respuesta rápida',
            'feature_composer_record_enabled' => 'grabar audio',
            'feature_composer_ai_enabled' => 'sugerencia IA',
            'feature_tab_assist_enabled' => 'pestaña asistir',
            'feature_rp_email_enabled' => 'botón email (panel derecho)',
            'feature_rp_schedule_enabled' => 'botón agendar (panel derecho)',
            'feature_rp_note_enabled' => 'botón nota (panel derecho)',
            'feature_rp_stats_enabled' => 'estadísticas LTV',
            'feature_rp_status_enabled' => 'estado de la conversación',
            'feature_rp_tags_section_enabled' => 'sección etiquetas',
            'feature_rp_integrations_enabled' => 'sección integraciones',
            'feature_tab_general_enabled' => 'pestaña general',
            'feature_tab_carts_enabled' => 'pestaña carritos',
            'feature_tab_files_enabled' => 'pestaña archivos',
            'feature_tab_tickets_enabled' => 'pestaña tickets',
            'feature_tab_document_enabled' => 'pestaña documentación',
            'feature_tab_previous_enabled' => 'pestaña anteriores',
            'feature_tab_activity_enabled' => 'pestaña actividad',
            'feature_tab_technology_enabled' => 'pestaña tecnología',
        ];
    }
}
