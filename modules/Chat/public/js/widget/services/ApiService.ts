/**
 * ApiService - HTTP client for widget API communication
 */

import type {
    ApiResponse,
    InitConversationResponse,
    SendMessageResponse,
    UploadFileResponse,
    Message,
    CsatSurveyData,
    Customer,
} from '../types';

export class ApiService {
    private baseUrl: string;
    private websiteToken: string;

    constructor(baseUrl: string, websiteToken: string) {
        this.baseUrl = baseUrl.replace(/\/$/, ''); // Remove trailing slash
        this.websiteToken = websiteToken;
    }

    /**
     * Generic request handler
     */
    private async request<T>(
        endpoint: string,
        options: RequestInit = {}
    ): Promise<ApiResponse<T>> {
        try {
            const url = `${this.baseUrl}${endpoint}`;
            const response = await fetch(url, {
                ...options,
                headers: {
                    'Accept': 'application/json',
                    ...options.headers,
                },
            });

            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.error || 'Request failed');
            }

            return data;
        } catch (error) {
            console.error('API request failed:', error);
            return {
                success: false,
                error: error instanceof Error ? error.message : 'Unknown error',
            };
        }
    }

    /**
     * Initialize a new conversation
     */
    async initConversation(customerData?: Partial<Customer>): Promise<InitConversationResponse> {
        return this.request<InitConversationResponse['data']>(
            `/api/widget/${this.websiteToken}/init`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(customerData || {}),
            }
        );
    }

    /**
     * Send a message
     */
    async sendMessage(
        conversationId: number,
        content: string,
        contentType: string = 'text'
    ): Promise<SendMessageResponse> {
        return this.request<SendMessageResponse['data']>(
            `/api/widget/${this.websiteToken}/messages`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    conversation_id: conversationId,
                    content,
                    content_type: contentType,
                }),
            }
        );
    }

    /**
     * Get messages for a conversation
     */
    async getMessages(conversationId: number): Promise<ApiResponse<{ messages: Message[] }>> {
        return this.request<{ messages: Message[] }>(
            `/api/widget/${this.websiteToken}/messages/${conversationId}`
        );
    }

    /**
     * Upload a file
     */
    async uploadFile(
        conversationId: number,
        file: File
    ): Promise<UploadFileResponse> {
        const formData = new FormData();
        formData.append('file', file);
        formData.append('conversation_id', conversationId.toString());

        return this.request<UploadFileResponse['data']>(
            `/api/widget/${this.websiteToken}/upload`,
            {
                method: 'POST',
                body: formData,
                // Don't set Content-Type header - browser will set it with boundary
            }
        );
    }

    /**
     * Update customer information
     */
    async updateCustomer(customerData: Partial<Customer>): Promise<ApiResponse> {
        return this.request(
            `/api/widget/${this.websiteToken}/contact`,
            {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(customerData),
            }
        );
    }

    /**
     * Submit CSAT survey
     */
    async submitCsatSurvey(surveyData: CsatSurveyData): Promise<ApiResponse> {
        return this.request(
            `/api/widget/${this.websiteToken}/csat`,
            {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(surveyData),
            }
        );
    }

    /**
     * Check agent availability
     */
    async checkAvailability(): Promise<ApiResponse<{ available: boolean; online_agents: number }>> {
        return this.request<{ available: boolean; online_agents: number }>(
            `/api/widget/${this.websiteToken}/availability`
        );
    }

    /**
     * Get widget configuration
     */
    async getConfig(): Promise<ApiResponse<any>> {
        return this.request(
            `/api/widget/config?website_token=${this.websiteToken}`
        );
    }

    /**
     * Mark conversation as read
     */
    async markAsRead(conversationId: number): Promise<ApiResponse> {
        return this.request(
            `/api/widget/conversation/${conversationId}/read`,
            {
                method: 'POST',
            }
        );
    }

    /**
     * Send typing indicator
     */
    async sendTypingIndicator(conversationId: number, isTyping: boolean): Promise<void> {
        // This will be handled via WebSocket in WebSocketService
        // Keeping this method for API consistency
        try {
            await this.request(
                `/api/widget/conversation/${conversationId}/typing`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ typing: isTyping }),
                }
            );
        } catch (error) {
            // Silently fail typing indicators
            console.debug('Typing indicator failed:', error);
        }
    }
}
