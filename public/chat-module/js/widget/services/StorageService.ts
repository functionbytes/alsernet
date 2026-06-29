/**
 * StorageService - LocalStorage wrapper for widget persistence
 */

import type { StorageData, Customer } from '../types';

const STORAGE_KEY = 'helpdesk_chat_widget';

export class StorageService {
    /**
     * Get all stored data
     */
    static getData(): StorageData | null {
        try {
            const data = localStorage.getItem(STORAGE_KEY);
            return data ? JSON.parse(data) : null;
        } catch (error) {
            console.error('Failed to get widget data from storage:', error);
            return null;
        }
    }

    /**
     * Set all data
     */
    static setData(data: StorageData): void {
        try {
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (error) {
            console.error('Failed to save widget data to storage:', error);
        }
    }

    /**
     * Update specific fields
     */
    static updateData(updates: Partial<StorageData>): void {
        const current = this.getData() || {};
        this.setData({ ...current, ...updates });
    }

    /**
     * Get conversation ID
     */
    static getConversationId(): number | null {
        const data = this.getData();
        return data?.conversationId || null;
    }

    /**
     * Set conversation ID
     */
    static setConversationId(id: number): void {
        this.updateData({ conversationId: id });
    }

    /**
     * Get customer data
     */
    static getCustomer(): Customer | null {
        const data = this.getData();
        return data?.customer || null;
    }

    /**
     * Set customer data
     */
    static setCustomer(customer: Customer): void {
        this.updateData({ customer });
    }

    /**
     * Get session token
     */
    static getSessionToken(): string | null {
        const data = this.getData();
        return data?.sessionToken || null;
    }

    /**
     * Set session token
     */
    static setSessionToken(token: string): void {
        this.updateData({ sessionToken: token });
    }

    /**
     * Clear all data
     */
    static clear(): void {
        try {
            localStorage.removeItem(STORAGE_KEY);
        } catch (error) {
            console.error('Failed to clear widget data:', error);
        }
    }

    /**
     * Check if there's an active session
     */
    static hasActiveSession(): boolean {
        const data = this.getData();
        return !!(data?.conversationId && data?.sessionToken);
    }
}
