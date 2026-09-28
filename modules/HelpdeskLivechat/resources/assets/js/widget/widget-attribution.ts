/**
 * Atribución de ventas al chat (live commerce, fase 4).
 *
 * Cookie de primera parte `hd_chat_session` en la tienda (30 días, último
 * contacto, como Oct8ne): se renueva al abrir/usar una conversación y al
 * añadir al carrito desde el chat. La tienda la lee al validar el pedido y la
 * envía en order.created; el Helpdesk comprueba que el token de sesión
 * pertenece a esa conversación antes de atribuir la venta.
 */

import { getSessionToken } from './widget-session';

const COOKIE = 'hd_chat_session';
const MAX_AGE_SECONDS = 30 * 24 * 3600;

export function touchChatSession(source: 'chat' | 'cart', conversationId?: string | number | null): void {
    let convId = conversationId;
    if (convId === undefined || convId === null || convId === '') {
        try {
            convId = localStorage.getItem('livechat_conversation_id');
        } catch {
            convId = null;
        }
    }
    if (!convId || !/^\d+$/.test(String(convId))) {
        return;
    }

    const value = encodeURIComponent(JSON.stringify({
        c: Number(convId),
        s: getSessionToken(),
        t: Math.floor(Date.now() / 1000),
        src: source,
    }));
    const secure = window.location.protocol === 'https:' ? '; Secure' : '';
    document.cookie = `${COOKIE}=${value}; Max-Age=${MAX_AGE_SECONDS}; Path=/; SameSite=Lax${secure}`;
}
