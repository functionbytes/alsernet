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
     * Por debajo de este número de caracteres no confiamos en la detección de
     * idioma de DeepL para ENRUTAR la auto-respuesta (elegir qué mensaje/idioma
     * enviar) — con textos muy cortos o ambiguos ("ok", "hola", fragmentos de
     * código de prueba) el clasificador falla con demasiada frecuencia y puede
     * mandar el mensaje equivocado en un idioma random. detectLanguage() en sí
     * ya exige mb_strlen >= 3; este umbral es más estricto, específico para
     * esta decisión de enrutamiento (no aplica a la traducción interactiva del
     * agente, que sigue usando el umbral de 3 de CachedTranslator).
     */
    private const int MIN_DETECTABLE_LENGTH = 12;

    /**
     * Ventana en minutos: si el cliente tuvo otra conversación (cualquier
     * canal) cuyo último mensaje o cierre cae dentro de esta ventana, esta
     * auto-respuesta se omite — evita re-saludar/re-avisar de fuera de horario
     * a alguien que acaba de reabrir el chat momentos después de cerrarse
     * (fallo de red, cierre accidental del agente, etc.). Pasada la ventana,
     * se trata como una conversación nueva de verdad y sí se envía.
     */
    private const int RECENT_CONTACT_WINDOW_MINUTES = 60;

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

        if (mb_strlen($body) < self::MIN_DETECTABLE_LENGTH) {
            return $stored;
        }

        try {
            $detected = app(CachedTranslator::class)->detectLanguage($body, 'auto_outgoing');
        } catch (Throwable) {
            return $stored;
        }

        return $detected ?: $stored;
    }

    /**
     * true si el mismo cliente tuvo otra conversación (cualquier canal, no
     * solo el de $conversation) con actividad reciente — ver
     * RECENT_CONTACT_WINDOW_MINUTES. Sin cliente identificado no hay nada que
     * comparar: se trata como "no reciente" (no bloquea el envío).
     */
    protected function customerRecentlyContacted(Conversation $conversation): bool
    {
        if (! $conversation->customer_id) {
            return false;
        }

        $since = now()->subMinutes(self::RECENT_CONTACT_WINDOW_MINUTES);

        return Conversation::query()
            ->where('customer_id', $conversation->customer_id)
            ->where('id', '!=', $conversation->id)
            ->where(function ($query) use ($since) {
                $query->where('closed_at', '>=', $since)
                    ->orWhere('last_message_at', '>=', $since);
            })
            ->exists();
    }
}
