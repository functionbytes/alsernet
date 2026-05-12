import { create } from 'zustand';
import { apiUrl } from './api';

export interface RecommendationProduct {
    id: string | number;
    name: string;
    price?: number;
    image_url?: string;
    url?: string;
}

export interface EngagementData {
    score: number | null;
    segment: 'cold' | 'warm' | 'hot' | string | null;
    recommendations: RecommendationProduct[];
    context: Record<string, any>;
    isIdentified: boolean;
}

export interface BotMessage {
    id: string;
    text: string;
    timestamp: number;
}

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
    welcome_title?: string;
    welcome_tagline?: string;
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
    widget_color?: string;
    widget_position?: string;
    logo_url?: string;
    team_avatars?: string[];

    // Widget - Additional Options
    show_timestamps?: boolean;
    typing_indicator?: boolean;
    sound_notifications?: boolean;
    enable_email_transcripts?: boolean;

    // Widget - Pre-chat form & offline mode
    pre_chat_form_enabled?: boolean;
    offline_message_enabled?: boolean;

    // Business hours / availability
    business_hours?: Record<string, unknown>;
    is_open?: boolean;

    // Live assistance
    enable_live_view?: boolean;
    enable_screen_share?: boolean;

    // File upload & emoji
    enableFileUpload?: boolean;
    enableEmoji?: boolean;
    allowedFileTypes?: string[];
}

interface WidgetState {
    settings: LiveChatSettings;
    isLoadingSettings: boolean;
    isOpen: boolean;

    // Engagement actions state
    botMessages: BotMessage[];
    prefillData: Record<string, string> | null;
    showPostChat: boolean;
    recommendations: RecommendationProduct[];

    setOpen: (open: boolean) => void;
    updateSettings: (newSettings: Partial<LiveChatSettings>) => void;
    fetchSettings: () => Promise<void>;

    // Engagement actions (widget commands)
    pushBotMessage: (text: string) => void;
    setPrefillData: (fields: Record<string, string>) => void;
    setShowPostChat: (show: boolean) => void;
    pushRecommendations: (products: RecommendationProduct[]) => void;
    clearRecommendations: () => void;

    // Engagement datalayer (SDK bridge)
    engagement: EngagementData;
    setEngagementScore: (score: number) => void;
    setEngagementSegment: (segment: string) => void;
    setEngagementRecommendations: (products: RecommendationProduct[]) => void;
    setEngagementContext: (context: Record<string, any>) => void;
    setEngagementIdentified: (identified: boolean) => void;
    resetEngagement: () => void;
}

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
    is_open: true,
    enable_live_view: false,
    enable_screen_share: false,
    enableFileUpload: true,
    enableEmoji: true,
    allowedFileTypes: ['images', 'documents'],
};

/**
 * Compute `is_open` from business_hours schedule.
 * Returns true if no schedule is configured (always open).
 */
function computeIsOpen(businessHours: Record<string, unknown> | null | undefined): boolean {
    if (!businessHours || Object.keys(businessHours).length === 0) return true;

    const now = new Date();
    const dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    const dayKey = dayNames[now.getDay()];
    const dayConfig = (businessHours as any)[dayKey];

    if (!dayConfig?.enabled) return false;

    const open = dayConfig.open as string | undefined;
    const close = dayConfig.close as string | undefined;
    if (!open || !close) return true;

    const [openH, openM] = open.split(':').map(Number);
    const [closeH, closeM] = close.split(':').map(Number);
    const nowMinutes = now.getHours() * 60 + now.getMinutes();
    const openMinutes = openH * 60 + openM;
    const closeMinutes = closeH * 60 + closeM;

    return nowMinutes >= openMinutes && nowMinutes < closeMinutes;
}

