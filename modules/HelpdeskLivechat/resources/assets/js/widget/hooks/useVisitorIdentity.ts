import { useState, useEffect } from 'react';

interface UseVisitorIdentityReturn {
    conversationId: string | null;
    setConversationId: (id: string | null) => void;
    customerEmail: string;
    setCustomerEmail: (email: string) => void;
    customerName: string;
    setCustomerName: (name: string) => void;
    customerId: number | null;
    setCustomerId: (id: number | null) => void;
}

/**
 * Manages visitor identity state — persists to localStorage and listens
 * for identity updates dispatched by the host site (e.g. PrestaShop login).
 */
export function useVisitorIdentity(): UseVisitorIdentityReturn {
    // Only restore a stored conversation when we also hold its per-conversation
    // token. Without the token the visitor can no longer authorize against it
    // (the customer_id alone is not accepted anymore), so treat it as no active
    // conversation and let a fresh one be created on the next message.
    const [conversationId, setConversationIdState] = useState<string | null>(() => {
        const id = localStorage.getItem('livechat_conversation_id');
        const token = localStorage.getItem('livechat_pubsub_token');
        return id && token ? id : null;
    });
    const [customerEmail, setCustomerEmailState] = useState<string>(
        () => localStorage.getItem('livechat_customer_email') || ''
    );
    const [customerName, setCustomerNameState] = useState<string>(
        () => localStorage.getItem('livechat_customer_name') || ''
    );
    const [customerId, setCustomerIdState] = useState<number | null>(() => {
        const stored = localStorage.getItem('livechat_customer_id');
        return stored ? parseInt(stored, 10) : null;
    });

    // Persist changes to localStorage
    useEffect(() => {
        if (conversationId) {
            localStorage.setItem('livechat_conversation_id', conversationId);
        }
    }, [conversationId]);

    useEffect(() => {
        if (customerEmail) {
            localStorage.setItem('livechat_customer_email', customerEmail);
        }
    }, [customerEmail]);

    useEffect(() => {
        if (customerName) {
            localStorage.setItem('livechat_customer_name', customerName);
        }
    }, [customerName]);

    useEffect(() => {
        if (customerId) {
            localStorage.setItem('livechat_customer_id', String(customerId));
        }
    }, [customerId]);

    // Listen for identity updates from the host site
    useEffect(() => {
        const handler = (e: Event) => {
            const identity = (e as CustomEvent).detail;
            if (identity?.email) setCustomerEmailState(identity.email);
            if (identity?.name) setCustomerNameState(identity.name);
            const id = identity?.customer_id ?? identity?.userId;
            if (id != null) setCustomerIdState(Number(id));
        };
        window.addEventListener('helpdesk:identity:changed', handler);
        return () => window.removeEventListener('helpdesk:identity:changed', handler);
    }, []);

    const setConversationId = (id: string | null) => {
        setConversationIdState(id);
        if (!id) {
            localStorage.removeItem('livechat_conversation_id');
            localStorage.removeItem('livechat_pubsub_token');
        }
    };

    return {
        conversationId,
        setConversationId,
        customerEmail,
        setCustomerEmail: setCustomerEmailState,
        customerName,
        setCustomerName: setCustomerNameState,
        customerId,
        setCustomerId: setCustomerIdState,
    };
}
