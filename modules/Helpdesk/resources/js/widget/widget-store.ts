import { create } from 'zustand';

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
    primary_color: '#90bb13',
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

        try {
            const response = await fetch('/lc/api/settings');

            if (!response.ok) {
                throw new Error('Failed to fetch backups');
            }

            const settings = await response.json();

            set({
                settings: {
                    ...defaultSettings,
                    ...settings
                },
                isLoadingSettings: false
            });

            console.log('✅ Widget backups loaded:', settings);
        } catch (error) {
            console.error('❌ Failed to load widget backups:', error);
            // Keep default backups on error
            set({ isLoadingSettings: false });
        }
    },
}));
