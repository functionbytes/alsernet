<?php

namespace Modules\Chat\Services\Widget;

use Modules\Chat\Models\Channels\Web;

class WidgetConfigService
{
    /**
     * Get widget configuration by website token.
     */
    public function getConfig(string $websiteToken): ?array
    {
        $webWidget = Web::where('website_token', $websiteToken)
            ->with('inbox')
            ->first();

        if (! $webWidget) {
            return null;
        }

        $config = $webWidget->getWidgetConfig();

        return [
            'website_token' => $webWidget->website_token,
            'inbox_id' => $webWidget->inbox_id,
            'widget_color' => $config['widgetColor'] ?? '#1f93ff',
            'widget_position' => $config['widgetPosition'] ?? 'right',
            'welcome_title' => $config['welcomeTitle'] ?? 'Hello! 👋',
            'welcome_tagline' => $config['welcomeTagline'] ?? 'How can we help you today?',
            'pre_chat_form_enabled' => $config['preChatFormEnabled'] ?? false,
            'pre_chat_form_options' => $config['preChatFormOptions'] ?? [],
            'business_hours_enabled' => $webWidget->inbox?->working_hours_enabled ?? false,
            'reply_time' => $config['replyTime'] ?? null,
            'show_powered_by' => $config['showPoweredBy'] ?? true,
        ];
    }

    /**
     * Get translations for widget.
     */
    public function getTranslations(string $lang): array
    {
        $supportedLocales = ['en', 'es', 'fr', 'de', 'pt', 'it'];

        if (! in_array($lang, $supportedLocales)) {
            $lang = 'en';
        }

        $translations = [
            'en' => [
                'send' => 'Send',
                'type_message' => 'Type your message...',
                'online' => 'We\'re online',
                'offline' => 'We\'re offline',
                'chat_with_us' => 'Chat with us',
                'start_conversation' => 'Start a conversation',
                'email' => 'Email',
                'name' => 'Name',
                'phone' => 'Phone (optional)',
                'message' => 'Message',
                'file_upload' => 'Upload file',
                'powered_by' => 'Powered by',
                'conversations' => 'Conversations',
                'new_conversation' => 'New conversation',
                'end_conversation' => 'End conversation',
                'agent_typing' => 'Agent is typing...',
            ],
            'es' => [
                'send' => 'Enviar',
                'type_message' => 'Escribe tu mensaje...',
                'online' => 'Estamos en línea',
                'offline' => 'Estamos desconectados',
                'chat_with_us' => 'Chatea con nosotros',
                'start_conversation' => 'Iniciar conversación',
                'email' => 'Correo electrónico',
                'name' => 'Nombre',
                'phone' => 'Teléfono (opcional)',
                'message' => 'Mensaje',
                'file_upload' => 'Subir archivo',
                'powered_by' => 'Desarrollado por',
                'conversations' => 'Conversaciones',
                'new_conversation' => 'Nueva conversación',
                'end_conversation' => 'Finalizar conversación',
                'agent_typing' => 'El agente está escribiendo...',
            ],
        ];

        return $translations[$lang] ?? $translations['en'];
    }
}
