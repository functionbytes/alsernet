import { create } from 'zustand';
import { apiUrl } from './api';

interface LiveChatSettings {
    // Widget - Home Screen
    show_avatars?: boolean;
    show_help_center?: boolean;
    hide_suggested_articles?: boolean;
    show_tickets_section?: boolean;
    enable_send_message?: boolean;
    enable_create_ticket?: boolean;
    enable_search_help?: boolean;

    // Widget - Chat Screen
    welcome_message?: string;
    input_placeholder?: string;
    offline_message?: string;
    queue_message?: string;

    // Widget - Launcher
    position?: string;
    side_spacing?: number;
    bottom_spacing?: number;
    hide_launcher?: boolean;

    // Widget - Style
    primary_color?: string;
    secondary_color?: string;
    header_title?: string;

    // Widget - Additional Options
    show_timestamps?: boolean;
    typing_indicator?: boolean;
    sound_notifications?: boolean;
    enable_email_transcripts?: boolean;

    // Widget - Branding (bedesk-style header)
    logo_url?: string;
    team_avatars?: string[];

    // Widget - Pre-chat form & offline mode
    pre_chat_form_enabled?: boolean;
    offline_message_enabled?: boolean;
    offline_message?: string;
}

interface WidgetState {
    settings: LiveChatSettings;
    isLoadingSettings: boolean;
    updateSettings: (newSettings: Partial<LiveChatSettings>) => void;
    fetchSettings: () => Promise<void>;
}

// Default backups
const defaultSettings: LiveChatSettings = {
    show_avatars: true,
    show_help_center: true,
    hide_suggested_articles: false,
    show_tickets_section: true,
    enable_send_message: true,
    enable_create_ticket: true,
    enable_search_help: true,
    welcome_message: 'Hola! ¿Cómo podemos ayudarte?',
    input_placeholder: 'Escribe tu mensaje...',
    offline_message: 'Nuestros agentes no están disponibles en este momento...',
    queue_message: 'Uno de nuestros agentes estará contigo en breve.',
    position: 'bottom-right',
    side_spacing: 16,
    bottom_spacing: 16,
    hide_launcher: false,
    primary_color: '#b10100',
    secondary_color: '#ffffff',
    header_title: 'Chat de Soporte',
    show_timestamps: true,
    typing_indicator: true,
    sound_notifications: true,
    enable_email_transcripts: true,
};

export const useWidgetStore = create<WidgetState>()((set) => ({
    settings: defaultSettings,
    isLoadingSettings: false,

    updateSettings: (newSettings) => set((state) => ({
        settings: {
            ...state.settings,
            ...newSettings
        }
    })),

    fetchSettings: async () => {
        set({ isLoadingSettings: true });

        // 1) Prefer server-injected WIDGET_CONFIG (set by /widget/script/{token})
        const injected = (window as any).HELPDESK_WIDGET_CONFIG;
        if (injected) {
            set({
                settings: {
                    ...defaultSettings,
                    primary_color: injected.widgetColor ?? defaultSettings.primary_color,
                    position: injected.position ?? defaultSettings.position,
                    header_title: injected.welcomeTitle ?? defaultSettings.header_title,
                    welcome_message: injected.welcomeTagline ?? defaultSettings.welcome_message,
                    pre_chat_form_enabled: injected.preChatFormEnabled ?? false,
                    offline_message_enabled: injected.offlineMessageEnabled ?? true,
                    offline_message: injected.offlineMessage ?? undefined,
                },
                isLoadingSettings: false,
            });
            return;
        }

        // 2) Fallback: hit the backend /hd/api/settings endpoint
        try {
            const token =
                (window as any).HELPDESK_WIDGET_CONFIG?.websiteToken ??
                new URLSearchParams(window.location.search).get('website_token');
            const url = token
                ? apiUrl(`/hd/api/settings?website_token=${encodeURIComponent(token)}`)
                : apiUrl('/hd/api/settings');

            const response = await fetch(url);
            if (!response.ok) {
                throw new Error('Failed to fetch settings');
            }

            const json = await response.json();
            const data = json?.data ?? json;

            set({
                settings: {
                    ...defaultSettings,
                    primary_color: data?.theme?.primary_color ?? defaultSettings.primary_color,
                    position: data?.theme?.position ?? defaultSettings.position,
                    header_title: data?.greeting?.title ?? defaultSettings.header_title,
                    welcome_message: data?.greeting?.message ?? defaultSettings.welcome_message,
                    show_help_center: data?.features?.helpcenter ?? defaultSettings.show_help_center,
                    pre_chat_form_enabled: data?.features?.pre_chat_form ?? false,
                    offline_message_enabled: data?.offline?.enabled ?? data?.features?.offline_message ?? true,
                    offline_message: data?.offline?.message ?? undefined,
                },
                isLoadingSettings: false,
            });
        } catch (error) {
            console.error('Failed to load widget settings:', error);
            set({ isLoadingSettings: false });
        }
    },
}));