/** Build a settings object from the flat injected WIDGET_CONFIG. */
function settingsFromInjected(injected: Record<string, any>): LiveChatSettings {
    const businessHours = injected.businessHours ?? null;
    return {
        ...defaultSettings,
        primary_color: injected.widgetColor ?? injected.primary_color ?? defaultSettings.primary_color,
        widget_color: injected.widgetColor ?? injected.primary_color,
        position: injected.position ?? injected.widgetPosition ?? defaultSettings.position,
        widget_position: injected.widgetPosition ?? injected.position,
        header_title: injected.header_title ?? injected.welcomeTitle ?? defaultSettings.header_title,
        welcome_title: injected.welcomeTitle,
        welcome_tagline: injected.welcomeTagline,
        welcome_message: injected.welcome_message ?? injected.welcomeTagline ?? defaultSettings.welcome_message,
        logo_url: injected.logo_url,
        team_avatars: injected.team_avatars ?? [],
        // Home screen
        show_avatars: injected.show_avatars ?? defaultSettings.show_avatars,
        show_help_center: injected.show_help_center ?? defaultSettings.show_help_center,
        hide_suggested_articles: injected.hide_suggested_articles ?? defaultSettings.hide_suggested_articles,
        show_tickets_section: injected.show_tickets_section ?? defaultSettings.show_tickets_section,
        enable_send_message: injected.enable_send_message ?? defaultSettings.enable_send_message,
        enable_create_ticket: injected.enable_create_ticket ?? defaultSettings.enable_create_ticket,
        enable_search_help: injected.enable_search_help ?? defaultSettings.enable_search_help,
        // Feature flags
        show_timestamps: injected.show_timestamps ?? defaultSettings.show_timestamps,
        typing_indicator: injected.typing_indicator ?? defaultSettings.typing_indicator,
        sound_notifications: injected.sound_notifications ?? defaultSettings.sound_notifications,
        enable_email_transcripts: injected.enable_email_transcripts ?? defaultSettings.enable_email_transcripts,
        // Offline / forms
        pre_chat_form_enabled: injected.preChatFormEnabled ?? injected.pre_chat_form_enabled ?? false,
        offline_message_enabled: injected.offlineMessageEnabled ?? injected.offline_message_enabled ?? true,
        offline_message: injected.offlineMessage ?? injected.offline_message,
        // Business hours
        business_hours: businessHours,
        is_open: computeIsOpen(businessHours),
        // Launcher
        side_spacing: injected.side_spacing ?? defaultSettings.side_spacing,
        bottom_spacing: injected.bottom_spacing ?? defaultSettings.bottom_spacing,
        hide_launcher: injected.hide_launcher ?? defaultSettings.hide_launcher,
        // Live assistance
        enable_live_view: injected.enable_live_view ?? defaultSettings.enable_live_view,
        enable_screen_share: injected.enable_screen_share ?? defaultSettings.enable_screen_share,
        // File upload & emoji — support both nested (features.X) and flat (enable_X) shapes
        enableFileUpload: injected.features?.file_upload !== false && injected.enable_file_upload !== false,
        enableEmoji:      injected.features?.emoji !== false && injected.enable_emoji !== false,
        allowedFileTypes: Array.isArray(injected.features?.allowed_file_types)
            ? injected.features.allowed_file_types
            : Array.isArray(injected.allowed_file_types)
                ? injected.allowed_file_types
                : ['images', 'documents'],
    };
}

/** Build a settings object from the structured API response (`data` key). */
function settingsFromApiData(data: Record<string, any>): LiveChatSettings {
    const businessHours = data?.business_hours?.schedule ?? null;
    return {
        ...defaultSettings,
        // Theme
        primary_color: data?.theme?.primary_color ?? defaultSettings.primary_color,
        secondary_color: data?.theme?.secondary_color ?? defaultSettings.secondary_color,
        position: data?.theme?.position ?? data?.launcher?.position ?? defaultSettings.position,
        header_title: data?.theme?.header_title ?? data?.greeting?.title ?? defaultSettings.header_title,
        logo_url: data?.theme?.logo_url,
        team_avatars: data?.theme?.team_avatars ?? [],
        // Greeting
        welcome_title: data?.greeting?.title,
        welcome_tagline: data?.greeting?.message,
        welcome_message: data?.greeting?.message ?? defaultSettings.welcome_message,
        input_placeholder: data?.greeting?.input_placeholder ?? defaultSettings.input_placeholder,
        queue_message: data?.greeting?.queue_message,
        // Launcher
        side_spacing: data?.launcher?.side_spacing ?? defaultSettings.side_spacing,
        bottom_spacing: data?.launcher?.bottom_spacing ?? defaultSettings.bottom_spacing,
        hide_launcher: data?.launcher?.hide_launcher ?? defaultSettings.hide_launcher,
        // Home screen flags
        show_avatars: data?.home_screen?.show_avatars ?? defaultSettings.show_avatars,
        show_help_center: data?.home_screen?.show_help_center ?? data?.features?.helpcenter ?? defaultSettings.show_help_center,
        hide_suggested_articles: data?.home_screen?.hide_suggested_articles ?? defaultSettings.hide_suggested_articles,
        show_tickets_section: data?.home_screen?.show_tickets_section ?? defaultSettings.show_tickets_section,
        enable_send_message: data?.home_screen?.enable_send_message ?? defaultSettings.enable_send_message,
        enable_create_ticket: data?.home_screen?.enable_create_ticket ?? defaultSettings.enable_create_ticket,
        enable_search_help: data?.home_screen?.enable_search_help ?? defaultSettings.enable_search_help,
        // Feature flags
        show_timestamps: data?.features?.show_timestamps ?? defaultSettings.show_timestamps,
        typing_indicator: data?.features?.typing_indicator ?? defaultSettings.typing_indicator,
        sound_notifications: data?.features?.sound_notifications ?? defaultSettings.sound_notifications,
        enable_email_transcripts: data?.features?.enable_email_transcripts ?? defaultSettings.enable_email_transcripts,
        // Offline / forms
        pre_chat_form_enabled: data?.forms?.pre_chat_enabled ?? data?.features?.pre_chat_form ?? false,
        offline_message_enabled: data?.offline?.enabled ?? data?.features?.offline_message ?? true,
        offline_message: data?.offline?.message,
        // Business hours
        business_hours: businessHours,
        is_open: computeIsOpen(businessHours),
        // Live assistance — flat at top level on API response
        enable_live_view: data?.enable_live_view ?? defaultSettings.enable_live_view,
        enable_screen_share: data?.enable_screen_share ?? defaultSettings.enable_screen_share,
        // File upload & emoji
        enableFileUpload: data?.features?.file_upload !== false,
        enableEmoji:      data?.features?.emoji !== false,
        allowedFileTypes: Array.isArray(data?.features?.allowed_file_types)
            ? data.features.allowed_file_types
            : ['images', 'documents'],
    };
}

