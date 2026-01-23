<?php

namespace Modules\HelpdeskChat\Services;

use Modules\HelpdeskChat\Models\Contacts\Contact;
use Modules\HelpdeskChat\Models\Conversations\Conversation;

class EmailTemplateVariableService
{
    /**
     * Get all available variables by event type.
     */
    public static function getAvailableVariables(string $eventType): array
    {
        $variables = [
            // Always available
            'app_name' => 'Nombre de la aplicación',
            'app_url' => 'URL de la aplicación',
            'current_date' => 'Fecha actual',
            'current_time' => 'Hora actual',
        ];

        switch ($eventType) {
            case 'conversation_assigned':
            case 'conversation_status_changed':
            case 'conversation_created':
                $variables = array_merge($variables, self::getConversationVariables());
                break;

            case 'new_message':
                $variables = array_merge($variables, self::getMessageVariables());
                break;

            case 'contact_created':
                $variables = array_merge($variables, self::getContactVariables());
                break;

            case 'agent_mentioned':
                $variables = array_merge($variables, self::getMentionVariables());
                break;

            case 'csat_survey':
                $variables = array_merge($variables, self::getCsatVariables());
                break;

            default:
                $variables = array_merge($variables, self::getConversationVariables());
        }

        return $variables;
    }

    /**
     * Get conversation-related variables.
     */
    protected static function getConversationVariables(): array
    {
        return [
            'conversation_id' => 'ID de la conversación',
            'conversation_url' => 'URL de la conversación',
            'conversation_status' => 'Estado de la conversación',
            'conversation_priority' => 'Prioridad de la conversación',
            'contact_name' => 'Nombre del contacto',
            'contact_email' => 'Email del contacto',
            'contact_phone' => 'Teléfono del contacto',
            'inbox_name' => 'Nombre del canal',
            'assigned_agent_name' => 'Nombre del agente asignado',
            'assigned_agent_email' => 'Email del agente asignado',
            'team_name' => 'Nombre del equipo',
        ];
    }

    /**
     * Get message-related variables.
     */
    protected static function getMessageVariables(): array
    {
        return array_merge(self::getConversationVariables(), [
            'message_content' => 'Contenido del mensaje',
            'message_sender_name' => 'Nombre del remitente',
            'message_time' => 'Hora del mensaje',
        ]);
    }

    /**
     * Get contact-related variables.
     */
    protected static function getContactVariables(): array
    {
        return [
            'contact_name' => 'Nombre del contacto',
            'contact_email' => 'Email del contacto',
            'contact_phone' => 'Teléfono del contacto',
            'contact_city' => 'Ciudad del contacto',
            'contact_country' => 'País del contacto',
        ];
    }

    /**
     * Get mention-related variables.
     */
    protected static function getMentionVariables(): array
    {
        return array_merge(self::getConversationVariables(), [
            'mentioned_agent_name' => 'Nombre del agente mencionado',
            'mentioning_agent_name' => 'Nombre del agente que mencionó',
            'message_content' => 'Contenido del mensaje',
        ]);
    }

    /**
     * Get CSAT survey variables.
     */
    protected static function getCsatVariables(): array
    {
        return array_merge(self::getConversationVariables(), [
            'survey_url' => 'URL de la encuesta',
            'survey_token' => 'Token de la encuesta',
        ]);
    }

    /**
     * Render template with actual data.
     */
    public static function render(string $template, array $data): string
    {
        $rendered = $template;

        // Replace app variables
        $rendered = str_replace('{{app_name}}', config('app.name'), $rendered);
        $rendered = str_replace('{{app_url}}', config('app.url'), $rendered);
        $rendered = str_replace('{{current_date}}', now()->format('d/m/Y'), $rendered);
        $rendered = str_replace('{{current_time}}', now()->format('H:i'), $rendered);

        // Replace conversation variables
        if (isset($data['conversation'])) {
            $conversation = $data['conversation'];
            $rendered = self::replaceConversationVariables($rendered, $conversation);
        }

        // Replace contact variables
        if (isset($data['contact'])) {
            $contact = $data['contact'];
            $rendered = self::replaceContactVariables($rendered, $contact);
        }

        // Replace message variables
        if (isset($data['message'])) {
            $message = $data['message'];
            $rendered = str_replace('{{message_content}}', $message->content ?? '', $rendered);
            $rendered = str_replace('{{message_sender_name}}', $message->sender->name ?? '', $rendered);
            $rendered = str_replace('{{message_time}}', $message->created_at?->format('H:i') ?? '', $rendered);
        }

        // Replace agent variables
        if (isset($data['agent'])) {
            $agent = $data['agent'];
            $rendered = str_replace('{{mentioned_agent_name}}', $agent->name ?? '', $rendered);
            $rendered = str_replace('{{assigned_agent_name}}', $agent->name ?? '', $rendered);
            $rendered = str_replace('{{assigned_agent_email}}', $agent->email ?? '', $rendered);
        }

        // Replace custom data
        foreach ($data as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $rendered = str_replace("{{{$key}}}", $value, $rendered);
            }
        }

        return $rendered;
    }

    /**
     * Replace conversation-specific variables.
     */
    protected static function replaceConversationVariables(string $template, Conversation $conversation): string
    {
        $replacements = [
            '{{conversation_id}}' => $conversation->id,
            '{{conversation_url}}' => route('admin.conversation.show', $conversation),
            '{{conversation_status}}' => ucfirst($conversation->status),
            '{{conversation_priority}}' => $conversation->priority ? ucfirst($conversation->priority) : 'Normal',
            '{{contact_name}}' => $conversation->contact->name ?? '',
            '{{contact_email}}' => $conversation->contact->email ?? '',
            '{{contact_phone}}' => $conversation->contact->phone_number ?? '',
            '{{inbox_name}}' => $conversation->inbox->name ?? '',
            '{{assigned_agent_name}}' => $conversation->assignee->name ?? 'Sin asignar',
            '{{assigned_agent_email}}' => $conversation->assignee->email ?? '',
            '{{team_name}}' => $conversation->team->name ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Replace contact-specific variables.
     */
    protected static function replaceContactVariables(string $template, Contact $contact): string
    {
        $replacements = [
            '{{contact_name}}' => $contact->name ?? '',
            '{{contact_email}}' => $contact->email ?? '',
            '{{contact_phone}}' => $contact->phone_number ?? '',
            '{{contact_city}}' => $contact->city ?? '',
            '{{contact_country}}' => $contact->country ?? '',
        ];

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Get event types.
     */
    public static function getEventTypes(): array
    {
        return [
            'conversation_created' => 'Nueva conversación creada',
            'conversation_assigned' => 'Conversación asignada',
            'conversation_status_changed' => 'Estado de conversación cambiado',
            'new_message' => 'Nuevo mensaje recibido',
            'contact_created' => 'Nuevo contacto creado',
            'agent_mentioned' => 'Agente mencionado en conversación',
            'csat_survey' => 'Encuesta de satisfacción',
        ];
    }
}
