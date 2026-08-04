<?php

namespace Modules\Helpdesk\Concerns;

use Modules\Helpdesk\Models\Conversation;
use Modules\HelpdeskTranslate\Services\CachedTranslator;
use Throwable;

/**
 * Idioma del cliente + traducción al vuelo del mensaje genérico, compartido
 * por los listeners de auto-respuesta (fuera de horario, bienvenida,
 * despedida). Extraído de RespondOffHoursOnConversationCreated.
 */
trait LocalizesAutoReplyMessage
{
    /**
     * Traduce el mensaje genérico al idioma del cliente cuando HelpdeskTranslate
     * está instalado y difiere del locale base de la app. Best-effort: cualquier
     * fallo (proveedor caído, módulo ausente) degrada al texto original — nunca
     * bloquea el envío por un problema de traducción.
     */
    protected function localize(string $message, ?string $target, string $source): string
    {
        if (! $target || $target === $source || ! class_exists(CachedTranslator::class)) {
            return $message;
        }

        try {
            $translated = app(CachedTranslator::class)->translate($message, $target, $source, 'auto_outgoing');
        } catch (Throwable) {
            return $message;
        }

        return $translated ?: $message;
    }

    /**
     * Igual criterio que TranslateIncomingMessage::resolveCustomerLanguage():
     * el 'es' de fábrica del customer se trata como "aún no detectado" (no
     * podemos confiar en que ya corrió — vive en otra cola, con otra prioridad,
     * y puede no haber terminado cuando este listener se ejecuta). Si es así,
     * se detecta a partir del primer mensaje en vez de asumir que coincide con
     * el locale de la app.
     */
    protected function resolveCustomerLanguage(Conversation $conversation, string $source): ?string
    {
        $customer = $conversation->customer;
        $stored = $customer?->language ? strtolower(substr($customer->language, 0, 2)) : null;

        if ($stored && $stored !== $source) {
            return $stored;
        }

        if (! class_exists(CachedTranslator::class)) {
            return $stored;
        }

        $body = trim((string) ($conversation->items->first()?->body ?? ''));

        if ($body === '') {
            return $stored;
        }

        try {
            $detected = app(CachedTranslator::class)->detectLanguage($body, 'auto_outgoing');
        } catch (Throwable) {
            return $stored;
        }

        return $detected ?: $stored;
    }
}