const defaultEngagement: EngagementData = {
    score: null,
    segment: null,
    recommendations: [],
    context: {},
    isIdentified: false,
};

export const useWidgetStore = create<WidgetState>()((set) => ({
    settings: defaultSettings,
    isLoadingSettings: false,
    isOpen: false,
    botMessages: [],
    prefillData: null,
    showPostChat: false,
    recommendations: [],
    engagement: defaultEngagement,

    setOpen: (open) => set({ isOpen: open }),

    pushBotMessage: (text) => set((state) => ({
        botMessages: [
            ...state.botMessages,
            { id: `bot-${Date.now()}-${Math.random().toString(36).slice(2)}`, text, timestamp: Date.now() },
        ],
    })),

    setPrefillData: (fields) => set({ prefillData: fields }),

    setShowPostChat: (show) => set({ showPostChat: show }),

    pushRecommendations: (products) => set((state) => ({
        recommendations: [...state.recommendations, ...products],
    })),

    clearRecommendations: () => set({ recommendations: [] }),

    setEngagementScore: (score) => set((state) => ({
        engagement: { ...state.engagement, score },
    })),

    setEngagementSegment: (segment) => set((state) => ({
        engagement: { ...state.engagement, segment },
    })),

    setEngagementRecommendations: (products) => set((state) => ({
        engagement: { ...state.engagement, recommendations: products },
    })),

    setEngagementContext: (context) => set((state) => ({
        engagement: { ...state.engagement, context },
    })),

    setEngagementIdentified: (identified) => set((state) => ({
        engagement: { ...state.engagement, isIdentified: identified },
    })),

    resetEngagement: () => set({ engagement: defaultEngagement }),

    updateSettings: (newSettings) => set((state) => ({
        settings: { ...state.settings, ...newSettings },
    })),

    fetchSettings: async () => {
        set({ isLoadingSettings: true });

        // 1) Prefer server-injected WIDGET_CONFIG (set by /widget/script/{token})
        const injected = (window as any).HELPDESK_WIDGET_CONFIG;
        if (injected) {
            set({ settings: settingsFromInjected(injected), isLoadingSettings: false });
            return;
        }

        // 2) Fallback: hit the backend /hd/api/settings endpoint
        try {
            const token = new URLSearchParams(window.location.search).get('website_token')
                ?? (window as any).helpdeskSettings?.websiteToken
                ?? null;
            const url = token
                ? apiUrl(`/hd/api/settings?website_token=${encodeURIComponent(token)}`)
                : apiUrl('/hd/api/settings');

            const response = await fetch(url);
            if (!response.ok) throw new Error('Failed to fetch settings');

            const json = await response.json();
            const data = json?.data ?? json;
            set({ settings: settingsFromApiData(data), isLoadingSettings: false });
        } catch (error) {
            console.error('Failed to load widget settings:', error);
            set({ isLoadingSettings: false });
        }
    },
}));
