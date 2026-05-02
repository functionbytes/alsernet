/**
 * Widget TypeScript Type Definitions
 * Chat Widget - Complete type system
 */

export interface WidgetConfig {
    websiteToken: string;
    baseUrl: string;
    widgetColor?: string;
    position?: 'bottom-right' | 'bottom-left';
    welcomeTitle?: string;
    welcomeTagline?: string;
    locale?: string;
}

export interface Customer {
    id?: number;
    name: string;
    email?: string;
    phone_number?: string;
    avatar_url?: string;
    custom_attributes?: Record<string, any>;
}

export interface Message {
    id: number;
    content: string;
    message_type: 'incoming' | 'outgoing';
    content_type: 'text' | 'image' | 'file' | 'audio' | 'video';
    content_attributes?: {
        file_url?: string;
        file_name?: string;
        file_size?: number;
        mime_type?: string;
    };
    created_at: string;
    sender: {
        id: number;
        type: 'Customer' | 'User' | 'Agent';
        name: string;
        avatar_url?: string;
    };
}

export interface Conversation {
    id: number;
    uuid?: string;
    status: 'open' | 'resolved' | 'pending' | 'snoozed';
    customer: Customer;
    assignee?: Agent;
    messages: Message[];
    last_message_at?: string;
    created_at: string;
}

export interface Agent {
    id: number;
    name: string;
    email: string;
    avatar_url?: string;
    available_name?: string;
    availability_status?: 'online' | 'offline' | 'busy';
}

export interface PreChatFormField {
    name: string;
    label: string;
    type: 'text' | 'email' | 'phone' | 'textarea' | 'select';
    required: boolean;
    placeholder?: string;
    options?: string[];
}

export interface WidgetState {
    isOpen: boolean;
    isMinimized: boolean;
    conversationId: number | null;
    customer: Customer | null;
    messages: Message[];
    unreadCount: number;
    isTyping: boolean;
    agentsOnline: boolean;
    showPreChatForm: boolean;
}

export interface ApiResponse<T = any> {
    success: boolean;
    data?: T;
    message?: string;
    error?: string;
    errors?: Record<string, string[]>;
}

export interface InitConversationResponse {
    success: boolean;
    data: {
        conversation_id: number;
        customer_id?: number;
        session_token?: string;
        config?: {
            widget_color: string;
            widget_position: string;
            welcome_title: string;
            welcome_tagline: string;
            pre_chat_form_enabled: boolean;
            pre_chat_form_fields: PreChatFormField[];
            business_hours_enabled: boolean;
            agents_online: boolean;
        };
    };
}

export interface SendMessageResponse {
    success: boolean;
    message?: string;
    data?: {
        message_id: number;
        conversation_id: number;
        created_at: string;
    };
}

export interface UploadFileResponse {
    success: boolean;
    data?: {
        message_id: number;
        file_url: string;
        file_name: string;
    };
    message?: string;
}

export interface CsatSurveyData {
    conversation_id: number;
    rating: 1 | 2 | 3 | 4 | 5;
    feedback?: string;
}

export type WebSocketEvent =
    | 'new_message'
    | 'typing_on'
    | 'typing_off'
    | 'agent_assigned'
    | 'conversation_status_changed'
    | 'presence_update';

export interface WebSocketMessage {
    event: WebSocketEvent;
    data: any;
}

export interface StorageData {
    conversationId?: number;
    customer?: Customer;
    sessionToken?: string;
    lastMessageAt?: string;
}
